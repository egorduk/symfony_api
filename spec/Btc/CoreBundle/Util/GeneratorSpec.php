<?php

namespace spec\Btc\CoreBundle\Util;

use Btc\CoreBundle\Util\Generator;
use Btc\CoreBundle\Util\GeneratorInterface;
use PhpSpec\ObjectBehavior;

class GeneratorSpec extends ObjectBehavior
{
    const FORMAT_LETTER_AND_SIX_DIGITS = '/^[A-Z][0-9]{6}$/';
    const FORMAT_ALPHANUMERIC_WITH_CAPITALS = '/^[A-Za-z0-9]{12}$/';

    public function it_is_initializable()
    {
        $this->shouldHaveType(Generator::class);
        $this->shouldHaveType(GeneratorInterface::class);
    }

    public function it_should_be_able_to_generate_random_username()
    {
        assert($this->generateUsername() != $this->generateUsername());
        $this->generateUsername()->shouldBeString();
    }

    public function it_should_generate_username_starting_with_a_letter_and_six_digits()
    {
        $this->generateUsername()->shouldMatch(self::FORMAT_LETTER_AND_SIX_DIGITS);
    }

    public function it_should_be_able_to_generate_random_password()
    {
        assert($this->generatePassword() != $this->generatePassword());
    }

    public function it_should_generate_password_containing_alphanumeric_and_capital_letters()
    {
        $this->generatePassword()->shouldMatch(self::FORMAT_ALPHANUMERIC_WITH_CAPITALS);
    }
}
