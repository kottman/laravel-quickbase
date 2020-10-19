# laravel-quickbase

## Installation

Add this to your composer.json under "repositories":

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/kottman/laravel-quickbase.git"
    }
],
```

Require this package with composer.

```shell
composer require kottman/laravel-quickbase
```

Add the following QB app specific in your app.config:

```twig
'qbBaseUrl' => env('QB_BASE_URL', ''),
'qbAppToken' => env('QB_AppToken', ''),
'qbUserToken' => env('QB_USER_TOKEN', ''),
'qbAppId' => env('QB_APP_ID', ''),
```

## Usage

You can now CRUD your QB models:

```php

namespace App\Models\QbModels;

use Kottman\Qb\QbModel;

class Shipments extends QbModel
{
    protected $dbId = 'shipmentDbId';
}
///////////----------///////

$shipment = Shipments::findByRecordIdOrFail(123);
// Access fields using:
$field1Value = $shipment[field1Id];
// or
$field1Value = $shipment->{'field1Name'};
```
