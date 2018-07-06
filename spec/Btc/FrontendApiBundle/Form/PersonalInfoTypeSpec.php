<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\CoreBundle\Entity\UserPersonalInfo;
use Btc\FrontendApiBundle\Form\PersonalInfoType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonalInfoTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(PersonalInfoType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('firstName', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('lastName', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('address', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('zipCode', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('city', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('country', Argument::exact('entity'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('birthDate', Argument::exact('date'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('phone', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('idPhotoContent', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('idPhotoName', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('residenceProofContent', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('residenceProofName', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('idBackSideContent', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('idBackSideName', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();

        $this->buildForm($builder, []);
    }

    public function it_has_data_class(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => UserPersonalInfo::class])->willReturn($resolver);

        $this->configureOptions($resolver);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
