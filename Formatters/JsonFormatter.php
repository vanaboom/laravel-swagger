<?php

namespace VanaBoom\LaravelSwagger\Formatters;

use VanaBoom\LaravelSwagger\Exceptions\ExtensionNotLoaded;

/**
 * Class JsonFormatter
 */
class JsonFormatter extends AbstractFormatter
{
    /**
     * {@inheritDoc}
     *
     * @throws ExtensionNotLoaded
     */
    public function format(): string
    {
        if (! extension_loaded('json')) {
            throw new ExtensionNotLoaded('JSON extends must be loaded to use the `json` output format');
        }

        return json_encode($this->documentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
