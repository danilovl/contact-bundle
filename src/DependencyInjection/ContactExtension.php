<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\{
    ContainerBuilder,
    Definition
};
use Symfony\Component\DependencyInjection\Extension\{
    Extension,
    PrependExtensionInterface
};
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContactExtension extends Extension implements PrependExtensionInterface
{
    public const string ALIAS = 'danilovl_contact';

    public function prepend(ContainerBuilder $container): void
    {
        $entityDirectory = __DIR__ . '/../Entity';

        $doctrineConfig = [
            'orm' => [
                'mappings' => [
                    'DanilovlContactBundle' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => $entityDirectory,
                        'prefix' => 'Danilovl\ContactBundle\Entity',
                        'alias' => 'DanilovlContact'
                    ]
                ]
            ]
        ];

        $container->prependExtensionConfig('doctrine', $doctrineConfig);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration;
        $config = $this->processConfiguration($configuration, $configs);

        $apiPrefix = mb_rtrim($config['api']['prefix'], '/');
        $dashboardPath = mb_rtrim($config['dashboard']['path'], '/');

        $container->setParameter('danilovl_contact.api.prefix', $apiPrefix);
        $container->setParameter('danilovl_contact.dashboard.path', $dashboardPath);

        $photoMaxSizeMb = (float) $config['photo']['max_size_mb'];
        $photoMaxSize = (int) round($photoMaxSizeMb * 1_048_576);

        $configDefinition = new Definition(ContactConfig::class, [
            $apiPrefix,
            $dashboardPath,
            $config['dashboard']['title'],
            $config['import']['max_file_size'],
            $config['default_owner'],
            $config['access']['mode'],
            $config['access']['admin']['enabled'],
            $config['access']['admin']['role'],
            $config['owner_property'],
            $config['phone']['default_country_code'],
            $photoMaxSize
        ]);
        $configDefinition->setPublic(false);
        $container->setDefinition(ContactConfig::class, $configDefinition);

        $configDirectory = __DIR__ . '/../Resources/config';
        $fileLocator = new FileLocator($configDirectory);
        $loader = new YamlFileLoader($container, $fileLocator);
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return self::ALIAS;
    }
}
