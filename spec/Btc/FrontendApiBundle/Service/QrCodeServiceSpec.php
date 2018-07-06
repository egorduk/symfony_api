<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\FrontendApiBundle\Service\QrCodeService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QrCodeServiceSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(QrCodeService::class);
    }

    public function it_get_qr_code()
    {
        $this->getUrl(Argument::any(), Argument::any())->shouldMatch('/[\S]+/');
    }
}
