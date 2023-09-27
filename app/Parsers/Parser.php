<?php

namespace App\Parsers;

use App\Interfaces\ParserInterface;
use DiDom\Document;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Parser implements ParserInterface
{
    protected $client;
    protected $document;
    public $url;

    public function __construct(string $url)
    {
        $this->client = new Client();
        $this->url = $url;
        $this->document = new Document();
    }

    public function run(): void
    {
    }

    public function loadDocument(string $url): void
    {
        $response = $this->client->get($url);
        $html = $response->getBody();
        $this->document->loadHtml($html);
    }

    public function getListInfo(array $list): array
    {
        $listInfo = [];

        foreach ($list as $listItem) {
            $title = $listItem->text();
            $href = $listItem->getAttribute('href');

            $listItemInfo = ['title' => $title, 'href' => $href];

            array_push($listInfo, $listItemInfo);
        }

        return $listInfo;
    }

    public function log(string $loggerName, mixed $stream, int $level, string $string, array $context): void
    {
        $logToConsole = new Logger($loggerName);
        $logToConsole->pushHandler(new StreamHandler($stream, $level));

        if ($level === 100) {
            $logToConsole->debug($string, $context);
        } else if ($level === 200) {
            $logToConsole->info($string, $context);
        }
    }
}
