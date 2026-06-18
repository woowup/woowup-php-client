<?php

namespace WoowUp\Cleansers\Tld;

class IanaTldProvider
{
    const IANA_URL = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';

    const FALLBACK_TLDS = [
        'com', 'net', 'org', 'edu', 'gov', 'mil', 'int',
        'ar', 'es', 'br', 'mx', 'co', 'cl', 'pe', 'bo', 'uy', 'py', 'ec', 've', 'cr', 'pa', 'gt', 'hn', 'sv', 'ni', 'do', 'cu', 'pr',
        'us', 'uk', 'ca', 'au', 'de', 'fr', 'it', 'pt', 'nl', 'be', 'ch', 'at', 'se', 'no', 'dk', 'fi', 'pl', 'cz', 'ru',
        'jp', 'cn', 'in', 'kr', 'io', 'lat', 'tv', 'info', 'biz', 'name', 'mobi', 'tel', 'pro',
    ];

    private string $cacheFile;
    private int    $ttl;
    private ?array $tlds = null;

    public function __construct(string $cacheFile = '/tmp/iana_tlds.cache', int $ttl = 604800)
    {
        $this->cacheFile = $cacheFile;
        $this->ttl       = $ttl;
    }

    public function isValid(string $tld): bool
    {
        return in_array(strtolower($tld), $this->getAll(), true);
    }

    public function getAll(): array
    {
        if ($this->tlds !== null) {
            return $this->tlds;
        }

        $this->tlds = $this->loadFromCache() ?? $this->fetchAndCache() ?? self::FALLBACK_TLDS;
        return $this->tlds;
    }

    public function refresh(): void
    {
        $this->tlds = null;
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
        $this->getAll();
    }

    private function loadFromCache(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        if ((time() - filemtime($this->cacheFile)) > $this->ttl) {
            return null;
        }

        $data = json_decode(file_get_contents($this->cacheFile), true);
        return is_array($data) ? $data : null;
    }

    private function fetchAndCache(): ?array
    {
        $raw = @file_get_contents(self::IANA_URL);
        if ($raw === false) {
            return null;
        }

        $tlds = $this->parse($raw);
        if (empty($tlds)) {
            return null;
        }

        @file_put_contents($this->cacheFile, json_encode($tlds));
        return $tlds;
    }

    private function parse(string $raw): array
    {
        $tlds = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            // Skip IDN entries (xn--)
            if (stripos($line, 'xn--') === 0) {
                continue;
            }
            $tlds[] = strtolower($line);
        }
        return $tlds;
    }
}
