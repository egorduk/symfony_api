<?php

namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Error\InsufficientBalanceError;
use Btc\Component\Market\Service\Wallet as WalletService;
use Btc\Component\Market\Service\OrderBookService;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Wallet;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Btc\FrontendApiBundle\Repository\WalletRepository;
use Btc\CoreBundle\Entity\Order as OrderEntity;
use Btc\Component\Market\Model\Order as OrderModel;
use Btc\Component\Market\Service\Order;
use Btc\Component\Market\Service\OrderSubmission;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform as Platform;
use Exmarkets\NsqBundle\Nsq;
use Exmarkets\NsqBundle\Message\Order\CancelOrderMessage;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OrderSubmissionSpec extends ObjectBehavior
{
    function let(
        Connection $db,
        Nsq $nsq,
        Platform $platform,
        OrderBookService $orderbookService,
        WalletRepository $walletRepository,
        EntityManager $em,
        OrderEntity $order,
        Market $market,
        Wallet $wallet,
        User $user
    ) {
        $order->getAmount()->willReturn(1);
        $order->getCurrentAmount()->willReturn(0);
        $order->getTimestamp()->willReturn(1388534400);
        $order->getMarket()->willReturn($market);
        $order->getInWallet()->willReturn($wallet);

        $wallet->getUser()->willReturn($user);

        $db->getDatabasePlatform()->willReturn($platform);

        $platform->getDateTimeFormatString()->willReturn('Y-m-d H:i');

        $this->beConstructedWith($db, $nsq, $orderbookService, $walletRepository, $em);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(OrderSubmission::class);
    }

  /*  function it_should_not_be_able_to_submit_any_deal_if_transaction_is_active(OrderModel $order, $db)
    {
        $db->isTransactionActive()->shouldBeCalled()->willReturn(true);

        $this->shouldThrow('RuntimeException')->duringSubmit($order);
    }*/

    function it_should_be_able_to_cancel_a_buy_deal(Connection $db, OrderEntity $order, Nsq $nsq)
    {
        $order->getId()->shouldBeCalled()->willReturn(1);
        $order->getType()->shouldBeCalled()->willReturn(OrderEntity::TYPE_LIMIT);
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getSide()->shouldBeCalled()->willReturn(OrderEntity::SIDE_BUY);
        $order->getAskedUnitPrice()->willReturn(100);

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        // should lock deal
        $db->fetchColumn(Order::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(OrderEntity::STATUS_OPEN);

        // should update deal status
        $db->executeUpdate(Order::SQL_CANCEL, [
            OrderEntity::STATUS_PENDING_CANCEL, 1
        ])->shouldBeCalled();

        // should commit a transaction
        $db->commit()->shouldBeCalled();

        $nsq->send(Argument::type(CancelOrderMessage::class))->shouldBeCalled();

        $this->cancelOrder($order)->shouldBe(true);
    }

    function it_should_be_able_to_cancel_a_sell_deal(Connection $db, OrderEntity $order, Nsq $nsq)
    {
        $order->getId()->shouldBeCalled()->willReturn(1);
        $order->getType()->shouldBeCalled()->willReturn(OrderEntity::TYPE_LIMIT);
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getSide()->shouldBeCalled()->willReturn(OrderEntity::SIDE_BUY);
        $order->getAskedUnitPrice()->willReturn(100);

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        // should lock deal
        $db->fetchColumn(Order::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(OrderEntity::STATUS_OPEN);

        // should update deal status
        $db->executeUpdate(Order::SQL_CANCEL, [
            OrderEntity::STATUS_PENDING_CANCEL, 1
        ])->shouldBeCalled();

        // should commit a transaction
        $db->commit()->shouldBeCalled();

        // should send nsq message
        $nsq->send(Argument::type(CancelOrderMessage::class))->shouldBeCalled();

        $this->cancelOrder($order)->shouldBe(true);
    }

    function it_should_not_be_able_to_cancel_market_buy_order(OrderEntity $order)
    {
        $order->getType()->shouldBeCalled()->willReturn(OrderEntity::TYPE_MARKET);

        $this
            ->shouldThrow(UnknownErrorException::class)
            ->duringCancelOrder($order);
    }

    function it_should_not_be_able_to_cancel_market_sell_order(OrderEntity $order)
    {
        $order->getType()->shouldBeCalled()->willReturn(OrderEntity::TYPE_MARKET);

        $this
            ->shouldThrow(UnknownErrorException::class)
            ->duringCancelOrder($order);
    }

    function it_should_not_cancel_a_deal_which_was_completed(Connection $db, OrderEntity $order)
    {
        $order->getType()->shouldBeCalled()->willReturn(OrderEntity::TYPE_LIMIT);
        $order->getId()->shouldBeCalled()->willReturn(1);

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        // should lock deal
        $db->fetchColumn(Order::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(OrderEntity::STATUS_CANCELLED);

        // should rollback
        $db->rollBack()->shouldBeCalled();

        $this
            ->shouldThrow(UnknownErrorException::class)
            ->duringCancelOrder($order);
    }

    function it_should_check_if_balance_in_wallet_is_sufficient_when_submitting_market_buy_order(
        Connection $db,
        OrderBookService $orderbookService,
        OrderModel $orderModel,
        EntityManager $em,
        QueryBuilder $queryBuilder,
        AbstractQuery $query
    ) {
        $orderModel->getAssetCurrencyCode()->willReturn('BTC')->shouldBeCalled();

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $db->fetchAssoc(WalletService::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 100, 'amount_reserved'=>0]);

        $orderModel->getMarketSlug()->willReturn('btc-usd')->shouldBeCalled();

        $orderbookService->getLowestAskPrice('btc-usd')->willReturn(500)->shouldBeCalled();

        $orderModel->getFundsCurrencyCode()->willReturn('USD')->shouldBeCalled();
        $orderModel->getAskedUnitPrice()->willReturn(0)->shouldBeCalled();
        $orderModel->setAskedUnitPrice(0)->shouldBeCalled();

        $em->createQueryBuilder()->willReturn($queryBuilder)->shouldBeCalled();

        $queryBuilder->from(Argument::any(), Argument::any())->willReturn($queryBuilder);
        $queryBuilder->select(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->where(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->andWhere(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->setParameter(Argument::any(), Argument::any())->willReturn($queryBuilder);
        $queryBuilder->setParameters(Argument::any())->willReturn($queryBuilder);
        $queryBuilder->getQuery()->willReturn($query);

        $query->getSingleScalarResult()->willReturn(1);
        $query->getSingleResult()->willReturn(1);

        $orderModel->getAmount()->willReturn(1)->shouldBeCalled();
        $orderModel->getType()->willReturn(OrderEntity::TYPE_MARKET)->shouldBeCalled();
        $orderModel->getSide()->willReturn(OrderEntity::SIDE_BUY)->shouldBeCalled();
        $orderModel->getFeePercent()->willReturn(0.1)->shouldBeCalled();

        $db->rollBack()->shouldBeCalled();

        $orderModel->getOutWalletId()->willReturn(1)->shouldBeCalled();

        $this->submitOrder($orderModel)->shouldHaveType(InsufficientBalanceError::class);
    }

    /*function it_should_rollback_on_less_than_min_amount(OrderModel $order, $db, OrderBookService $orderbookService)
    {
        $order->getOutWalletId()->willReturn(1);
        $order->getAskedUnitPrice()->willReturn(100);
        $order->setAskedUnitPrice(100)->shouldBeCalled();
        $order->getMarketSlug()->willReturn('btc-usd');
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $order->getFundsCurrencyCode()->willReturn('USD');
        $order->getAmount()->willReturn(0.0001);

        $db->fetchAssoc(Wallet::SQL_LOCK, [1])->willReturn(['amount_available' => 200, 'amount_reserved' => 0]);
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);

        $db->isTransactionActive()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $db->rollBack()->shouldBeCalled();

        $this->submit($order)->shouldHaveType("Btc\Component\Market\Error\MinOrderAmountError");
    }

    function it_should_rollback_without_best_sell_price_when_submitting_market_buy_order(OrderModel $order, $db, OrderBookService $orderbookService)
    {
        $order->getType()->shouldBeCalled()->willReturn(OrderEntity::TYPE_MARKET);
        $order->getSide()->shouldBeCalled()->willReturn(OrderEntity::SIDE_BUY);
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $db->fetchAssoc(Wallet::SQL_LOCK, [1,])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 200, 'amount_reserved' => 0]);

        $order->getMarketSlug()->shouldBeCalled()->willReturn('btc-usd');
        $orderbookService->getLowestAskPrice('btc-usd')->shouldBeCalled()->willReturn(0);

        $db->rollBack()->shouldBeCalled();

        $order->getOutWalletId()->willReturn(1);
        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(0);
        $order->setAskedUnitPrice(0)->shouldBeCalled();

        $this->submit($order)->shouldHaveType("Btc\Component\Market\Error\MarketEmptyError");
    }

    function it_should_be_able_to_submit_a_market_buy_deal(OrderModel $order, Connection $db, Nsq $nsq, Wallet $wallet, OrderBookService $orderbookService)
    {
        $order->getType()->willReturn(OrderEntity::TYPE_MARKET);
        $order->getSide()->willReturn(OrderEntity::SIDE_BUY);
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);

        $order->getOutWalletId()->willReturn(1);
        $order->getMarketId()->willReturn(1);
        $order->getMarketSlug()->willReturn('btc-usd');
        $order->getInWalletId()->willReturn(2);
        $order->getFeePercent()->willReturn(0.3);

        $order->getAmount()->willReturn(1);
        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(0);
        $order->setAskedUnitPrice(0)->shouldBeCalled();

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        // should lock wallet
        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 505, 'amount_reserved' => 0]);

        // should look for best sell deal price
        $order->getMarketSlug()->shouldBeCalled()->willReturn('btc-usd');
        $orderbookService->getLowestAskPrice('btc-usd')->shouldBeCalled()->willReturn(500);

        // should reserve amount in wallet
        $db->executeUpdate(Wallet::SQL_RESERVE, ['amount' => 505, 'wallet' => 1])->shouldBeCalled();

        // should insert buy deal, arguments tested elsewhere
        $db->insert('orders', Argument::Any())->shouldBeCalled();

        // should get last insert id
        $db->lastInsertId()->shouldBeCalled()->willReturn(2);
        $order->setId(2)->shouldBeCalled();
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getTimestamp()->willReturn(4656644);
        $order->getId()->willReturn(2);
        $order->setReserveTotal(503.4895349)->shouldBeCalled();
        $order->getReserveTotal()->willReturn(503.4895349);
        $order->setFeeReserved(1.5104651)->shouldBeCalled();
        $order->getFeeReserved()->shouldBeCalled()->willReturn(
            bcmul(500, bcdiv(0.3, bcadd(100, 0.3, 8), 8), 8)
        );

        // should commit a transaction
        $db->commit()->shouldBeCalled();

        // should send a message
        $nsq->send(Argument::type('Exmarkets\NsqBundle\Message\Order\NewOrderMessage'))->shouldBeCalled();

        $this->submit($order)->shouldReturn(null);
    }

    function it_should_check_if_balance_in_wallet_is_sufficient_when_submitting_limit_buy(OrderModel $order, $db)
    {
        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $order->getType()->willReturn(OrderEntity::TYPE_LIMIT)->shouldBeCalled();
        $order->getSide()->willReturn(OrderEntity::SIDE_BUY)->shouldBeCalled();
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);

        $order->getOutWalletId()->willReturn(1);
        $order->getMarketId()->willReturn(1);
        $order->getOrderValue()->shouldBeCalled()->willReturn(500);
        $order->getFeePercent()->willReturn(0.3);
        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(500);
        $order->setAskedUnitPrice(500)->shouldBeCalled();
        $fee = bcmul(500, bcdiv(0.3, 100, 8), 8);
        $order->setFeeReserved($fee)->shouldBeCalled();
        $order->getFeeReserved()->shouldBeCalled()->willReturn($fee);
        $order->setReserveTotal(500 + $fee)->shouldBeCalled();
        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 100, 'amount_reserved' => 0]);

        $db->rollBack()->shouldBeCalled();

        $this->submit($order)->shouldHaveType("Btc\Component\Market\Error\InsufficientBalanceError");
    }

    function it_should_be_able_to_submit_a_buy_deal(OrderModel $order, $db, $nsq)
    {
        $order->getOutWalletId()->willReturn(1);
        $order->getMarketId()->willReturn(1);
        $order->getMarketSlug()->willReturn('btc-usd');
        $order->getType()->shouldBeCalled()->willReturn(OrderEntity::TYPE_LIMIT);
        $order->getSide()->shouldBeCalled()->willReturn(OrderEntity::SIDE_BUY);
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);
        $order->getAskedUnitPrice()->willReturn(100);
        $order->setAskedUnitPrice(100)->shouldBeCalled();
        $order->getInWalletId()->willReturn(2);
        $order->getOrderValue()->willReturn(150);
        $order->getFeePercent()->willReturn(5);


        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        // should lock wallet
        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 200, 'amount_reserved' => 0]);

        // fee should be set for deal
        $order->setFeeReserved(7.5)->shouldBeCalled();
        $order->getFeeReserved()->willReturn(7.5);

        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAmount()->willReturn(0.1);
        $order->setReserveTotal(157.5)->shouldBeCalled();
        $order->getReserveTotal()->shouldBeCalled()->willReturn(157.5);

        // should reserve amount in wallet
        $db->executeUpdate(Wallet::SQL_RESERVE, ['amount' => 157.5, 'wallet' => 1])->shouldBeCalled();

        // should insert buy deal, arguments tested elsewhere
        $db->insert('orders', Argument::Any())->shouldBeCalled();

        // should get last insert id
        $db->lastInsertId()->shouldBeCalled()->willReturn(2);
        $order->setId(2)->shouldBeCalled();
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getTimestamp()->willReturn(4656644);
        $order->getId()->willReturn(2);

        // should commit a transaction
        $db->commit()->shouldBeCalled();

        // should send a message
        $nsq->send(Argument::type('Exmarkets\NsqBundle\Message\Order\NewOrderMessage'))->shouldBeCalled();

        $this->submit($order)->shouldReturn(null);
    }

    function it_should_check_if_balance_in_wallet_is_sufficient_when_submitting_market_sell(OrderModel $order, $db, OrderBookService $orderbookService)
    {
        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 20, 'amount_reserved' => 0]);

        $order->getMarketSlug()->shouldBeCalled()->willReturn('btc-usd');
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);
        $orderbookService->getHighestBidPrice('btc-usd')->shouldBeCalled()->willReturn(500);

        $db->rollBack()->shouldBeCalled();

        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(0);
        $order->setAskedUnitPrice(0)->shouldBeCalled();
        $order->getOutWalletId()->willReturn(1);
        $order->getType()->willReturn(OrderEntity::TYPE_MARKET);
        $order->getSide()->willReturn(OrderEntity::SIDE_SELL);
        $order->getAmount()->willReturn(25);

        $this->submit($order)->shouldHaveType("Btc\Component\Market\Error\InsufficientBalanceError");
    }

    function it_should_rollback_without_best_buy_price_when_submitting_market_sell(OrderModel $order, $db, OrderBookService $orderbookService)
    {
        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 20, 'amount_reserved' => 0]);

        $order->getMarketSlug()->shouldBeCalled()->willReturn('btc-usd');
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);
        $orderbookService->getHighestBidPrice('btc-usd')->shouldBeCalled()->willReturn(0);

        $db->rollBack()->shouldBeCalled();

        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(0);
        $order->setAskedUnitPrice(0)->shouldBeCalled();
        $order->getOutWalletId()->willReturn(1);
        $order->getType()->willReturn(OrderEntity::TYPE_MARKET);
        $order->getSide()->willReturn(OrderEntity::SIDE_SELL);

        $order->getAmount()->willReturn(15);

        $this->submit($order)->shouldHaveType("Btc\Component\Market\Error\MarketEmptyError");
    }

    function it_should_be_able_to_submit_an_market_sell_deal(OrderModel $order, $db, $nsq, OrderBookService $orderbookService)
    {
        $order->getAmount()->willReturn(15);
        $order->getOutWalletId()->willReturn(1);
        $order->getMarketId()->willReturn(1);
        $order->getMarketSlug()->willReturn('btc-eur');
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);
        $order->getType()->willReturn(OrderEntity::TYPE_MARKET);
        $order->getSide()->willReturn(OrderEntity::SIDE_SELL);
        $order->getInWalletId()->willReturn(2);
        $order->getFeePercent()->willReturn(5);
        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(0);
        $order->setAskedUnitPrice(0)->shouldBeCalled();

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        // should lock wallet
        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 20, 'amount_reserved' => 0]);

        // should look for best buy deal price
        $order->getMarketSlug()->shouldBeCalled()->willReturn('btc-usd');
        $orderbookService->getHighestBidPrice('btc-usd')->shouldBeCalled()->willReturn(500);

        // should reserve amount in wallet
        $db->executeUpdate(Wallet::SQL_RESERVE, ['amount' => 15, 'wallet' => 1])->shouldBeCalled();

        // revaluate sell deal
        $order->getAskedUnitPrice()->willReturn(0);

        // should insert buy deal, arguments tested elsewhere
        $db->insert('orders', Argument::Any())->shouldBeCalled();

        // should get last insert id
        $db->lastInsertId()->shouldBeCalled()->willReturn(2);
        $order->setId(2)->shouldBeCalled();
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getTimestamp()->willReturn(4656644);
        $order->getId()->willReturn(2);

        $order->setReserveTotal(15)->shouldBeCalled();
        $order->getReserveTotal()->shouldBeCalled()->willReturn(15);
        $order->getFeeReserved()->willReturn(0);

        // should commit a transaction
        $db->commit()->shouldBeCalled();

        // should send a message
        $nsq->send(Argument::type('Exmarkets\NsqBundle\Message\Order\NewOrderMessage'))->shouldBeCalled();

        $this->submit($order)->shouldReturn(null);
    }

    function it_should_check_if_balance_in_wallet_is_sufficient_when_submitting_limit_sell(OrderModel $order, $db)
    {
        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        $order->getType()->willReturn(OrderEntity::TYPE_LIMIT);
        $order->getSide()->willReturn(OrderEntity::SIDE_SELL);
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);
        $order->getOutWalletId()->willReturn(1);
        $order->getMarketId()->willReturn(1);
        $order->getAmount()->willReturn(25);
        $order->getOrderValue()->shouldBeCalled()->willReturn(25*1000);
        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->getAskedUnitPrice()->shouldBeCalled()->willReturn(1000);
        $order->setAskedUnitPrice(1000)->shouldBeCalled();

        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 20, 'amount_reserved' => 0]);

        $db->rollBack()->shouldBeCalled();

        $this->submit($order)->shouldHaveType("Btc\Component\Market\Error\InsufficientBalanceError");
    }

    function it_should_be_able_to_submit_a_sell_deal(OrderModel $order, $db, $nsq)
    {
        $order->getAmount()->willReturn(1);
        $order->getOutWalletId()->willReturn(1);
        $order->getMarketId()->willReturn(1);
        $order->getMarketSlug()->willReturn('btc-eur');
        $order->getType()->willReturn(OrderEntity::TYPE_LIMIT);
        $order->getSide()->willReturn(OrderEntity::SIDE_SELL);
        $order->getAssetCurrencyCode()->willReturn("BTC");
        $db->fetchColumn(OrderSubmission::SQL_MIN_AMOUNT, ["btc-min-order-amount"])->willReturn(0.01);
        $order->getInWalletId()->willReturn(2);
        $order->getAskedUnitPrice()->willReturn(150);
        $order->getFeePercent()->willReturn(5);
        $order->getFundsCurrencyCode()->shouldBeCalled()->willReturn('USD');
        $order->setAskedUnitPrice(150)->shouldBeCalled();
        $order->getOrderValue()->shouldBeCalled()->willReturn(1*150);

        $db->isTransactionActive()->shouldBeCalled()->willReturn(false);
        $db->beginTransaction()->shouldBeCalled();

        // should lock wallet
        $db->fetchAssoc(Wallet::SQL_LOCK, [1])
            ->shouldBeCalled()
            ->willReturn(['amount_available' => 20, 'amount_reserved' => 0]);

        // should reserve amount in wallet
        $db->executeUpdate(Wallet::SQL_RESERVE, ['amount' => 1, 'wallet' => 1])->shouldBeCalled();

        // should insert buy deal, arguments tested elsewhere
        $db->insert('orders', Argument::Any())->shouldBeCalled();

        // should get last insert id
        $db->lastInsertId()->shouldBeCalled()->willReturn(2);
        $order->setId(2)->shouldBeCalled();
        $order->setTimestamp(Argument::any())->shouldBeCalled();
        $order->getTimestamp()->willReturn(4656644);
        $order->getId()->willReturn(2);
        $order->setReserveTotal(1)->shouldBeCalled();
        $order->getReserveTotal()->shouldBeCalled()->willReturn(1);
        $order->getFeeReserved()->shouldBeCalled()->willReturn(0);

        // should commit a transaction
        $db->commit()->shouldBeCalled();

        // should send a message
        $nsq->send(Argument::type('Exmarkets\NsqBundle\Message\Order\NewOrderMessage'))->shouldBeCalled();

        $this->submit($order)->shouldReturn(null);
    }

    function it_should_round_value_up()
    {
        $this->roundValueUp(7.79999999, 5)->shouldReturn(7.80);
        $this->roundValueUp(7.79999001, 5)->shouldReturn(7.80);
        $this->roundValueUp(7.33333001, 5)->shouldReturn(7.33334);
        $this->roundValueUp(1.99999999, 5)->shouldReturn(2.0);
        $this->roundValueUp(7.77779001, 5)->shouldReturn(7.7778);
        $this->roundValueUp(1.11111111, 5)->shouldReturn(1.11112);
    }*/
}
