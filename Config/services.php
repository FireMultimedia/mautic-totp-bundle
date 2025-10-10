<?php declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;

return static function(ContainerConfigurator $configurator): void {
    $services = $configurator->services()
                             ->defaults()
                             ->autowire()
                             ->autoconfigure()
                             ->public();

    $excludes = [
        "Helper",
    ];

    $services->load("MauticPlugin\\MauticTOTPBundle\\", "../")
             ->exclude(sprintf("../{%s}", implode(",", array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes))));
};
