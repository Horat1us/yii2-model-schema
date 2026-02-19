<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Horat1us\Yii\Model;

#[CoversClass(Model\AttributesExamplesTrait::class)]
class AttributesExamplesTraitTest extends TestCase
{
    public Model\AttributesExamples $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new class implements Model\AttributesExamples {
            use Model\AttributesExamplesTrait;

            public function attributesExamples(): array
            {
                return [
                    'real' => ['first', 'second'],
                    'empty' => [],
                    'equalNull' => null,
                ];
            }
        };
    }

    public static function examplesDataProvider(): array
    {
        return [
            ['real', ['first', 'second',],],
            ['empty', null,],
            ['notConfigured', null,],
            ['equalNull', null],
        ];
    }

    #[DataProvider('examplesDataProvider')]
    public function testGetAttributeExamples(string $attribute, ?array $expected = null): void
    {
        $this->assertEquals(
            $this->model->getAttributeExamples($attribute),
            $expected
        );
    }

    public static function exampleDataProvider(): array
    {
        return [
            ['real', 'first',],
            ['empty', null,],
            ['notConfigured', null,],
            ['equalNull', null],
        ];
    }

    #[DataProvider('exampleDataProvider')]
    public function testGetAttributeExample(string $attribute, ?string $expected = null): void
    {
        $this->assertEquals(
            $this->model->getAttributeExample($attribute),
            $expected
        );
    }
}
