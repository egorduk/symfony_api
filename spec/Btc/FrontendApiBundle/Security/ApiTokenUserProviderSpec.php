<?php

namespace spec\Btc\FrontendApiBundle\Security;

use Btc\FrontendApiBundle\Security\ApiTokenUserProvider;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiTokenUserProviderSpec extends ObjectBehavior
{
    public function let(EntityManager $em)
    {
        $this->beConstructedWith($em);
    }

    public function it_should_extend_from_abstract_token()
    {
        $this->shouldHaveType(ApiTokenUserProvider::class);
        $this->shouldImplement(UserProviderInterface::class);
    }

    public function it_should_supports_class(ApiTokenUserProvider $apiTokenUserProvider)
    {
        $this->supportsClass($apiTokenUserProvider)->shouldReturn(false);
    }
}
