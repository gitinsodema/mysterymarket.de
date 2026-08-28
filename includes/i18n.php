<?php
declare(strict_types=1);

function mmSupportedLanguages(): array
{
    return ['de', 'en', 'nl'];
}

function mmLanguage(): string
{
    $lang = strtolower(trim((string)($_GET['lang'] ?? 'de')));
    return in_array($lang, mmSupportedLanguages(), true) ? $lang : 'de';
}

function mmTranslations(): array
{
    static $translations = [];
    $lang = mmLanguage();

    if (!isset($translations[$lang])) {
        $path = dirname(__DIR__) . '/lang/' . $lang . '.php';
        $translations[$lang] = is_file($path) ? (require $path) : [];
    }

    return $translations[$lang];
}

function mmT(string $key, ?string $fallback = null): string
{
    $translations = mmTranslations();
    return (string)($translations[$key] ?? $fallback ?? $key);
}

function mmLangUrl(string $path, ?string $lang = null): string
{
    $lang ??= mmLanguage();
    if ($lang === 'de') {
        return $path;
    }

    $separator = str_contains($path, '?') ? '&' : '?';
    return $path . $separator . 'lang=' . rawurlencode($lang);
}
