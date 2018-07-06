<?php namespace Btc\TransferBundle;

use Btc\TransferBundle\DependencyInjection\CheckoutCompilerPass;
use Btc\TransferBundle\DependencyInjection\IpnCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * TransferBundle.
 *
 * Bundle is middleware and responsible for transfering funds to (deposit) / out (withdrawal) of the user account
 * from/to external sources.
 *
 * @package Btc\TransferBundle
 */
class BtcTransferBundle extends Bundle
{
    public function build(ContainerBuilder $containerBuilder)
    {
        parent::build($containerBuilder);
    }
}
