<?php

namespace App\Support;

/**
 * Resolves an available Chrome/Chromium executable across environments
 * (local Windows, Forge Linux, puppeteer cache, etc).
 */
class ChromePath
{
    public static function resolve(): ?string
    {
        $candidates = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = [
                'C:\Program Files\Google\Chrome\Application\chrome.exe',
                'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
                'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            ];
        } else {
            $candidates = [
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium',
                // Puppeteer cache (Forge usually has it via npx puppeteer)
                glob(getenv('HOME') . '/.cache/puppeteer/chrome/*/chrome-linux64/chrome')[0] ?? null,
                glob(getenv('HOME') . '/.cache/puppeteer/chrome-headless-shell/*/chrome-headless-shell-linux64/chrome-headless-shell')[0] ?? null,
                // Playwright cache
                glob(getenv('HOME') . '/.cache/ms-playwright/chromium-*/chrome-linux/chrome')[0] ?? null,
            ];
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function nodeBinary(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'node' : '/usr/bin/node';
    }
}
