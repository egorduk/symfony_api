<?php

namespace spec\Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\Rest\AlreadyExistsException;
use Btc\FrontendApiBundle\Exception\RestException;
use Symfony\Component\HttpFoundation\Response;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AlreadyExistsExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(AlreadyExistsException::class);
        $this->shouldHaveType(RestException::class);
        $this->shouldHaveType(HttpException::class);
    }

    public function it_should_have_the_correct_error_message()
    {
        $this->getMessage()->shouldReturn(RestCodeError::ALREADY_EXISTS);
    }

    public function it_should_have_the_correct_error_code()
    {
        $this->getCode()->shouldReturn(Response::HTTP_BAD_REQUEST);
    }
}
