<?php

namespace App\Parsers;

use DiDom\Document;
use Exception;

class AlleParser extends Parser
{
    private function getDnrgList(Document $document): array
    {
        $dnrgItemsNodes = $document->find('.dnrg li a');

        // Delete Sonstige Link
        unset($dnrgItemsNodes[count($dnrgItemsNodes) - 1]);

        $dnrgList = $this->getListInfo($dnrgItemsNodes);

        return $dnrgList;
    }

    private function getSecondDnrgLists(array $firstDnrgList): array
    {
        $secondDnrgLists = [];

        foreach ($firstDnrgList as $firstDnrgListItem) {
            $parsedAmount = array_search($firstDnrgListItem, $firstDnrgList);
            $total = count($firstDnrgList);
            $last = $total - $parsedAmount;

            $message = 'Parse ' . $firstDnrgListItem['href'] . ' page' . PHP_EOL . 'Parsed: ' . $parsedAmount . PHP_EOL . 'Left: ' . $last . PHP_EOL;

            echo $message;

            $this->loadDocument($this->url . '/' . $firstDnrgListItem['href']);
            $secondDnrgList = $this->getDnrgList($this->document);
            $secondDnrgLists = [...$secondDnrgLists, ...$secondDnrgList];
        }

        return $secondDnrgLists;
    }

    private function loadDocument(string $url): void
    {
        $response = $this->client->get($url);
        $html = $response->getBody();
        $this->document->loadHtml($html);

        sleep(rand(1, 3));
    }

    public function run(): void
    {
        echo 'Parsing was started...' . PHP_EOL;

        try {
            $this->loadDocument($this->url . '/uebersicht.html');

            $dnrgList = $this->getDnrgList($this->document);
            $secondDnrgLists = $this->getSecondDnrgLists($dnrgList);
            print_r($secondDnrgLists);
        } catch (Exception $err) {
            echo $err->getMessage();
        }

        echo 'Parsing ended';
    }
}
