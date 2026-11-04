<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests\Fixtures;

use yii\base;
use yii\validators;

/**
 * Range from the model's class constants, resolved by a closure set in init(),
 * the way horat1us/yii2-base ConstRangeValidator does it.
 */
class ConstRangeValidator extends validators\RangeValidator
{
    public function init(): void
    {
        $this->range = function (base\Model $model, string $attribute): array {
            $prefix = strtoupper($attribute) . '_';
            $constants = (new \ReflectionClass($model))->getConstants();
            return array_values(array_filter(
                $constants,
                fn(string $name) => str_starts_with($name, $prefix),
                ARRAY_FILTER_USE_KEY,
            ));
        };
        parent::init();
    }
}
