<?php

namespace spec\Btc\FrontendApiBundle\Controller;

use Btc\Component\Market\Service\OrderSubmission;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\OhlcvCandle;
use Btc\FrontendApiBundle\Controller\ExportController;
use Btc\FrontendApiBundle\Exception\Rest\NoMarketException;
use Btc\FrontendApiBundle\Repository\OrderRepository;
use Btc\FrontendApiBundle\Service\MarketService;
use Btc\FrontendApiBundle\Service\UserOrderService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportControllerSpec extends ObjectBehavior
{
    const MARKET_ID_FAKE = 1;
    const PRICE_FAKE = 1.2;
    const AMOUNT_FAKE = 1;
    const ORDER_ID_FAKE = 1;
    const CORRECT_INTERVAL_FAKE = '1m';
    const TABLE_NAME_FAKE = 'ohlcv';

    public function let(
        ContainerInterface $container,
        ViewHandler $viewHandler,
        Response $response,
        MarketService $marketService,
        Market $market,
        UserOrderService $userOrderService,
        UserOrderService $userOrderService,
        OrderRepository $orderRepository,
        OrderSubmission $orderSubmission,
        EntityManager $entityManager,
        Request $request
    ) {
        $this->setContainer($container);

        $container->get('em')->willReturn($entityManager);
        $container->get('rest.service.market')->willReturn($marketService);
        $container->get('rest.service.user_order')->willReturn($userOrderService);
        $container->get('rest.service.deal_submission')->willReturn($orderSubmission);
        $container->get('rest.repository.order')->willReturn($orderRepository);
        $container->get('fos_rest.view_handler')->willReturn($viewHandler);
        $container->get('request')->willReturn($request);

        $marketService->get(self::MARKET_ID_FAKE)->willReturn($market);

        $viewHandler->handle(Argument::type(View::class))->willReturn($response);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ExportController::class);
        $this->shouldHaveType(FOSRestController::class);
    }

    public function it_should_respond_to_get_candles_action(
        ClassMetadata $classMetadata,
        QueryBuilder $queryBuilder,
        EntityManager $entityManager,
        AbstractQuery $query,
        Request $request
    ) {
        $entityManager->getClassMetadata(OhlcvCandle::class)->willReturn($classMetadata)->shouldBeCalled();

        OhlcvCandle::getTableNameForInterval(Argument::any());

        $classMetadata->setPrimaryTable(['name' => self::TABLE_NAME_FAKE])->shouldBeCalled();

        $entityManager->createQueryBuilder()->willReturn($queryBuilder)->shouldBeCalled();

        $queryBuilder->select('f')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->from(OhlcvCandle::class, 'f')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->where('f.marketId = :marketId')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setParameters(['marketId' => self::MARKET_ID_FAKE])->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->setMaxResults(ExportController::LIMIT)->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->orderBy('f.intervalId', 'desc')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->getQuery()->willReturn($query)->shouldBeCalled();

        $request->getRequestFormat()->willReturn(Argument::any())->shouldBeCalled();

        $response = $this->getCandlesAction(self::MARKET_ID_FAKE, self::CORRECT_INTERVAL_FAKE);
        $response->shouldHaveType(Response::class);
    }

    public function it_throws_an_exception_if_market_not_found_by_id(MarketService $marketService)
    {
        $marketService->get(self::MARKET_ID_FAKE)->willReturn(null);

        $this
            ->shouldThrow(NoMarketException::class)
            ->duringGetCandlesAction(self::MARKET_ID_FAKE, self::CORRECT_INTERVAL_FAKE);
    }
}
