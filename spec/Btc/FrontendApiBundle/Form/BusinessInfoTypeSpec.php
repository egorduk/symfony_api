<?php

namespace spec\Btc\FrontendApiBundle\Form;

use Btc\CoreBundle\Entity\UserBusinessInfo;
use Btc\FrontendApiBundle\Form\BusinessInfoType;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessInfoTypeSpec extends ObjectBehavior
{
    public function it_is_a_form()
    {
        $this->shouldHaveType(BusinessInfoType::class);
        $this->shouldHaveType(AbstractType::class);
    }

    public function it_build_form(FormBuilderInterface $builder)
    {
        $builder->add('companyName', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('vatId', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('registrationNumber', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('state', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('city', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('street', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('building', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('zipCode', Argument::type('string'))->willReturn($builder)->shouldBeCalled();
        $builder->add('country', Argument::exact('entity'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        $builder->add('officeNumber', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();

        for ($i = BusinessInfoType::START_INDEX_FILES; $i <= BusinessInfoType::COUNT_FILES; ++$i) {
            $builder->add('companyDetails'.$i.'Content', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
            $builder->add('companyDetails'.$i.'Name', Argument::type('string'), Argument::type('array'))->willReturn($builder)->shouldBeCalled();
        }

        $this->buildForm($builder, []);
    }

    public function it_has_data_class(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => UserBusinessInfo::class])->willReturn($resolver);

        $this->configureOptions($resolver);
    }

    public function it_has_empty_name()
    {
        $this->getName()->shouldBe('');
    }
}
