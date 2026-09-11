<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('CORE_PATH', BASE_PATH . '/core');
define('APP_PATH', BASE_PATH . '/app');
define('PLUGINS_PATH', BASE_PATH . '/plugins');
define('CONFIG_PATH', BASE_PATH . '/config');
define('DATABASE_PATH', BASE_PATH . '/database');

require CORE_PATH . '/Autoloader.php';

Core\Autoloader::register();
Core\Autoloader::addNamespace('Core', CORE_PATH);
Core\Autoloader::addNamespace('App', APP_PATH);

$app = new Core\App();
$app->run();
