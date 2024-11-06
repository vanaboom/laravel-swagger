<?php

namespace VanaBoom\LaravelSwagger\Parameters\Interfaces;

/**
 * Interface ParametersGenerator
 */
interface ParametersGenerator
{
    /**
     * Get list of parameters
     */
    public function getParameters(): array;

    /**
     * Get parameter location
     */
    public function getParameterLocation(): string;
}
