<?php

namespace Btc\FrontendApiBundle\Entity;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="coin_submission")
 */
class CoinSubmit implements RestEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ORM\Column()
     */
    private $blockchain;

    /**
     * @ORM\Column(nullable=true)
     */
    private $icoTokenPrice;

    /**
     * @ORM\Column()
     */
    private $isListingToken;

    /**
     * @ORM\Column()
     */
    private $projectLink;

    /**
     * @ORM\Column()
     */
    private $representativeEmail;

    /**
     * @ORM\Column()
     */
    private $representativeName;

    /**
     * @ORM\Column()
     */
    private $representativePosition;

    /**
     * @ORM\Column(nullable=true)
     */
    private $saleEnd;
    /**
     * @ORM\Column(nullable=true)
     */
    private $saleEndTime;
    /**
     * @ORM\Column(nullable=true)
     */
    private $saleStart;
    /**
     * @ORM\Column()
     */
    private $saleStartTime;
    /**
     * @ORM\Column()
     */
    private $socialThreads;
    /**
     * @ORM\Column()
     */
    private $tokenName;
    /**
     * @ORM\Column(nullable=true)
     */
    private $tokenSupply;
    /**
     * @ORM\Column(nullable=true)
     */
    private $tokenTicker;

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getBlockchain()
    {
        return $this->blockchain;
    }

    /**
     * @param mixed $blockchain
     */
    public function setBlockchain($blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * @return mixed
     */
    public function getIcoTokenPrice()
    {
        return $this->icoTokenPrice;
    }

    /**
     * @param mixed $icoTokenPrice
     */
    public function setIcoTokenPrice($icoTokenPrice)
    {
        $this->icoTokenPrice = $icoTokenPrice;
    }

    /**
     * @return mixed
     */
    public function getisListingToken()
    {
        return $this->isListingToken;
    }

    /**
     * @param mixed $isListingToken
     */
    public function setIsListingToken($isListingToken)
    {
        $this->isListingToken = $isListingToken;
    }

    /**
     * @return mixed
     */
    public function getProjectLink()
    {
        return $this->projectLink;
    }

    /**
     * @param mixed $projectLink
     */
    public function setProjectLink($projectLink)
    {
        $this->projectLink = $projectLink;
    }

    /**
     * @return mixed
     */
    public function getRepresentativeEmail()
    {
        return $this->representativeEmail;
    }

    /**
     * @param mixed $representativeEmail
     */
    public function setRepresentativeEmail($representativeEmail)
    {
        $this->representativeEmail = $representativeEmail;
    }

    /**
     * @return mixed
     */
    public function getRepresentativeName()
    {
        return $this->representativeName;
    }

    /**
     * @param mixed $representativeName
     */
    public function setRepresentativeName($representativeName)
    {
        $this->representativeName = $representativeName;
    }

    /**
     * @return mixed
     */
    public function getRepresentativePosition()
    {
        return $this->representativePosition;
    }

    /**
     * @param mixed $representativePosition
     */
    public function setRepresentativePosition($representativePosition)
    {
        $this->representativePosition = $representativePosition;
    }

    /**
     * @return mixed
     */
    public function getSaleEnd()
    {
        return $this->saleEnd;
    }

    /**
     * @param mixed $saleEnd
     */
    public function setSaleEnd($saleEnd)
    {
        $this->saleEnd = $saleEnd;
    }

    /**
     * @return mixed
     */
    public function getSaleEndTime()
    {
        return $this->saleEndTime;
    }

    /**
     * @param mixed $saleEndTime
     */
    public function setSaleEndTime($saleEndTime)
    {
        $this->saleEndTime = $saleEndTime;
    }

    /**
     * @return mixed
     */
    public function getSaleStart()
    {
        return $this->saleStart;
    }

    /**
     * @param mixed $saleStart
     */
    public function setSaleStart($saleStart)
    {
        $this->saleStart = $saleStart;
    }

    /**
     * @return mixed
     */
    public function getSaleStartTime()
    {
        return $this->saleStartTime;
    }

    /**
     * @param mixed $saleStartTime
     */
    public function setSaleStartTime($saleStartTime)
    {
        $this->saleStartTime = $saleStartTime;
    }

    /**
     * @return mixed
     */
    public function getSocialThreads()
    {
        return $this->socialThreads;
    }

    /**
     * @param mixed $socialThreads
     */
    public function setSocialThreads($socialThreads)
    {
        $this->socialThreads = $socialThreads;
    }

    /**
     * @return mixed
     */
    public function getTokenName()
    {
        return $this->tokenName;
    }

    /**
     * @param mixed $tokenName
     */
    public function setTokenName($tokenName)
    {
        $this->tokenName = $tokenName;
    }

    /**
     * @return mixed
     */
    public function getTokenSupply()
    {
        return $this->tokenSupply;
    }

    /**
     * @param mixed $tokenSupply
     */
    public function setTokenSupply($tokenSupply)
    {
        $this->tokenSupply = $tokenSupply;
    }

    /**
     * @return mixed
     */
    public function getTokenTicker()
    {
        return $this->tokenTicker;
    }

    /**
     * @param mixed $tokenTicker
     */
    public function setTokenTicker($tokenTicker)
    {
        $this->tokenTicker = $tokenTicker;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
