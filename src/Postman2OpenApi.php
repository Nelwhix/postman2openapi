<?php declare(strict_types=1);

namespace Nelwhix\Postman2openapi;

use Cerbero\JsonParser\JsonParser;
use Nelwhix\Postman2openapi\utils\Map;
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
        $obj = new \ArrayObject($items['item']);
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
                        ...self::parseParameters($urlObj->query, $element['header'], $joinedPath, $urlObj->pathVars),
                    ];
                }
            }
        }
    }

    private static function paramInserter($parameterMap, $param) {
        if (!$parameterMap->has($param->name)) {
            $parameterMap->set($param->name, $param);
        }

        return $parameterMap;
    }

    /* Parse the Postman query and header and transform into OpenApi parameters */
    private static function parseParameters(string $query, array $header, string $joinedPath, array $pathVars): array {
        $disabledParams = [
            'includeQuery' => false,
            'includeHeader' => false,
        ];

        $parameters = [
            array_reduce($header, self::mapParameters('query', false, self::paramInserter), new Map())];
        ];
    }

    /* Accumulator function for different types of parameters */
    private static function mapParameters($type, $includeDisabled, $paramInserter)

    /* calculate the type of variable based on OPenApi types */
    private static function inferType($value): string
    {
        if (preg_match('/^\d+$/', $value)) {
            return 'integer';
        }
        if (preg_match('/^[+-]?([0-9]*[.])?[0-9]+$/', $value)) {
            return 'number';
        }
        if (preg_match('/^(true|false)$/i', $value)) {
            return 'boolean';
        }
        
        return 'string';
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
                $language = $body['options']['raw']['language'];
                if ($language === 'json') {
                    if ($body['options']['raw']) {
                        $errors = [];
                        $example = json_decode($body['options']['raw']);
                    }
                    $content = [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                $example,
                            ]
                        ]
                    ];
                } else if ($language === 'text') {
                    $content = [
                      'text/plain' => [
                          'schema' => [
                              'type' => 'string',
                              'example' => $body['options']['raw']
                          ]
                      ]
                    ];
                }
                else {
                    $content = [
                      '*/*' => [
                          'schema' => [
                              'type' => 'string',
                              'example' => json_encode($body['options']['raw'])
                          ]
                      ]
                    ];
                }
                break;
            case 'file':
                $content = [
                    'text/plain' => []
                ];
                break;
            case 'formdata':
                $content = [
                    'multipart/form-data' => self::parseFormData($body['formdata'])
                ];
                break;
            case 'urlencoded':
                $content = [
                    'application/x-www-form-urlencoded' => self::parseFormData($body['urlencoded'])
                ];
                break;
        }

        return [
          'requestBody' => [
              $content,
          ]
        ];
    }

    /** Parse the body for create a form data structure */
    private static function parseFormData(array $data): array
    {
        $objectSchema = [
            'schema' => [
                'type' => 'object'
            ]
        ];

        return array_reduce($data, function ($carry, $item) {
            if (self::isRequired($item['description'])) {
                $carry['schema']['required'] = $carry['schema']['required'] || [];
                array_push($carry['schema']['required'], $item['key']);
            }

            $schemaProperties = $carry['schema']['properties'] ?? [];
            $description = [];
            if (isset($item['description'])) {
                $description = [
                  'description' => preg_replace('/ ?\[required] ?/i', '', $item['description']);
                ];
            }

            $value = [];
            if (isset($item['value'])) {
                $value = [
                    'example' => $item['value']
                ];
            }

            $type = [];
            if ($item['type'] === 'file') {
                $type = [
                    'format' => 'binary'
                ];
            }
            $schemaProperties[$item['key']] = [
                'type' => self::inferType($item['value']),
                $description,
                $value,
                $type
            ];

            return $item;
        }, $objectSchema);
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