<?php
/** Einstellungen ab 0.3.0: Umzug alter Werte, Prüfung, Reihenfolge, Kontrastfarbe, Shortcode-Werte. */
define('ABSPATH', __DIR__);
// kleine WP-Attrappen
function sanitize_key($k) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $k)); }
function sanitize_text_field($t) { return trim(strip_tags((string) $t)); }
function sanitize_hex_color($c) { return preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $c) ? $c : null; }
$root = dirname(__DIR__) . '/mgd-share-wordpress/includes/';
require $root . 'class-mgd-share-options.php';
require $root . 'class-mgd-share-icons.php';
final class MGD_Share_Plugin { public const NETWORKS = array('copy', 'native', 'email', 'whatsapp', 'linkedin', 'facebook', 'telegram', 'pinterest', 'reddit', 'xing', 'bluesky', 'threads', 'print'); }

function check(bool $ok, string $name): void { if (!$ok) { fwrite(STDERR, 'FAIL ' . $name . "\n"); exit(1); } }

// alte 0.2.0-Option von der Demo
$old = array('enabled' => true, 'placement' => 'after-content', 'post_types' => array('post', 'product'), 'networks' => array('copy', 'email', 'linkedin'), 'label' => 'Jetzt teilen', 'style' => 'square', 'accent' => '#c8161a', 'show_label' => true);
$s = MGD_Share_Options::sanitize($old);
check($s['shape'] === 'square' && $s['container'] === 'card' && $s['variant'] === 'outline' && $s['display'] === 'text', 'Alter Stil eckig wird übernommen');
check($s['accent'] === '#c8161a' && $s['label'] === 'Jetzt teilen' && $s['placement'] === 'after-content', 'Alte Werte bleiben');
check(!isset($s['style']), 'style-Schlüssel ist weg');
$min = MGD_Share_Options::sanitize(array('style' => 'minimal'));
check($min['container'] === 'plain' && $min['shape'] === 'rounded', 'Minimal wird ohne Box');

// Prüfung
$bad = MGD_Share_Options::sanitize(array('shape' => 'blob', 'networks' => array('x', 'twitter', 'whatsapp', 'copy', 'whatsapp'), 'accent' => 'red', 'label' => '<b>Hi</b>', 'floating' => 'left'));
check($bad['shape'] === 'rounded', 'Unbekannte Form fällt auf Standard');
check($bad['networks'] === array('whatsapp', 'copy'), 'X raus, doppelt raus, Reihenfolge bleibt');
check($bad['accent'] === '#1d4ed8', 'Ungültige Farbe fällt auf Standard');
check($bad['label'] === 'Hi' && $bad['floating'] === 'left', 'Text gesäubert, gültige Werte bleiben');

// Shortcode: Komma-Liste + Bool-Strings über gespeicherte Werte
$sc = MGD_Share_Options::sanitize(array('networks' => 'telegram, print', 'show_label' => 'false', 'color_mode' => 'brand'), $s);
check($sc['networks'] === array('telegram', 'print') && $sc['show_label'] === false && $sc['color_mode'] === 'brand' && $sc['shape'] === 'square', 'Shortcode überschreibt gezielt');

// Kontrast
check(MGD_Share_Options::on_color('#1d4ed8') === '#ffffff', 'Dunkles Blau bekommt weiße Schrift');
check(MGD_Share_Options::on_color('#fde047') === '#111827', 'Gelb bekommt dunkle Schrift');
check(MGD_Share_Options::on_color('#fff') === '#111827', 'Kurzform geht');

// Klassen + Icons
check(str_contains(MGD_Share_Options::classes($s), 'mgd-share--color-mode-accent'), 'Klassen gebaut');
foreach (MGD_Share_Plugin::NETWORKS as $n) {
    check(isset(MGD_Share_Icons::ALL[$n]) && preg_match('/^#[0-9A-Fa-f]{6}$/', MGD_Share_Icons::color($n)), 'Icon und Farbe für ' . $n);
    check(!str_contains(MGD_Share_Icons::svg($n), 'http'), 'Icon ohne externen Abruf ' . $n);
}
echo "PASS Einstellungen: Umzug 0.2.0, Prüfung, Reihenfolge, Shortcode, Kontrast, Icons\n";
