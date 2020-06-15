<?php declare(strict_types=1);

namespace Horat1us\Yii\Tests;

use Horat1us\Yii\JsonSchema;
use PHPUnit\Framework\TestCase;
use yii\base;
use yii\validators;

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
            ]), ['enum' => [1, 2,],]]
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
