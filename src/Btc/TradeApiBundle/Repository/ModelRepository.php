<?php

namespace Btc\TradeApiBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

abstract class ModelRepository
{
    protected $conn;
    protected $meta;
    protected $className;

    private $repositories;

    public function __construct(Connection $conn, $className, array $meta, RepositoryFactory $repositories)
    {
        $this->conn = $conn;
        $this->meta = $meta;
        $this->className = $className;
        $this->repositories = $repositories;
    }

    /**
     * Generate a select statement aliased by $alias
     *
     * @param string $alias
     * @return string
     */
    public function select($alias = '')
    {
        $cols = [];
        foreach ($this->meta['fields'] as $fieldName => $mapping) {
            $cols[] = $alias ? "{$alias}.{$mapping['column']} AS {$alias}_{$mapping['column']}"
                : $mapping['column'];
        }
        return implode(', ', $cols);
    }

    /**
     * Get all mapped types for $class
     *
     * @return array - [column => type] map
     */
    public function types()
    {
        $types = [];
        foreach ($this->meta['fields'] as $fieldName => $mapping) {
            $types[$mapping['column']] = Type::getType($mapping['type'])->getBindingType();
        }
        return $types;
    }

    /**
     * Get field => column mapping
     *
     * @return array
     */
    public function fields()
    {
        $fields = [];
        foreach ($this->meta['fields'] as $fieldName => $mapping) {
            $fields[$fieldName] = $mapping['column'];
        }
        return $fields;
    }

    /**
     * Get type for $fieldName
     *
     * @param string $fieldName
     * @return string
     * @throws \InvalidArgumentException
     */
    public function type($fieldName)
    {
        if (!isset($this->meta['fields'][$fieldName])) {
            throw new \InvalidArgumentException("Field: {$fieldName} is not mapped");
        }
        return Type::getType($this->meta['fields'][$fieldName]['type'])->getBindingType();
    }

    /**
     * Creates a model with from data
     *
     * @param array $data
     * @param string $alias
     * @return object - model created from className this repository represents
     */
    public function read(array $data, $alias = '')
    {
        $args = [];
        foreach ($this->meta['fields'] as $fieldName => $mapping) {
            $key = $alias ? $alias . '_' . $mapping['column'] : $mapping['column'];
            if (array_key_exists($key, $data)) {
                $args[$fieldName] = Type::getType($mapping['type'])->convertToPHPValue(
                    $data[$key],
                    $this->conn->getDatabasePlatform()
                );
            }
        }
        return new $this->className($args);
    }


    /**
     * Get repository by classname
     *
     * @param string $className
     * @return \Btc\ApiBundle\Repository\ModelRepository
     */
    protected function getRepository($className)
    {
        return $this->repositories->getRepository($className);
    }
}
