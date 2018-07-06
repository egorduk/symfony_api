<?php

namespace spec\Btc\FrontendApiBundle\Pagination;

use Btc\FrontendApiBundle\Pagination\FilterInterface;
use Btc\FrontendApiBundle\Pagination\TransactionsWithinDaysFilter;
use Doctrine\ORM\QueryBuilder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;

class TransactionsWithinDaysFilterSpec extends ObjectBehavior
{
    public function it_should_implement_filter_interface()
    {
        $this->shouldHaveType(TransactionsWithinDaysFilter::class);
        $this->shouldImplement(FilterInterface::class);
    }

    public function it_should_determine_available_active_option()
    {
        $this->active(['days' => '7'])->shouldBe(7);
    }

    public function it_should_fallback_to_null_if_option_is_not_recognized()
    {
        $this->active(['days' => '28'])->shouldBe(null);
    }

    public function it_should_fallback_to_null_if_option_is_not_set()
    {
        $this->active([])->shouldBe(null);
    }

    public function it_should_apply_filter(ParameterBag $params, QueryBuilder $qb)
    {
        $qb->andWhere('d.updatedAt >= :date')->shouldBeCalled()->willReturn($qb);
        $qb->setParameter('date', Argument::that(function (\DateTime $date) {
            return true; // todo: would be cool to check it somehow
        }))->shouldBeCalled();

        $params->all()->shouldBeCalled()->willReturn(['days' => '7']);

        $this->apply($qb, $params);
    }

    public function it_should_not_apply_filter_when_days_are_null(ParameterBag $params, QueryBuilder $qb)
    {
        $qb->andWhere('t.completedAt >= :date')->shouldNotBeCalled();
        $qb->setParameter('date')->shouldNotBeCalled();

        $params->all()->shouldBeCalled()->willReturn(['days' => '0']);

        $this->apply($qb, $params);
    }
}
