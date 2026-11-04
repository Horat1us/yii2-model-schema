<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests\Fixtures;

use Horat1us\Yii\Model\AttributesExamples;
use Horat1us\Yii\Model\AttributesExamplesTrait;
use Horat1us\Yii\Model\AttributeValuesLabels;
use yii\base;

/**
 * The rule shapes of the borrower registration data (bobra-backend BorrowerRequestData),
 * whose schema the site renders. Its output must not change between releases.
 */
class RegistrationModel extends base\Model implements AttributeValuesLabels, AttributesExamples
{
    use AttributesExamplesTrait;

    public const string PASSPORT_TYPE_ID_CARD = 'idCard';
    public const string PASSPORT_TYPE_PASSPORT = 'passport';
    public const string PASSPORT_TYPE_INTERNATIONAL_PASSPORT = 'intPassport';
    public const string GENDER_MALE = 'male';
    public const string GENDER_FEMALE = 'female';

    public ?string $first_name = null;
    public ?string $inn = null;
    public ?string $passport_type = null;
    public ?string $passport = null;
    public ?string $passport_date_issue = null;
    public ?string $email = null;
    public ?string $gender = null;

    public function formName(): string
    {
        return 'BorrowerRequestData';
    }

    public function rules(): array
    {
        return [
            ['first_name', 'required', 'message' => 'Представьтесь, пожалуйста.'],
            ['inn', 'required'],
            [['inn'], InnValidator::class, 'when' => fn() => false],
            [['inn'], 'match', 'pattern' => '/^\d{10}$/'],
            [['first_name'], 'match', 'pattern' => '/^[А-ЯІЇЄҐ][а-яіїєґ\']+$/u'],
            ['passport_type', 'required'],
            [['passport_type'], ConstRangeValidator::class],
            [['passport'], 'filter', 'filter' => fn($value) => $value],
            [['passport'], 'match', 'pattern' => '/^[А-ЯІЇҐA-Z]{2}\d{6}$/u', 'when' => fn() => false],
            [['passport'], 'match', 'pattern' => '/^[А-ЯІЇҐ]{2}\d{6}$/u'],
            [
                'passport',
                'required',
                'when' => fn(self $model) => $model->passport_type === self::PASSPORT_TYPE_PASSPORT,
            ],
            [['passport'], 'string', 'length' => 8],
            [
                ['passport_date_issue'],
                'date',
                'type' => 'date',
                'format' => 'php:Y-m-d',
                'locale' => 'uk',
                'timeZone' => 'Europe/Kyiv',
            ],
            [['email'], 'email'],
            [['gender'], ConstRangeValidator::class, 'range' => [self::GENDER_MALE, self::GENDER_FEMALE]],
            [['first_name'], 'default', 'value' => ''],
        ];
    }

    public function attributeValuesLabels(string $attribute): ?array
    {
        return [
            'passport_type' => [
                self::PASSPORT_TYPE_ID_CARD => 'ID-карта',
                self::PASSPORT_TYPE_PASSPORT => 'Паспорт',
                self::PASSPORT_TYPE_INTERNATIONAL_PASSPORT => 'Закордонний паспорт',
            ],
        ][$attribute] ?? null;
    }

    public function attributesExamples(): array
    {
        return [
            'first_name' => ['Олег'],
            'passport' => ['АА123456'],
        ];
    }

    public function attributeHints(): array
    {
        return ['inn' => 'ІПН'];
    }
}
