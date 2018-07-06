<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\FrontendApiBundle\Service\CurrencyService;
use Btc\FrontendApiBundle\Service\RestService;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;

class CurrencyServiceSpec extends ObjectBehavior
{
    public function let(EntityManager $em, RestEntityInterface $entityClass)
    {
        $this->beConstructedWith($em, $entityClass);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CurrencyService::class);
        $this->shouldHaveType(RestService::class);
    }
}
