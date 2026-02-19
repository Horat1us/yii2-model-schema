<?php

declare(strict_types=1);

namespace Horat1us\Yii\Validation;

/**
 * @property-read array<string, mixed> $jsonSchema
 */
trait JsonSchemaTrait
{
    public function getJsonSchema(): array
    {
        if (property_exists($this, 'jsonSchema')) {
            /** @var array<string, mixed> */
            return $this->jsonSchema;
        }
        return [];
    }
}
