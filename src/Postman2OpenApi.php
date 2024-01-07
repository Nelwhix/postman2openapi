<?php declare(strict_types=1);

namespace Nelwhix\Postman2openapi;

use Cerbero\JsonParser\JsonParser;
use Nelwhix\Postman2openapi\utils\Map;
use types\Url;
use utils\Set;

class Postman2OpenApi
{
  public static function parse(string $postmanSpec): string {
      $openApiSpec = [
          'openapi' => '3.1.0'
      ];
      $postmanJson = json_decode($postmanSpec, true);

      // parse info
      self::parseInfo($postmanJson, $openApiSpec);
      return json_encode($openApiSpec);
      // loop through item array
  }

  private static function parseInfo(array $postmanJson, array &$openApiArray): void {
      $info = $postmanJson["info"];
      $result = [
          'title' => $info['name'],
          'description' => $info['description']
      ];

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

      $openApiArray['info'] = $result;
  }
}