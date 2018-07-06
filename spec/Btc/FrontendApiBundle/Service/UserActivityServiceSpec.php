<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\Activity;
use Btc\CoreBundle\Entity\User;
use Btc\FrontendApiBundle\Repository\ActivityRepository;
use Btc\FrontendApiBundle\Service\UserActivityService;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UserActivityServiceSpec extends ObjectBehavior
{
    public function let(ActivityRepository $activityRepository)
    {
        $this->beConstructedWith($activityRepository);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserActivityService::class);
    }

    public function it_should_log_an_event(User $user, ActivityRepository $activityRepository, EntityManager $em, Activity $activity)
    {
        $user->addActivity(Argument::type(Activity::class))->shouldBeCalled();

        $activityRepository->save(Argument::type(Activity::class), true)->shouldBeCalled();

        $this->log($user, Argument::any(), Argument::any(), []);
    }
}
