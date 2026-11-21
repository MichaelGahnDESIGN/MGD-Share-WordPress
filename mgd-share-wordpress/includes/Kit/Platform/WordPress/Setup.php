<?php

declare(strict_types=1);

namespace Mgd\Share\Kit\Platform\WordPress;

use Mgd\Share\Kit\Install\{CallbackStore, CurlTransport};
use Mgd\Share\Kit\Install\Platform\WordPress;
use Mgd\Share\Kit\Catalog\Recommendations;
use Mgd\Share\Kit\License\{CurlPost, LicenseClient};

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

    /**
     * Lizenz-Baustein. Standard aus: dann kein Netz, kein UI, isPro() = false
     * Plugin-Lizenzen für wordpress sind auf dem Server noch nicht verkäuflich > erst mit Schritt 7 einschalten
     */
    public static function license(string $product, string $platform, bool $licenseEnabled = false): LicenseClient
    {
        try {
            if (!$licenseEnabled || preg_match(self::PRODUCT_RE, $product) !== 1) {
                return self::licenseOff();
            }
            $opt = 'mgd_kit_lic_' . str_replace('-', '_', $product);
            $store = new CallbackStore(
                static fn (): mixed => \get_option($opt, ''),
                static function (string $v) use ($opt): void {
                    \update_option($opt, $v, false);
                },
            );
            $domain = strtolower((string) \wp_parse_url(\home_url(), PHP_URL_HOST));

            return new LicenseClient(new CurlPost('MGDKit/1.0 (' . $platform . ')'), $store, $domain, true);
        } catch (\Throwable) {
            return self::licenseOff();
        }
    }

    /**
     * Admin-Seite mit allen Bereichen. $parent = options-general.php (Einstellungen) oder eigener Menü-Slug, '' = kein Menüpunkt
     * Speichern von "Installation melden" läuft wie bisher über WordPress::boot()
     */
    public static function dashboard(
        string $product,
        string $platform,
        string $version,
        string $pluginName,
        ?Updater $updater = null,
        ?Recommendations $recommendations = null,
        bool $licenseEnabled = false,
        string $parent = 'options-general.php',
    ): ?Dashboard {
        try {
            $d = new Dashboard(
                $pluginName,
                $version,
                $product,
                $updater,
                $recommendations,
                self::license($product, $platform, $licenseEnabled),
                static fn (): string => WordPress::settingsHtml($product, $platform, $version),
            );
            if ($parent !== '') {
                $d->register($parent);
            }

            return $d;
        } catch (\Throwable) {
            return null;
        }
    }

    // aus = Attrappe ohne Netz
    private static function licenseOff(): LicenseClient
    {
        return new LicenseClient(null, new CallbackStore(static fn (): mixed => '', static function (string $v): void {
        }), '', false);
    }
}
