<?php

namespace App\Services;

use App\Env;

/**
 * Hangt een bestaande phpMyAdmin-server onder de URL van deze app, zodat je
 * hem op /phpmyadmin bereikt zonder een tweede adres of poort te onthouden.
 *
 * Twee standen (PHPMYADMIN_MODE in .env):
 *  - proxy (standaard): de app haalt phpMyAdmin zelf op en stuurt het antwoord
 *    door. De URL in de browser blijft die van de app.
 *  - redirect: een gewone doorverwijzing naar PHPMYADMIN_URL. Minder mooi, maar
 *    werkt altijd, ook als phpMyAdmin absolute paden gebruikt.
 *
 * Alleen een ingelogde beheerder komt erlangs; dat regelt public/index.php.
 */
class PhpMyAdminProxy
{
    public const PREFIX = '/phpmyadmin';

    /** Hoort deze request bij phpMyAdmin? */
    public static function handles(string $path): bool
    {
        return $path === self::PREFIX || str_starts_with($path, self::PREFIX.'/');
    }

    public function handle(string $requestUri): void
    {
        $target = Env::get('PHPMYADMIN_URL');

        if ($target === null) {
            http_response_code(503);
            echo 'phpMyAdmin is niet ingesteld: zet PHPMYADMIN_URL (en eventueel PHPMYADMIN_MODE) in .env.';

            return;
        }

        $base = rtrim($target, '/');

        if (strtolower((string) Env::get('PHPMYADMIN_MODE', 'proxy')) === 'redirect') {
            header('Location: '.$base.'/');

            return;
        }

        if (! function_exists('curl_init')) {
            http_response_code(500);
            echo 'De PHP-extensie curl ontbreekt; zet PHPMYADMIN_MODE=redirect in .env of installeer php-curl.';

            return;
        }

        // Alles achter /phpmyadmin (inclusief querystring) gaat één op één mee.
        $suffix = substr($requestUri, strlen(self::PREFIX));

        // Zonder afsluitende slash zouden de relatieve links in phpMyAdmin een
        // niveau te hoog uitkomen, dus die zetten we er eerst netjes bij.
        if ($suffix === '' || $suffix === false) {
            header('Location: '.self::PREFIX.'/');

            return;
        }

        $this->forward($base.$suffix, $base);
    }

    private function forward(string $url, string $base): void
    {
        $body = file_get_contents('php://input');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            // Zelf doorsturen zou de app-URL kwijtraken: een redirect van
            // phpMyAdmin geven we hieronder herschreven aan de browser.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            CURLOPT_HTTPHEADER => $this->requestHeaders(),
            CURLOPT_ENCODING => '', // curl pakt gzip uit, wij sturen platte tekst door
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
        ]);

        if (! in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            http_response_code(502);
            echo 'phpMyAdmin is niet bereikbaar: '.htmlspecialchars(curl_error($ch), ENT_QUOTES);
            curl_close($ch);

            return;
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($response, 0, $headerSize);
        $payload = substr($response, $headerSize);

        http_response_code($status);
        $this->sendHeaders($rawHeaders, $base);
        echo $payload;
    }

    /** @return array<int, string> */
    private function requestHeaders(): array
    {
        $headers = [];

        // Alleen wat phpMyAdmin nodig heeft. Host laten we weg: die moet van de
        // phpMyAdmin-server zelf komen, niet van deze app.
        $doorsturen = [
            'HTTP_COOKIE' => 'Cookie',
            'CONTENT_TYPE' => 'Content-Type',
            'HTTP_ACCEPT' => 'Accept',
            'HTTP_ACCEPT_LANGUAGE' => 'Accept-Language',
            'HTTP_USER_AGENT' => 'User-Agent',
            'HTTP_X_REQUESTED_WITH' => 'X-Requested-With',
        ];

        foreach ($doorsturen as $server => $naam) {
            if (! empty($_SERVER[$server])) {
                $headers[] = $naam.': '.$_SERVER[$server];
            }
        }

        return $headers;
    }

    private function sendHeaders(string $rawHeaders, string $base): void
    {
        foreach (explode("\r\n", $rawHeaders) as $regel) {
            if (! str_contains($regel, ':')) {
                continue;
            }

            [$naam, $waarde] = explode(':', $regel, 2);
            $naam = trim($naam);
            $waarde = trim($waarde);
            $laag = strtolower($naam);

            // Content-Length en -Encoding kloppen niet meer na het uitpakken, en
            // Transfer-Encoding regelt PHP zelf.
            if (in_array($laag, ['content-length', 'content-encoding', 'transfer-encoding', 'connection', 'host', 'date', 'server'], true)) {
                continue;
            }

            if ($laag === 'location') {
                $waarde = self::PREFIX.'/'.ltrim(str_replace($base, '', $waarde), '/');
            }

            if ($laag === 'set-cookie') {
                // Zonder pad-herschrijving stuurt de browser de phpMyAdmin-cookie
                // niet meer mee: die hoort nu onder /phpmyadmin te horen.
                $waarde = preg_replace('#(;\s*[Pp]ath=)[^;]*#', '$1'.self::PREFIX.'/', $waarde);

                if (! str_contains(strtolower($waarde), 'path=')) {
                    $waarde .= '; Path='.self::PREFIX.'/';
                }
            }

            // Set-Cookie mag meerdere keren, de rest overschrijft.
            header($naam.': '.$waarde, $laag !== 'set-cookie');
        }
    }
}
