<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests;

use Horat1us\Yii\JsonSchema;
use Horat1us\Yii\Tests\Fixtures\RegistrationModel;
use PHPUnit\Framework\TestCase;

/**
 * The borrower registration schema is rendered by the client sites, so its output is pinned
 * exactly (key order and types included): the output as of 2.1.0.
 */
class RegistrationSchemaTest extends TestCase
{
    private const array PASSPORT_TYPE_ONE_OF = [
        ['const' => 'idCard', 'title' => 'ID-карта'],
        ['const' => 'passport', 'title' => 'Паспорт'],
        ['const' => 'intPassport', 'title' => 'Закордонний паспорт'],
    ];

    public function testJsonSerialize(): void
    {
        $this->assertSame([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'Borrower Request Data',
            'type' => 'object',
            'properties' => [
                'first_name' => [
                    'title' => 'First Name',
                    'examples' => ['Олег'],
                    'type' => 'string',
                    'pattern' => '^[А-ЯІЇЄҐ][а-яіїєґ\']+$',
                    'default' => '',
                ],
                'inn' => [
                    'title' => 'Inn',
                    'description' => 'ІПН',
                    'type' => 'string',
                    'format' => 'inn',
                    'pattern' => '^\d{10}$',
                ],
                'passport_type' => [
                    'title' => 'Passport Type',
                    'oneOf' => self::PASSPORT_TYPE_ONE_OF,
                ],
                'passport' => [
                    'title' => 'Passport',
                    'examples' => ['АА123456'],
                    'type' => 'string',
                    'pattern' => '^[А-ЯІЇҐA-Z]{2}\d{6}$',
                    'minLength' => 8,
                ],
                'passport_date_issue' => [
                    'title' => 'Passport Date Issue',
                    'type' => 'string',
                    'format' => 'date',
                ],
                'email' => [
                    'title' => 'Email',
                    'type' => 'string',
                    'format' => 'email',
                ],
                'gender' => [
                    'title' => 'Gender',
                    'enum' => ['male', 'female'],
                ],
            ],
            'required' => ['first_name', 'inn', 'passport_type'],
        ], (new JsonSchema(new RegistrationModel()))->jsonSerialize());
    }

    public function testConditionalRequired(): void
    {
        $model = new RegistrationModel();
        $model->passport_type = RegistrationModel::PASSPORT_TYPE_PASSPORT;

        $this->assertSame(
            ['first_name', 'inn', 'passport_type', 'passport'],
            (new JsonSchema($model))->jsonSerialize()['required'] ?? null,
        );
    }

    public function testAttributeWithClosureRange(): void
    {
        $this->assertSame([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'Passport Type',
            'oneOf' => self::PASSPORT_TYPE_ONE_OF,
        ], (new JsonSchema(new RegistrationModel()))->attribute('passport_type'));
    }

    public function testAttributeWithExplicitRange(): void
    {
        $this->assertSame([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'Gender',
            'enum' => ['male', 'female'],
        ], (new JsonSchema(new RegistrationModel()))->attribute('gender'));
    }

    public function testAttributeWithSeveralRules(): void
    {
        $this->assertSame([
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'Passport',
            'examples' => ['АА123456'],
            'type' => 'string',
            'pattern' => '^[А-ЯІЇҐA-Z]{2}\d{6}$',
            'minLength' => 8,
        ], (new JsonSchema(new RegistrationModel()))->attribute('passport'));
    }
}
