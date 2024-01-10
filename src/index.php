<?php declare(strict_types=1);

use Nelwhix\Postman2openapi\Postman2OpenApi;

require __DIR__ . '/../vendor/autoload.php';

$postman = file_get_contents(__DIR__ . "/../../../../Downloads/insights_postman.json");

$p2o = new Postman2OpenApi();
try {
    $openApi = $p2o->parse($postman);
    file_put_contents(__DIR__ . '/../test/' . date('Y-m-d H:i:s') . '.json', $openApi);
} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage();
}