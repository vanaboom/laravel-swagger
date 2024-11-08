<?php

namespace VanaBoom\LaravelSwagger\Definitions;

use Doctrine\DBAL\Types\Type;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use ReflectionClass;

/**
 * Class DefinitionGenerator
 */
class DefinitionGenerator
{
    /**
     * array of models
     */
    protected array $models = [];

    /**
     * DefinitionGenerator constructor.
     */
    public function __construct(array $ignoredModels = [])
    {
        $files = collect(array_merge(
            glob(base_path('app/Models/*.php')),
            glob(base_path('Modules/*/app/Models/*.php')),
            glob(base_path('Modules/*/app/Entities/*.php'))
        ));

        $this->models = $files->map(function ($file) {
            $path = $file;
            $relativePath = preg_replace('/^'.preg_quote(base_path().DIRECTORY_SEPARATOR, '/').'/', '', $path);
            if (strpos($relativePath, 'Modules'.DIRECTORY_SEPARATOR) === 0) {
                if (str_contains($relativePath, 'Entities')) {
                    $relativePath = str_replace('/app/Models', '/Entities', $relativePath);
                } else {
                    $relativePath = str_replace('/app/Models', '/Models', $relativePath);
                }
                $class = strtr(substr($relativePath, 0, strrpos($relativePath, '.')), '/', '\\');
            } else {
                $relativePath = str_replace('app/Models', 'Models', $relativePath);
                //                dd($relativePath);
                $containerInstance = Container::getInstance();
                $class = sprintf(
                    '%s%s',
                    $containerInstance->getNamespace(),
                    strtr(substr($relativePath, 0, strrpos($relativePath, '.')), '/', '\\')
                );
            }

            return $class;
        })
            ->filter(function ($class) {
                if (class_exists($class)) {
                    if (! $this->checkModuleAvailable($class)) {
                        return false;
                    }
                    $reflection = new ReflectionClass($class);

                    return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract();
                }

                return false;
            })
            ->diff($ignoredModels)
            ->values()
            ->toArray();
    }

    /**
     * Generate definitions informations
     */
    public function generateSchemas(): array
    {
        $schemas = [];
        foreach ($this->models as $model) {
            /** @var Model $model */
            $obj = new $model;
            if ($obj instanceof Model) { //check to make sure it is a model
                $reflection = new ReflectionClass($obj);

                // $with = $reflection->getProperty('with');
                // $with->setAccessible(true);

                $appends = $reflection->getProperty('appends');
                $appends->setAccessible(true);

                $relations = collect($reflection->getMethods())
                    ->filter(
                        fn ($method) => ! empty($method->getReturnType()) &&
                            str_contains(
                                $method->getReturnType(),
                                \Illuminate\Database\Eloquent\Relations\Relation::class
                            )
                    )
                    ->pluck('name')
                    ->all();

                $table = $obj->getTable();
                $columns = Schema::getColumns($table);

                $properties = [];
                $required = [];

                /**
                 * @var \Illuminate\Database\Connection
                 */
                $conn = $obj->getConnection();
                $prefix = $conn->getTablePrefix();

                if ($prefix !== '') {
                    $table = $prefix.$table;
                }

                foreach ($columns as $column) {
                    $description = $column['comment'];
                    if (! is_null($description)) {
                        if (empty($column['description'])) {
                            $column['description'] = '';
                        }
                        $column['description'] .= ": $description";
                    }

                    $this->addExampleKey($column);

                    $properties[$column['name']] = $column;

                    if (! $column['nullable']) {
                        $required[] = $column['name'];
                    }
                }

                foreach ($relations as $relationName) {
                    $relatedClass = get_class($obj->{$relationName}()->getRelated());
                    $refObject = [
                        'type' => 'object',
                        '$ref' => '#/components/schemas/'.last(explode('\\', $relatedClass)),
                    ];

                    $resultsClass = get_class((object) ($obj->{$relationName}()->getResults()));

                    if (str_contains($resultsClass, \Illuminate\Database\Eloquent\Collection::class)) {
                        $properties[$relationName] = [
                            'type' => 'array',
                            'items' => $refObject,
                        ];
                    } else {
                        $properties[$relationName] = $refObject;
                    }
                }

                // $required = array_merge($required, $with->getValue($obj));

                foreach ($appends->getValue($obj) as $item) {
                    $methodeName = 'get'.ucfirst(Str::camel($item)).'Attribute';
                    if (! $reflection->hasMethod($methodeName)) {
                        Log::warning("[VanaBoom\LaravelSwagger] Method $model::$methodeName not found while parsing '$item' attribute");

                        continue;
                    }
                    $reflectionMethod = $reflection->getMethod($methodeName);
                    $returnType = $reflectionMethod->getReturnType();

                    $data = [];

                    // A schema without a type matches any data type – numbers, strings, objects, and so on.
                    if ($reflectionMethod->hasReturnType()) {
                        $type = $returnType->getName();

                        if (Str::contains($type, '\\')) {
                            $data = [
                                'type' => 'object',
                                '$ref' => '#/components/schemas/'.last(explode('\\', $type)),
                            ];
                        } else {
                            $data['type'] = $type;
                            $this->addExampleKey($data);
                        }
                    }

                    $properties[$item] = $data;

                    if ($returnType && $returnType->allowsNull() == false) {
                        $required[] = $item;
                    }
                }

                $definition = [
                    'type' => 'object',
                    'properties' => (object) $properties,
                ];

                if (! empty($required)) {
                    $definition['required'] = $required;
                }

                $schemas[$this->getModelName($obj)] = $definition;
            }
        }

        return $schemas;
    }

    /**
     * Get array of models
     *
     * @return array array of models
     */
    public function getModels(): array
    {
        return $this->models;
    }

    private function getModelName($model): string
    {
        return last(explode('\\', get_class($model)));
    }

    private function addExampleKey(array &$property): void
    {
        if (Arr::has($property, 'type')) {
            switch ($property['type']) {
                case 'bigserial':
                case 'bigint':
                    Arr::set($property, 'example', rand(1000000000000000000, 9200000000000000000));
                    break;
                case 'serial':
                case 'integer':
                    Arr::set($property, 'example', rand(1000000000, 2000000000));
                    break;
                case 'mediumint':
                    Arr::set($property, 'example', rand(1000000, 8000000));
                    break;
                case 'smallint':
                    Arr::set($property, 'example', rand(10000, 32767));
                    break;
                case 'tinyint':
                    Arr::set($property, 'example', rand(100, 127));
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                case 'real':
                    Arr::set($property, 'example', 0.5);
                    break;
                case 'date':
                    Arr::set($property, 'example', date('Y-m-d'));
                    break;
                case 'time':
                    Arr::set($property, 'example', date('H:i:s'));
                    break;
                case 'datetime':
                    Arr::set($property, 'example', date('Y-m-d H:i:s'));
                    break;
                case 'timestamp':
                    Arr::set($property, 'example', date('Y-m-d H:i:s'));
                    break;
                case 'string':
                    Arr::set($property, 'example', 'string');
                    break;
                case 'text':
                    Arr::set($property, 'example', 'a long text');
                    break;
                case 'boolean':
                    Arr::set($property, 'example', rand(0, 1) == 0);
                    break;

                default:
                    // code...
                    break;
            }
        }
    }

    /**
     * @return array array of with 'type' and 'format' as keys
     */
    private function convertDBalTypeToSwaggerType(string $type): array
    {
        $lowerType = strtolower($type);
        switch ($lowerType) {
            case 'bigserial':
            case 'bigint':
                $property = [
                    'type' => 'integer',
                    'format' => 'int64',
                ];
                break;
            case 'serial':
            case 'integer':
            case 'mediumint':
            case 'smallint':
            case 'tinyint':
            case 'tinyint':
            case 'year':
                $property = ['type' => 'integer'];
                break;
            case 'float':
                $property = [
                    'type' => 'number',
                    'format' => 'float',
                ];
                break;
            case 'decimal':
            case 'double':
            case 'real':
                $property = [
                    'type' => 'number',
                    'format' => 'double',
                ];
                break;
            case 'boolean':
                $property = ['type' => 'boolean'];
                break;
            case 'date':
                $property = [
                    'type' => 'string',
                    'format' => 'date',
                ];
                break;
            case 'datetime':
            case 'timestamp':
                $property = [
                    'type' => 'string',
                    'format' => 'date-time',
                ];
                break;
            case 'binary':
            case 'varbinary':
            case 'blob':
                $property = [
                    'type' => 'string',
                    'format' => 'binary',
                ];
                break;
            case 'time':
            case 'string':
            case 'text':
            case 'char':
            case 'varchar':
            case 'enum':
            case 'set':
            default:
                $property = ['type' => 'string'];
                break;
        }

        $property['description'] = $type;

        return $property;
    }

    private function checkModuleAvailable($name): bool
    {
        if (str_starts_with($name, 'Modules')) {
            $uriSegments = explode('\\', $name);
            $module = Arr::get($uriSegments, 1);
            if (! empty($module) && class_exists('Nwidart\\Modules\\Facade\\Module') && \Nwidart\Modules\Facades\Module::find(ucfirst($module))) {
                $swaggerAvailable = Config::get(strtolower($module).'.swagger');
                if (! empty($swaggerAvailable)) {
                    return true;
                }

                return false;
            }
        }

        return true;
    }
}
