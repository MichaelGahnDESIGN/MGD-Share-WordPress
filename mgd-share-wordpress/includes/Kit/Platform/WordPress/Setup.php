<?php

declare(strict_types=1);

namespace Mgd\Share\Kit\Platform\WordPress;

use Mgd\Share\Kit\Install\{CallbackStore, CurlTransport};
use Mgd\Share\Kit\Install\Platform\WordPress;
use Mgd\Share\Kit\Catalog\Recommendations;

// Einstieg fürs Hauptplugin (docs/PLUGIN-BAUKASTEN.md). Fehler > null, der WP-Admin läuft immer weiter
final class Setup
{
    public const PRODUCT_RE = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D';

    /** Updater + Hinweis anmelden. $platform = wordpress oder woocommerce */
    public static function updater(string $pluginFile, string $product, string $platform, string $version, string $pluginName): ?Updater
    {
        try {
            $c = WordPress::client($product, $platform, $version);
            if ($c === null) {
                return null;
            }
            $u = new Updater($c, \plugin_basename($pluginFile), $version, $pluginName);
            $u->register();
            NoticeView::register($u, $pluginName);

            return $u;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Liste "Weitere Plugins". $selfSlug = Katalog-Kennung des eigenen Plugins (wird ausgeblendet)
     * @param list<array<string,mixed>> $fallback mitgelieferte Liste
     */
    public static function recommendations(string $product, string $platform, string $selfSlug, array $fallback = []): ?Recommendations
    {
        try {
            if (preg_match(self::PRODUCT_RE, $product) !== 1) {
                return null;
            }
            // eigene Option ohne Autoload
            $opt = 'mgd_kit_rec_' . str_replace('-', '_', $product);
            $store = new CallbackStore(
                static fn (): mixed => \get_option($opt, ''),
                static function (string $v) use ($opt): void {
                    \update_option($opt, $v, false);
                },
            );

            return new Recommendations(new CurlTransport('MGDKit/1.0 (' . $platform . ')'), $store, $platform, $selfSlug, $fallback);
        } catch (\Throwable) {
            return null;
        }
    }
}
