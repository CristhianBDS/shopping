<?php
// inc/settings.php
require_once __DIR__ . '/../config/db.php';

function setting_get(string $key, $default = ''): string {
  $pdo = getConnection();
  $st = $pdo->prepare('SELECT value FROM settings WHERE `key` = ?');
  $st->execute([$key]);
  $v = $st->fetchColumn();
  return ($v !== false) ? (string)$v : (string)$default;
}

function setting_set(string $key, string $value): void {
  $pdo = getConnection();
  $st = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES(?,?)
                       ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
  $st->execute([$key, $value]);
}
