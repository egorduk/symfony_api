<?php namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Exception\OrderBookException;
use Btc\Component\Market\Service\OrderBookFetcherInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class OrderBookServiceSpec extends ObjectBehavior
{
    const HIGHEST_PRICE = "600";
    const LOWEST_PRICE = "710";
    private $bids = [
        [
            "id" => 1,
            "platform" => "EXM",
            "price" => 400,
            "amount" => 1

        ],
        [
            "id" => 2,
            "platform" => "EXM",
            "price" => 300,
            "amount" => 2
        ]
    ];
    private $asks = [
        [
            "id" => 4,
            "platform" => "EXM",
            "price" => 600,
            "amount" => 2
        ],
        [
            "id" => 3,
            "platform" => "EXM",
            "price" => 500,
            "amount" => 1

        ]
    ];

    function let(
        OrderBookFetcherInterface $orderbook,
        LoggerInterface $logger
    ) {
        $orderbook->get(Argument::any(), Argument::any())
            ->willReturn(['bids' => $this->bids, 'asks' => $this->asks]);
        $orderbook->highest(Argument::any())->willReturn(self::HIGHEST_PRICE);
        $orderbook->lowest(Argument::any())->willReturn(self::LOWEST_PRICE);

        $this->beConstructedWith($orderbook, $logger);
    }

    function it_should_return_bids()
    {
        $this->getBuyDeals('btc-usd')->shouldBe($this->bids);
    }

    function it_should_return_asks()
    {
        $this->getSellDeals('btc-usd')->shouldBe($this->asks);
    }

    function it_logs_when_orderbook_exception_occurs(
        OrderBookFetcherInterface $orderbook,
        LoggerInterface $logger
    ) {
        $orderbook->get(Argument::any(), Argument::any())
            ->willThrow(new OrderBookException());

        $logger->critical(Argument::any())->shouldBeCalled();

        $this->getBuyDeals('btc-usd');
    }

    function it_returns_highest_bid_price_in_market()
    {
        $this->getHighestBidPrice('btc-usd')->shouldBe(self::HIGHEST_PRICE);
    }

    function it_returns_zero_and_logs_if_exception_occurred_during_highest_price(
        OrderBookFetcherInterface $orderbook,
        LoggerInterface $logger
    ) {
        $orderbook->highest(Argument::any())
            ->willThrow(new OrderBookException());

        $logger->critical(Argument::any())->shouldBeCalled();

        $this->getHighestBidPrice('btc-usd')->shouldBe("0");
    }

    function it_returns_lowest_ask_price_in_market()
    {
        $this->getLowestAskPrice('btc-usd')->shouldBe(self::LOWEST_PRICE);
    }

    function it_returns_zero_and_logs_if_exception_occurred_during_lowest_price(
        OrderBookFetcherInterface $orderbook,
        LoggerInterface $logger
    ) {
        $orderbook->lowest(Argument::any())
            ->willThrow(new OrderBookException());

        $logger->critical(Argument::any())->shouldBeCalled();

        $this->getLowestAskPrice('btc-usd')->shouldBe("0");
    }

} 