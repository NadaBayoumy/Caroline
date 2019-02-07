<?php

namespace App\DependencyInjection;

class AppExtension extends Extension implements PrependExtensionInterface {

    public function load(array $configs, ContainerBuilder $container) {
        var_dump($container);
        exit;
    }

    public function prepend(ContainerBuilder $container) {
        var_dump($container);
        exit;

        foreach ($container->getExtensionConfig('jms_serializer') as $config) {
            foreach ($config['metadata']['directories'] as $directoryKey => $directoryConfig) {
                if (strpos($directoryKey, 'custom-sylius') === 0) {

                    $container->prependExtensionConfig('jms_serializer', $config);
                    continue;
                }
            }
        }
    }

}
