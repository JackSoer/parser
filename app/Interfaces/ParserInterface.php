<?php

namespace App\Interfaces;

interface ParserInterface
{
    function __construct(string $url, array $clientOptions);
    function getListInfo(array $list): array;
    function loadDocument(string $url): void;
    function log(string $loggerName, mixed $stream, int $level, string $string, array $context): void;
    function run(): void;
    function initTasks(array $list): void;
    function processTask(mixed $task): void;
}
