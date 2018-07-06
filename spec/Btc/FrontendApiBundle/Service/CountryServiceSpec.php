<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\FrontendApiBundle\Service\CountryService;
use Btc\FrontendApiBundle\Service\RestService;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;

class CountryServiceSpec extends ObjectBehavior
{
    public function let(EntityManager $em, RestEntityInterface $entityClass)
    {
        $this->beConstructedWith($em, $entityClass);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CountryService::class);
        $this->shouldHaveType(RestService::class);
    }
}
