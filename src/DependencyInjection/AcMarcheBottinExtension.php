<?php

namespace AcMarche\Bottin\DependencyInjection;

use Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AcMarcheBottinExtension extends Extension implements PrependExtensionInterface, CompilerPassInterface
{
    private PhpFileLoader $loader;

    public function load(array $configs, ContainerBuilder $containerBuilder): void
    {
        $this->loader->load('services.php');
    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $containerBuilder): void
    {
        $this->loader = $this->initPhpFilerLoader($containerBuilder);

        foreach (array_keys($containerBuilder->getExtensions()) as $name) {
            switch ($name) {
                case 'twig':

                    break;
                case 'framework':

                    break;
                case 'monolog':

                    break;
                case 'doctrine':

                    break;
            }
        }
    }

    protected function loadConfig(string $name): void
    {
        $this->loader->load('packages/'.$name.'.php');
    }

    protected function initPhpFilerLoader(ContainerBuilder $containerBuilder): PhpFileLoader
    {
        return new PhpFileLoader(
            $containerBuilder,
            new FileLocator(__DIR__.'/../../config/'),
            null,
            class_exists(ConfigBuilderGenerator::class) ? new ConfigBuilderGenerator(
                $containerBuilder->getParameter('kernel.cache_dir')
            ) : null
        );
    }

    public function process(ContainerBuilder $container)
    {

    }
}
