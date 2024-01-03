<?php

use Cerbero\JsonParser\JsonParser;
use utils\Set;

class Postman2OpenApi
{
    public static function parse(string $postmanSpec): string {
        $paths = [];
        $domains = new Set();
        $tags = [];
        $securitySchemes = [];
        $items = JsonParser::parse($postmanSpec)->pointer(('/item'))->toArray();
        $obj = new ArrayObject($items['item']);
        $entries = $obj->getIterator();

        foreach ($entries as $i => $element) {
            while ($element !== null && $element['item'] !== null) { // is a folder
                $tag = self::calculateFolderTag($element['tag'], $element['name']);
                $tagged = array_map(function ($e) use ($tag) {
                    return [...$e, $tag];
                }, $element['item']);
                $tags[$tag] = $element['description'];
                array_splice($items, $i, 1, ...$tagged);
                // Empty folders will have tagged empty
                $element = (count($tagged) > 0) ? array_shift($tagged) : $items[$i];
            }

            if ($element !== null) {

            }
        }
    }

    private static function calculateFolderTag(string $tag, string $name, string $separator = ' > ', bool $concat = true): string {
        return ($tag && $concat) ? sprintf("%v%v%v", $tag, $separator, $name) : $name;
    }
}