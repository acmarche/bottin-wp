<?php

namespace AcMarche\Bottin\DependencyInjection;

use AcMarche\Bottin\Search\SearchMeili;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;

class BottinContainer
{
    private ContainerInterface $container;

    public function __construct(bool $debug = false)
    {
        $this->init($debug);
    }

    private function init(bool $debug = false): void
    {
        if ($debug) {
            Debug::enable();
            $env = 'dev';
        } else {
            $env = 'prod';
        }

        //todo try
        $containerBuilder = new ContainerBuilder();

        $kernel = new Kernel($env, $debug);
        (new Dotenv())
            ->bootEnv($kernel->getProjectDir().'/.env');

        $kernel->boot();

        $this->container = $kernel->getContainer();
    }

    public function getService(string $service): ?object
    {
        if ($this->container->has($service)) {
            return $this->container->get($service);
        }

        return null;
    }

    public static function getSearchMeili(bool $debug = false): SearchMeili
    {
        $container = new self($debug);

        /**
         * @var SearchMeili
         */
        return $container->getService('searchMeiliBottin');
    }
}
