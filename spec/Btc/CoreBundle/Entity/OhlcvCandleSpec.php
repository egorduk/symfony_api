<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\OhlcvCandle;
use Btc\CoreBundle\Entity\RestEntityInterface;
use PhpSpec\ObjectBehavior;

class OhlcvCandleSpec extends ObjectBehavior
{
    const HIGH_NAME_FAKE = '1M';
    const LOW_NAME_FAKE = '1m';
    const ID_FAKE = 1;

    public function it_is_initializable()
    {
        $this->shouldHaveType(OhlcvCandle::class);
        $this->shouldImplement(RestEntityInterface::class);
    }

    public function it_is_strtolower_interval_name()
    {
        $this->setIntervalName(self::HIGH_NAME_FAKE);

        $this->getIntervalName()->shouldBe(self::LOW_NAME_FAKE);
    }

    public function it_should_return_correct_id()
    {
        $this->setIntervalId(self::ID_FAKE);
        $this->setMarketId(self::ID_FAKE);

        $this->getId()->shouldBe(self::ID_FAKE.':'.self::ID_FAKE);
    }

    public function it_should_return_correct_timestamp()
    {
        $this->setIntervalName(self::HIGH_NAME_FAKE);
        $this->setIntervalId(self::ID_FAKE);

        $this->getTimestamp()->shouldBe(60);
    }
}
