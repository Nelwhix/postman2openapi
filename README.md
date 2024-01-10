# postman2openapi
PHP library to convert postman v2.1 to OpenApi 3.1.0

## Installation
```bash 
    composer require nelwhix/postman2openapi
```

## Usage
```php
<?php declare(strict_types=1);

use Nelwhix\Postman2openapi\Postman2OpenApi;

require __DIR__ . '/../vendor/autoload.php';

// get postman contents from file as string
$postman = file_get_contents(__DIR__ . "/../../../../Downloads/insights_postman.json");

// instantiate library
$p2o = new Postman2OpenApi();
try {
    // parse
    $openApi = $p2o->parse($postman);
    file_put_contents(__DIR__ . '/../test/' . date('Y-m-d H:i:s') . '.json', $openApi);
} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage();
}
```

## TODO
- Provide p2o cli
- parse response examples
- parse version, contact information etc. from collection variables

