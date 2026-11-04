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

## Validators

[JsonSchema](./src/JsonSchema.php) builds each property from the attribute's active validators:

| Validator | Schema |
|-----------|--------|
| `required` | adds the attribute to `required` (see [Conditional Required Fields](#conditional-required-fields)) |
| `string` | `{"type": "string"}`, `minLength`/`maxLength` from `min`/`max`, `"format": "textarea"` when `max > 255` |
| `boolean` | `{"type": "boolean"}` |
| `url` | `{"type": "string", "format": "uri"}` |
| `email` | `{"type": "string", "format": "email"}` |
| `number`, `integer` | `{"type": "number"}` or `{"type": "integer"}`, `minimum`/`maximum` from `min`/`max` |
| `match` | `{"type": "string", "pattern": "..."}`, PHP delimiters and flags stripped |
| `date` | `{"type": "string", "format": "date"}`, `"time"` or `"date-time"` by the validator `type` |
| `in` | `{"oneOf": [{"const": ..., "title": ...}]}` when the model labels every value, `{"enum": [...]}` otherwise |
| `in` with `allowArray` | `{"type": "array", "uniqueItems": true, "items": <the in schema above>}` |
| `each` | `{"type": "array", "items": <schema of the rule>}`; `items` is left out when the rule maps to nothing |
| `default` | `{"default": ...}` |
| implements [Validation\JsonSchema](./src/Validation/JsonSchema.php) | the validator's `getJsonSchema()` |
| `filter`, inline and others | nothing |

When an attribute has several rules, their schemas are merged and the first rule wins for a key.
The table is checked top to bottom, so a subclass of a listed validator (e.g. of `match`)
gets that validator's schema even if it implements `Validation\JsonSchema`.

Labels for `in` come from [AttributeValuesLabels](./src/Model/AttributeValuesLabels.php):

```php
class Settings extends base\Model implements Model\AttributeValuesLabels
{
    public array $audiences = ['borrowers'];
    public array $mediums = [];

    public function rules(): array
    {
        return [
            [['audiences'], 'in', 'range' => ['borrowers', 'partners'], 'allowArray' => true],
            [['mediums'], 'each', 'rule' => ['string', 'max' => 255]],
        ];
    }

    public function attributeValuesLabels(string $attribute): ?array
    {
        return [
            'audiences' => ['borrowers' => 'Borrowers', 'partners' => 'Partners'],
        ][$attribute] ?? null;
    }
}

(new JsonSchema(new Settings()))->jsonSerialize()['properties'];
// 'audiences' => [
//     'title' => 'Audiences', 'type' => 'array', 'uniqueItems' => true,
//     'items' => ['oneOf' => [
//         ['const' => 'borrowers', 'title' => 'Borrowers'],
//         ['const' => 'partners', 'title' => 'Partners'],
//     ]],
// ],
// 'mediums' => ['title' => 'Mediums', 'type' => 'array', 'items' => ['type' => 'string', 'maxLength' => 255]],
```

`uniqueItems` describes a choice of options (a checkbox group); `RangeValidator` itself accepts duplicates,
so filter them out (e.g. `array_unique`) if they matter.

## Contributors
- [Alexander Letnikow](mailto:reclamme@gmail.com)

## License
[MIT](./LICENSE)
