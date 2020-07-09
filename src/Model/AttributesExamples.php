<?php declare(strict_types=1);

namespace Horat1us\Yii\Model;

interface AttributesExamples
{
    public function attributesExamples(): array;

    public function getAttributeExamples(string $attribute): ?array;

    public function getAttributeExample(string $attribute): ?string;
}
