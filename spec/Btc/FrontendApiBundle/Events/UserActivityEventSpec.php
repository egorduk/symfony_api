<?php

namespace spec\Btc\FrontendApiBundle\Events;

use Btc\CoreBundle\Entity\User;
use PhpSpec\ObjectBehavior;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class UserActivityEventSpec extends ObjectBehavior
{
    public function let(
        User $user,
        Request $request
    ) {
        $this->beConstructedWith($user, $request, []);
    }

    public function it_is_an_event()
    {
        $this->shouldHaveType(Event::class);
    }

    public function it_returns_client_ip_from_request(Request $request)
    {
        $clientIp = '127.0.0.1';
        $request->getClientIp()->willReturn($clientIp);

        $this->getClientIp()->shouldBe($clientIp);
    }

    public function it_returns_user_object_which_was_passed_through_construct(User $user)
    {
        $this->getUser()->shouldBe($user);
    }

    public function it_returns_params_array_which_was_passed_through_construct()
    {
        $this->getParams()->shouldBe([]);
    }
}
