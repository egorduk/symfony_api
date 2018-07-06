<?php

namespace spec\Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Service\Listener\DBALExceptionListener;
use Doctrine\DBAL\DBALException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class DBALExceptionListenerSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(0);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DBALExceptionListener::class);
        $this->shouldImplement(EventSubscriberInterface::class);
    }

    public function it_is_rest_exception_handler(GetResponseForExceptionEvent $event, DBALException $exception)
    {
        $event->getException()->willReturn($exception);

        $event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $this->onKernelException($event);
    }

    public function it_is_http_exception_handler(GetResponseForExceptionEvent $event, \InvalidArgumentException $exception)
    {
        $event->getException()->willReturn($exception);

        $event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $this->onKernelException($event);
    }

    public function it_is_other_exception_handler(GetResponseForExceptionEvent $event, $exception)
    {
        $event->getException()->willReturn($exception);

        $event->setResponse(Argument::type(JsonResponse::class))->shouldNotBeCalled();

        $this->onKernelException($event);
    }
}
