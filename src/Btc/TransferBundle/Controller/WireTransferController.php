<?php

namespace Btc\TransferBundle\Controller;

use Doctrine\ORM\NoResultException;
use Doctrine\DBAL\LockMode;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WireTransferController extends Controller
{
    /**
     * @Route("/deposits", name="btc_wire_transfer_deposits")
     * @Method({"GET"})
     * @Template
     */
    public function depositsAction()
    {
        $deposits = $this->get('deposits')->findUserInternationalDeposits($this->getUser());
        return compact('deposits');
    }

    /**
     * @Route("/show/deposit/{id}", name="btc_wire_transfer_deposits_show")
     * @Method({"GET"})
     * @Template
     */
    public function showAction($id)
    {
        try {
            /** @var Deposit $deposit */
            $deposit = $this->get('deposits')->findUserWireDeposit($this->getUser(), intval($id));
        } catch (NoResultException $e) {
            throw new HttpException(404, 'Requested deposit was not found');
        }
        return compact('deposit');
    }

    /**
     * @Route("/cancel/deposit/{id}", name="btc_wire_transfer_deposits_cancel")
     * @Method({"GET"})
     * @Template
     */
    public function cancelAction($id)
    {
        try {
            $deposit = $this->get('deposits')->getUserUnconfirmedWireDeposit($this->getUser(), intval($id));
        } catch (NoResultException $e) {
            throw new HttpException(404, 'Requested deposit was not found');
        }
        // lock deposit
        $deposit = $this->get('em')->find(get_class($deposit), $id, LockMode::PESSIMISTIC_WRITE);
        if ($deposit->isNew()) {
            $deposit->canceled();
            $this->get('em')->persist($deposit);
            $this->get('em')->flush($deposit);
            $this->get('session')->getFlashBag()->add('success', 'Deposit request was cancelled.');
        } else {
            $this->get('session')->getFlashBag()->add('info', 'Deposit was already processed.');
        }
        return $this->redirect($this->generateUrl('btc_transfer_deposit_bank', ['bank' => $deposit->getBank()->getSlug()]));
    }
}
