<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Attachment;
use Btc\CoreBundle\Entity\Country;
use Btc\CoreBundle\Entity\UserPersonalInfo;
use Btc\CoreBundle\Helper\UserInfo;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UserPersonalInfoSpec extends ObjectBehavior
{
    const API_GROUP = ['api'];
    const API_GROUPS = ['api', 'api_signup'];

    use SpecValidatorTrait;

    private function initValidatorStubs()
    {
        $this->initValidator();
    }

    public function let()
    {
        $this->initValidatorStubs();
    }

    public function it_should_be_initializable()
    {
        $this->shouldHaveType(UserPersonalInfo::class);
        $this->shouldHaveType(UserInfo::class);
    }

    public function it_should_not_allow_validate_proof_of_residence_by_default()
    {
        $verification = new UserPersonalInfo();
        $verification->setResidenceProof(new Attachment());

        $violations = $this->validator->validate($verification->getResidenceProof());
        $this->shouldNotHaveViolation($violations, Argument::any());
    }

    public function it_should_not_allow_validate_id_photo_by_default()
    {
        $verification = new UserPersonalInfo();
        $verification->setIdPhoto(new Attachment());

        $violations = $this->validator->validate($verification->getIdPhoto());
        $this->shouldNotHaveViolation($violations, Argument::any());
    }

    public function it_should_not_allow_validate_id_back_side_by_default()
    {
        $verification = new UserPersonalInfo();
        $verification->setIdBackSide(new Attachment());

        $violations = $this->validator->validate($verification->getIdBackSide());
        $this->shouldNotHaveViolation($violations, Argument::any());
    }

    public function it_should_return_user_full_name_when_to_string()
    {
        $firstName = 'firstname';
        $lastName = 'lastname';
        $fullName = $firstName.' '.$lastName;

        $this->setFirstname($firstName);
        $this->setLastname($lastName);

        $this->__toString()->shouldReturn($fullName);
    }

    private function getUserPersonalInfo()
    {
        return new UserPersonalInfo();
    }

    public function it_should_not_allow_blank_first_name_and_last_name()
    {
        $userPersonalInfo = $this->getUserPersonalInfo();

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUPS);
        $this->shouldHaveViolation($violations, 'core_user.firstname.blank');
        $this->shouldHaveViolation($violations, 'core_user.lastname.blank');

        $userPersonalInfo->setFirstName('firstname');
        $userPersonalInfo->setLastName('lastname');
        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUPS);
        $this->shouldNotHaveViolation($violations, 'core_user.firstname.blank');
        $this->shouldNotHaveViolation($violations, 'core_user.lastname.blank');

        $violations = $this->validator->validate($userPersonalInfo);
        $this->shouldNotHaveViolation($violations, 'core_user.firstname.blank');
        $this->shouldNotHaveViolation($violations, 'core_user.lastname.blank');
    }

    public function it_should_check_country()
    {
        $userPersonalInfo = $this->getUserPersonalInfo();
        $userPersonalInfo->setCountry(null);

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.country.blank');

        $violations = $this->validator->validate($userPersonalInfo);
        $this->shouldNotHaveViolation($violations, 'core_user.country.blank');

        $country = new Country();
        $country->setRestricted(false);

        $userPersonalInfo->setCountry($country);

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldNotHaveViolation($violations, 'core_user.country.restricted');

        $country->setRestricted(true);

        $userPersonalInfo->setCountry($country);

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.country.restricted');

        $violations = $this->validator->validate($userPersonalInfo);
        $this->shouldNotHaveViolation($violations, 'core_user.country.restricted');
    }

    public function it_should_check_birth_date()
    {
        $userPersonalInfo = $this->getUserPersonalInfo();

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.birthdate.blank');

        $userPersonalInfo->setBirthDate(1);

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.birthdate.invalid');

        $userPersonalInfo->setBirthDate('2018-11-11');

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldNotHaveViolation($violations, 'core_user.birthdate.invalid');

        $violations = $this->validator->validate($userPersonalInfo);
        $this->shouldNotHaveViolation($violations, 'core_user.birthdate.invalid');
    }

    public function it_should_check_address()
    {
        $userPersonalInfo = $this->getUserPersonalInfo();
        $userPersonalInfo->setAddress('');

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.address.blank');

        $userPersonalInfo->setAddress('address');

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldNotHaveViolation($violations, 'core_user.address.blank');

        $violations = $this->validator->validate($userPersonalInfo);
        $this->shouldNotHaveViolation($violations, 'core_user.address.blank');
    }

    public function it_should_check_city()
    {
        $userPersonalInfo = $this->getUserPersonalInfo();
        $userPersonalInfo->setCity('');

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.city.blank');

        $userPersonalInfo->setCity('city');

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldNotHaveViolation($violations, 'core_user.city.blank');

        $violations = $this->validator->validate($userPersonalInfo);
        $this->shouldNotHaveViolation($violations, 'core_user.city.blank');
    }

    public function it_should_check_zip_code()
    {
        $userPersonalInfo = $this->getUserPersonalInfo();
        $userPersonalInfo->setZipCode('');

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.zip.blank');

        $userPersonalInfo->setZipCode('123456');

        $violations = $this->validator->validate($userPersonalInfo, self::API_GROUP);
        $this->shouldNotHaveViolation($violations, 'core_user.zip.blank');

        $violations = $this->validator->validate($userPersonalInfo);
        $this->shouldNotHaveViolation($violations, 'core_user.zip.blank');
    }

    public function it_should_check_pending_status()
    {
        $this->setStatus(UserPersonalInfo::STATUS_PENDING);

        $this->isPending()->shouldBe(true);
    }

    public function it_should_check_declined_status()
    {
        $this->setStatus(UserPersonalInfo::STATUS_DECLINED);

        $this->isDeclined()->shouldBe(true);
    }

    public function it_should_check_approved_status()
    {
        $this->setStatus(UserPersonalInfo::STATUS_APPROVED);

        $this->isApproved()->shouldBe(true);
    }

    public function it_should_check_unsubmitted_status()
    {
        $this->setStatus(UserPersonalInfo::STATUS_UNSUBMITTED);

        $this->isUnsubmitted()->shouldBe(true);
    }
}
