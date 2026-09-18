<?php

declare(strict_types=1);

namespace Integrify\PostaGuvercini\Enum;

/**
 * SMS kanalı.
 *
 * `Otp` birdəfəlik parollar üçündür və adətən daha sürətli çatdırılır; `Bulk` isə
 * kütləvi göndərişlər üçündür. Default `Otp`-dir — Python kitabxanasındakı kimi.
 */
enum Channel: string
{
    case Otp = 'OTP';
    case Bulk = 'BULK';
}
