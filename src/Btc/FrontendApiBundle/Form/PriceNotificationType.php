<?php

namespace Btc\FrontendApiBundle\Form;

use Btc\Component\Market\Validator\Constraints\FloatValue;
use Btc\FrontendBundle\Service\MarketGroupService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PriceNotificationType extends AbstractType
{
    private $markets;

    public function __construct(MarketGroupService $markets)
    {
        $this->markets = $markets;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $markets = $this->markets->getTradingMarkets();
        $default = current($markets);
        foreach ($markets as $m) {
            if ($m->getSlug() === $this->markets->currentMarketInfo()->slug()) {
                $default = $m;
                break;
            }
        }
        $builder
            ->add('price', 'money', [
                'currency' => false,
                'precision' => 2,
                'constraints' => [new FloatValue()],
            ])
            ->add('market', 'entity', [
                'class' => 'BtcCoreBundle:Market',
                'choices' => $markets,
                'property' => 'name',
                'preferred_choices' => [$default],
                'empty_value' => false,
            ])
            ->add('email', 'text');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'Price',
            'required' => false,
            'data_class' => 'Btc\CoreBundle\Entity\PriceNotification',
            'intention' => 'price_notification',
        ]);
    }

    public function getName()
    {
        return 'price_notification_subscription';
    }
}
