<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\Attachment;
use Btc\FrontendApiBundle\Classes\RestFile;
use Btc\FrontendApiBundle\Service\VerificationUploaderService;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VerificationUploaderServiceSpec extends ObjectBehavior
{
    public function let(EntityManager $em, ValidatorInterface $validator)
    {
        $this->beConstructedWith($em, '/data', $validator);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(VerificationUploaderService::class);
    }

    public function it_should_upload_file(RestFile $restFile, EntityManager $em)
    {
        $em->persist(Argument::type(Attachment::class))->shouldBeCalled();

        $this->uploadFile($restFile)->shouldHaveType(Attachment::class);
    }
}
