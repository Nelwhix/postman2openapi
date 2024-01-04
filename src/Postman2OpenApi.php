<?php declare(strict_types=1);

use Cerbero\JsonParser\JsonParser;
use types\Url;
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
                $urlObj = self::scrapeURL($element['request']['url']);

                if ($urlObj->valid) {
                    $summary = preg_replace('/ \[([^\[\]]*)]/i', '', $element['name']);
                    $domains->add(self::calculateDomains($urlObj->protocol, $urlObj->host, $urlObj->port));
                    $joinedPath = self::calculatePath($urlObj->path);
                    $description = self::parseDescription($element['rawDesc']);

                    if (!$paths[$joinedPath]) {
                        $paths[$joinedPath] = [];
                    }
                    $paths[$joinedPath][mb_strtolower($element['method'])] = [
                      'tags' => [$tag],
                      $summary,
                      ...($description ? [ $description ] : []),
                        ...self::parseBody($element['body'], $element['method']),
                        ...parseOperationAuth(auth, securitySchemes, optsAuth),
                        ...parseParameters(query, header, joinedPath, paramsMeta, pathVars, disabledParams),
                        ...parseResponse(response, events, responseHeaders)
                    ];
                }
            }
        }
    }

    private static function parseBody(string $method, array $body = []): array {
        // Swagger validation return an error if GET has body
        if (in_array($method, ['GET', 'DELETE'])) {
            return [];
        }
        $content = [];

        switch ($body['mode']) {
            case 'raw':
                $example = '';
                if ($body['options']['raw']['language'] === 'json') {
                    if ($body[;])
                }
        }
    }

    /* From the path array compose the real path for OpenApi specs */
    private static function calculatePath(array $paths): string {
        $pathDepth = 0;
        $paths = array_slice($paths, $pathDepth);
        $replacedPaths = array_map(function ($path) {
            $path = preg_replace('/([{}])\1+/', '$1', $path);
            return preg_replace('/^:(.*)/', '{$1}', $path);

        }, $paths);

        return '/' . implode('/', $replacedPaths);
    }

    private static function calculateDomains(string $protocol, array $hosts, string $port): string {
        return $protocol . '://' . implode('.', $hosts)  . ($port ? ":{$port}" : '');
    }

    private static function scrapeURL($url): Url {
        if (empty($url) || $url['raw'] === '') {
            $url = new Url();
            $url->setValid(false);
            return $url;
        }
        $rawUrl = (is_string($url)) ? $url : $url['raw'];
        $fixedUrl = (mb_strpos('{{', $rawUrl) == 0) ? 'https://' . $rawUrl : $rawUrl;

        $url = new Url($fixedUrl);
        $url->setRawUrl($rawUrl);
        $url->setPathVars($url['variable']);

        return $url;
    }

    private static function parseDescription(string $description): array {
        if (empty($description)) {
            return [
              'description' => null
            ];
        }

        $splitDesc = preg_split('/# postman-to-openapi/i', $description);
        if (count($splitDesc) === 1) {
            return [
                'description' => null
            ];
        }

        return [
            'description' => trim($splitDesc[0]),
        ];
    }

    private static function calculateFolderTag(string $tag, string $name, string $separator = ' > ', bool $concat = true): string {
        return ($tag && $concat) ? sprintf("%v%v%v", $tag, $separator, $name) : $name;
    }
}