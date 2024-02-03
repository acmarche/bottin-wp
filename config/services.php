<?php

use AcMarche\Bottin\Search\SearchMeili;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {

    $services = $containerConfigurator
        ->services()
        ->defaults()
        #Automatically injects dependencies in your services
        ->autowire()
        #Automatically registers your services as commands, event subscribers, etc.
        ->autoconfigure()
        # Allows optimizing the container by removing unused services; this also means
        # fetching services directly from the container via $container->get() won't work
        ->private();

    #Makes classes in src/ available to be used as services;
    #this creates a service per class whose id is the fully-qualified class name
    $services->load('AcMarche\Bottin\\', __DIR__.'/../src/*')
             ->exclude([__DIR__.'/../src/{Entities,Tests}']);

    if (class_exists(SearchMeili::class)) {
        $services->set('searchMeiliBottin', SearchMeili::class)
            ->public();
    }

};