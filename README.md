# Yii2 Model Schema

[![Latest Stable Version](https://poser.pugx.org/horat1us/yii2-model-schema/version)](https://packagist.org/packages/horat1us/yii2-model-schema)
[![Total Downloads](https://poser.pugx.org/horat1us/yii2-model-schema/downloads)](https://packagist.org/packages/horat1us/yii2-model-schema)
[![CI](https://github.com/Horat1us/yii2-model-schema/actions/workflows/ci.yml/badge.svg)](https://github.com/Horat1us/yii2-model-schema/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/horat1us/yii2-model-schema/branch/master/graph/badge.svg)](https://codecov.io/gh/horat1us/yii2-model-schema)

Create JSON Schema from Yii2 Model using validation rules and other public methods.

## Installation
Using composer:
```bash
composer require horat1us/yii2-model-schema:^2.0
```

## Usage

### [base\Model](https://www.yiiframework.com/doc/api/2.0/yii-base-model) extensions
Additional interfaces that will be used for generating JsonSchema,
when they are implemented in model.

#### [AttributesExamples](./src/Model/AttributesExamples.php)
Will be used to generate property
[*examples*](https://json-schema.org/understanding-json-schema/reference/generic.html?highlight=examples)  

*See [AttributesExamplesTrait](./src/Model/AttributesExamplesTrait.php) for implementation*
*Since [1.1.0](https://github.com/Horat1us/yii2-model-schema/tree/1.0.3)*

```php
<?php declare(strict_types=1);

namespace App;

use Horat1us\Yii\Model;
use yii\base;

$model = new class extends base\Model implements Model\AttributesExamples {
    use Model\AttributesExamplesTrait;
    public function attributesExamples(): array {
        return [
            'a' => [1,2],
            'b' => [],
        ];
    }
};
echo $model->getAttributeExamples('a'); // [1,2]
echo $model->getAttributeExamples('b'); // null
echo $model->getAttributeExamples('c'); // null
echo $model->getAttributeExample('a'); // 1
echo $model->getAttributeExample('b'); // null
echo $model->getAttributeExample('c'); // null
```

## Conditional Required Fields

When a `RequiredValidator` has a `when` callable, `JsonSchema` evaluates it
against the model at schema-generation time. Only attributes whose `when`
returns `true` (or have no `when`) appear in the `required` array.

```php
$model = new class extends base\Model {
    public string $passport_type = 'idcard';
    public string $passport_number = '';
    public string $idcard_number = '';

    public function rules(): array {
        return [
            [['passport_number'], 'required',
             'when' => fn(base\Model $m): bool => $m->passport_type === 'legacy'],
            [['idcard_number'], 'required',
             'when' => fn(base\Model $m): bool => $m->passport_type === 'idcard'],
        ];
    }
};
// passport_type is 'idcard' → only idcard_number in required
$schema = (new JsonSchema($model))->jsonSerialize();
// $schema['required'] === ['idcard_number']
```

## TODO
Write docs:
- [JsonSchema](./src/JsonSchema.php)

## Contributors
- [Alexander Letnikow](mailto:reclamme@gmail.com)

## License
[MIT](./LICENSE)
