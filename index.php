<?php

use App\Parsers\AlleParser;

require './vendor/autoload.php';

$parser = new AlleParser('https://www.kreuzwort-raetsel.net');

$parser->run();
