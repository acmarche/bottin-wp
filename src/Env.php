<?php

namespace AcMarche\Bottin;

use Exception;
use Symfony\Component\Dotenv\Dotenv;

class Env
{
    public static function loadEnv(): void
    {
        $dotenv = new Dotenv();
        try {
            // loads .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV
            $dotenv->load(ABSPATH. '.env');
        } catch (Exception $exception) {
            echo "error load env: " . $exception->getMessage();
        }
    }
}
