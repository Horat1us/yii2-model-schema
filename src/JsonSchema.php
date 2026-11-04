<?php

declare(strict_types=1);

namespace Horat1us\Yii;

use yii\helpers\Inflector;
use yii\validators;
use yii\base;

class JsonSchema implements \JsonSerializable
{
    protected base\Model $model;

    public function __construct(base\Model $model)
    {
        $this->model = $model;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-07/schema#",
            'title' => Inflector::camel2words($this->model->formName()),
            'type' => 'object',
        ];
        $properties = [];
        $required = [];
        $examples = [];
        if ($this->model instanceof Model\AttributesExamples) {
            $examples = $this->model->attributesExamples();
        }
        $attributes = $this->model->activeAttributes();
        $validators = $this->model->getActiveValidators();
        $hints = $this->model->attributeHints();
        foreach ($attributes as $attribute) {
            $property = [
                'title' => $this->model->getAttributeLabel($attribute),
            ];
            if (array_key_exists($attribute, $examples)) {
                $property['examples'] = $examples[$attribute];
            }
            if (array_key_exists($attribute, $hints)) {
                $property['description'] = $hints[$attribute];
            }
            foreach ($validators as $validator) {
                if (!in_array($attribute, (array)$validator->attributes)) {
                    continue;
                }
                if ($validator instanceof validators\RequiredValidator) {
                    if ($validator->when === null || call_user_func($validator->when, $this->model, $attribute)) {
                        $required[] = $attribute;
                    }
                    continue;
                }
                $property += $this->validator($validator, $attribute);
            }
            $properties[$attribute] = $property;
        }
        if (count($properties)) {
            $schema['properties'] = $properties;
        }
        if (count($required)) {
            $schema['required'] = $required;
        }
        return $schema;
    }

    /** @return array<string, mixed> */
    public function attribute(string $name): array
    {
        $schema = [
            '$schema' => "http://json-schema.org/draft-07/schema#",
            'title' => $this->model->getAttributeLabel($name),
        ];
        if ($hint = $this->model->getAttributeHint($name)) {
            $schema['description'] = $hint;
        }
        if (
            $this->model instanceof Model\AttributesExamples
            && ($examples = $this->model->getAttributeExamples($name))
        ) {
            $schema['examples'] = $examples;
        }
        /** @var array<string, mixed> $result */
        $result = array_reduce(
            $this->model->getActiveValidators($name),
            fn(array $schema, validators\Validator $v) => $schema + $this->validator($v, $name),
            $schema,
        );
        return $result;
    }

    /** @return array<string, mixed> */
    public function validator(validators\Validator $validator, ?string $name = null): array
    {
        if (
            $validator instanceof validators\RequiredValidator
            || $validator instanceof validators\FilterValidator
            || $validator instanceof validators\InlineValidator
        ) {
            return [];
        }
        if ($validator instanceof validators\StringValidator) {
            $length = (array)$validator->length;
            $max = $length[1] ?? $validator->max;
            return array_filter([
                'type' => 'string',
                'minLength' => $length[0] ?? $validator->min,
                'maxLength' => $max,
                'format' => $max !== null && $max > 255 ? 'textarea' : null,
            ]);
        } elseif ($validator instanceof validators\BooleanValidator) {
            return ['type' => 'boolean'];
        } elseif ($validator instanceof validators\UrlValidator) {
            return ['type' => 'string', 'format' => 'uri'];
        } elseif ($validator instanceof validators\RangeValidator) {
            $schema = $this->range($validator, $name);
            if ($validator->allowArray) {
                return [
                    'type' => 'array',
                    'uniqueItems' => true,
                    'items' => $schema,
                ];
            }
            return $schema;
        } elseif ($validator instanceof validators\EachValidator) {
            return $this->each($validator, $name);
        } elseif ($validator instanceof validators\NumberValidator) {
            $schema = [
                'type' => $validator->integerOnly ? 'integer' : 'number'
            ];
            if (!is_null($validator->min)) {
                $schema['minimum'] = $validator->min;
            }
            if (!is_null($validator->max)) {
                $schema['maximum'] = $validator->max;
            }
            return $schema;
        } elseif ($validator instanceof validators\RegularExpressionValidator) {
            return [
                'type' => 'string',
                'pattern' => static::pattern($validator->pattern),
            ];
        } elseif ($validator instanceof validators\DateValidator) {
            $schema = [
                'type' => 'string',
                'format' => $validator->type,
            ];
            if ($validator->type === validators\DateValidator::TYPE_DATETIME) {
                $schema['format'] = 'date-time';
            }
            return $schema;
        } elseif ($validator instanceof validators\EmailValidator) {
            return [
                'type' => 'string',
                'format' => 'email',
            ];
        } elseif ($validator instanceof Validation\JsonSchema) {
            return $validator->getJsonSchema();
        } elseif ($validator instanceof validators\DefaultValueValidator) {
            return [
                'default' => $validator->value,
            ];
        }

        return [];
    }

    /**
     * A value of the range: `oneOf` with labels when the model labels every value, `enum` otherwise.
     *
     * @return array<string, mixed>
     */
    private function range(validators\RangeValidator $validator, ?string $name): array
    {
        $values = $validator->range;
        if ($values instanceof \Closure) {
            if (is_null($name)) {
                $reflection = new \ReflectionFunction($values);
                if ($reflection->getNumberOfRequiredParameters() !== 0) {
                    return ['enum' => [],];
                }
                $values = (array) call_user_func($values);
            } else {
                $values = (array) call_user_func($values, $this->model, $name);
            }
        }
        $values = (array) $values;
        if (
            is_null($name)
            || !($this->model instanceof Model\AttributeValuesLabels)
            || !($labels = $this->model->attributeValuesLabels($name))
            || !array_reduce(
                $values,
                fn($result, $value) => $result
                    && (is_string($value) || is_int($value))
                    && array_key_exists($value, $labels),
                true
            )
        ) {
            return [
                'enum' => $values,
            ];
        }
        $oneOf = array_map(
            function ($value) use ($labels): array {
                /** @var string|int $value */
                return ['const' => $value, 'title' => $labels[$value]];
            },
            $values
        );
        return compact('oneOf');
    }

    /**
     * An array whose `items` are described by the rule each element is validated with.
     * `items` is left out when that rule maps to nothing.
     *
     * @return array<string, mixed>
     */
    private function each(validators\EachValidator $validator, ?string $name): array
    {
        $schema = ['type' => 'array'];
        $rule = $validator->rule;
        if (!is_array($rule) || !isset($rule[0])) {
            return $schema;
        }
        /** @var string|\Closure $type */
        $type = $rule[0];
        $item = $this->validator(
            validators\Validator::createValidator(
                $type,
                $this->model,
                (array) $validator->attributes,
                array_slice($rule, 1),
            ),
            $name,
        );
        if (count($item)) {
            $schema['items'] = $item;
        }
        return $schema;
    }

    public static function pattern(string $phpRegExp): string
    {
        return preg_replace("(^/|/\w*$)", "", $phpRegExp) ?? $phpRegExp;
    }
}
