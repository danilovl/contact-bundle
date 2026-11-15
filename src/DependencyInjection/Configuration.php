<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(ContactExtension::ALIAS);
        $root = $treeBuilder->getRootNode();

        $root
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')->defaultValue('/danilovl/contact/api')->end()
                    ->end()
                ->end()
                ->arrayNode('dashboard')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')->defaultValue('/danilovl/contact')->end()
                        ->scalarNode('title')->defaultValue('Contacts')->end()
                    ->end()
                ->end()
                ->arrayNode('import')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_file_size')->defaultValue(20 * 1_024 * 1_024)->min(1)->end()
                    ->end()
                ->end()
                ->arrayNode('photo')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->floatNode('max_size_mb')->defaultValue(1.0)->min(0.05)->end()
                    ->end()
                ->end()
                ->scalarNode('default_owner')->defaultValue('default')->end()
                ->scalarNode('owner_property')
                    ->defaultNull()
                    ->validate()
                        ->ifTrue(static function (mixed $value): bool {
                            if ($value === null) {
                                return false;
                            }

                            return !is_string($value) || preg_match('~^[a-z][a-z0-9_]*$~i', $value) !== 1;
                        })
                        ->thenInvalid('Property name must contain letters, digits and underscores only.')
                    ->end()
                ->end()
                ->arrayNode('phone')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_country_code')
                            ->defaultNull()
                            ->beforeNormalization()
                                ->ifTrue(static function (mixed $value): bool {
                                    return is_string($value) || is_int($value);
                                })
                                ->then(static function (int|string $value): ?string {
                                    $digits = mb_ltrim((string) $value, '+');

                                    return $digits === '' ? null : $digits;
                                })
                            ->end()
                            ->validate()
                                ->ifTrue(static function (mixed $value): bool {
                                    if ($value === null) {
                                        return false;
                                    }

                                    return !is_string($value) || preg_match('~^[1-9]\d{0,2}$~', $value) !== 1;
                                })
                                ->thenInvalid('Country calling code must contain 1-3 digits.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('access')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('mode')
                            ->values([ContactConfig::ACCESS_OWNER, ContactConfig::ACCESS_SHARED])
                            ->defaultValue(ContactConfig::ACCESS_OWNER)
                        ->end()
                        ->arrayNode('admin')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                                ->scalarNode('role')->defaultValue('ROLE_ADMIN')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
