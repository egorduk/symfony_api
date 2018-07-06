<?php

namespace spec\Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Exception\RestException;
use Btc\FrontendApiBundle\Service\Listener\RestExceptionListener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RestExceptionListenerSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(0);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RestExceptionListener::class);
        $this->shouldImplement(EventSubscriberInterface::class);
    }

    public function it_is_rest_exception_handler(GetResponseForExceptionEvent $event, RestException $exception)
    {
        $event->getException()->willReturn($exception);

        $event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $this->onKernelException($event);
    }

    public function it_is_http_exception_handler(GetResponseForExceptionEvent $event, HttpException $exception)
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
