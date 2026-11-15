<?php declare(strict_types=1);

use Danilovl\ContactBundle\Service\OwnerResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $defaults = $services->defaults();
    $defaults->autowire();
    $defaults->autoconfigure();

    $excludes = [
        '../../DependencyInjection',
        '../../Dto',
        '../../Entity',
        '../../Model',
        '../../Exception',
        '../../Resources',
        '../../ContactBundle.php'
    ];

    $bundle = $services->load('Danilovl\\ContactBundle\\', '../../*');
    $bundle->exclude($excludes);

    $ownerSecurity = service('security.helper');
    $ownerSecurity->ignoreOnInvalid();

    $ownerResolver = $services->set(OwnerResolver::class);
    $ownerResolver->arg('$security', $ownerSecurity);
};
