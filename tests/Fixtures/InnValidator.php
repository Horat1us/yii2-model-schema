<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests\Fixtures;

use Horat1us\Yii\Validation;
use yii\validators;

class InnValidator extends validators\Validator implements Validation\JsonSchema
{
    public function validateAttribute($model, $attribute): void
    {
    }

    public function getJsonSchema(): array
    {
        return ['type' => 'string', 'format' => 'inn'];
    }
}
