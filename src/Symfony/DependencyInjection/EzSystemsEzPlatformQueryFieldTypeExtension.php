<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformQueryFieldType\Symfony\DependencyInjection;

use EzSystems\EzPlatformQueryFieldType\Controller\QueryFieldController;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class EzSystemsEzPlatformQueryFieldTypeExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('fieldtypes.yml');
        $loader->load('indexable_fieldtypes.yml');
        $loader->load('field_value_converters.yml');
        $loader->load('graphql.yml');
        $loader->load('services.yml');

        $this->setContentViewConfig($container);
    }

    public function prepend(ContainerBuilder $container)
    {
        // Do we still need this ? Not in v3, for sure.
        $container->prependExtensionConfig('assetic', ['bundles' => ['EzSystemsEzPlatformQueryFieldTypeBundle']]);

        $configFile = __DIR__ . '/../Resources/config/field_templates.yml';
        $config = Yaml::parse(file_get_contents($configFile));
        $container->prependExtensionConfig('ezpublish', $config);
        $container->addResource(new FileResource($configFile));

        $configFile = __DIR__ . '/../Resources/config/field_templates_ui.yml';
        $config = Yaml::parse(file_get_contents($configFile));
        $container->prependExtensionConfig('ezpublish', $config);
        $container->addResource(new FileResource($configFile));
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function setContentViewConfig(ContainerBuilder $container): void
    {
        $contentViewDefaults = $container->getParameter('ezsettings.default.content_view_defaults');
        $contentViewDefaults['query_field'] = [
            'default' => [
                'controller' => QueryFieldController::class . ':renderQueryFieldAction',
                'template' => 'EzSystemsEzPlatformQueryFieldTypeBundle::query_field_view.html.twig',
                'match' => [],
            ],
        ];
        $container->setParameter('ezsettings.default.content_view_defaults', $contentViewDefaults);
    }
}