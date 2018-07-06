<?php

namespace Btc\ApiBundle\Model;

class FeeSet
{
    private $name;

    private $percent;

    public function __construct(array $data = [])
    {
        foreach ($data as $fieldName => $value) {
            $this->{$fieldName} = $value;
        }
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPercent($percent)
    {
        $this->percent = $percent;
        return $this;
    }

    public function getPercent()
    {
        return $this->percent;
    }
}
