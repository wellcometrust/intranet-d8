<?php

// @codingStandardsIgnoreFile

// Extract lando service config from environment.
$lando_info = json_decode(getenv('LANDO_INFO'), TRUE);
$settings['hash_salt'] = 'XSll24zdRA-7sIM-dt_PZNB4J6iGFmeajKve9AOZIYUAbmY5nf-aWbyt4NS6MSjRE2B59NucAg';
$db_mapping = [
  // Service Name -> Drupal ID
  'database' => 'default',
  'migrationdb' => 'migration',
];

$databases = [];

foreach ($db_mapping as $service_name => $drupal_key) {
  // DB info is split by credentials, and connection info.
  $db_info = $lando_info[$service_name];
  $db_creds = $db_info['creds'];
  $db_connection = $db_info['internal_connection'];

  $databases[$drupal_key]['default'] = [
    'database' => $db_creds['database'],
    'username' => $db_creds['user'],
    'password' => $db_creds['password'],
    'prefix' => '',
    'host' => $db_connection['host'],
    'port' => $db_connection['port'],
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  ];
}

$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^.+\.lndo\.site',
];

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/development.services.yml';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
// Disable drupal's built in cron trigger.
$conf['cron_safe_threshold'] = 0;
//$config['elasticsearch_connector.cluster.search']['url'] = 'http://readstore';
//$settings['redis.connection']['interface'] = 'PhpRedis';
//$settings['redis.connection']['host'] = 'redis';
//$settings['cache']['default'] = 'cache.backend.redis';
//
//// Note that unlike memcached, redis persists cache items to disk so we can
//// actually store cache_class_cache_form in the default cache.
//$conf['cache_class_cache'] = 'Redis_Cache';
//
//// Apply changes to the container configuration to better leverage Redis.
//// This includes using Redis for the lock and flood control systems, as well
//// as the cache tag checksum. Alternatively, copy the contents of that file
//// to your project-specific services.yml file, modify as appropriate, and
//// remove this line.
//$settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
//
//// Allow the services to work before the Redis module itself is enabled.
//$settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';
