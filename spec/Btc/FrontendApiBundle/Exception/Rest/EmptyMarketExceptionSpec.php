<?php

namespace spec\Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\Rest\EmptyMarketException;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmptyMarketExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EmptyMarketException::class);
        $this->shouldHaveType(RestException::class);
        $this->shouldHaveType(HttpException::class);
    }

    public function it_should_have_the_correct_error_message()
    {
        $this->getMessage()->shouldReturn(RestCodeError::MARKET_EMPTY);
    }

    public function it_should_have_the_correct_error_code()
    {
        $this->getCode()->shouldReturn(Response::HTTP_NOT_FOUND);
    }
}
