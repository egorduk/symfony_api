<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            // 3rd party bundles
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            new \FOS\RestBundle\FOSRestBundle(),
            new \Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new Sentry\SentryBundle\SentryBundle(),
            // our bundles
            new Btc\CoreBundle\BtcCoreBundle(),
            new Btc\PaginationBundle\BtcPaginationBundle(),
            new Btc\CommonBundle\BtcCommonBundle(),
            new Exmarkets\NsqBundle\ExmarketsNsqBundle(),
            new Exmarkets\NewsBundle\ExmarketsNewsBundle(),
            // api bundles
            new Btc\FrontendApiBundle\BtcFrontendApiBundle(),
            new Btc\TradeApiBundle\BtcTradeApiBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
