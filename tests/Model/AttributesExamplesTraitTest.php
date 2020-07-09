<?php declare(strict_types=1);

namespace Horat1us\Yii\Tests\Model;

use PHPUnit\Framework\TestCase;
use Horat1us\Yii\Model;

/**
 * @coversDefaultClass \Horat1us\Yii\Model\AttributesExamplesTrait
 */
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

    public function examplesDataProvider(): array
    {
        return [
            ['real', ['first', 'second',],],
            ['empty', null,],
            ['notConfigured', null,],
            ['equalNull', null],
        ];
    }

    /**
     * @covers       \Horat1us\Yii\Model\AttributesExamplesTrait::getAttributeExamples
     * @dataProvider examplesDataProvider
     */
    public function testGetAttributeExamples(string $attribute, array $expected = null): void
    {
        $this->assertEquals(
            $this->model->getAttributeExamples($attribute),
            $expected
        );
    }

    public function exampleDataProvider(): array
    {
        return [
            ['real', 'first',],
            ['empty', null,],
            ['notConfigured', null,],
            ['equalNull', null],
        ];
    }

    /**
     * @covers       \Horat1us\Yii\Model\AttributesExamplesTrait::getAttributeExample
     * @dataProvider exampleDataProvider
     */
    public function testGetAttributeExample(string $attribute, string $expected = null): void
    {
        $this->assertEquals(
            $this->model->getAttributeExample($attribute),
            $expected
        );
    }
}
