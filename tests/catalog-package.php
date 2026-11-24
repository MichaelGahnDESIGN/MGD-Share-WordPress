<?php
/** Paketvertrag: gemeinsame Katalogdaten, neue Klasse, Versionsangaben und lokale Grafiken. */
$root = dirname(__DIR__);
$config = json_decode(file_get_contents(__DIR__ . '/catalog-config.json'), true);
$runtime = $root . '/' . $config['runtime'];
function mgd_catalog_assert(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, 'FAIL ' . $name . "\n"); exit(1); }
}
$main = file_get_contents($runtime . '/' . $config['main']);
mgd_catalog_assert((bool) preg_match('/Version:\s*' . preg_quote($config['version'], '/') . '\s/', $main), 'Pluginversion');
$metadata = json_decode(file_get_contents($runtime . '/includes/plugin-catalog/plugins.json'), true);
mgd_catalog_assert(count($metadata) === 7, 'Sieben bekannte WordPress-Plugins');
foreach ($metadata as $repo => $item) {
    mgd_catalog_assert($item['repository'] === $repo && $item['tier'] === 'free', 'Bekannte kostenlose Einträge');
    $file = $runtime . '/assets/plugin-catalog/icons/' . $item['icon'];
    mgd_catalog_assert(is_file($file) && filesize($file) > 100, 'Lokales Icon ' . $repo);
    mgd_catalog_assert(!str_contains(file_get_contents($file), 'https://'), 'Kein externer Bildabruf im Icon');
    $picture = $runtime . '/assets/plugin-catalog/backgrounds/' . pathinfo($item['icon'], PATHINFO_FILENAME) . '.webp';
    $size = is_file($picture) ? getimagesize($picture) : false;
    mgd_catalog_assert($size && $size[0] === 1080 && $size[1] === 360 && $size['mime'] === 'image/webp', 'Lokales Kartenmotiv ' . $repo);
    mgd_catalog_assert(filesize($picture) < 80000, 'Kompaktes Kartenmotiv ' . $repo);
}
// ab 0.3.1: alte Seite MGD Plugin-Kollektion ganz raus, nur plugins.json bleibt als Ersatzliste
mgd_catalog_assert(is_file($runtime . '/includes/plugin-catalog/plugins.json'), 'Ersatzliste plugins.json');
foreach (array('includes/class-mgd-wordpress-plugin-catalog.php', 'includes/plugin-catalog/class-provider.php', 'includes/plugin-catalog/class-media.php', 'includes/plugin-catalog/view.php', 'assets/plugin-catalog/catalog.css', 'assets/plugin-catalog/catalog.js') as $name) {
    mgd_catalog_assert(!is_file($runtime . '/' . $name), 'Alte Katalogdatei entfernt: ' . $name);
}
$bootstrap = $main;
foreach (glob($runtime . '/includes/*.php') as $file) { $bootstrap .= file_get_contents($file); }
// ab 0.2.0: Weitere Plugins über den Baukasten, alter GitHub-Updater nicht mehr angemeldet
mgd_catalog_assert(str_contains($main, 'Setup::tools('), 'MGD Tools vom Baukasten');
mgd_catalog_assert(!str_contains($bootstrap, 'add_menu_page') && !str_contains($bootstrap, 'add_submenu_page'), 'Kein eigenes Menü');
// X/Twitter darf nie als Ziel auftauchen
foreach (glob($runtime . '/{includes,templates,assets}/*.{php,js}', GLOB_BRACE) as $file) { mgd_catalog_assert(!preg_match("/'x'|x\\.com|twitter/i", (string) preg_replace('#//.*#', '', file_get_contents($file))), 'Kein X in ' . basename($file)); }
// Installieren meldet der Baukasten selbst an (Setup::boot > catalogAuto, ab Pro 6a64577)
mgd_catalog_assert(str_contains((string) file_get_contents($runtime . '/includes/Kit/Platform/WordPress/Setup.php'), 'self::catalogAuto('), 'Knopf Installieren vom Baukasten angemeldet');
mgd_catalog_assert(is_file($runtime . '/includes/Kit/Platform/WordPress/CatalogInstaller.php') && is_file($runtime . '/includes/Kit/Platform/WordPress/assets/kit-catalog.js'), 'Neue Ansicht Weitere Plugins im Baukasten');
mgd_catalog_assert(!str_contains($bootstrap, 'class-mgd-wordpress-plugin-catalog.php') && !str_contains($bootstrap, 'MGD_Catalog_V4_Provider') && !str_contains($bootstrap, 'MGD_WordPress_Plugin_Catalog_V4'), 'Kein require der alten Katalogseite');
mgd_catalog_assert(str_contains($bootstrap, "Setup::updater( __FILE__, 'mgd-share-wordpress'"), 'Baukasten-Updater angemeldet');
mgd_catalog_assert(!str_contains($main, 'MGD_Plugin_Admin_Tools_20261023::register'), 'Alter GitHub-Updater abgeschaltet');
mgd_catalog_assert(is_file($runtime . '/includes/Kit/Platform/WordPress/Updater.php'), 'Baukasten vorhanden');
echo "PASS Pluginversion, sieben Katalogeinträge, lokale Icons, neue Ansicht Weitere Plugins, alte Katalogseite entfernt\n";
