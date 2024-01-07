<?php

namespace Nelwhix\Postman2openapi\utils;

class Map
{
    private array $contents;

    public function __construct() {
        $contents = [];
    }

    public function set(string $key, $value) {
        $this->contents[$key] = $value;
    }

    public function get(string $key) {
        return $this->contents[$key];
    }
}