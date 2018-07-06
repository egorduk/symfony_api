<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\Market;
use Btc\CoreBundle\Entity\RestEntityInterface;
use PhpSpec\ObjectBehavior;

class MarketSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Market::class);
        $this->shouldImplement(RestEntityInterface::class);
    }

    public function it_is_crypto()
    {
        $currency = new Currency();
        $currency->setCrypto(true);

        $this->setWithCurrency($currency);
        $this->setCurrency($currency);

        $this->isCrypto()->shouldBe(true);
    }

    public function it_is_not_crypto()
    {
        $currency = new Currency();
        $currency->setCrypto(false);

        $this->setWithCurrency($currency);

        $currency->setCrypto(false);
        $this->setCurrency($currency);

        $this->isCrypto()->shouldBe(false);
    }
}
