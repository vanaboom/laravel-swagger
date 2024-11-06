<?php

namespace VanaBoom\LaravelSwagger\Formatters;

use VanaBoom\LaravelSwagger\Exceptions\ExtensionNotLoaded;

/**
 * Class AbstractFormatter
 */
abstract class AbstractFormatter
{
    /**
     * Documentation array
     */
    protected array $documentation;

    /**
     * Formatter constructor.
     */
    public function __construct(array $documentation)
    {
        $this->documentation = $documentation;
    }

    /**
     * Format documentation
     *
     * @throws ExtensionNotLoaded
     */
    abstract public function format(): string;
}
