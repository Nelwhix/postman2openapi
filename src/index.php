<?php declare(strict_types=1);

use Nelwhix\Postman2openapi\Postman2OpenApi;

require __DIR__ . '/../vendor/autoload.php';

$postman = '{"info":{"_postman_id":"e5600773-37fe-462e-80eb-18791f791b11","name":"Swave-API","schema":"https://schema.getpostman.com/json/collection/v2.1.0/collection.json","_exporter_id":"29092434","_collection_link":"https://cloudy-water-338684-1.postman.co/workspace/Team-Workspace~f63a1f2e-db36-4fa0-8eee-d45adbeafb03/collection/25505213-e5600773-37fe-462e-80eb-18791f791b11?action=share&source=collection_link&creator=29092434"}}';

$p2o = new Postman2OpenApi();
try {
    var_dump($p2o->parse($postman));
} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage();
}