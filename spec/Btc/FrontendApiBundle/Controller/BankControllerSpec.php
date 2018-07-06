<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\CoreBundle\Entity\Bank;
use Btc\FrontendApiBundle\Controller\BankController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Service\BankService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class BankControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        BankService $bankService,
        ViewHandler $viewHandler
    ) {
        $this->setContainer($container);

        $container->get('rest.service.bank')->willReturn($bankService);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(BankController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_banks_action(
        ParamFetcher $paramFetcher,
        BankService $bankService,
        Response $response,
        ViewHandler $viewHandler
    ) {
        $bank = new Bank();

        $bankService->all(Argument::any(), Argument::any())->willReturn([$bank])->shouldBeCalled();

        $view = new View(['banks' => [$bank]], Response::HTTP_OK);

        $viewHandler->handle($view)->willReturn($response)->shouldBeCalled();

        $response = $this->getBanksAction($paramFetcher);
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_data_not_found(BankService $bankService, ParamFetcher $paramFetcher)
    {
        $bankService->all(Argument::any(), Argument::any())->willReturn([])->shouldBeCalled();

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetBanksAction($paramFetcher);
    }
}
