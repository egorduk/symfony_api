<?php

namespace Btc\TransferBundle\Form\DataTransformer;

use Btc\CoreBundle\Entity\Currency;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\ORM\EntityManager;

class CurrencyToCodeTransformer implements DataTransformerInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Transforms Currency object to code (string)
     *
     * @param Currency|null $currency
     * @return string
     */
    public function transform($currency)
    {
        if ($currency === null) {
            return '';
        }

        return $currency->getCode();
    }

    /**
     * Transforms code (string) to Currency object.
     * @param string $code
     *
     * @return Currency|null
     *
     * @throws TransformationFailedException if Currency object is not found.
     */
    public function reverseTransform($code)
    {
        if (!$code) {
            return null;
        }

        $currency = $this->em->getRepository(Currency::class)
            ->findOneBy(compact('code'));

        if ($currency === null) {
            throw new TransformationFailedException(sprintf(
                'A Currency with code "%s" does not exist!',
                $code
            ));
        }

        return $currency;
    }
}
