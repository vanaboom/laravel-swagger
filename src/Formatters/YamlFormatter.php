<?php

namespace VanaBoom\LaravelSwagger\Formatters;

use VanaBoom\LaravelSwagger\Exceptions\ExtensionNotLoaded;

/**
 * Class YamlFormatter
 */
class YamlFormatter extends AbstractFormatter
{
    /**
     * {@inheritDoc}
     *
     * @throws ExtensionNotLoaded
     */
    public function format(): string
    {
        if (! extension_loaded('yaml')) {
            throw new ExtensionNotLoaded('YAML extends must be loaded to use the `yaml` output format');
        }

        return yaml_emit($this->documentation);
    }
}
