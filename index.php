<?php

use App\Models\DB;
use App\Parsers\AlleParser;
use Dotenv\Dotenv;

require './vendor/autoload.php';

define('STORAGE_PATH', __DIR__ . '/storage/logs/questionsLog');

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = [
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_NAME'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
];

$db = new DB($config);
$proxies = [
    'http://45.8.104.227:80',
    'http://185.162.231.70:80',
    'http://141.101.123.26:80',
    'http://185.162.229.52:80',
];

$parser = new AlleParser($_ENV['PARSE_SITE_URL'], $proxies);

$parser->run();
