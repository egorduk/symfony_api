<?php

namespace Btc\TradeApiBundle\Repository;

use Btc\TradeApiBundle\Mapping\ModelMetadata;
use Doctrine\DBAL\Connection;

class RepositoryFactory
{
    private $repositoryMap;
    private $conn;
    private $metadatas;
    private $initialized = [];

    public function __construct(Connection $conn, ModelMetadata $metadatas, array $repositoryMap)
    {
        $this->repositoryMap = $repositoryMap;
        $this->conn = $conn;
        $this->metadatas = $metadatas;
    }

    public function getRepository($name)
    {
        if (!isset($this->initialized[$name])) {
            if (!isset($this->repositoryMap[$name])) {
                throw new \InvalidArgumentException("Unrecognized repository by: {$name}");
            }
            $repoClassName = $this->repositoryMap[$name];
            $this->initialized[$name] = new $repoClassName(
                $this->conn,
                $name,
                $this->metadatas->load($name),
                $this
            );
        }
        return $this->initialized[$name];
    }
}
