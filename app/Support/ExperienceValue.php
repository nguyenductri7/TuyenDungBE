<?php

namespace App\Support;

class ExperienceValue
{
    public static function normalize(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        if (!is_string($value)) {
            return $value;
        }

        $text = trim(mb_strtolower($value));
        if ($text === '') {
            return $value;
        }

        $normalizedNumberText = str_replace(',', '.', $text);
        if (!preg_match('/\d+(?:\.\d+)?/', $normalizedNumberText, $matches)) {
            return $value;
        }

        $number = (float) $matches[0];
        $isMonthValue = str_contains($text, 'tháng')
            || str_contains($text, 'thang')
            || preg_match('/\b(month|months|mo)\b/', $text);

        return round($isMonthValue ? ($number / 12) : $number, 2);
    }
}
