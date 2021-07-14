<?php


namespace AcMarche\Bottin;

use AcMarche\Common\Env;
use stdClass;

class Bottin
{
    public static function getUrlBottin(): string
    {
        Env::loadEnv();

        return $_ENV['DB_BOTTIN_URL'].'/bottin/fiches/';
    }

    public static function getUrlDocument(): string
    {
        Env::loadEnv();

        return $_ENV['DB_BOTTIN_URL'].'/bottin/documents/';
    }

    public function getImageUrl()
    {
        //  /public/bottin/fiches/
    }

    public static function getExcerpt(stdClass $fiche): string
    {
        $twig = Twig::LoadTwig();

        return $twig->render(
            'fiche/_excerpt.html.twig',
            [
                'fiche' => $fiche,
            ]
        );
    }
}
