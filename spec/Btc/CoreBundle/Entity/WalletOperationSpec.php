<?php

namespace spec\Btc\CoreBundle\Entity;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Btc\CoreBundle\Entity\WalletOperation;
use PhpSpec\ObjectBehavior;

class WalletOperationSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(WalletOperation::class);
        $this->shouldImplement(RestEntityInterface::class);
    }

    public function it_should_be_current_creation_date()
    {
        $walletOperation = new WalletOperation();

        assert($walletOperation->getCreatedAt() == new \DateTime());
    }
}
