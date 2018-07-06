<?php

namespace Btc\TransferBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IpnController extends Controller
{
    /**
     * Endpoint for receiving IPN notifications from Gateways
     *
     * @Route("/ipn/{gateway}/{tx}")
     * @Method({"POST"})
     */
    public function receiveAction(Request $request, $gateway, $tx)
    {
        $gateway = strtolower($gateway);
        $doctrine = $this->getDoctrine();
        $bankRepository = $doctrine->getRepository('BtcCoreBundle:Bank');

        if (!($bank = $bankRepository->findOneBySlugFiat($gateway))) {
            throw new HttpException(400, 'Bad Request');
        }

        // egopay is currently disabled
        if ($bank->getSlug() === 'egopay') {
            throw new HttpException(403, 'Egopay is currently disabled.');
        }

        $deposit = $doctrine->getRepository('BtcCoreBundle:Deposit')->findOneBy(
            ['bank' => $bank, 'id' => $tx, 'status' => 'new']);

        if (!$deposit) {
            throw new HttpException(400, 'Bad Request');
        }

        // gateway
        try {
            $ipnHandler = $this->get('exm_payment_core.gateway.ipn_handler')->make($gateway);

            $ipnHandler->accept($deposit, $request->request->all());
            $em = $doctrine->getManager();
            $em->persist($deposit);
            $em->flush();

            if ($this->isDepositSuccessful($deposit) && $this->isGatewayAutoCompletable($gateway)) {
                $this->get('logger')->info('Auto-confirming deposit #' . $deposit->getId());
                $this->get('exm_payment_core.gateway.service.deposit_confirmation')
                    ->confirm($deposit);
            }

        } catch (\Exception $e) {
            $this->get('logger')->error("IPN Handler: {$e->getMessage()}");
            throw new \HttpException(500, "Internal server error");
        }

        return new Response('');
    }

    /**
     * @param $deposit
     * @return bool
     */
    private function isDepositSuccessful($deposit)
    {
        return $deposit->isApproving() && strtolower($deposit->getForeignStatus()) == 'completed';
    }

    /**
     * @param $gateway
     * @return bool
     */
    private function isGatewayAutoCompletable($gateway)
    {
        return in_array($gateway, ['egopay', 'okpay', 'perfect-money']);
    }
}
