<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\Country;
use Btc\CoreBundle\Entity\UserBusinessInfo;
use Btc\CoreBundle\Helper\UserInfo;
use Btc\FrontendApiBundle\Classes\SpecValidatorTrait;
use PhpSpec\ObjectBehavior;

class UserBusinessInfoSpec extends ObjectBehavior
{
    const API_GROUP = ['api'];

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
        $this->shouldHaveType(UserBusinessInfo::class);
        $this->shouldHaveType(UserInfo::class);
    }

    public function it_should_not_allow_blank_company_name_when_verifying()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.company_name.blank');
    }

    public function it_should_allow_blank_company_name_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_business_info.company_name.blank');
    }

    public function it_should_not_allow_blank_vat_id_when_verifying()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.vat_id.blank');
    }

    public function it_should_allow_blank_vat_id_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_business_info.vat_id.blank');
    }

    public function it_should_not_allow_country_to_be_blank_on_verify()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.country.blank');
    }

    public function it_should_allow_restricted_country_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_user.country.restricted');
    }

    public function it_should_not_allow_restricted_country_by_verifying()
    {
        $country = new Country();
        $country->setName('name');
        $country->setRestricted(true);

        $userBusinessInfo = new UserBusinessInfo();
        $userBusinessInfo->setCountry($country);

        $violations = $this->validator->validate($userBusinessInfo, self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_user.country.restricted');
    }

    public function it_should_not_allow_blank_registration_number_when_verifying()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.registration_number.blank');
    }

    public function it_should_allow_blank_registration_number_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_business_info.registration_number.blank');
    }

    public function it_should_not_allow_blank_state_when_verifying()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.state.blank');
    }

    public function it_should_allow_blank_state_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_business_info.state.blank');
    }

    public function it_should_not_allow_blank_city_when_verifying()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.city.blank');
    }

    public function it_should_allow_blank_city_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_business_info.city.blank');
    }

    public function it_should_not_allow_blank_building_when_verifying()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.building.blank');
    }

    public function it_should_allow_blank_building_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_business_info.building.blank');
    }

    public function it_should_not_allow_blank_zip_code_when_verifying()
    {
        $violations = $this->validator->validate(new UserBusinessInfo(), self::API_GROUP);
        $this->shouldHaveViolation($violations, 'core_business_info.zip_code.blank');
    }

    public function it_should_allow_blank_zip_code_by_default()
    {
        $violations = $this->validator->validate(new UserBusinessInfo());
        $this->shouldNotHaveViolation($violations, 'core_business_info.zip_code.blank');
    }
}
