<?php

namespace spec\Btc\UserBundle\EventListener;

use Btc\CoreBundle\Entity\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class ForcePasswordChangeSpec extends ObjectBehavior
{
    private $forceChangePasswordPathInfo = '/account/profile/force-change-password';

    private $forceChangePasswordRoute = 'btc_user_force_change_password';

    private $changePasswordRole = 'ROLE_FORCE_CHANGE_PASSWORD';

    public function let(
        Router $router,
        SecurityContextInterface $securityContext,
        Session $session,
        GetResponseEvent $event,
        Request $request
    ) {
        $event->getRequest()->willReturn($request);
        $request->get('_route')->willReturn('any_route');
        $request->getPathInfo()->willReturn($this->forceChangePasswordPathInfo);

        $router->generate($this->forceChangePasswordRoute)->willReturn($this->forceChangePasswordPathInfo);

        $this->beConstructedWith($router, $securityContext, $session);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Btc\UserBundle\EventListener\ForcePasswordChange');
    }

    public function it_does_nothing_if_user_is_not_fully_authenticated(
        SecurityContextInterface $securityContext,
        GetResponseEvent $event
    ) {
        $securityContext->getToken()->willReturn(null);
        $securityContext->isGranted(Argument::any())->willReturn(false);

        $this->onCheckStatus($event)->shouldBe(false);
    }

    public function it_ignores_when_request_is_a_fragment(
        GetResponseEvent $event,
        Request $request
    ) {
        $request->getPathInfo()->willReturn('/_fragment');

        $this->onCheckStatus($event)->shouldBe(false);
    }

    public function it_redirects_if_user_has_change_password_role(
        SecurityContextInterface $securityContext,
        GetResponseEvent $event,
        TokenInterface $token,
        User $user,
        Request $request
    ) {
        $securityContext->getToken()->willReturn($token);
        $securityContext->isGranted(Argument::any())->willReturn(true);
        $request->getPathInfo()->willReturn('/account/balance');

        $token->getUser()->willReturn($user);
        $user->hasRole($this->changePasswordRole)->shouldBeCalled()->willReturn(true);

        $event->setResponse(Argument::any())->shouldBeCalled();

        $this->onCheckStatus($event);
    }

    public function it_does_not_redirect_user_if_he_is_already_in_password_change_page(
        SecurityContextInterface $securityContext,
        GetResponseEvent $event,
        TokenInterface $token,
        User $user,
        Request $request
    ) {
        $securityContext->getToken()->willReturn($token);
        $securityContext->isGranted(Argument::any())->willReturn(true);
        $event->getRequest()->willReturn($request);
        $token->getUser()->willReturn($user);
        $user->hasRole($this->changePasswordRole)->willReturn(true);

        $request->get('_route')->willReturn($this->forceChangePasswordRoute);
        $request->getPathInfo()->willReturn($this->forceChangePasswordPathInfo);

        $event->setResponse(Argument::any())->shouldNotBeCalled();
        $this->onCheckStatus($event);
    }
}
