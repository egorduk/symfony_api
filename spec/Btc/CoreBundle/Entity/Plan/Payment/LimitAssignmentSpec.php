<?php

namespace spec\Btc\CoreBundle\Entity\Plan\Payment;

use Btc\CoreBundle\Entity\Plan\Assignment;
use Btc\CoreBundle\Entity\Plan\Payment\LimitAssignment;
use Btc\CoreBundle\Entity\Plan\Plan;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use PhpSpec\ObjectBehavior;

class LimitAssignmentSpec extends ObjectBehavior
{
    use SpecValidatorTrait;

    public function let()
    {
        $this->initValidator();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(LimitAssignment::class);
        $this->shouldImplement(Assignment::class);
    }

    public function it_should_update_fallbackplan_if_it_was_not_set(Plan $fallback)
    {
        $this->setFallbackPlan($fallback);
        $this->getFallbackPlan()->shouldBe($fallback);
    }

    public function it_should_update_fallbackplan_if_it_is_better(Plan $fallback, Plan $oldFallback)
    {
        $oldFallback->getWeight()->willReturn(10);
        $fallback->getWeight()->willReturn(100);
        $this->setFallbackPlan($oldFallback);
        $this->setFallbackPlan($fallback);
        $this->getFallbackPlan()->shouldBe($fallback);
    }

    public function it_should_not_update_fallbackplan_if_it_is_worse(Plan $fallback, Plan $oldFallback)
    {
        $oldFallback->getWeight()->willReturn(100);
        $fallback->getWeight()->willReturn(10);
        $this->setFallbackPlan($oldFallback);
        $this->setFallbackPlan($fallback);
        $this->getFallbackPlan()->shouldBe($oldFallback);
    }

    public function it_should_assign_new_plan(Plan $new)
    {
        $new->isDurable()->willReturn(false);
        $new->expirationDate()->willReturn(null);

        $this->setPlan($new);
        $this->getPlan()->shouldBe($new);
        $this->getExpiresAt()->shouldBe(null);
    }

    public function it_should_not_assign_worse_plan(Plan $old, Plan $new)
    {
        $new->isDurable()->willReturn(false);
        $old->isDurable()->willReturn(false);
        $old->getWeight()->willReturn(100);
        $old->expirationDate()->willReturn(null);
        $new->getWeight()->willReturn(10);
        $new->expirationDate()->willReturn($dt = new \DateTime());

        $this->setPlan($old);
        $this->setPlan($new);
        $this->getPlan()->shouldBe($new);

        $this->getFallbackPlan()->shouldBe(null);
    }

    public function it_should_set_fallback_plan_if_durable(Plan $old, Plan $new)
    {
        $old->getWeight()->willReturn(10);
        $old->expirationDate()->willReturn(null);
        $old->isDurable()->willReturn(false);
        $new->getWeight()->willReturn(100);
        $new->isDurable()->willReturn(true);
        $new->expirationDate()->willReturn($dt = new \DateTime());

        $this->setPlan($old);
        $this->setPlan($new);
        $this->getPlan()->shouldBe($new);
        $this->getFallbackPlan()->shouldBe($old);
        $this->getExpiresAt()->shouldBe($dt);
    }

    public function it_should_reset_fallback_plan_if_new_plan_is_not_durable(Plan $old, Plan $new)
    {
        $old->getWeight()->willReturn(10);
        $old->expirationDate()->willReturn(null);
        $old->isDurable()->willReturn(false);
        $new->getWeight()->willReturn(100);
        $new->isDurable()->willReturn(false);
        $new->expirationDate()->willReturn(null);

        $this->setPlan($old);
        $this->setPlan($new);
        $this->getPlan()->shouldBe($new);
        $this->getFallbackPlan()->shouldBe(null);
        $this->getExpiresAt()->shouldBe(null);
    }
}
