<?php declare(strict_types=1);

namespace Horat1us\Yii\Validation;

/**
 * @property-read array $jsonSchema
 */
trait JsonSchemaTrait
{
    public function getJsonSchema(): array
    {
        if (property_exists($this, 'jsonSchema')) {
            return $this->jsonSchema;
        }
        return [];
    }
}
