<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\FrontendApiBundle\Controller\CountryController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Service\CountryService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class CountryControllerSpec extends ObjectBehavior
{
    public function let(ContainerInterface $container, CountryService $countryService, ViewHandler $viewHandler)
    {
        $this->setContainer($container);

        $container->get('rest.service.country')->willReturn($countryService);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
        $container->get('rest.repository.transaction')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(CountryController::class);
        $this->shouldHaveType(FOSRestController::class);
    }
    public function it_should_respond_to_get_countries_action(
        ParamFetcher $paramFetcher,
        CountryService $countryService,
        Response $response,
        ViewHandler $viewHandler
    ) {
        $countryService->all(Argument::any(), Argument::any())->willReturn(['countries']);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getCountriesAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_data_not_found(CountryService $countryService, ParamFetcher $paramFetcher)
    {
        $countryService->all(Argument::any(), Argument::any())->willReturn([]);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetCountriesAction($paramFetcher);
    }
}
