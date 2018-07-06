<?php

namespace spec\Btc\CoreBundle\Service;

use Btc\CoreBundle\Entity\Plan\Payment\LimitAssignment;
use Btc\CoreBundle\Entity\UserFeeSet;
use Btc\CoreBundle\Entity\Wallet;
use Btc\CoreBundle\Service\UserRegistrationService;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Btc\CoreBundle\Util\GeneratorInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Currency;
use Btc\CoreBundle\Entity\FeeSet;
use Btc\CoreBundle\Entity\Plan\Payment\LimitPlan;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;

class UserRegistrationServiceSpec extends ObjectBehavior
{
    public function let(
        EncoderFactoryInterface $encoderFactory,
        GeneratorInterface $generator,
        EntityManager $em
    ) {
        $this->beConstructedWith($encoderFactory, $generator, $em);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UserRegistrationService::class);
    }

    public function it_should_encrypt_password($encoderFactory, User $user, PasswordEncoderInterface $passwordEncoder)
    {
        $passwordEncoder
            ->encodePassword(Argument::any(), Argument::any())
            ->willReturn('EncodedPassword');
        $encoderFactory->getEncoder($user)->shouldBeCalled()->willReturn($passwordEncoder);

        $this->encryptPassword($user);
    }

    public function it_should_create_user_with_random_username_and_password(User $user, $generator, $em)
    {
        $generator->generatePassword()->shouldBeCalled()->willReturn('RandomPassword');
        $generator->generateUsername()->shouldBeCalled()->willReturn('first_rand_username');
        $this->expectSameUsernameCountQuery($em, 'first_rand_username', 0);

        $this->initUser($user);
    }

    public function it_should_create_user_with_random_username_and_password_if_user_was_found(User $user, $generator, $em)
    {
        $generator->generatePassword()->shouldBeCalled()->willReturn('RandomPassword');
        $generator->generateUsername()->shouldBeCalled()->willReturn('first_rand_username');
        $generator->generateUsername()->shouldBeCalled()->willReturn('second_rand_username');
        $this->expectSameUsernameCountQuery($em, 'first_rand_username', 1);
        $this->expectSameUsernameCountQuery($em, 'second_rand_username', 0);

        $this->initUser($user);
    }

    public function it_should_create_user_with_force_change_password_role_and_default_properties(User $user, $em, $generator)
    {
        $generator->generatePassword()->shouldBeCalled()->willReturn('RandomPassword');
        $generator->generateUsername()->shouldBeCalled()->willReturn('first_rand_username');
        $this->expectSameUsernameCountQuery($em, 'first_rand_username', 0);
        $user->setUsername('first_rand_username')->shouldBeCalled();
        $user->setPlainPassword('RandomPassword')->shouldBeCalled();
        $user->addRole(User::FORCE_CHANGE_PASSWORD)->shouldBeCalled();

        $this->initUser($user);
    }

    public function it_should_create_wallets_for_user(EntityManager $em, User $user, EntityRepository $repo)
    {
        $em->getRepository(Currency::class)->shouldBeCalled()->willReturn($repo);
        $repo->findAll()->shouldBeCalled()->willReturn($this->someCurrencies());

        $user->addWallet(Argument::type(Wallet::class))->shouldBeCalled();
        $em->persist(Argument::type(Wallet::class))->shouldBeCalled();

        $this->createWallets($em, $user);
    }

    public function it_should_assign_default_fee_set_for_user(
        EntityManager $em,
        User $user,
        EntityRepository $repo,
        FeeSet $feeSet,
        LimitPlan $plan
    ) {
        $em->getRepository(FeeSet::class)->shouldBeCalled()->willReturn($repo);
        $em->getRepository(LimitPlan::class)->shouldBeCalled()->willReturn($repo);

        $repo->findOneBy(['default' => 1])->shouldBeCalled()->willReturn($feeSet);
        $repo->findOneBy(['slug' => 'unverified'])->shouldBeCalled()->willReturn($plan);

        $em->persist(Argument::type(UserFeeSet::class))->shouldBeCalled();
        $em->persist(Argument::type(LimitAssignment::class))->shouldBeCalled();

        $this->assignDefaultPlans($user);
    }

    private function someCurrencies()
    {
        return array_map(function ($code) {
            $c = new Currency();
            $c->setCode($code);
            $c->setCrypto($code === 'BTC');

            return $c;
        }, ['USD', 'BTC', 'EUR']);
    }

    private function expectSameUsernameCountQuery(EntityManager $em, $username, $count)
    {
        $prophet = new Prophet();
        $qb = $prophet->prophesize('Doctrine\ORM\QueryBuilder');
        $q = $prophet->prophesize('Doctrine\ORM\AbstractQuery');

        $em->createQueryBuilder()->willReturn($qb->reveal());
        $qb->select('COUNT(u.id)')->willReturn($qb);
        $qb->from('BtcCoreBundle:User', 'u')->willReturn($qb);
        $qb->where('u.username = :username')->willReturn($qb);
        $qb->getQuery()->willReturn($q->reveal());

        $q->setParameters(compact('username'))->shouldBeCalled()->willReturn($q->reveal());
        $q->getSingleScalarResult()->willReturn($count);
    }
}
