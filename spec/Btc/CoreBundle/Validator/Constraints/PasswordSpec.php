<?php

namespace spec\Btc\CoreBundle\Validator\Constraints;

use Btc\CoreBundle\Validator\Constraints\Password;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Validator\Constraint;

class PasswordSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Password::class);
    }

    public function it_should_be_an_constraint()
    {
        $this->shouldHaveType(Constraint::class);
    }
}
