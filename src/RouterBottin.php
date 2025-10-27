<?php


namespace AcMarche\Bottin;

use AcMarche\Bottin\Repository\BottinRepository;
use AcMarche\MarcheTail\Inc\Theme;
use stdClass;

class RouterBottin
{
    public const PARAM_BOTTIN_FICHE = 'slugfiche';
    public const PARAM_BOTTIN_CATEGORY = 'slugcategory';
    public const BOTTIN_FICHE_URL = 'bottin/fiche/';
    public const BOTTIN_CATEGORY_URL = 'bwp/categorie';

    public function __construct()
    {
        //   $this->flushRoutes();
        $this->addRouteBottin();
        $this->addRouteBottinCategory();
    }

    /**
     * Retourne la base du blog (/economie/, /sante/, /culture/...
     *
     *
     */
    public static function getBaseUrlSite(?int $blodId = null): string
    {
        if (is_multisite()) {
            if (!$blodId) {
                $blodId = Theme::CITOYEN;
            }

            return get_blog_details($blodId)->path;
        }

        return '/';
    }

    public function flushRoutes()
    {
        if (is_multisite()) {
            $current = get_current_blog_id();
            foreach (get_sites(['fields' => 'ids']) as $site) {
                switch_to_blog($site);
                flush_rewrite_rules();
            }
            switch_to_blog($current);
        } else {
            flush_rewrite_rules();
        }
    }

    public static function getCurrentUrl(): string
    {
        global $wp;

        return home_url($wp->request);
    }

    public static function getReferer(): string
    {
        return wp_get_referer();
    }

    public static function getUrlCategoryBottin(stdClass $category): ?string
    {
        if (self::isEconomie([$category], new BottinRepository()) !== null) {
            return self::generateCategoryUrlCap($category, new BottinRepository());
        }

        return self::getBaseUrlSite(Theme::ECONOMIE).self::BOTTIN_CATEGORY_URL.'/'.$category->slug;
    }

    public static function getUrlFicheBottin(int $blogId, stdClass $fiche): string
    {
        if ($url = self::generateFicheUrlCap($fiche)) {
            return $url;
        }

        return self::getBaseUrlSite($blogId).self::BOTTIN_FICHE_URL.$fiche->slug;
    }

    public function addRouteBottin(): void
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
                if (is_admin() || !$wp_query->is_main_query()) {
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

    public function addRouteBottinCategory(): void
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
                if (is_admin() || !$wp_query->is_main_query()) {
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

    /**
     * url pour recherche via le site de marche.
     */
    public static function generateFicheUrlCap(stdClass $fiche): ?string
    {
        $bottinRepository = new BottinRepository();
        $categories = $bottinRepository->getCategoriesOfFiche($fiche->id);
        if (self::isEconomie($categories, $bottinRepository) === null) {
            return null;
        }

        $urlBase = 'https://cap.marche.be/en_GB/commerce?id='.$fiche->id;

        return $urlBase;
    }

    /**
     * url pour recherche via le site de marche.
     */
    public static function generateCategoryUrlCap(stdClass $category, BottinRepository $bottinRepository): string
    {
        $parents = [574, 520, 609, 548, 582, 553, 527, 540, 534, 636, 568, 591];

        if (in_array($category->id, $parents)) {
            $categoryId = $category->id;
            $sousCategory = '';
        } else {
            $parent = $bottinRepository->getCategory($category->parent_id);
            $categoryId = $parent->id;
            $sousCategory = $category->id;
        }

        return "https://cap.marche.be/en_GB/liste-commercants?search=&categorie_id=$categoryId&sous_categorie_id=$sousCategory";
    }

    private static function isEconomie(array $categories, BottinRepository $bottinRepository): ?stdClass
    {
        foreach ($categories as $category) {
            if (isset($category->parent_id)) {
                $parent = $bottinRepository->getCategory($category->parent_id);
                if (in_array($parent->id, Bottin::ALL)) {
                    return $category;
                }
                if (isset($parent->parent_id)) {
                    $parent2 = $bottinRepository->getCategory($parent->parent_id);
                    if (in_array($parent2->id, Bottin::ALL)) {
                        return $category;
                    }
                }
            }
        }

        return null;
    }
}
