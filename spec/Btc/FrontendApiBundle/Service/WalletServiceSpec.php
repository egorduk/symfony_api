<?php

namespace spec\Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\User;
use Btc\CoreBundle\Entity\Wallet;
use Btc\FrontendApiBundle\Repository\BankRepository;
use Btc\FrontendApiBundle\Repository\CurrencyRepository;
use Btc\FrontendApiBundle\Repository\WalletOperationRepository;
use Btc\FrontendApiBundle\Service\RestService;
use Btc\FrontendApiBundle\Service\WalletService;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\View\View;
use JMS\Serializer\Serializer;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Form\FormFactoryInterface;

class WalletServiceSpec extends ObjectBehavior
{
    public function let(
        EntityManager $em,
        CurrencyRepository $currencyRepository,
        BankRepository $bankRepository,
        WalletOperationRepository $operationRepository,
        FormFactoryInterface $formFactory,
        Serializer $serializer,
        Wallet $wallet
    ) {
        $this->beConstructedWith($em, $currencyRepository, $bankRepository, $operationRepository, $formFactory, $serializer, $wallet, '1');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(WalletService::class);
        $this->shouldHaveType(RestService::class);
    }

    public function it_should_set_wallet_for_user(User $user, CurrencyRepository $currencyRepository)
    {
        $currencyRepository->findAll()->willReturn([]);

        $this->setWalletsForUser($user, 1)->shouldHaveType(User::class);
    }

    public function it_should_get_wallet_view(Wallet $wallet)
    {
        $this->getWalletView($wallet)->shouldHaveType(View::class);
    }
}
