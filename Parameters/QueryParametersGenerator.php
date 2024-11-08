<?php

namespace VanaBoom\LaravelSwagger\Parameters;

use Illuminate\Support\Arr;
use VanaBoom\LaravelSwagger\Parameters\Interfaces\ParametersGenerator;
use VanaBoom\LaravelSwagger\Parameters\Traits\GeneratesFromRules;

/**
 * Class QueryParametersGenerator
 */
class QueryParametersGenerator implements ParametersGenerator
{
    use GeneratesFromRules;

    /**
     * Rules array
     */
    protected array $rules;

    /**
     * Parameters location
     */
    protected string $location = 'query';

    /**
     * QueryParametersGenerator constructor.
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        $parameters = [];
        $arrayTypes = [];

        foreach ($this->rules as $parameter => $rule) {
            $parameterRules = $this->splitRules($rule);
            $enums = $this->getEnumValues($parameterRules);
            $type = $this->getParameterType($parameterRules);
            $default = $this->getDefaultValue($parameterRules);
            $min = $this->getMinValue($parameterRules);
            $max = $this->getMaxValue($parameterRules);

            if ($this->isArrayParameter($parameter)) {
                $key = $this->getArrayKey($parameter);
                $arrayTypes[$key] = $type;

                continue;
            }

            $parameterObject = [
                'in' => $this->getParameterLocation(),
                'name' => $parameter,
                'description' => '',
                'required' => $this->isParameterRequired($parameterRules),
            ];

            if (\count($enums) > 0) {
                Arr::set($parameterObject, 'enum', $enums);
            } else {
                Arr::set($parameterObject, 'schema.type', $type);
            }

            if ($default) {
                settype($default, $type);
                Arr::set($parameterObject, 'schema.default', $default);
            }
            if ($min) {
                settype($min, $type);
                Arr::set($parameterObject, 'schema.minimum', $min);
            }
            if ($max) {
                settype($max, $type);
                Arr::set($parameterObject, 'schema.maximum', $max);
            }

            if ($type === 'array') {
                Arr::set($parameterObject, 'items', [
                    'type' => 'string',
                ]);
            }
            Arr::set($parameters, $parameter, $parameterObject);
        }

        $parameters = $this->addArrayTypes($parameters, $arrayTypes);

        return array_values($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function getParameterLocation(): string
    {
        return $this->location;
    }

    /**
     * Add array types
     */
    protected function addArrayTypes(array $parameters, array $arrayTypes): array
    {
        foreach ($arrayTypes as $key => $type) {
            if (! isset($parameters[$key])) {
                $parameters[$key] = [
                    'name' => $key,
                    'in' => $this->getParameterLocation(),
                    'type' => 'array',
                    'required' => false,
                    'description' => '',
                    'items' => [
                        'type' => $type,
                    ],
                ];
            } else {
                $parameters[$key]['type'] = 'array';
                $parameters[$key]['items']['type'] = $type;
            }
        }

        return $parameters;
    }
}
