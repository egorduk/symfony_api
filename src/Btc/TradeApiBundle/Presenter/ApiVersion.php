<?php

namespace Btc\ApiBundle\Presenter;

class ApiVersion implements PresenterInterface
{
    /**
     * @var string
     */
    private $version;

    /**
     * Initialize presenter with an api version
     * to present
     *
     * @param string $version
     */
    public function __construct($version)
    {
        $this->version = $version;
    }

    /**
     * Present api version as json array
     *
     * @return array
     */
    public function presentAsJson()
    {
        return ['version' => $this->version];
    }
}
