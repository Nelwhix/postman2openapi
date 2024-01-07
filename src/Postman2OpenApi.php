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
      $postmanJson = json_decode($postmanSpec);

      // parse info
      self::parseInfo($postmanJson);

      // loop through item array
  }

  private static function parseInfo(array &$postmanJson): void {
      $info = $postmanJson["info"];
  }
}