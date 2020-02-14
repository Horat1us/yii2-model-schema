<?php declare(strict_types=1);

namespace Horat1us\Yii\Model;

use yii\validators;
use yii\base;

class JsonSchema
{
    public base\Model $model;

    public function __construct(base\Model $model)
    {
        $this->model = $model;
    }

    public function attribute(string $name): array
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-07/schema#",
            'title' => $this->model->getAttributeLabel($name),
        ];

        if ($hint = $this->model->getAttributeHint($name)) {
            $schema['description'] = $hint;
        }

        $validators = array_filter(
            $this->model->getActiveValidators(),
            fn(validators\Validator $validator): bool => in_array($name, $validator->attributes)
        );
        foreach ($validators as $validator) {
            if ($validator instanceof validators\RequiredValidator
                || $validator instanceof validators\FilterValidator
            ) {
                continue;
            }
            if ($validator instanceof validators\StringValidator) {
                $length = (array)$validator->length;
                $schema += [
                    'type' => 'string',
                    'minLength' => $length[0] ?? $validator->min,
                    'maxLength' => $length[1] ?? $validator->max,
                ];
            } elseif ($validator instanceof validators\RangeValidator) {
                $values = $validator->range instanceof \Closure
                    ? call_user_func($validator->range, $this->model, $name)
                    : $validator->range;
                if (!($this->model instanceof AttributeValuesLabels)
                    || !($labels = $this->model->attributeValuesLabels($name))
                    || !array_reduce(
                        $values,
                        fn($result, $value) => $result
                            && (is_string($value) || is_int($value))
                            && array_key_exists($value, $labels), true
                    )
                ) {
                    $schema['enum'] ??= $values;
                } else {
                    $schema['oneOf'] ??= array_map(
                        fn($value) => ['const' => $value, 'title' => $labels[$value]],
                        $values
                    );
                }
            } elseif ($validator instanceof validators\NumberValidator) {
                $schema['type'] ??= $validator->integerOnly ? 'integer' : 'number';
                if (!is_null($validator->min)) {
                    $schema += ['minimum' => $validator->min];
                }
                if (!is_null($validator->max)) {
                    $schema += ['maximum' => $validator->max];
                }
            } elseif ($validator instanceof validators\RegularExpressionValidator) {
                $schema += [
                    'type' => 'string',
                    'pattern' => trim($validator->pattern, "\\/"),
                ];
                if (property_exists('format', $validator)) {
                    $schema['format'] ??= $validator->format;
                }
            } elseif ($validator instanceof validators\DateValidator) {
                $schema['type'] ??= 'string';
                switch ($validator->type) {
                    case validators\DateValidator::TYPE_DATE:
                        $schema['format'] ??= 'date';
                        break;
                    case validators\DateValidator::TYPE_TIME;
                        $schema['format'] ??= 'time';
                        break;
                    case validators\DateValidator::TYPE_DATETIME;
                        $schema['format'] ??= 'date-time';
                        break;
                }
            }
            if (property_exists($validator, 'comment')) {
                $schema['comment'] = array_key_exists('comment', $schema)
                    ? "{$schema['comment']};{$validator->comment}"
                    : $validator->comment;
            }
        }
        return $schema;
    }
}
