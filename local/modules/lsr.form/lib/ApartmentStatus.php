<?php

namespace Lsr\Form;

final class ApartmentStatus
{
    public const FREE   = 'F';
    public const BOOKED = 'B';
    public const SOLD   = 'S';

    public static function getList(): array
    {
        return [
            self::FREE   => 'Свободна',
            self::BOOKED => 'Забронирована',
            self::SOLD   => 'Продана',
        ];
    }

    public static function getTitle(string $code): string
    {
        return self::getList()[$code] ?? $code;
    }
}