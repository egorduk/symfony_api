<?php

namespace spec\Btc\FrontendApiBundle\Exception\Rest;

use Btc\FrontendApiBundle\Classes\RestCodeError;
use Btc\FrontendApiBundle\Exception\Rest\NotValidDataException;
use Btc\FrontendApiBundle\Exception\RestException;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotValidDataExceptionSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(NotValidDataException::class);
        $this->shouldHaveType(RestException::class);
        $this->shouldHaveType(HttpException::class);
    }

    public function it_should_have_the_correct_error_message()
    {
        $this->getMessage()->shouldReturn(RestCodeError::VALIDATION_ERROR);
    }

    public function it_should_have_the_correct_error_code()
    {
        $this->getCode()->shouldReturn(Response::HTTP_BAD_REQUEST);
    }
}
