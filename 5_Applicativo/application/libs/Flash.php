<?php

namespace libs;

class Flash
{
    public static function get($key, $default=[])
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
}