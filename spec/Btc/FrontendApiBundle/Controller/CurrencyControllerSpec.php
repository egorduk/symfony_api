<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Controller\CurrencyController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Service\CurrencyService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class CurrencyControllerSpec extends ObjectBehavior
{
    public function let(ContainerInterface $container, CurrencyService $currencyService, ViewHandler $viewHandler)
    {
        $this->setContainer($container);

        $container->get('rest.service.currency')->willReturn($currencyService);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CurrencyController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_currencies_action(
        ParamFetcher $paramFetcher,
        CurrencyService $currencyService,
        Response $response,
        ViewHandler $viewHandler
    ) {
        $currencyService->all(Argument::any(), Argument::any())->willReturn(['currencies']);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getCurrenciesAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_data_not_found(CurrencyService $currencyService, ParamFetcher $paramFetcher)
    {
        $currencyService->all(Argument::any(), Argument::any())->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetCurrenciesAction($paramFetcher)
        ;
    }
}
