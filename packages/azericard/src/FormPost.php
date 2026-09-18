<?php

declare(strict_types=1);

namespace Integrify\Azericard;

/**
 * Brauzerin göndərməli olduğu forma.
 *
 * **Azericard-ın kart əməliyyatları server-server sorğu deyil.** `authorize()`,
 * `authorizeAndSaveCard()`, `authorizeWithSavedCard()`, `finalize()` və
 * `startTransfer()` heç bir HTTP sorğusu atmır — onlar müştərinin brauzerinin
 * Azericard-a **özünün** göndərməli olduğu formanı qaytarır. Ona görə bu metodların
 * cavabı yoxdur: nəticə sonradan callback URL-inizə gəlir.
 *
 * ```php
 * $form = $client->authorize(amount: 10.5, currency: '944', order: '123456', description: 'Sifariş');
 *
 * echo $form->toHtml();   // özünü göndərən forma
 * ```
 *
 * Öz şablonunuzu qurmaq istəyirsinizsə `$url`, `$method` və `$fields` açıqdır.
 */
final readonly class FormPost
{
    /**
     * @param string $url Formanın göndəriləcəyi ünvan.
     * @param string $method HTTP metodu (`POST` və ya `GET`).
     * @param array<string, string> $fields Gizli field-lər — imza da daxil.
     */
    public function __construct(
        public string $url,
        public string $method,
        public array $fields,
    ) {
    }

    /**
     * Özünü göndərən HTML forması.
     *
     * Bütün dəyərlər `htmlspecialchars()` ilə escape olunur: field dəyərləri
     * sifariş IDsi, müştəri adı kimi **xarici** mənbələrdən gəlir, və escape
     * olunmasa `"` simvolu atributdan çıxıb HTML/JS injection-a səbəb olardı.
     *
     * @param bool $withSubmit Submit düyməsi əlavə edilsinmi. JavaScript sönülüdürsə
     *     forma özü göndərilmir, ona görə düymə yeganə yoldur.
     */
    public function toHtml(bool $withSubmit = false): string
    {
        $inputs = '';

        foreach ($this->fields as $name => $value) {
            $inputs .= sprintf(
                '<input type="hidden" name="%s" value="%s">' . "\n",
                self::escape($name),
                self::escape($value),
            );
        }

        $submit = $withSubmit ? '<input type="submit" value="Submit">' . "\n" : '';

        return sprintf(
            '<form name="azericard_form" action="%s" method="%s">' . "\n" . '%s%s</form>' . "\n"
            . '<script>document.azericard_form.submit();</script>',
            self::escape($this->url),
            self::escape($this->method),
            $inputs,
            $submit,
        );
    }

    /**
     * Formanı massiv kimi qaytarır — öz şablonunuz və ya testlər üçün.
     *
     * @return array{url: string, method: string, fields: array<string, string>}
     */
    public function toArray(): array
    {
        return ['url' => $this->url, 'method' => $this->method, 'fields' => $this->fields];
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
