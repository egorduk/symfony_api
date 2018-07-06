<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Helper\UserInfo;
use PhpSpec\ObjectBehavior;

class UserInfoSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(UserInfo::class);
    }
}
