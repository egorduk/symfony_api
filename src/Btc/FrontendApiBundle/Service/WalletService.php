<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Wallet;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

class WalletService extends RestService
{
    const CRYPTO_AMOUNT = 100;
    const NOT_CRYPTO_AMOUNT = 20000;

    private $currencyRepository;
    private $formFactory;
    private $bankRepository;
    private $operationRepository;
    private $em;
    private $serializer;
    private $depositOnRegistration = false;

    public function __construct(
        EntityManager $em,
        ObjectRepository $currencyRepository,
        ObjectRepository $bankRepository,
        ObjectRepository $operationRepository,
        FormFactoryInterface $formFactory,
        Serializer $serializer,
        $entityClass,
        $depositOnRegistration
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->formFactory = $formFactory;
        $this->bankRepository = $bankRepository;
        $this->operationRepository = $operationRepository;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->depositOnRegistration = $depositOnRegistration;

        parent::__construct($em, $entityClass);
    }

    public function setWalletsForUser(User $user, $deposit = null)
    {
        $currencies = $this->currencyRepository->findAll();

        if (is_null($deposit)) {
            $deposit = $this->depositOnRegistration;
        }

        foreach ($currencies as $currency) {
            $wallet = new Wallet();
            $wallet->setCurrency($currency);

            if ($deposit) {
                $depositAmount = $currency->isCrypto() ? self::CRYPTO_AMOUNT : self::NOT_CRYPTO_AMOUNT;

                $wallet->setAmountTotal($depositAmount);
                $wallet->setAmountAvailable($depositAmount);
            }

            $user->addWallet($wallet);
        }

        return $user;
    }

    public function getWalletView(Wallet $wallet)
    {
        $serializedData = $this->serializer->serialize($wallet, 'json', SerializationContext::create()->setGroups(['api']));
        $wallet = $this->serializer->deserialize($serializedData, Wallet::class, 'json');

        return new View([
            'wallet' => $wallet,
        ], Response::HTTP_OK);
    }
}
