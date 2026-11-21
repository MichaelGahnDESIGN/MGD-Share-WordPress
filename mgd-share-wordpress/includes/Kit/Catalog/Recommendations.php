<?php

declare(strict_types=1);

namespace Mgd\Share\Kit\Catalog;

use Mgd\Share\Kit\Install\{Store, Transport};
use Mgd\Share\Kit\Support\Text;

// "Weitere Plugins" aus /api/v1/recommendations/<plattform>.json (Regeln docs/KATALOG.md)
// 15 Min Cache, 5 Min Pause nach Fehler, letzte gute Liste 7 Tage, sonst die mitgelieferte Liste. Wirft nie
final class Recommendations
{
    public const URL_PREFIX = 'https://theme.michael-gahn.de/api/v1/recommendations/';
    public const DETAILS_PREFIX = 'https://theme.michael-gahn.de/plugins/';
    public const CATALOG_PREFIX = 'https://theme.michael-gahn.de/downloads?plattform=';
    public const TTL = 900;
    public const PAUSE = 300;
    public const STALE_MAX = 604800;
    public const MAX_ITEMS = 80;
    public const MAX_BYTES = 524288;
    public const SLUG_RE = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

    // Kit-Shopsystem > Feed-Name + erlaubte platform-Werte im alten Feed
    public const PLATFORMS = [
        'wordpress' => ['wordpress', ['wordpress', 'woocommerce']],
        'woocommerce' => ['woocommerce', ['wordpress', 'woocommerce']],
        'jtl5' => ['jtl', ['jtl', 'jtl5']],
        'shopware6' => ['shopware', ['shopware', 'shopware6']],
    ];

    /** @var \Closure():int */
    private \Closure $clock;
    private readonly string $feed;
    /** @var list<string> */
    private readonly array $allowed;

    /** @param list<array<string,mixed>> $fallback mitgelieferte Liste (slug, name, description, version) */
    public function __construct(
        private readonly Transport $http,
        private readonly Store $store,
        string $platform,
        private readonly string $selfSlug,
        private readonly array $fallback = [],
        ?\Closure $clock = null,
    ) {
        if (!isset(self::PLATFORMS[$platform])) {
            throw new \InvalidArgumentException('Shopsystem ungültig.');
        }
        [$this->feed, $this->allowed] = self::PLATFORMS[$platform];
        $this->clock = $clock ?? static fn (): int => time();
    }

    /**
     * @return array{items:list<array{slug:string,name:string,description:string,version:string,url:string}>,source:string}
     *   source: fresh | cache | stale | fallback
     */
    public function load(): array
    {
        try {
            return $this->resolve();
        } catch (\Throwable) {
            return ['items' => $this->fallbackItems(), 'source' => 'fallback'];
        }
    }

    /**
     * Feed auf die schmale Anzeigeliste bringen. null = Feed unbrauchbar
     * @param array<mixed> $feed
     */
    public function normalize(array $feed): ?array
    {
        if (!is_array($feed['items'] ?? null)) {
            return null;
        }
        $items = array_slice(array_values($feed['items']), 0, self::MAX_ITEMS);
        $rows = [];
        if (($feed['schema'] ?? null) === 1) {
            // Vertrag v1: Feed muss genau zu unserem System gehören
            if (($feed['platform'] ?? null) !== $this->feed || ($feed['catalog_url'] ?? null) !== self::CATALOG_PREFIX . $this->feed) {
                return null;
            }
            foreach ($items as $row) {
                if (is_array($row) && ($row['platform'] ?? $this->feed) === $this->feed) {
                    $rows[] = $row;
                }
            }
        } elseif (($feed['schemaVersion'] ?? null) === 1) {
            // alter Feed, den liefert die Adresse heute noch > id = slug
            $ok = [...$this->allowed, 'other', 'all'];
            foreach ($items as $row) {
                if (is_array($row) && in_array($row['platform'] ?? '', $ok, true) && in_array($row['status'] ?? '', ['live', 'announced'], true)) {
                    $rows[] = ['slug' => $row['id'] ?? null] + $row;
                }
            }
        } else {
            return null;
        }

        return $this->clean($rows);
    }

    private function resolve(): array
    {
        $now = ($this->clock)();
        $st = $this->state();
        $at = (int) ($st['rec_at'] ?? 0);
        $has = is_array($st['rec_items'] ?? null);
        if ($has && $at <= $now && $now - $at < self::TTL) {
            return ['items' => $this->clean($st['rec_items']), 'source' => 'cache'];
        }
        if ((int) ($st['rec_pause'] ?? 0) <= $now) {
            $items = $this->fetch();
            if ($items !== null) {
                $this->save(['rec_items' => $items, 'rec_at' => $now]);

                return ['items' => $items, 'source' => 'fresh'];
            }
            $this->save(['rec_pause' => $now + self::PAUSE] + $st);
        }
        if ($has && $at <= $now && $now - $at <= self::STALE_MAX) {
            return ['items' => $this->clean($st['rec_items']), 'source' => 'stale'];
        }

        return ['items' => $this->fallbackItems(), 'source' => 'fallback'];
    }

    private function fetch(): ?array
    {
        try {
            $r = $this->http->send('GET', self::URL_PREFIX . $this->feed . '.json', ['Accept' => 'application/json']);
        } catch (\Throwable) {
            return null;
        }
        if ($r->status !== 200 || $r->body === '' || strlen($r->body) > self::MAX_BYTES) {
            return null;
        }
        $feed = json_decode($r->body, true, 16);

        return is_array($feed) ? $this->normalize($feed) : null;
    }

    /** @param array<mixed> $rows */
    private function clean(array $rows): array
    {
        $out = [];
        foreach (array_slice(array_values($rows), 0, self::MAX_ITEMS) as $row) {
            $it = is_array($row) ? $this->item($row) : null;
            if ($it !== null && !isset($out[$it['slug']])) {
                $out[$it['slug']] = $it;
            }
        }

        return array_values($out);
    }

    private function item(array $row): ?array
    {
        $slug = $row['slug'] ?? null;
        $name = is_string($row['name'] ?? null) ? Text::plain($row['name'], 120) : '';
        // eigenes Plugin raus
        if (!is_string($slug) || strlen($slug) > 100 || preg_match(self::SLUG_RE, $slug) !== 1 || $slug === $this->selfSlug || $name === '') {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => is_string($row['description'] ?? null) ? Text::plain($row['description'], 500) : '',
            'version' => is_string($row['version'] ?? null) ? ltrim(Text::plain($row['version'], 30), 'vV') : '',
            'url' => self::DETAILS_PREFIX . $slug,
        ];
    }

    private function fallbackItems(): array
    {
        try {
            return $this->clean($this->fallback);
        } catch (\Throwable) {
            return [];
        }
    }

    private function state(): array
    {
        try {
            return $this->store->load();
        } catch (\Throwable) {
            return [];
        }
    }

    private function save(array $st): void
    {
        try {
            $this->store->save($st);
        } catch (\Throwable) {
            // Speichern kaputt, egal - nächster Aufruf fragt halt neu
        }
    }
}
