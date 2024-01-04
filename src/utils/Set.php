<?php declare(strict_types=1);

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

    public function add(string $item): void {
        if (!in_array($item, $this->contents)) {
            $this->contents[] = $item;
        }
    }
}