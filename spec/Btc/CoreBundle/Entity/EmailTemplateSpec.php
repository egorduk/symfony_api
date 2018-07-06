<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\EmailTemplate;
use PhpSpec\ObjectBehavior;

class EmailTemplateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EmailTemplate::class);
    }
}
