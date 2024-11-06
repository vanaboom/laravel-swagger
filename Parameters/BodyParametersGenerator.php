<?php

namespace VanaBoom\LaravelSwagger\Parameters;

use Exception;
use Illuminate\Support\Arr;
use VanaBoom\LaravelSwagger\Parameters\Interfaces\ParametersGenerator;
use VanaBoom\LaravelSwagger\Parameters\Traits\GeneratesFromRules;
use TypeError;
use function count;

/**
 * Class BodyParametersGenerator
 */
class BodyParametersGenerator implements ParametersGenerator
{
    use GeneratesFromRules;

    /**
     * Rules array
     */
    protected array $rules;

    /**
     * Parameters location
     */
    protected string $location = 'body';

    /**
     * BodyParametersGenerator constructor.
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Get parameters
     *
     * @return array[]
     */
    public function getParameters(): array
    {
        $required = [];
        $properties = [];

        $schema = [];

        foreach ($this->rules as $parameter => $rule) {
            try {
                $parameterRules = $this->splitRules($rule);
                $nameTokens = explode('.', $parameter);
                $this->addToProperties($properties, $nameTokens, $parameterRules);

                if ($this->isParameterRequired($parameterRules)) {
                    $required[] = $parameter;
                }
            } catch (TypeError $e) {
                $ruleStr = json_encode($rule);
                dd($e->getFile(),$e->getLine().'-'.$e->getMessage());
                throw new Exception("Rule `$parameter => $ruleStr` is not well formated", 0, $e);
            }
        }

        if (count($required) > 0) {
            Arr::set($schema, 'required', $required);
        }

        Arr::set($schema, 'properties', $properties);

        $mediaType = 'application/json'; // or  "application/x-www-form-urlencoded"
        foreach ($properties as $prop) {
            if (isset($prop['format']) && $prop['format'] == 'binary') {
                $mediaType = 'multipart/form-data';
            }
        }

        return [
            'content' => [
                $mediaType => [
                    'schema' => $schema,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getParameterLocation(): string
    {
        return $this->location;
    }

    /**
     * Add data to properties array
     */
    protected function addToProperties(array &$properties, array $nameTokens, array $rules): void
    {
        if (count($nameTokens) === 0) {
            return;
        }

        $name = array_shift($nameTokens);

        if (! empty($nameTokens)) {
            $type = $this->getNestedParameterType($nameTokens);
        } else {
            $type = $this->getParameterType($rules);
        }

        if ($name === '*') {
            $name = 0;
        }

        if (! Arr::has($properties, $name)) {
            $propertyObject = $this->createNewPropertyObject($type, $rules);
            Arr::set($properties, $name, $propertyObject);
            $extra = $this->getParameterExtra($type, $rules);
            foreach ($extra as $key => $value) {
                Arr::set($properties, $name.'.'.$key, $value);
            }
        } else {
            Arr::set($properties, $name.'.type', $type);
        }

        if ($type === 'array') {
            $this->addToProperties($properties[$name]['items'], $nameTokens, $rules);
        } elseif ($type === 'object' && isset($properties[$name]['properties'])) {
            $this->addToProperties($properties[$name]['properties'], $nameTokens, $rules);
        }
    }

    /**
     * Get nested parameter type
     */
    protected function getNestedParameterType(array $nameTokens): string
    {
        if (current($nameTokens) === '*') {
            return 'array';
        }

        return 'object';
    }

    /**
     * Create new property object
     *
     * @return string[]
     */
    protected function createNewPropertyObject(string $type, array $rules): array
    {
        $propertyObject = [
            'type' => $type,
        ];

        if ($enums = $this->getEnumValues($rules)) {
            Arr::set($propertyObject, 'enum', $enums);
        }

        if ($type === 'array') {
            Arr::set($propertyObject, 'items', []);
        } elseif ($type === 'object') {
            Arr::set($propertyObject, 'properties', []);
        }

        return $propertyObject;
    }
}
