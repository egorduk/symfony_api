<?php

namespace Btc\TradeBundle\Controller;

use Btc\PaginationBundle\Exception\UnsupportedSortingFieldException;
use Btc\PaginationBundle\Filters\PageLimitFilter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Btc\Component\Market\Model\Order;
use Btc\CoreBundle\Entity\Market;
use Btc\TradeBundle\Form\Type\CancelOrderType;
use Btc\TradeBundle\Model\CancelDeal;
use Btc\PaginationBundle\Target;

class OpenOrderController extends Controller
{
    /**
     * @Route("/open")
     * @Method({"GET"})
     * @Template()
     */
    public function openAction(Request $request)
    {
        $qb = $this->get('orders')->getUserOpenDealsQueryBuilder($this->getUser());

        $target = new Target($qb, null, [new PageLimitFilter()]);

        try {
            $orders = $this->get('paginator')->paginate($request, $target);

            $cancelOrderForms = [];
            /*
             * Maybe we could find different solution?
             * This creates high memory usage.
             */
            foreach ($orders as $order) {
                $cancelOrderForms[$order->getId()] = $this->createForm(
                    new CancelOrderType('cancel_order'),
                    new CancelDeal($order->getId()),
                    ['action' => $this->generateUrl('btc_trade_openorder_cancel')]
                )->createView();
            }
        } catch (UnsupportedSortingFieldException $e) {
            throw new HttpException(400, $e->getMessage());
        }

        return compact('orders', 'cancelOrderForms');
    }

    /**
     * @Method({"GET"})
     * @Template()
     */
    public function openOrdersSidebarAction($max = 3)
    {
        $orders = $this->get('orders')->getUserOpenOrderWithLimit($this->getUser(), 6);
        return compact('max', 'orders');
    }

    /**
     * @Route("/cancel")
     * @Method({"POST"})
     */
    public function cancelAction(Request $request)
    {
        $params = $request->request->get('cancel_order');
        if (!$order = $this->get('orders')->getUserOpenDeal($id = intval($params['id']), $user = $this->getUser())) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans(
                    'order.error.buy',
                    ['%id%' => $id, '%user%' => $user->getUsername()],
                    'Trade'
                )
            );
        }
        $deal = new Order;
        $deal->setId($id);
        $deal->setSide($order->getSide());
        $deal->setAmount($order->getAmount());
        $deal->setCurrentAmount($order->getCurrentAmount());
        $deal->setAskedUnitPrice($order->getAskedUnitPrice());
        $dealService = $this->get('btc_trade.service.deal_submission');
        $deal->setMarketSlug($order->getMarket()->getSlug());
        try {
            if ($error = $dealService->cancel($deal)) {
                $this->get('session')
                    ->getFlashBag()
                    ->add('danger', $this->get('translator')->trans($error, 'Trade'));
            } else {
                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans(
                    'flash.success.cancel_scheduled', [
                                                        '%id%' => $order->getId(),
                                                        '%side%' => strtolower($order->getSide()),
                                                      ], 'Trade'
                ));
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $this->get('translator')->trans('order.error.cancel_buy', [], 'Trade'), $e);
        }
        return $this->redirect($this->generateUrl('btc_trade_openorder_open'));
    }
}
