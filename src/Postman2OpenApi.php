<?php declare(strict_types=1);

namespace Nelwhix\Postman2openapi;

use Cerbero\JsonParser\JsonParser;
use Nelwhix\Postman2openapi\utils\Map;
use types\Url;
use utils\Set;

class Postman2OpenApi
{
    private array $openApiSpec;
    private array $collectionVariables;
    public function __construct() {
        $this->openApiSpec = [
            'openapi' => '3.1.0'
        ];
        $this->collectionVariables = [];
        $this->openApiSpec['paths'] = [];
        $this->openApiSpec['servers'] = [];
    }

    /**
     * @throws \Exception
     */
    public function parse(string $postmanSpec): string {
      $postmanJson = json_decode($postmanSpec, true);

      if ($postmanJson === null) {
          throw new \Exception("invalid json input");
      }
      $this->parseInfo($postmanJson);

      if (isset($postmanJson['variable'])) {
        $this->parseCollectionVariables($postmanJson);
      }

      foreach($postmanJson['item'] as $item) {
          if (isset($item['item'])) {
              // treat as a folder
          }
          $this->parsePaths($item);
      }

      return json_encode($this->openApiSpec);
  }

  private function parseServer(string $rawPath) {
        // check for a proper url
      $urlComponents = parse_url($rawPath);

      if (isset($urlComponents['scheme'], $urlComponents['host'])) {
          // check if that server url already exists in the server block
          $serverUrl = $urlComponents['scheme'] . '://' . $urlComponents['host'];
          $this->setServer($serverUrl);
      }

      // check for server url in collection variables
      $collectionVarString = explode("/", $rawPath)[0];
      $result = preg_replace('/[{}]/', '', $collectionVarString);

      foreach ($this->collectionVariables as $key => $value) {
          if ($key === $result) {
              $this->setServer($value);
          }
      }
  }

  private function setServer(string $potentialServerUrl) {
        $existingUrls = array_column((array)$this->openApiSpec['servers'], 'url');

        if (!in_array($potentialServerUrl, $existingUrls)) {
            $this->openApiSpec['servers'][] = [
                'url' => $potentialServerUrl
            ];
        }
  }

  private function parsePaths(array $item, ?string $tag = null): void {
        // handle case where it is string
        if (is_string($item['request'])) {
            if (!array_key_exists($item['request'], (array)$this->openApiSpec['paths'])) {
                $this->openApiSpec['paths'][$item['request']] = [
                    'get' => []
                ];
            } else {
                $array = (array)$this->openApiSpec['paths'][$item['request']];
                $array[] = [
                    'get' => []
                ];
            }
        }

        // parse server url from path
        $this->parseServer($item['request']['url']['raw']);
        $path = '/' . implode('/', $item['request']['url']['path']);
        $httpMethod = mb_strtolower($item['request']['method']);

       if (!array_key_exists($path, (array)$this->openApiSpec['paths'])) {
           $this->openApiSpec['paths'][$path] = [
                $httpMethod => [
                    'summary' => $item['name'],
                    'description' => $item['name'],
                    'operationId' => $item['name']
                ]
           ];
       } else {
           $array = (array)$this->openApiSpec['paths'][$path];
           $array[] = [
               $httpMethod => [
                   'summary' => $item['name'],
                   'description' => $item['name'],
                   'operationId' => $item['name']
               ]
           ];
       }

       if (isset($item['request']['auth'])) {
           $key = $item['request']['auth']['type'] . "Auth";
           $this->openApiSpec['paths'][$path][$httpMethod]['security'] =
               [
                   [
                       $key => []
                   ]
               ];
       }

       if (isset($item['request']['header'])) {
           $this->parseHeaders($item['request']['header'], $path, $httpMethod);
       }

       if (isset($item['request']['body'])) {
           $this->parseBody($item['request']['body'], $path, $httpMethod, $item['name']);
       }

//       // parse responses
//      if (empty($item['response'])) {
//          $this->openApiSpec['paths'][$path][$httpMethod] = [
//              '200' => [
//                  'description' => ''
//              ]
//          ];
//      } else {
//
//      }

  }

  private function parseBody(array $requestBody, string $path, string $httpMethod, string $operationName): void {
      switch ($requestBody['mode']) {
          case 'raw':
              $this->openApiSpec['paths'][$path][$httpMethod]['requestBody'] = [
                      'content' => [
                          'application/json' => [
                              'examples' => [
                                  $operationName => [
                                      'value' => $requestBody['raw']
                                  ]
                              ]
                          ]
                      ]
              ];
              break;
          case 'urlencoded':
              $properties = [];
              $examples = [];
              foreach ($requestBody['urlencoded'] as $formField) {
                  $properties[] = [
                      $formField['key'] => [
                          'type' => 'string',
                          'example' => $formField['value']
                      ]
                  ];

                  if (!array_key_exists($operationName, $examples)) {
                      $examples[] = [
                          $operationName => [
                              $formField['key'] => $formField['value']
                          ]
                      ];
                  } else {
                      $examples[$operationName][] = [
                          $formField['key'] => $formField['value']
                      ];
                  }
              }
              $this->openApiSpec['paths'][$path][$httpMethod]['requestBody'] = [
                      'content' => [
                          'application/x-www-form-urlencoded' => [
                              'examples' => $examples,
                              'schema' => [
                                  'type' => 'object',
                                  'properties' => $properties,
                              ]
                          ]
                      ]
              ];
              break;
          case 'formdata':
              $properties = [];
              foreach ($requestBody['formdata'] as $formField) {
                  $properties[] = [
                      $formField['key'] => [
                          'type' => 'string',
                          'format' => 'binary'
                      ]
                  ];
              }
              $this->openApiSpec['paths'][$path][$httpMethod]['requestBody'] = [
                      'content' => [
                          'multipart/form-data' => [
                              'schema' => [
                                  'type' => 'object',
                                  'properties' => $properties,
                              ]
                          ]
                      ]
              ];
              break;
      }
  }

  private function parseHeaders(array $headers, string $path, string $httpMethod): void {
      foreach ($headers as $header) {
          if (!array_key_exists('parameters', (array)$this->openApiSpec['paths'][$path][$httpMethod])) {
              $this->openApiSpec['paths'][$path][$httpMethod]['parameters'] = [
                      [
                          'name' => $header['key'],
                          'in' => 'header',
                          'schema' => [
                              'type' => 'string',
                              'example' => $header['value']
                          ]
                      ]
              ];
          } else {
              $this->openApiSpec['paths'][$path][$httpMethod]['parameters'][] = [
                      'name' => $header['key'],
                      'in' => 'header',
                      'schema' => [
                          'type' => 'string',
                          'example' => $header['value']
                      ]
                  ];
          }
      }
  }

  private function parseInfo(array $postmanJson): void {
      $info = $postmanJson["info"];
      $result = [
          'title' => $info['name'],
      ];

      if (isset($info['description'])) {
          $result['info'] = $info['description'];
      }

      if (isset($info['version'])) {
          if (is_string($info['version'])) {
              $result['version'] = $info['version'];
          } else {
              // parse postman versioning
              $major = $info['version']['major'];
              $minor = $info['version']['minor'];
              $patch = $info['version']['patch'];
              $result['version'] = sprintf('%s.%s.%s', $major, $minor, $patch);

              if (isset($info['version']['identifier'])) {
                  $result['version'] .= sprintf("-%s", $info['version']['identifier']);
              }
          }
      } else {
          $result['version'] = '1.0.0';
      }

      $this->openApiSpec['info'] = $result;
  }

  private function parseCollectionVariables(array $postmanJson): void {
        $this->collectionVariables = $postmanJson['variable'];
  }
}