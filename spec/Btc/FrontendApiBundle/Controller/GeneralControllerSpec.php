<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Market;
use Btc\FrontendApiBundle\Controller\GeneralController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Service\CurrencyService;
use Btc\FrontendApiBundle\Service\MarketService;
use Btc\FrontendApiBundle\Service\RestRedis;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use JMS\Serializer\Serializer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class GeneralControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        MarketService $marketService,
        ViewHandler $viewHandler,
        CurrencyService $currencyService,
        Serializer $serializer
    ) {
        $this->setContainer($container);

        $container->get('rest.service.market')->willReturn($marketService);
        $container->get('rest.service.currency')->willReturn($currencyService);
        $container->get('rest.redis')->willReturn(new RestRedis('127.0.0.1', '6379', 1));   // mock redis without segmentation fault
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
        $container->get('jms_serializer')->willReturn($serializer);
        $container->getParameter('rest_api_version')->willReturn(1);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(GeneralController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_currencies_and_markets_action(
        Response $response,
        ViewHandler $viewHandler,
        MarketService $marketService,
        CurrencyService $currencyService
    ) {
        $marketService->all(Argument::any())->willReturn([new Market()]);
        $currencyService->all(Argument::any())->willReturn([new Currency()]);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getCurrenciesAndMarketsAction();
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_data_not_found(MarketService $marketService, CurrencyService $currencyService)
    {
        $marketService->all(Argument::any())->willReturn([]);
        $currencyService->all(Argument::any())->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetCurrenciesAndMarketsAction();
    }
}
