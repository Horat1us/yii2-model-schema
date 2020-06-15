<?php declare(strict_types=1);

namespace Horat1us\Yii\Tests;

use Horat1us\Yii\JsonSchema;
use Horat1us\Yii\Model\AttributeValuesLabels;
use PHPUnit\Framework\TestCase;
use yii\base;
use yii\validators;
use Horat1us\Yii\Validation;

class JsonSchemaTest extends TestCase
{
    public function modelDataProvider(): array
    {
        return [
            [new class extends base\Model {
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
                    ],
                ],
                'required' => [
                    'str',
                ],
            ]],
        ];
    }

    /**
     * @dataProvider modelDataProvider
     */
    public function testJsonSerialize(base\Model $model, array $expected): void
    {
        $schema = new JsonSchema($model);
        $actual = $schema->jsonSerialize();
        $this->assertEquals($expected, $actual);
    }

    public function attributeDataProvider(): array
    {
        return [
            [new class extends base\Model {
                public string $str = 'testCase';

                public function rules(): array
                {
                    return [
                        [['str',], 'string', 'min' => 4,],
                    ];
                }

                public function attributeHints(): array
                {
                    return [
                        'str' => 'This is str!',
                    ];
                }
            }, 'str', [
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'title' => 'Str',
                'type' => 'string',
                'description' => 'This is str!',
                'minLength' => 4,
            ]],
        ];
    }

    /**
     * @dataProvider attributeDataProvider
     */
    public function testAttribute(base\Model $model, string $attribute, array $expected): void
    {
        $schema = new JsonSchema($model);
        $actual = $schema->attribute($attribute);
        $this->assertEquals($expected, $actual);
    }

    public function validatorDataProvider(): array
    {
        $model = new base\Model();
        return [
            [$model, new validators\RequiredValidator, [],],
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
                    $this->assertEquals($rangeModel, $m);
                    $this->assertEquals('atr', $attribute);
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
            [$model, $this->createMock(validators\Validator::class), []],
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
        ];
    }

    /**
     * @dataProvider validatorDataProvider
     */
    public function testValidator(base\Model $model, validators\Validator $validator, array $expected): void
    {
        $schema = new JsonSchema($model);
        $attributes = $validator->attributes;
        $actual = $schema->validator($validator, array_shift($attributes));
        $this->assertEquals($expected, $actual);
    }
}
