<?php

namespace VanaBoom\LaravelSwagger\Responses\SchemaBuilders;

use Illuminate\Support\Str;
use VanaBoom\LaravelSwagger\Responses\SchemaBuilder;

/**
 * Class
 */
class LaravelPaginateSchemaBuilder implements SchemaBuilder
{
    /**
     * Build a schema for Laravel pagination
     *
     * @param  string  $modelRef  the swagger reference for model
     * @param  string  $uri  the current parsing uri
     */
    public function build(string $modelRef, string $uri): array
    {
        if (! Str::startsWith($uri, '/')) {
            $uri = '/'.$uri;
        }
        $url = env('APP_URL').$uri;

        return [
            'type' => 'object',
            'required' => [
                'current_page',
                'data',
                'first_page_url',
                'last_page',
                'last_page_url',
                'path',
                'per_page',
                'total',
            ],
            'properties' => [
                'current_page' => [
                    'type' => 'integer',
                    'example' => 2,
                ],
                'data' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        '$ref' => $modelRef,
                    ],
                ],
                'first_page_url' => [
                    'type' => 'string',
                    'example' => "$url?page=1",
                ],
                'from' => [
                    'type' => 'integer',
                    'example' => 16,
                ],
                'last_page' => [
                    'type' => 'integer',
                    'example' => 10,
                ],
                'last_page_url' => [
                    'type' => 'string',
                    'example' => "$url?page=10",
                ],
                'next_page_url' => [
                    'type' => 'string',
                    'example' => "$url?page=3",
                ],
                'path' => [
                    'type' => 'string',
                    'example' => "$url",
                ],
                'per_page' => [
                    'type' => 'integer',
                    'example' => 15,
                ],
                'prev_page_url' => [
                    'type' => 'string',
                    'example' => "$url?page=1",
                ],
                'to' => [
                    'type' => 'integer',
                    'example' => 30,
                ],
                'total' => [
                    'type' => 'integer',
                    'example' => 150,
                ],
            ],
        ];
    }
}
