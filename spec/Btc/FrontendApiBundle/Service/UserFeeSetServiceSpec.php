<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\FrontendApiBundle\Service\RestService;
use Btc\FrontendApiBundle\Service\UserFeeSetService;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;

class UserFeeSetServiceSpec extends ObjectBehavior
{
    public function let(EntityManager $em, RestEntityInterface $entityClass)
    {
        $this->beConstructedWith($em, $entityClass);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserFeeSetService::class);
        $this->shouldHaveType(RestService::class);
    }
}
