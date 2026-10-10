<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
function cfg(): array { static $c; if ($c === null) { $f=APP_ROOT.'/dontlookhere/config.php'; if (!is_file($f)) { header('Location: '.base_url().'setup/'); exit; } $c=require $f; } return $c; }
function base_url(): string { $script=str_replace('\\','/',$_SERVER['SCRIPT_NAME'] ?? '/index.php'); $dir=dirname($script); foreach (['/admin','/setup'] as $part) { if (str_ends_with($dir,$part)) $dir=substr($dir,0,-strlen($part)); } return rtrim($dir,'/').'/'; }
function url(string $path=''): string { return base_url().ltrim($path,'/'); }
function db(): PDO { static $db; if (!$db) { $c=cfg()['db']; $db=new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['name'].';charset=utf8mb4',$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); } return $db; }
function h(?string $s): string { return htmlspecialchars($s ?? '',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function check_csrf(): void { if (!hash_equals(csrf(),(string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid form token'); } }
function setting(string $key,string $default=''): string { $s=db()->prepare('SELECT value FROM settings WHERE name=?');$s->execute([$key]);return (string)($s->fetchColumn() ?: $default); }
function admin(): bool { return !empty($_SESSION['admin_id']); }
function driver_id(): ?int { return isset($_SESSION['driver_id']) ? (int)$_SESSION['driver_id'] : (isset($_COOKIE['vl_driver']) && setting('auth_mode','remember')==='remember' ? (int)$_COOKIE['vl_driver'] : null); }
function theme(): string { $theme=setting('theme','forest'); return in_array($theme,['forest','ocean','slate','burgundy'],true) ? $theme : 'forest'; }
function page(string $title): void { echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' | Vehicle Log</title><link rel="stylesheet" href="'.h(url('assets/style.css')).'"></head><body class="theme-'.h(theme()).'"><header><a href="'.h(url()).'">Vehicle Log</a><nav><a href="'.h(url('history.php')).'">History</a> <a href="'.h(url('admin/')).'">Admin</a></nav></header><main><h1>'.h($title).'</h1>'; }
function end_page(): void { echo '</main><footer class="site-footer">Vehicle Log v1.1.0-dev</footer></body></html>'; }

// Setup has no saved connection yet. Installed sites upgrade before serving pages.
if (is_file(APP_ROOT.'/dontlookhere/config.php')) {
    try {
        require_once APP_ROOT.'/includes/migrations.php';
        vehicle_log_upgrade(db());
    } catch (Throwable $e) {
        error_log('Vehicle Log database upgrade failed: '.$e->getMessage());
        http_response_code(503);
        exit('Vehicle Log database upgrade failed. Please contact the site administrator and check the server error log.');
    }
}
