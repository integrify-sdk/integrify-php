<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini;

/**
 * Posta Güvercini SMS API-sinin endpoint-ləri (`https://www.poctgoyercini.com`).
 *
 * Host adına diqqət: şirkətin adı "Posta Güvercini"dir, domen isə `poctgoyercini` —
 * yazılış fərqi onlarındır, səhv deyil.
 *
 * `Send_1_N` bir mətni çox nömrəyə, `Send_N_N` isə hər nömrəyə öz mətnini göndərir.
 */
enum Endpoint: string
{
    case SendSingle = '/api_json/v1/Sms/Send_1_N';
    case SendMultiple = '/api_json/v1/Sms/Send_N_N';
    case Status = '/api_json/v1/Sms/Status';
    case CreditBalance = '/api_json/v1/Sms/CreditBalance';
}
