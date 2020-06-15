<?php declare(strict_types=1);

namespace Horat1us\Yii\Model;

interface AttributeValuesLabels
{
    public function attributeValuesLabels(string $attribute): ?array;
}
