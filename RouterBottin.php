<?php


namespace AcMarche\Bottin;

use AcMarche\Common\Router;
use AcMarche\Theme\Inc\Theme;

class RouterBottin extends Router
{
    const PARAM_BOTTIN_FICHE = 'slugfiche';
    const PARAM_BOTTIN_CATEGORY = 'slugcategory';
    const BOTTIN_FICHE_URL = 'bottin/fiche/';
    const BOTTIN_CATEGORY_URL = 'bwp/categorie';

    public function __construct()
    {
        //   $this->flushRoutes();
        $this->addRouteBottin();
        $this->addRouteBottinCategory();
    }

    public static function getUrlCategoryBottin(\stdClass $category): string
    {
        return self::getBaseUrlSite(Theme::ECONOMIE).self::BOTTIN_CATEGORY_URL.'/'.$category->slug;
    }

    public static function getUrlFicheBottin(\stdClass $fiche): string
    {
        $url = self::getBaseUrlSite(Theme::ECONOMIE).self::BOTTIN_FICHE_URL.$fiche->slug;
      //  $who = \json_encode(debug_backtrace());
    //    Mailer::sendError("404 url fiche: ", $fiche->societe.' \n qurl: '.$url.'who '.$who);

        return $url;
    }

    public function addRouteBottin()
    {
        add_action(
            'init',
            function () {
                add_rewrite_rule(
                    self::BOTTIN_FICHE_URL.'([a-zA-Z0-9-]+)[/]?$',
                    'index.php?'.self::PARAM_BOTTIN_FICHE.'=$matches[1]',
                    'top'
                );
            }
        );
        add_filter(
            'query_vars',
            function ($query_vars) {
                $query_vars[] = self::PARAM_BOTTIN_FICHE;

                return $query_vars;
            }
        );
        add_action(
            'template_include',
            function ($template) {
                global $wp_query;
                if (is_admin() || ! $wp_query->is_main_query()) {
                    return $template;
                }

                if (get_query_var(self::PARAM_BOTTIN_FICHE) == false ||
                    get_query_var(self::PARAM_BOTTIN_FICHE) == '') {
                    return $template;
                }

                return get_template_directory().'/single-bottin_fiche.php';
            }
        );
    }

    public function addRouteBottinCategory()
    {
        add_action(
            'init',
            function () {
                add_rewrite_rule(
                    self::BOTTIN_CATEGORY_URL.'/([a-zA-Z0-9-]+)[/]?$',
                    'index.php?'.self::PARAM_BOTTIN_CATEGORY.'=$matches[1]',
                    'top'
                );
            }
        );
        add_filter(
            'query_vars',
            function ($query_vars) {
                $query_vars[] = self::PARAM_BOTTIN_CATEGORY;

                return $query_vars;
            }
        );
        add_action(
            'template_include',
            function ($template) {
                global $wp_query;
                if (is_admin() || ! $wp_query->is_main_query()) {
                    return $template;
                }

                if (get_query_var(self::PARAM_BOTTIN_CATEGORY) == false ||
                    get_query_var(self::PARAM_BOTTIN_CATEGORY) == '') {
                    return $template;
                }

                return get_template_directory().'/category_bottin.php';
            }
        );
    }
}
