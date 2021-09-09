<?php


namespace AcMarche\Bottin;

use stdClass;

class Bottin
{
    public const COMMERCES = 610;
    public const LIBERALES = 591;
    public const PHARMACIES = 390;
    public const ECO = 511;
    public const SANTECO = 636;

    public const ALL = [self::COMMERCES,self::LIBERALES,self::PHARMACIES,self::ECO,self::SANTECO];

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
