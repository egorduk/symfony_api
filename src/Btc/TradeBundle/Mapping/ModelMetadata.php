<?php

namespace Btc\TradeApiBundle\Mapping;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Yaml\Parser;

class ModelMetadata
{
    private $ns;
    private $path;

    private $cache;
    private $metadatas = [];
    private $yamlParser;

    public function __construct($metadataNs, $metadataDir, Cache $cache)
    {
        $this->ns = $metadataNs;
        $this->path = $metadataDir;

        $this->cache = $cache;
        $this->yamlParser = new Parser;
    }

    public function load($className)
    {
        if (array_key_exists($className, $this->metadatas)) {
            return $this->metadatas[$className];
        }

        $id = 'BTCX_API_' . $className;
        if ($data = $this->cache->fetch($id)) {
            return $this->metadatas[$className] = $data;
        }

        $this->metadatas[$className] = $this->read($className);
        $this->cache->save($id, $this->metadatas[$className]);
        return $this->metadatas[$className];
    }

    private function read($className)
    {
        $name = str_replace('\\', '/', str_replace($this->ns, '', $className));
        $path = rtrim($this->path, '/') . '/' . ltrim($name, '/') . '.yml';
        $data = $this->yamlParser->parse(file_get_contents($path));
        return $data[$className];
    }
}
