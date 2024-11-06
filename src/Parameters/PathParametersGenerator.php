<?php

namespace VanaBoom\LaravelSwagger\Parameters;

use Illuminate\Support\Str;
use VanaBoom\LaravelSwagger\Parameters\Interfaces\ParametersGenerator;

/**
 * Class PathParametersGenerator
 */
class PathParametersGenerator implements ParametersGenerator
{
    /**
     * Path URI
     */
    protected string $uri;

    /**
     * Parameters location
     */
    protected string $location = 'path';

    /**
     * PathParametersGenerator constructor.
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        $parameters = [];
        $pathVariables = $this->getVariablesFromUri();

        foreach ($pathVariables as $variable) {
            $parameters[] = [
                'name' => strip_optional_char($variable),
                'in' => $this->getParameterLocation(),
                'required' => $this->isPathVariableRequired($variable),
                'description' => '',
                'schema' => [
                    'type' => 'string',
                ],
            ];
        }

        return $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameterLocation(): string
    {
        return $this->location;
    }

    /**
     * Get path variables from URI
     */
    private function getVariablesFromUri(): array
    {
        preg_match_all('/{(\w+\??)}/', $this->uri, $pathVariables);

        return $pathVariables[1];
    }

    /**
     * Get variable type from string
     */
    private function getTypeFromString(string $string): string
    {
        return gettype($this->guessVariableType($string));
    }

    /**
     * Guess variable type
     *
     * @return bool|float|int|string
     */
    private function guessVariableType(string $string)
    {
        $string = trim($string);
        if (empty($string)) {
            return '';
        }
        if (! preg_match('/[^0-9.]+/', $string)) {
            if (preg_match('/[.]+/', $string)) {
                return (float) $string;
            }

            return (int) $string;
        }
        if ($string === 'true') {
            return (bool) true;
        }
        if ($string === 'false') {
            return (bool) false;
        }

        return (string) $string;
    }

    /**
     * Check whether this is a required variable
     */
    private function isPathVariableRequired(string $pathVariable): bool
    {
        return ! Str::contains($pathVariable, '?');
    }
}
