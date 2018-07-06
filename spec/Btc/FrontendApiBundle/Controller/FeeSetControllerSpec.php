<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\Component\Market\Model\FeeSet;
use Btc\Component\Market\Service\FeeService;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Controller\FeeSetController;
use Btc\FrontendApiBundle\Exception\Rest\NotFoundException;
use Btc\FrontendApiBundle\Service\MarketService;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class FeeSetControllerSpec extends ObjectBehavior
{
    public function let(
        ContainerInterface $container,
        MarketService $marketService,
        ViewHandler $viewHandler,
        FeeService $feeService,
        TokenStorage $tokenStorage,
        User $user,
        PreAuthenticatedToken $preAuthenticatedToken
    ) {
        $this->setContainer($container);

        $container->get('rest.service.market')->willReturn($marketService);
        $container->get('rest.service.fee_service')->willReturn($feeService);
        $container->get('security.token_storage')->willReturn($tokenStorage);
        $container->has('security.token_storage')->willReturn(true);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);

        $tokenStorage->getToken()->willReturn($preAuthenticatedToken);
        $preAuthenticatedToken->getUser()->willReturn($user);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FeeSetController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_fees_action(
        Response $response,
        ViewHandler $viewHandler,
        MarketService $marketService,
        ParamFetcherInterface $paramFetcher,
        FeeService $feeService,
        Market $market,
        FeeSet $feeSet
    ) {
        $marketService->get(1)->willReturn($market);

        $feeService
            ->getFeeSet(Argument::type(User::class), Argument::type(Market::class), Argument::any(), Argument::any())
            ->willReturn($feeSet);

        $feeSet->getFees()->willReturn(['fees']);

        $feeSet->getBuyFeeByMarket(1)->willReturn([Argument::any(), Argument::any()]);
        $feeSet->getSellFeeByMarket(1)->willReturn([Argument::any(), Argument::any()]);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);

        $response = $this->getFeesAction($paramFetcher, 1);
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_data_not_found(MarketService $marketService, ParamFetcherInterface $paramFetcher)
    {
        $marketService->get(0)->willReturn(null);

        $this
            ->shouldThrow(NotFoundException::class)
            ->duringGetFeesAction($paramFetcher, 0);
    }
}
