<?php declare(strict_types=1);

namespace Horat1us\Yii\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Horat1us\Yii;

class JsonSchemaTraitTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            [$hasSchema = new class implements Yii\Validation\JsonSchema {
                use Yii\Validation\JsonSchemaTrait;

                public array $jsonSchema = [
                    'type' => 'string',
                    'format' => 'email',
                ];
            }, $hasSchema->jsonSchema],
            [
                new class implements Yii\Validation\JsonSchema {
                    use Yii\Validation\JsonSchemaTrait;
                },
                [],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetJsonSchema(Yii\Validation\JsonSchema $validator, array $jsonSchema): void
    {
        $this->assertEquals($jsonSchema, $validator->getJsonSchema());
    }
}
