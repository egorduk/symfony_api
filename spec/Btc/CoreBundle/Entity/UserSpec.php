<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Model\LoggableActivityInterface;
use Btc\CoreBundle\Validator\Constraints\HotpProviderInterface;
use Btc\CoreBundle\Validator\Constraints\TotpProviderInterface;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Security\Core\User\UserInterface;

class UserSpec extends ObjectBehavior
{
    use SpecValidatorTrait;

    private function initValidatorStubs()
    {
        $this->validatorsToInit = [
            'doctrine.orm.validator.unique' => $this->stubDummyValidator(),
            'security.validator.user_password' => $this->stubDummyValidator(),
            'core_user.email.not_allowed' => $this->stubDummyValidator(),
        ];

        $this->initValidator();
    }

    public function let()
    {
        $this->initValidatorStubs();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(User::class);
        $this->shouldImplement(UserInterface::class);
        $this->shouldImplement(LoggableActivityInterface::class);
        $this->shouldImplement(TotpProviderInterface::class);
        $this->shouldImplement(HotpProviderInterface::class);
        $this->shouldImplement(RestEntityInterface::class);
        $this->shouldImplement(\Serializable::class);
    }

    public function it_should_have_mutable_auth_key_field()
    {
        $key = 'RANDOMKEYUSEDFORTOTP';
        $this->getAuthKey()->shouldReturn(null);
        $this->setAuthKey($key);
        $this->getAuthKey()->shouldReturn($key);
    }

    public function it_should_be_inactive_by_default()
    {
        $this->isActive()->shouldReturn(false);
    }

    public function it_should_properly_initialize_wallets()
    {
        $wallets = $this->getWallets()->shouldBeAnInstanceOf(ArrayCollection::class);
        assert($wallets->isEmpty());
    }

    public function it_should_properly_initialize_activities()
    {
        $activities = $this->getActivities()->shouldBeAnInstanceOf(ArrayCollection::class);
        assert($activities->isEmpty());
    }

    public function it_should_have_mutable_active_field()
    {
        $this->isActive()->shouldReturn(false);
        $this->setActive();
        $this->isActive()->shouldReturn(true);
    }

    public function it_should_not_allow_email_to_be_blank_on_signup()
    {
        $user = new User();
        $violations = $this->validator->validate($user, ['api_signup', 'api']);
        $this->shouldHaveViolation($violations, 'core_user.email.blank');
    }

    public function it_should_not_allow_invalid_emails_on_signup()
    {
        $user = new User();
        $user->setEmail('a@a');

        $violations = $this->validator->validate($user, ['api_signup', 'api']);
        $this->shouldHaveViolation($violations, 'core_user.email.invalid');
    }

    public function it_should_not_allow_restricted_emails_on_signup()
    {
        $user = new User();
        $user->setEmail('newsletter@exmarkets.com');

        $violations = $this->validator->validate($user, ['api_signup', 'api']);
        $this->shouldHaveViolation($violations, 'core_user.email.not_allowed');
    }

    public function it_should_not_allow_plain_password_to_be_blank_on_change()
    {
        $user = new User();

        $violations = $this->validator->validate($user, ['Change']);
        $this->shouldHaveViolation($violations, 'core_user.password.blank');
    }

    public function it_should_not_allow_short_password_on_change()
    {
        $user = new User();
        $user->setPlainPassword('a');

        $violations = $this->validator->validate($user, ['Change']);
        $this->shouldHaveViolation($violations, 'core_user.password.short');
    }

    public function it_should_not_allow_too_long_passwords_on_change()
    {
        $longPassword = 'a';

        for ($i = 0; $i < 5000; ++$i) {
            $longPassword .= 'a';
        }

        $user = new User();
        $user->setPlainPassword($longPassword);

        $violations = $this->validator->validate($user, ['Change']);
        $this->shouldHaveViolation($violations, 'core_user.password.long');
    }
}
