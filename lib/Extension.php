<?php

namespace Postcon\BehatShellExtension;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Extension implements \Behat\Testwork\ServiceContainer\Extension
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'postcon_shell_extension';
    }

    /**
     * @param ExtensionManager $extensionManager
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->prototype('array')
                ->children()
                    ->enumNode('type')->values(['local', 'remote', 'docker'])->defaultValue('local')->end()
                    ->scalarNode('base_dir')->defaultNull()->end()
                    ->scalarNode('ssh_command')->defaultValue('ssh')->end()
                    ->scalarNode('scp_command')->defaultValue('scp')->end()
                    ->scalarNode('ssh_options')->defaultNull()->end()
                    ->scalarNode('ssh_hostname')->defaultNull()->end()
                    ->scalarNode('timeout')->defaultNull()->end()
                    ->scalarNode('docker_command')->defaultValue('docker')->end()
                    ->scalarNode('docker_options')->defaultNull()->end()
                    ->scalarNode('docker_containername')->defaultNull()->end()
                ->end()
            ->end();
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $definition = $container->register('postcon_shell_context_initializer', ContextInitializer::CLASS_NAME);
        $definition->addArgument($config);
        $definition->addTag(ContextExtension::INITIALIZER_TAG);
    }
}
