// Məxfi sənəd bölmələrinin parol qoruması.
//
// `/private/<ad>/` altındakı hər url-i qoruyur (bax: `bin/build-docs.php` və
// `docs/private.json`). Ziyarətçi kiçik bir parol səhifəsi görür; düzgün parol həmin
// bölmə üçün imzalanmış, HttpOnly cookie qoyur.
//
// Parollar Netlify mühit dəyişənindən gəlir: `DOCS_AUTH_<AD>` (ad böyük hərflərlə,
// "-" -> "_"). Bir və ya bir neçə parol, vergül və ya sətir sonu ilə ayrılmış — məsələn
// hər komanda üçün bir dənə. Cookie İSTİFADƏ OLUNAN parolla imzalanır, ona görə parolu
// siyahıdan silmək onunla girmiş hər kəsi də çıxarır. Dəyişən təyin olunmayıbsa, bölmə
// tamamilə bağlıdır — "parol yoxdursa hamı girsin" səhvinin əksi.
//
// Dəyişən "Functions" scope-unda əlçatan olmalıdır.
//
// Çıxış: istənilən url-ə `?logout` əlavə edin.

import type { Config, Context } from "@netlify/edge-functions";

const PRIVATE_PATH = /^\/private(?:$|\/(?:([^/]+)(?:\/|$))?)/;
const SESSION_DAYS = 30;
const FAILED_LOGIN_DELAY_MS = 700;

const TEXT = {
  title: "Məxfi sənəd",
  lead: "Bu bölməni oxumaq üçün şifrəni daxil edin.",
  label: "Şifrə",
  submit: "Daxil ol",
  wrong: "Şifrə yanlışdır.",
  back: "Sənədlərə qayıt",
};

const encoder = new TextEncoder();

// Böyük/kiçik hərf və faiz-kodlaşdırma ilə yoxlamadan yan keçmək mümkün olmasın deyə
// yol əvvəlcə normallaşdırılır: `/PRIVATE/`, `/pri%76ate/` və `//private//` eyni sayılır.
function normalise(pathname: string): string {
  let path = pathname;
  try {
    path = decodeURIComponent(pathname);
  } catch {
    // Yanlış kodlaşdırma: xam yol da aşağıda yoxlanılır.
  }
  return path.toLowerCase().replace(/\/{2,}/g, "/");
}

function passwordsFor(name: string): string[] {
  if (!/^[a-z0-9-]+$/.test(name)) return [];
  const key = `DOCS_AUTH_${name.toUpperCase().replace(/[^A-Z0-9]/g, "_")}`;
  return (Netlify.env.get(key) ?? "")
    .split(/[,\n]/)
    .map((s) => s.trim())
    .filter(Boolean);
}

function hmacKey(password: string): Promise<CryptoKey> {
  return crypto.subtle.importKey(
    "raw",
    encoder.encode(password),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign", "verify"],
  );
}

function toBase64Url(bytes: ArrayBuffer): string {
  return btoa(String.fromCharCode(...new Uint8Array(bytes)))
    .replace(/\+/g, "-")
    .replace(/\//g, "_")
    .replace(/=+$/, "");
}

function fromBase64Url(value: string): ArrayBuffer | null {
  try {
    const binary = atob(value.replace(/-/g, "+").replace(/_/g, "/"));
    const bytes = new ArrayBuffer(binary.length);
    const view = new Uint8Array(bytes);
    for (let i = 0; i < binary.length; i++) view[i] = binary.charCodeAt(i);
    return bytes;
  } catch {
    return null;
  }
}

async function sha256(value: string): Promise<Uint8Array> {
  return new Uint8Array(await crypto.subtle.digest("SHA-256", encoder.encode(value)));
}

// Sabit uzunluqlu heşləri qısa-qapanma olmadan müqayisə edir və HƏR parola baxır, ona
// görə cavab müddəti təxminin nə qədərinin doğru olduğunu bildirmir.
async function matchPassword(given: string, passwords: string[]): Promise<string | null> {
  const a = await sha256(given);
  let found: string | null = null;
  for (const password of passwords) {
    const b = await sha256(password);
    let diff = 0;
    for (let i = 0; i < a.length; i++) diff |= a[i] ^ b[i];
    if (diff === 0) found = password;
  }
  return found;
}

function cookieName(name: string): string {
  return `integrify_docs_${name.replace(/-/g, "_")}`;
}

async function sessionValue(name: string, password: string): Promise<string> {
  const expires = Math.floor(Date.now() / 1000) + SESSION_DAYS * 86400;
  const signature = await crypto.subtle.sign(
    "HMAC",
    await hmacKey(password),
    encoder.encode(`${name}:${expires}`),
  );
  return `${expires}.${toBase64Url(signature)}`;
}

function readCookie(request: Request, name: string): string | undefined {
  for (const part of (request.headers.get("cookie") ?? "").split(";")) {
    const [key, ...rest] = part.trim().split("=");
    if (key === name) return rest.join("=");
  }
  return undefined;
}

function setCookie(name: string, value: string, maxAge: number): string {
  return `${name}=${value}; Path=/; Max-Age=${maxAge}; HttpOnly; Secure; SameSite=Lax`;
}

async function hasValidSession(
  request: Request,
  name: string,
  passwords: string[],
): Promise<boolean> {
  const value = readCookie(request, cookieName(name));
  const match = value?.match(/^(\d+)\.([A-Za-z0-9_-]+)$/);
  if (!match || Number(match[1]) < Date.now() / 1000) return false;
  const signature = fromBase64Url(match[2]);
  if (!signature) return false;
  const data = encoder.encode(`${name}:${match[1]}`);
  let ok = false;
  for (const password of passwords) {
    const valid = await crypto.subtle.verify("HMAC", await hmacKey(password), signature, data);
    ok = ok || valid;
  }
  return ok;
}

function loginPage(error: boolean): Response {
  const html = `<!doctype html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>${TEXT.title} · Integrify (PHP)</title>
<style>
  :root { --bg: #fafafa; --card: #fff; --fg: #1f2328; --muted: #656d76; --line: #d0d7de;
          --accent: #00796b; --on-accent: #fff; --error: #cf222e; color-scheme: light dark; }
  @media (prefers-color-scheme: dark) {
    :root { --bg: #1e2129; --card: #262a33; --fg: #e6e8eb; --muted: #9aa3ad; --line: #3a404b;
            --accent: #26a69a; --on-accent: #0b1f1d; --error: #ff7b72; }
  }
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px;
         background: var(--bg); color: var(--fg);
         font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  main { width: 100%; max-width: 360px; background: var(--card); border: 1px solid var(--line);
         border-radius: 12px; padding: 28px; }
  h1 { font-size: 1.25rem; margin: 0 0 4px; }
  p { margin: 0 0 20px; color: var(--muted); }
  label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: 6px; }
  input { width: 100%; padding: 10px 12px; font: inherit; color: inherit; background: transparent;
          border: 1px solid var(--line); border-radius: 8px; }
  input:focus { outline: 2px solid var(--accent); outline-offset: 1px; border-color: transparent; }
  button { width: 100%; margin-top: 16px; padding: 10px 12px; font: inherit; font-weight: 600;
           color: var(--on-accent); background: var(--accent); border: 0; border-radius: 8px; cursor: pointer; }
  .error { color: var(--error); font-size: .875rem; margin: 8px 0 0; }
  a { display: inline-block; margin-top: 20px; font-size: .875rem; color: var(--muted); }
</style>
</head>
<body>
<main>
  <h1>${TEXT.title}</h1>
  <p>${TEXT.lead}</p>
  <form method="post">
    <label for="password">${TEXT.label}</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required autofocus>
    ${error ? `<p class="error" role="alert">${TEXT.wrong}</p>` : ""}
    <button type="submit">${TEXT.submit}</button>
  </form>
  <a href="/">${TEXT.back}</a>
</main>
</body>
</html>`;
  return new Response(html, {
    status: 401,
    headers: {
      "Content-Type": "text/html; charset=utf-8",
      "Cache-Control": "no-store",
      "X-Robots-Tag": "noindex, nofollow",
    },
  });
}

function redirect(url: URL, cookie?: string): Response {
  // "//" birləşdirilir ki, Location protokol-nisbi url-ə (`//evil.example`) çevrilməsin.
  const headers = new Headers({
    Location: url.pathname.replace(/\/{2,}/g, "/") + url.search,
    "Cache-Control": "no-store",
  });
  if (cookie) headers.set("Set-Cookie", cookie);
  return new Response(null, { status: 303, headers });
}

export default async (request: Request, context: Context) => {
  const url = new URL(request.url);
  const match = normalise(url.pathname).match(PRIVATE_PATH);
  if (!match) return; // ictimai səhifə: adi qaydada verilir

  const name = match[1] ?? "";
  const passwords = passwordsFor(name);

  if (url.searchParams.has("logout")) {
    url.searchParams.delete("logout");
    return redirect(url, passwords.length ? setCookie(cookieName(name), "", 0) : undefined);
  }

  if (request.method === "POST") {
    let given = "";
    try {
      given = String((await request.formData()).get("password") ?? "");
    } catch {
      // form post deyil
    }
    const password = passwords.length ? await matchPassword(given, passwords) : null;
    if (!password) {
      await new Promise((resolve) => setTimeout(resolve, FAILED_LOGIN_DELAY_MS));
      return loginPage(true);
    }
    const session = await sessionValue(name, password);
    return redirect(url, setCookie(cookieName(name), session, SESSION_DAYS * 86400));
  }

  if (!passwords.length || !(await hasValidSession(request, name, passwords))) {
    return loginPage(false);
  }

  const upstream = await context.next();
  const response = new Response(upstream.body, upstream); // kopya: upstream header-ləri dəyişməz ola bilər
  response.headers.set("Cache-Control", "private, no-cache");
  response.headers.set("X-Robots-Tag", "noindex, nofollow");
  return response;
};

export const config: Config = {
  // Yalnız `/private/*` deyil, HƏR ŞEYƏ qoşulur: əks halda böyük hərf və ya
  // faiz-kodlaşdırma hiyləsi ilə yoxlamadan yan keçib fayllara çatmaq olardı
  // (Netlify-ın `path` uyğunlaşdırması normallaşdırma etmir, bizim `normalise()` edir).
  path: "/*",
  excludedPath: ["/assets/*"],
};
