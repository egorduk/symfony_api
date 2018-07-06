<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\FeeSet;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;

class FeeSetSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(FeeSet::class);
    }

    public function it_should_properly_initialize_fees()
    {
        $fees = $this->getFees()->shouldBeAnInstanceOf(ArrayCollection::class);

        assert($fees->isEmpty());
    }

    public function it_should_properly_initialize_children()
    {
        $children = $this->getChildren()->shouldBeAnInstanceOf(ArrayCollection::class);

        assert($children->isEmpty());
    }

    public function it_has_user_specific_type()
    {
        $this->setType(FeeSet::TYPE_USER_SPECIFIC);

        $this->isUserSpecific()->shouldBe(true);
    }

    public function it_has_vip_type()
    {
        $this->setType(FeeSet::TYPE_VIP);

        $this->isVip()->shouldBe(true);
    }

    public function it_has_standard_type()
    {
        $this->setType(FeeSet::TYPE_STANDARD);

        $this->isStandard()->shouldBe(true);
    }

    public function it_has_volume_based_type()
    {
        $this->setType(FeeSet::TYPE_VOLUME_BASED);

        $this->isVolumeBased()->shouldBe(true);
    }

    public function it_has_default()
    {
        $this->setDefault(1);

        $this->isDefault()->shouldBe(true);
    }

    public function it_has_not_default()
    {
        $this->setDefault(0);

        $this->isDefault()->shouldBe(false);
    }
}
