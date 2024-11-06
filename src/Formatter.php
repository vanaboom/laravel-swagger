<?php

namespace VanaBoom\LaravelSwagger;

use VanaBoom\LaravelSwagger\Exceptions\ExtensionNotLoaded;
use VanaBoom\LaravelSwagger\Exceptions\InvalidFormatException;
use VanaBoom\LaravelSwagger\Formatters\AbstractFormatter;
use VanaBoom\LaravelSwagger\Formatters\JsonFormatter;
use VanaBoom\LaravelSwagger\Formatters\YamlFormatter;

/**
 * Class Formatter
 */
class Formatter
{
    /**
     * Documentation array
     */
    private array $documentation;

    /**
     * Formatter instance
     */
    private AbstractFormatter $formatter;

    /**
     * Formatter constructor.
     */
    public function __construct(array $documentation)
    {
        $this->documentation = $documentation;
        $this->formatter = new JsonFormatter($this->documentation);
    }

    /**
     * Set desired output format
     *
     * @return Formatter|static
     *
     * @throws InvalidFormatException
     */
    public function setFormat(string $format): self
    {
        $format = strtolower($format);
        $this->formatter = $this->getFormatter($format);

        return $this;
    }

    /**
     * Get formatter instance
     *
     * @throws InvalidFormatException
     */
    protected function getFormatter(string $format): AbstractFormatter
    {
        switch ($format) {
            case 'json':
                return new JsonFormatter($this->documentation);
            case 'yaml':
                return new YamlFormatter($this->documentation);
            default:
                throw new InvalidFormatException('Invalid format specified');
        }
    }

    /**
     * Format documentation
     *
     * @return mixed
     *
     * @throws ExtensionNotLoaded
     */
    public function format()
    {
        return $this->formatter->format();
    }
}
