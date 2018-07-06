<?php

namespace spec\Btc\FrontendApiBundle\Service\Listener;

use Btc\FrontendApiBundle\Service\Listener\JsonRequestTransformerListener;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class JsonRequestTransformerListenerSpec extends ObjectBehavior
{
    public function let(Request $request, ParameterBag $parameterBag)
    {
        $request->request = $parameterBag;
        $parameterBag->replace(Argument::type('array'));
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(JsonRequestTransformerListener::class);
    }

    public function it_is_on_request(GetResponseEvent $event, Request $request)
    {
        $event->getRequest()->willReturn($request);

        $request->getContentType()->willReturn('json')->shouldBeCalled();
        $request->getContent()->willReturn(json_encode(Argument::type('array')))->shouldBeCalled();

        $event->setResponse(Argument::type(JsonResponse::class))->shouldBeCalled();

        $this->onKernelRequest($event);
    }
}
