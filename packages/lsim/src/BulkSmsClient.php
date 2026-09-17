<?php

declare(strict_types=1);

namespace Integrify\Lsim;

use DateTimeInterface;
use Integrify\Client;
use Integrify\Exception\InvalidRequest;
use Integrify\Exception\RequestFailed;
use Integrify\Http\HttpTransport;
use Integrify\Http\Transport;
use Integrify\Lsim\Dto\Response\BulkBalance;
use Integrify\Lsim\Dto\Response\BulkDetailedReport;
use Integrify\Lsim\Dto\Response\BulkReport;
use Integrify\Lsim\Dto\Response\BulkSubmission;

/**
 * Toplu SMS göndərilməsi (`https://www.sendsms.az`).
 *
 * Tək SMS API-sindən tamamilə ayrıdır: başqa host, tək endpoint, və hər sorğuda
 * nə edildiyini `operation` field-i bildirir. Payload iç-içə strukturdadır:
 *
 * ```json
 * {"request": {"head": { ... }, "body": [ ... ]}}
 * ```
 *
 * ```php
 * $client = new BulkSmsClient(LsimConfig::fromEnvironment());
 *
 * $submission = $client->sendSameMessage(1, ['994501234567', '994551234567'], 'Salam!');
 * $report = $client->fetchReport($submission->taskId ?? 0);
 * ```
 */
final class BulkSmsClient extends Client
{
    public const BASE_URL = 'https://www.sendsms.az';

    public const ENDPOINT = '/smxml/api';

    public function __construct(
        private readonly LsimConfig $config,
        ?Transport $transport = null,
        string $baseUrl = self::BASE_URL,
    ) {
        parent::__construct($transport ?? new HttpTransport(), $baseUrl);
    }

    /**
     * Eyni mesajı bir neçə nömrəyə göndərir.
     *
     * **POST** `/smxml/api` (`operation: submit`, `isbulk: true`)
     *
     * @param int $controlId Sizin təyin etdiyiniz təkrarlanmayan id. LSIM eyni id ilə
     *     ikinci sorğunu `Duplicate` kimi rədd edir.
     * @param list<string> $msisdns Nömrələr.
     * @param string $message Hamısına gedəcək mesaj.
     * @param DateTimeInterface|null $at Planlaşdırılmış göndərilmə zamanı.
     *
     * @throws InvalidRequest Nömrə siyahısı boşdursa.
     * @throws RequestFailed
     */
    public function sendSameMessage(
        int $controlId,
        array $msisdns,
        string $message,
        ?DateTimeInterface $at = null,
        ?string $title = null,
    ): BulkSubmission {
        if ($msisdns === []) {
            throw new InvalidRequest('sendSameMessage() needs at least one msisdn.');
        }

        $body = [];

        foreach ($msisdns as $msisdn) {
            $body[] = ['msisdn' => $msisdn];
        }

        $payload = $this->send(
            'POST',
            self::ENDPOINT,
            $this->envelope(
                head: [
                    'controlid' => $controlId,
                    'bulkmessage' => $message,
                    'scheduled' => self::timestamp($at),
                    'title' => $title ?? $this->config->senderName,
                    'operation' => BulkOperation::Submit->value,
                    'isbulk' => true,
                ],
                body: $body,
            ),
        )->toArray();

        return BulkSubmission::from([
            'responseCode' => self::head($payload)['responsecode'] ?? null,
            'taskId' => self::bodyValue($payload, 'taskid'),
        ]);
    }

    /**
     * Hər nömrəyə öz mesajını göndərir.
     *
     * **POST** `/smxml/api` (`operation: submit`, `isbulk: false`)
     *
     * @param list<string> $msisdns Nömrələr.
     * @param list<string> $messages Nömrələrlə **eyni sırada** mesajlar.
     *
     * @throws InvalidRequest Siyahılar boşdursa və ya uzunluqları fərqlidirsə.
     * @throws RequestFailed
     */
    public function sendDifferentMessages(
        int $controlId,
        array $msisdns,
        array $messages,
        ?DateTimeInterface $at = null,
        ?string $title = null,
    ): BulkSubmission {
        if ($msisdns === []) {
            throw new InvalidRequest('sendDifferentMessages() needs at least one msisdn.');
        }

        if (count($msisdns) !== count($messages)) {
            // Python `zip()` istifadə edir və artıq elementləri səssizcə atır —
            // bir mesajın heç kimə getməməsi aşkar edilməz xətadır.
            throw new InvalidRequest(sprintf(
                'sendDifferentMessages() got %d msisdns but %d messages; they must line up.',
                count($msisdns),
                count($messages),
            ));
        }

        $body = [];

        foreach (array_values($msisdns) as $index => $msisdn) {
            $body[] = ['msisdn' => $msisdn, 'message' => array_values($messages)[$index]];
        }

        $payload = $this->send(
            'POST',
            self::ENDPOINT,
            $this->envelope(
                head: [
                    'controlid' => $controlId,
                    'scheduled' => self::timestamp($at),
                    'title' => $title ?? $this->config->senderName,
                    'operation' => BulkOperation::Submit->value,
                    'isbulk' => false,
                ],
                body: $body,
            ),
        )->toArray();

        return BulkSubmission::from([
            'responseCode' => self::head($payload)['responsecode'] ?? null,
            'taskId' => self::bodyValue($payload, 'taskid'),
        ]);
    }

    /**
     * Tapşırığın yekun hesabatı — hər statusdan neçə SMS olduğu.
     *
     * **POST** `/smxml/api` (`operation: report`)
     *
     * @throws RequestFailed
     */
    public function fetchReport(int $taskId): BulkReport
    {
        $payload = $this->operation(BulkOperation::Report, ['taskid' => $taskId]);
        $body = self::bodyArray($payload);

        return BulkReport::from([
            'responseCode' => self::head($payload)['responsecode'] ?? null,
            ...$body,
        ]);
    }

    /**
     * Tapşırığın detallı hesabatı — hər SMS üçün bir sətir.
     *
     * **POST** `/smxml/api` (`operation: detailedreport`)
     *
     * @throws RequestFailed
     */
    public function fetchDetailedReport(int $taskId): BulkDetailedReport
    {
        return $this->detailed(BulkOperation::DetailedReport, $taskId);
    }

    /**
     * Detallı hesabat, hər sətirdə çatdırılma tarixi ilə.
     *
     * **POST** `/smxml/api` (`operation: detailedreportwithdate`)
     *
     * @throws RequestFailed
     */
    public function fetchDetailedReportWithDates(int $taskId): BulkDetailedReport
    {
        return $this->detailed(BulkOperation::DetailedReportWithDate, $taskId);
    }

    /**
     * Toplu SMS hesabının balansı.
     *
     * **POST** `/smxml/api` (`operation: units`)
     *
     * @throws RequestFailed
     */
    public function checkBalance(): BulkBalance
    {
        $payload = $this->operation(BulkOperation::Units);

        return BulkBalance::from([
            'responseCode' => self::head($payload)['responsecode'] ?? null,
            'units' => self::bodyValue($payload, 'units') ?? -1,
        ]);
    }

    /**
     * @throws RequestFailed
     */
    private function detailed(BulkOperation $operation, int $taskId): BulkDetailedReport
    {
        $payload = $this->operation($operation, ['taskid' => $taskId]);

        /** @var list<mixed> $rows */
        $rows = self::bodyList($payload);

        return BulkDetailedReport::from([
            'responseCode' => self::head($payload)['responsecode'] ?? null,
            'reports' => $rows,
        ]);
    }

    /**
     * Yalnız `head` olan sorğu (hesabat və balans).
     *
     * @param array<string, scalar> $head
     *
     * @return array<array-key, mixed>
     *
     * @throws RequestFailed
     */
    private function operation(BulkOperation $operation, array $head = []): array
    {
        return $this->send(
            'POST',
            self::ENDPOINT,
            $this->envelope(head: [...$head, 'operation' => $operation->value]),
        )->toArray();
    }

    /**
     * LSIM-in gözlədiyi `{"request": {"head": ..., "body": ...}}` zərfini qurur.
     *
     * Kimlik məlumatları həmişə `head`-ə əlavə olunur. Tək SMS API-sindən fərqli
     * olaraq burada parol **göndərilir** — imza (`key`) mexanizmi yoxdur.
     *
     * @param array<string, scalar> $head
     * @param list<array<string, string>>|null $body
     *
     * @return array<string, mixed>
     */
    private function envelope(array $head, ?array $body = null): array
    {
        $request = [
            'head' => [
                'login' => $this->config->login,
                'password' => $this->config->password,
                ...$head,
            ],
        ];

        if ($body !== null) {
            $request['body'] = $body;
        }

        return ['request' => $request];
    }

    /**
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    private static function head(array $payload): array
    {
        /** @var mixed $response */
        $response = $payload['response'] ?? null;

        if (!is_array($response)) {
            return [];
        }

        /** @var mixed $head */
        $head = $response['head'] ?? null;

        return is_array($head) ? $head : [];
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private static function bodyValue(array $payload, string $key): mixed
    {
        return self::bodyArray($payload)[$key] ?? null;
    }

    /**
     * `body` obyekt kimi gələn sorğular üçün (`report`, `units`).
     *
     * @param array<array-key, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    private static function bodyArray(array $payload): array
    {
        /** @var mixed $response */
        $response = $payload['response'] ?? null;

        if (!is_array($response)) {
            return [];
        }

        /** @var mixed $body */
        $body = $response['body'] ?? null;

        return is_array($body) && !array_is_list($body) ? $body : [];
    }

    /**
     * `body` siyahı kimi gələn sorğular üçün (`detailedreport`).
     *
     * @param array<array-key, mixed> $payload
     *
     * @return list<mixed>
     */
    private static function bodyList(array $payload): array
    {
        /** @var mixed $response */
        $response = $payload['response'] ?? null;

        if (!is_array($response)) {
            return [];
        }

        /** @var mixed $body */
        $body = $response['body'] ?? null;

        return is_array($body) ? array_values($body) : [];
    }

    private static function timestamp(?DateTimeInterface $at): string
    {
        return $at?->format('Y-m-d H:i:s') ?? 'NOW';
    }
}
