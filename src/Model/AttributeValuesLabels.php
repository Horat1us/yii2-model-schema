<?php

declare(strict_types=1);

namespace Horat1us\Yii\Model;

interface AttributeValuesLabels
{
    /** @return array<int|string, string>|null */
    public function attributeValuesLabels(string $attribute): ?array;
}
