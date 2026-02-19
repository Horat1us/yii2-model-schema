<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests;

use Horat1us\Yii\JsonSchema;
use Horat1us\Yii\Model\AttributesExamples;
use Horat1us\Yii\Model\AttributesExamplesTrait;
use Horat1us\Yii\Model\AttributeValuesLabels;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use yii\base;
use yii\validators;
use Horat1us\Yii\Validation;

class JsonSchemaTest extends TestCase
{
    public static function modelDataProvider(): array
    {
        return [
            [new class extends base\Model implements AttributesExamples {
                use AttributesExamplesTrait;

                public string $str = 'testCase';
                public ?int $num = null;

                public function formName(): string
                {
                    return "TestForm";
                }

                public function rules(): array
                {
                    return [
                        [['str',], 'required',],
                        [['str',], 'string',],
                        [['num',], 'number', 'integerOnly' => true,],
                    ];
                }

                public function attributeHints(): array
                {
                    return [
                        'num' => 'This is Number!',
                    ];
                }

                public function attributesExamples(): array
                {
                    return [
                        'num' => [1, 2],
                    ];
                }
            }, [
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'title' => 'Test Form',
                'type' => 'object',
                'properties' => [
                    'str' => [
                        'title' => 'Str',
                        'type' => 'string',
                    ],
                    'num' => [
                        'title' => 'Num',
                        'type' => 'integer',
                        'description' => 'This is Number!',
                        'examples' => [1, 2,],
                    ],
                ],
                'required' => [
                    'str',
                ],
            ]],
            [new class extends base\Model {
                public string $passport_type = 'idcard';
                public string $passport_number = '';

                public function formName(): string
                {
                    return "ConditionalForm";
                }

                public function rules(): array
                {
                    return [
                        [['passport_type'], 'safe'],
                        [['passport_number'], 'required',
                            'when' => fn(base\Model $model, string $attribute): bool => false],
                    ];
                }
            }, [
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'title' => 'Conditional Form',
                'type' => 'object',
                'properties' => [
                    'passport_type' => ['title' => 'Passport Type'],
                    'passport_number' => ['title' => 'Passport Number'],
                ],
            ]],
            [new class extends base\Model {
                public string $passport_type = 'legacy';
                public string $passport_number = '';

                public function formName(): string
                {
                    return "ConditionalForm";
                }

                public function rules(): array
                {
                    return [
                        [['passport_type'], 'safe'],
                        [['passport_number'], 'required',
                            'when' => fn(base\Model $model, string $attribute): bool => true],
                    ];
                }
            }, [
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'title' => 'Conditional Form',
                'type' => 'object',
                'properties' => [
                    'passport_type' => ['title' => 'Passport Type'],
                    'passport_number' => ['title' => 'Passport Number'],
                ],
                'required' => ['passport_number'],
            ]],
        ];
    }

    #[DataProvider('modelDataProvider')]
    public function testJsonSerialize(base\Model $model, array $expected): void
    {
        $schema = new JsonSchema($model);
        $actual = $schema->jsonSerialize();
        $this->assertEquals($expected, $actual);
    }

    public static function attributeDataProvider(): array
    {
        return [
            [new class extends base\Model implements AttributesExamples {
                use AttributesExamplesTrait;

                public string $str = 'testCase';

                public function rules(): array
                {
                    return [
                        [['str',], 'string', 'min' => 4,],
                        [['str',], 'default', 'value' => 'Slavic',]
                    ];
                }

                public function attributeHints(): array
                {
                    return [
                        'str' => 'This is str!',
                    ];
                }

                public function attributesExamples(): array
                {
                    return [
                        'str' => ['White', 'Black',],
                    ];
                }
            }, 'str', [
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'title' => 'Str',
                'type' => 'string',
                'description' => 'This is str!',
                'minLength' => 4,
                'examples' => ['White', 'Black',],
                'default' => 'Slavic',
            ]],
        ];
    }

    #[DataProvider('attributeDataProvider')]
    public function testAttribute(base\Model $model, string $attribute, array $expected): void
    {
        $schema = new JsonSchema($model);
        $actual = $schema->attribute($attribute);
        $this->assertEquals($expected, $actual);
    }

    public static function validatorDataProvider(): array
    {
        $model = new base\Model();
        return [
            [$model, new validators\RequiredValidator(), [],],
            [$model, new validators\FilterValidator(['filter' => fn() => null]), [],],
            [$model, new validators\InlineValidator(), [],],
            [$model, new validators\RangeValidator([
                'range' => [1, 2,],
            ]), ['enum' => [1, 2,]],],
            [$model, new validators\RangeValidator([
                'range' => fn() => [1, 2,],
            ]), ['enum' => [1, 2,]],],
            [$model, new validators\RangeValidator([
                'range' => fn(string $a) => [1, 2,],
            ]), ['enum' => [],]],
            [$rangeModel = new base\DynamicModel(['atr']), $rangeValidator = new validators\RangeValidator([
                'range' => function (base\Model $m, string $attribute) use ($rangeModel) {
                    \PHPUnit\Framework\Assert::assertEquals($rangeModel, $m);
                    \PHPUnit\Framework\Assert::assertEquals('atr', $attribute);
                    return [1, 2,];
                },
                'attributes' => ['atr',],
            ]), ['enum' => [1, 2,],]],
            [$model, new validators\NumberValidator(['min' => 1, 'max' => 10,]), [
                'type' => 'number',
                'minimum' => 1,
                'maximum' => 10,
            ],],
            [
                new class extends base\Model implements AttributeValuesLabels {
                    public function attributeValuesLabels(string $attribute): ?array
                    {
                        return [
                                'atr' => [
                                    1 => 'One',
                                    2 => 'Two',
                                ],
                            ][$attribute] ?? null;
                    }
                },
                new validators\RangeValidator([
                    'range' => [1, 2,],
                    'attributes' => 'atr',
                ]),
                [
                    'oneOf' => [
                        [
                            'title' => 'One',
                            'const' => 1,
                        ],
                        [
                            'title' => 'Two',
                            'const' => 2,
                        ],
                    ],
                ],
            ],
            [$model, new validators\RegularExpressionValidator([
                'pattern' => '/^\d+$/u',
            ]), [
                'type' => 'string',
                'pattern' => '^\d+$'
            ]],
            [$model, new validators\DateValidator([
                'type' => validators\DateValidator::TYPE_DATE,
                'locale' => 'en',
                'timeZone' => 'Europe/Kiev',
                'format' => '',
            ]), [
                'type' => 'string',
                'format' => 'date',
            ]],
            [$model, new validators\DateValidator([
                'type' => validators\DateValidator::TYPE_TIME,
                'locale' => 'en',
                'timeZone' => 'Europe/Kiev',
                'format' => '',
            ]), [
                'type' => 'string',
                'format' => 'time',
            ]],
            [$model, new validators\DateValidator([
                'type' => validators\DateValidator::TYPE_DATETIME,
                'locale' => 'en',
                'timeZone' => 'Europe/Kiev',
                'format' => '',
            ]), [
                'type' => 'string',
                'format' => 'date-time',
            ]],
            [$model, new validators\EmailValidator(), [
                'type' => 'string',
                'format' => 'email',
            ]],
            [$model, new class extends validators\Validator {
                public function validateAttribute($model, $attribute): void
                {
                }
            }, []],
            [$model, new class extends validators\Validator implements Validation\JsonSchema {
                public function getJsonSchema(): array
                {
                    return [
                        'type' => 'string',
                        'format' => 'inn',
                    ];
                }
            }, [
                'type' => 'string',
                'format' => 'inn',
            ]],
            [$model, new validators\DefaultValueValidator([
                'value' => 'Whitelist',
            ]), [
                'default' => 'Whitelist',
            ]],
        ];
    }

    #[DataProvider('validatorDataProvider')]
    public function testValidator(base\Model $model, validators\Validator $validator, array $expected): void
    {
        $schema = new JsonSchema($model);
        $attributes = (array)$validator->attributes;
        $name = array_shift($attributes);
        $actual = $schema->validator($validator, is_string($name) ? $name : null);
        $this->assertEquals($expected, $actual);
    }

    public static function patternDataProvider(): array
    {
        return [
            [
                '/^[А-ЯЄЇЫЁІЪ][а-яєїыёъі\']+(?:-[А-ЯЄЇЫЁІЪ][а-яєїыёъі\']+)?$/u',
                '^[А-ЯЄЇЫЁІЪ][а-яєїыёъі\']+(?:-[А-ЯЄЇЫЁІЪ][а-яєїыёъі\']+)?$',
            ],
            ['^380(([1-9]{2})|(00))\d{7}$', '^380(([1-9]{2})|(00))\d{7}$'],
            ['/^380(([1-9]{2})|(00))\d{7}$/', '^380(([1-9]{2})|(00))\d{7}$'],
        ];
    }

    #[DataProvider('patternDataProvider')]
    public function testPattern(string $phpRegExp, string $expected): void
    {
        $this->assertEquals(
            $expected,
            JsonSchema::pattern($phpRegExp)
        );
    }
}
