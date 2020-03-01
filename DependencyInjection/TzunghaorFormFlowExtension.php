<?php

namespace Tzunghaor\FormFlowBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Tzunghaor\FormFlowBundle\EventListener\AlreadyFinishedExceptionListener;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlow;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class TzunghaorFormFlowExtension extends Extension implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->loadFlows($config['flows'], $config['storage'], $container);
        $this->loadAlreadyFinishedConfig($config['redirect_already_finished'], $container);
    }

    /**
     * Registers the configured flows as services
     *
     * @param array $flows
     * @param string $storageService
     * @param ContainerBuilder $container
     */
    protected function loadFlows(array $flows, string $storageService, ContainerBuilder $container)
    {
        foreach ($flows as $flowName => $definition) {
            $flowServiceName = 'tzunghaor_form_flow.flows.' . $flowName;
            if (substr($definition, 0, 1) === '@') {
                $definitionServiceId = ltrim($definition, '@');
            } else {
                $definitionServiceId = 'tzunghaor_form_flow.definitions.' . $flowName;
                $container->setDefinition($definitionServiceId, new Definition($definition));
            }

            $flowDefinition = new Definition(FormFlow::class, [
                $flowName,
                new Reference($definitionServiceId),
                new Reference($storageService),
                new Reference('form.factory'),
                new Reference('event_dispatcher'),
                new Reference('validator'),
                new Reference('serializer'),
            ]);

            $container->setDefinition($flowServiceName, $flowDefinition);
        }
    }

    /**
     * Sets up configured default behaviour
     *
     * @param bool $redirectAlreadyFinished
     * @param ContainerBuilder $container
     */
    protected function loadAlreadyFinishedConfig(bool $redirectAlreadyFinished, ContainerBuilder $container)
    {
        if ($redirectAlreadyFinished) {
            $alreadyFinishedDefinition = new Definition(AlreadyFinishedExceptionListener::class, [
                new Reference('router.default')
            ]);
            $alreadyFinishedDefinition->addTag(
                'kernel.event_listener',
                [ 'event' => 'kernel.exception', 'method' => 'onKernelException']
            );

            $container->setDefinition(
                'tzunghaor_form_flow.event_listener.already_finished_exception_listener',
                $alreadyFinishedDefinition
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $flowFactoryDefinition = $container->getDefinition('tzunghaor_form_flow.form_flow_locator');

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->getClass() === FormFlow::class || is_subclass_of($definition->getClass(), FormFlow::class)) {
                $flowFactoryDefinition->addMethodCall('addFormFlow', [new Reference($id)]);
            }
        }
    }
}
