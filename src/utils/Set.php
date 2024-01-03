<?php

namespace utils;

class Set
{
    public array $contents;
    public function __construct(...$args) {
        $this->contents = [];

        foreach ($args as $arg) {
            if (!in_array($arg, $this->contents)) {
                $this->contents[] = $arg;
            }
        }
    }
}