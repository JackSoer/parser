<?php

namespace App\Parsers;

use App\Interfaces\ParserInterface;
use DiDom\Document;
use GuzzleHttp\Client;

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
}
