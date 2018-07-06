<?php
namespace Btc\FrontendApiBundle\Events;

use Btc\FrontendApiBundle\Entity\CoinSubmit;
use Symfony\Component\EventDispatcher\Event;

class CoinSubmissionEvent extends Event
{
    const EVENT = 'btc_coin.submitted';

    private $submission;


    public function __construct(CoinSubmit $submission)
    {
        $this->submission = $submission;
    }

    /**
     * @return CoinSubmit
     */
    public function getSubmission()
    {
        return $this->submission;
    }
}
