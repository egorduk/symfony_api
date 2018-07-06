<?php

namespace Btc\TradeApiBundle\Repository;

use Btc\CoreBundle\Entity\ApiKey;
use Btc\CoreBundle\Entity\ApiNonce;
use Doctrine\ORM\EntityRepository;

class ApiNonceRepository extends EntityRepository
{
    public function persistNonceForApiKey($nonce, ApiKey $apiKey)
    {
        $apiNonce = new ApiNonce();
        $apiNonce->setApiKey($apiKey);
        $apiNonce->setCreatedAt(new \DateTime);
        $apiNonce->setNonce($nonce);

        $this->_em->persist($apiNonce);
        $this->_em->flush();
    }

}
