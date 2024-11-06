<?php

namespace VanaBoom\LaravelSwagger\DataObjects;

use Illuminate\Support\Arr;

/**
 * Class Middleware
 */
class Middleware
{
    /**
     * Middleware origal string
     */
    private string $original;

    /**
     * Middleware name
     */
    private string $name;

    /**
     * Middleware parameters
     */
    private array $parameters;

    /**
     * Middleware constructor.
     */
    public function __construct(string $middleware)
    {
        $this->original = $middleware;
        $tokens = explode(':', $middleware, 2);
        $this->name = Arr::first($tokens);
        $this->parameters = \count($tokens) > 1 ? explode(',', Arr::last($tokens)) : [];
    }

    /**
     * Get middleware name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get middleware parameters
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function __toString(): string
    {
        return $this->original;
    }
}
