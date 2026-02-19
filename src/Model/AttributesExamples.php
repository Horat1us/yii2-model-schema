<?php

declare(strict_types=1);

namespace Horat1us\Yii\Model;

/**
 * Interface to extend \yii\base\Model with attribute example.
 * It will be used when Json Schema will be generated.
 * @since 1.1.0
 * @author Alexander Letnikow <reclamme@gmail.com>
 * @
 */
interface AttributesExamples
{
    /**
     * Returns attributes examples.
     * Each entry should validate against validation rules.
     *
     * ```php
     * return [
     *     'attributeName' => ['validValue', 'anotherValidValue']
     * ];
     * ```
     *
     * @see https://json-schema.org/understanding-json-schema/reference/generic.html?highlight=examples
     * @return array<string, array<int, mixed>|null>
     */
    public function attributesExamples(): array;

    /**
     * Returns the examples for the specified attribute.
     * @return array<int, mixed>|null
     */
    public function getAttributeExamples(string $attribute): ?array;

    /**
     * Returns first available example for the specified attribute.
     */
    public function getAttributeExample(string $attribute): ?string;
}
