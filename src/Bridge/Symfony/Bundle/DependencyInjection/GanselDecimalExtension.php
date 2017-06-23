<?php

declare(strict_types=1);

namespace Gansel\Decimal\Bridge\Symfony\Bundle\DependencyInjection;

use Gansel\Decimal\Bridge\Doctrine\DBAL\Types\DecimalType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Gansel\Decimal\Decimal;

/**
 * @author Oliver Hoff <hoff@gansel-rechtsanwaelte.de>
 */
class GanselDecimalExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface::prepend()
     */
    public function prepend(ContainerBuilder $container)
    {
        $config = [];
        $config['dbal']['types'][DecimalType::NAME]['class'] = DecimalType::class;
        $config['dbal']['types'][DecimalType::NAME]['commented'] = false;

        $container->prependExtensionConfig('doctrine', $config);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\DependencyInjection\Extension\ExtensionInterface::load()
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
