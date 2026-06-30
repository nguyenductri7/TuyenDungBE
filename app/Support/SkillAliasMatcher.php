<?php

namespace App\Support;

class SkillAliasMatcher
{
    private static ?array $lookup = null;

    public static function canonicalKey(?string $value): string
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return '';
        }

        return self::lookup()[$normalized]['key'] ?? $normalized;
    }

    public static function displayName(?string $value): string
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return '';
        }

        return self::lookup()[$normalized]['name'] ?? trim((string) $value);
    }

    public static function matches(?string $left, ?string $right): bool
    {
        $leftKey = self::canonicalKey($left);
        $rightKey = self::canonicalKey($right);

        return $leftKey !== '' && $leftKey === $rightKey;
    }

    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $converted !== false ? $converted : $value;
        $value = preg_replace('/[^a-z0-9+#.\s]/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private static function lookup(): array
    {
        if (self::$lookup !== null) {
            return self::$lookup;
        }

        $catalogPath = dirname(__DIR__, 3) . '/AI/data/skill_aliases.json';
        if (!is_file($catalogPath)) {
            return self::$lookup = [];
        }

        $items = json_decode((string) file_get_contents($catalogPath), true);
        if (!is_array($items)) {
            return self::$lookup = [];
        }

        $lookup = [];
        $exactNames = [];

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['skill_name'])) {
                continue;
            }

            $key = self::normalize((string) $item['skill_name']);
            if ($key === '') {
                continue;
            }

            $exactNames[$key] = [
                'key' => $key,
                'name' => (string) $item['skill_name'],
            ];
        }

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['skill_name'])) {
                continue;
            }

            $canonicalKey = self::normalize((string) $item['skill_name']);
            $canonical = $exactNames[$canonicalKey] ?? null;
            if (!$canonical) {
                continue;
            }

            foreach (($item['aliases'] ?? []) as $alias) {
                $aliasKey = self::normalize((string) $alias);
                if ($aliasKey !== '' && !isset($lookup[$aliasKey])) {
                    $lookup[$aliasKey] = $canonical;
                }
            }
        }

        return self::$lookup = array_replace($lookup, $exactNames);
    }
}
