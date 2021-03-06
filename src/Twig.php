<?php


namespace AcMarche\Bottin;

use AcMarche\Pivot\DependencyInjection\Kernel;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;

class Twig
{
    public static function LoadTwig(?string $path = null): Environment
    {
        $debug = true;
        if ( ! $path) {
            $path = get_template_directory().'/templates';
        }

        $loader = new FilesystemLoader($path);
        $dir = Kernel::getDir();

        $environment = new Environment(
            $loader,
            [
                'cache'            => $dir.'var/cache',
                'debug'            => $debug,
                'strict_variables' => $debug,
            ]
        );

        if ($debug) {
            $environment->addExtension(new DebugExtension());
        }
        $environment->addExtension(new StringExtension());

        $environment->addGlobal('template_directory', get_template_directory_uri());

        return $environment;
    }


}
