<?php

/**
 * @file
 * Drupal site-specific configuration file.
 */

// Database configuration.
$databases['default']['default'] = [
  'database' => getenv('DB_NAME') ?: 'drupal11',
  'username' => getenv('DB_USER') ?: 'drupal',
  'password' => getenv('DB_PASSWORD') ?: 'drupal_secret_pass',
  'prefix' => '',
  'host' => getenv('DB_HOST') ?: 'database',
  'port' => getenv('DB_PORT') ?: '3306',
  'isolation_level' => 'READ COMMITTED',
  'driver' => 'mysql',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
];

// Hash salt.
$settings['hash_salt'] = 'funiber_super_secure_hash_salt_key_2026_test';

// Configuration sync directory.
$settings['config_sync_directory'] = '../config/sync';

// Trusted host patterns.
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^127\.0\.0\.1$',
  '^webserver$',
];

// File paths.
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = '../private';

// Enable update access only via authorized methods.
$settings['update_free_access'] = FALSE;

// Container YAML services.
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// Local development overrides (if exists).
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
