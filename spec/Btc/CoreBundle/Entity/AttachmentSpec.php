<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Attachment;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use PhpSpec\ObjectBehavior;

class AttachmentSpec extends ObjectBehavior
{
    use SpecValidatorTrait;

    public function let()
    {
        $this->initValidator();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Attachment::class);
    }

    public function it_should_not_allow_blank_file_when_verifying()
    {
        $violations = $this->validator->validate(new Attachment(), ['Verify']);

        $this->shouldHaveViolation($violations, 'core_attachment.file.blank');
    }
}
