<?php

declare(strict_types=1);

namespace Horat1us\Yii\Model;

/**
 * @since 1.1.0
 * @author Alexander Letnikow <reclamme@gmail.com>
 * @see \Horat1us\Yii\Model\AttributesExamples
 */
trait AttributesExamplesTrait
{
    /** @return array<string, array<int, mixed>|null> */
    abstract public function attributesExamples(): array;

    public function getAttributeExamples(string $attribute): ?array
    {
        $attributesExamples = $this->attributesExamples();
        return array_key_exists($attribute, $attributesExamples)
            ? $attributesExamples[$attribute] ?: null
            : null;
    }

    public function getAttributeExample(string $attribute): ?string
    {
        $examples = $this->getAttributeExamples($attribute);
        if (!$examples) {
            return null;
        }
        $first = array_shift($examples);
        return is_string($first) ? $first : null;
    }
}
