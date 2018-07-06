<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\Activity;
use Btc\CoreBundle\Model\LoggableActivityInterface;
use Btc\FrontendApiBundle\Repository\ActivityRepository;

class UserActivityService
{
    private $activityRepository;

    public function __construct(ActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    /**
     * @param LoggableActivityInterface $target
     * @param string                    $eventAction
     * @param string                    $ip
     * @param array                     $params
     */
    public function log(LoggableActivityInterface $target, $eventAction, $ip, $params = [])
    {
        $activity = new Activity();
        $activity->setAction($eventAction);
        $activity->setIpAddress($ip);
        $activity->setAdditionalInfo($params);

        $target->addActivity($activity);

        $this->activityRepository->save($activity, true);
    }
}
