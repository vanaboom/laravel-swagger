<?php

namespace VanaBoom\LaravelSwagger\DataObjects;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class Route
 */
class Route
{
    /**
     * Laravel route instance
     */
    protected LaravelRoute $route;

    /**
     * Laravel middleware information
     */
    protected array $middleware;

    /**
     * Get original URI for route
     */
    public function originalUri(): string
    {
        $uri = $this->route->uri();
        if (! Str::startsWith($uri, '/')) {
            $uri = '/'.$uri;
        }

        return $uri;
    }

    /**
     * Get URI
     */
    public function uri(): string
    {
        return \strip_optional_char($this->originalUri());
    }

    /**
     * Get middleware information
     *
     * @return array|Middleware[]
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get route name
     */
    public function name(): ?string
    {
        return $this->route->getName();
    }

    /**
     * Get route action name
     */
    public function action(): string
    {
        return $this->route->getActionName();
    }

    /**
     * Get route methods
     */
    public function methods(): array
    {
        return array_map('strtolower', $this->route->methods());
    }

    /**
     * Route constructor.
     */
    public function __construct(LaravelRoute $route)
    {
        $this->route = $route;
        $this->middleware = $this->formatMiddleware();
    }

    /**
     * Format middleware information
     */
    protected function formatMiddleware(): array
    {
        $middleware = $this->route->getAction()['middleware'] ?? [];

        return array_map(function (string $middleware): Middleware {
            return new Middleware($middleware);
        }, Arr::wrap($middleware));
    }
}
