<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

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
            // Auditoria 2026-09-06 (M2): getenv('HOME') puede ser false en
            // algunos contenedores Forge; concatenar con false produce
            // TypeError. Usamos un fallback explicito a /var/www.
            $home = getenv('HOME') ?: '/var/www';
            $candidates = [
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium',
                // Puppeteer cache (Forge usually has it via npx puppeteer)
                glob($home.'/.cache/puppeteer/chrome/*/chrome-linux64/chrome')[0] ?? null,
                glob($home.'/.cache/puppeteer/chrome-headless-shell/*/chrome-headless-shell-linux64/chrome-headless-shell')[0] ?? null,
                // Playwright cache
                glob($home.'/.cache/ms-playwright/chromium-*/chrome-linux/chrome')[0] ?? null,
            ];
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                // Auditoria 2026-09-06 (Q7): cachear el primer match. Evita
                // N filesystem checks (stat + is_file) por cada PDF generado.
                // Se invalida automaticamente al reiniciar el OPcache de Forge
                // o cuando el path deja de existir (cache miss + re-scan).
                Cache::forever('chrome.path', $candidate);

                return $candidate;
            }
        }

        // Si llegamos aquí, no había Chrome. Limpiamos el cache para que
        // la próxima petición tras instalar Chrome lo encuentre.
        Cache::forget('chrome.path');

        return null;
    }

    public static function nodeBinary(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'node' : '/usr/bin/node';
    }
}
