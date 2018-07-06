<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\FrontendApiBundle\Service\RestService;
use Btc\FrontendApiBundle\Service\RestServiceInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RestServiceSpec extends ObjectBehavior
{
    const ID_FAKE = 1;

    public function let(EntityManager $em, RestEntityInterface $entityClass, EntityRepository $entityRepository)
    {
        $em->getRepository(Argument::type(RestEntityInterface::class))->willReturn($entityRepository);

        $this->beConstructedWith($em, $entityClass);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RestService::class);
        $this->shouldImplement(RestServiceInterface::class);
    }

    public function it_has_get_object(RestEntityInterface $entityClass, EntityRepository $entityRepository)
    {
        $entityRepository->find(Argument::any())->willReturn($entityClass)->shouldBeCalled();

        $this->get(self::ID_FAKE);
    }

    public function it_has_get_one_object_by(RestEntityInterface $entityClass, EntityRepository $entityRepository)
    {
        $entityRepository->findOneBy(Argument::type('array'))->willReturn($entityClass)->shouldBeCalled();

        $this->getOneBy([]);
    }

    public function it_has_get_all(RestEntityInterface $entityClass, EntityRepository $entityRepository)
    {
        $entityRepository->findBy(Argument::type('array'), Argument::any(), Argument::any(), Argument::any())->willReturn($entityClass)->shouldBeCalled();

        $this->all(10, 0);
    }
}
