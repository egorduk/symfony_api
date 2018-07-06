<?php

namespace spec\Btc\Component\Market\Service;

use Btc\Component\Market\Service\OrderBookFetcherInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderBookSpec extends ObjectBehavior
{
    const BASE_API_URL = 'http://localhost:8888';
    const HIGHEST_PRICE = "500";
    const LOWEST_PRICE = "600";

    private $market = [
        'bids' => [
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
        ],
        'asks' => [
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
        ]
    ];

    function let(
        Client $client,
        Request $request,
        Response $response
    ) {
        //$client->setBaseUrl(self::BASE_API_URL)->shouldBeCalled();
        //$client->get(Argument::any())->willReturn($request);
        //$request->send()->willReturn($response);

        $this->beConstructedWith($client, self::BASE_API_URL);
    }

    function it_is_an_orderbook_fetcher()
    {
        $this->shouldHaveType(OrderBookFetcherInterface::class);
    }

    function it_should_form_url_appending_market_slug(
        Client $client,
        Request $request,
        ResponseInterface $response
    ) {
        $client->get('/orderbook/btc-usd/grouped/0')->willReturn($response)->shouldBeCalled();

        $this->get('btc-usd');
    }

    /*function it_should_append_limit_parameter_if_it_is_set(
        Client $client,
        Request $request
    ) {
        $client->get('/orderbook/btc-usd/grouped/10')->shouldBeCalled()->willReturn($request);

        $this->get('btc-usd', 10);
    }

    function it_should_return_whole_market(
        Response $response
    ) {
        $response->getBody(true)->willReturn(json_encode($this->market));
        $this->get('btc-usd')->shouldBe($this->market);
    }

    function it_throws_orderbook_exception_when_there_are_connectivity_problems(
        Request $request
    ) {
        // Request exception is thrown if there are connectivity problems
        // or the status code is bad
        $request->send()->willThrow(new RequestException());

        $this
            ->shouldThrow('Btc\Component\Market\Exception\OrderBookException')
            ->duringGet('btc-usd');
    }

    function it_should_form_highest_bid_url(
        Client $client,
        Request $request
    ) {
        $client->get('/bids/btc-usd/highest-price')
            ->shouldBeCalled()
            ->willReturn($request);

        $this->highest('btc-usd');
    }

    function it_returns_highest_bid_price(
        Client $client,
        Request $request,
        Response $response
    ) {
        $request->send()->willReturn($response);
        $client->get('/bids/btc-usd/highest-price')->willReturn($request);

        $response->getBody(true)->willReturn(json_encode(['highest_bid' => self::HIGHEST_PRICE]));

        $this->highest('btc-usd')->shouldBe(self::HIGHEST_PRICE);
    }

    function it_throws_an_orderbook_exception_if_failed_fetching_highest_price(
        Client $client
    ) {
        $client->get(Argument::any())->willThrow(new RequestException());

        $this->shouldThrow('Btc\Component\Market\Exception\OrderBookException')->duringHighest('btc-usd');

    }

    function it_should_form_lowest_ask_url(
        Client $client,
        Request $request
    ) {
        $client->get('/asks/btc-usd/lowest-price')
            ->shouldBeCalled()
            ->willReturn($request);

        $this->lowest('btc-usd');
    }

    function it_returns_lowest_ask_price(
        Client $client,
        Request $request,
        Response $response
    ) {
        $request->send()->willReturn($response);
        $client->get('/asks/btc-usd/lowest-price')->willReturn($request);

        $response->getBody(true)->willReturn(json_encode(['lowest_ask' => self::LOWEST_PRICE]));

        $this->lowest('btc-usd')->shouldBe(self::LOWEST_PRICE);
    }

    function it_throws_an_orderbook_exception_if_failed_fetching_lowest_price(
        Client $client
    ) {
        $client->get(Argument::any())->willThrow(new RequestException());

        $this->shouldThrow('Btc\Component\Market\Exception\OrderBookException')->duringLowest('btc-usd');

    }*/
} 