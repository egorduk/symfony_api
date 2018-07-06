<?php

namespace spec\Btc\Component\Market\Util;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VoucherGeneratorSpec extends ObjectBehavior
{
    const FORMAT_VOUCHER_CODE = '/^VX\-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]{4}$/';

    function getMatchers() {
        return [
            'haveFormat' => function($value, $format) {
                    return preg_match($format, $value, $matches);
            }
        ];
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Btc\Component\Market\Util\VoucherGenerator');
    }

    function it_should_be_able_to_generate_random_code()
    {
        assert($this->generateCode() != $this->generateCode());
    }

    function it_should_generate_code_by_defined_format()
    {
        $this->generateCode()->shouldHaveFormat(self::FORMAT_VOUCHER_CODE);
    }
}
