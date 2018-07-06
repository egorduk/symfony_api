<?php

namespace Btc\FrontendApiBundle\Repository;

use Btc\CoreBundle\Entity\PhoneVerification;
use Btc\CoreBundle\Entity\User;

interface PhoneVerificationRepositoryInterface
{
    public function findNotConfirmedByUser(User $user);
    public function findRequestedByUserAndPin(User $user, $pin);
    public function isVerified(User $user);
    public function findConfirmed(User $user);
    public function deleteAllUserVerifications(User $user);
    public function save(PhoneVerification $phoneVerification);
}
