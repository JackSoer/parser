<?php

namespace App\Interfaces;

interface ParserInterface
{
    public function __construct(string $url);
    public function getListInfo(array $list): array;
    public function run(): void;
}
