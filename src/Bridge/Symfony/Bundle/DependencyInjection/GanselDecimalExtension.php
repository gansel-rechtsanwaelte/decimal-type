<?php

declare(strict_types=1);

namespace Gansel\Decimal\Bridge\Symfony\Bundle\DependencyInjection;

use Gansel\Decimal\Bridge\Doctrine\DBAL\Types\DecimalType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class GanselDecimalExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $config = [];
        $config['dbal']['types'][DecimalType::NAME]['class'] = DecimalType::class;
        $config['dbal']['types'][DecimalType::NAME]['commented'] = false;

        $container->prependExtensionConfig('doctrine', $config);
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
