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

$parser = new AlleParser($_ENV['PARSE_SITE_URL']);

$parser->run();
