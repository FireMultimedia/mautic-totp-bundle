<?php declare(strict_types=1);

namespace MauticPlugin\MauticTOTPBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;

use \Exception;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

use Symfony\Component\Config\FileLocator;

class MauticTOTPExtension extends Extension {

    /**
     * <h2>load</h2>
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . "/../Config"));

        $loader->load("services.php");
    }

}
