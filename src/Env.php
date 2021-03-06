<?php

namespace AcMarche\Bottin;

use AcMarche\Pivot\DependencyInjection\Kernel;
use Exception;
use Symfony\Component\Dotenv\Dotenv;

class Env
{
    public static function loadEnv(): void
    {
        $dir = Kernel::getDir();
        $dotenv = new Dotenv();
        try {
            // loads .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV
            $dotenv->load($dir . '.env');
        } catch (Exception $exception) {
            echo "error load env: " . $exception->getMessage();
        }
    }
}
