<?php

namespace AcMarche\Bottin\Elasticsearch\Data;

use AcMarche\Bottin\Repository\BottinRepository;

class ElasticBottinData
{
    public function __construct()
    {
        $this->bottinRepository = new BottinRepository();
    }

    public function generateUrlCapCategorie(object $category): ?string
    {
        if ($category->parent_id) {
            $parent  = $this->bottinRepository->getCategory($category->parent_id);
            $urlBase = "https://cap.marche.be/secteur/".$parent->slug."/";
            //$content = $this->getDataForCategoryByFiches($categorie);
        } else {
            $urlBase = "https://cap.marche.be/secteur/";
        }

        return $urlBase.$category->slug;
    }

    public function generateUrlCapFiche(\stdClass $fiche): ?string
    {
        $urlBase     = "https://cap.marche.be/commerces-et-entreprises/";
        $classements = $this->bottinRepository->getClassementsFiche($fiche->id);

        if (count($classements) > 0) {
            $first    = $classements[0];
            $category = $this->bottinRepository->getCategory($first['category_id']);
            $secteur  = $category->slug;

            return $urlBase.$secteur."/".$fiche->slug;
        }

        return $urlBase."/".$fiche->slug;
    }

    public function getContentFiche($fiche): string
    {
        return ' '.$fiche->societe.' '.$fiche->email.' '.$fiche->website.''.$fiche->twitter.' '.$fiche->facebook.' '.$fiche->nom.' '.$fiche->prenom.' '.$fiche->comment1.''.$fiche->comment2.' '.$fiche->comment3;
    }

    public function getContentForCategory(array $fiches): string
    {
        $content = '';

        foreach ($fiches as $fiche) {
            $content .= $this->getContentFiche($fiche);
        }

        return $content;
    }

    /**
     * @param $fiche
     *
     * @return string[]
     */
    public function getCategoriesFiche($fiche): array
    {
        $data       = $this->bottinRepository->getCategoriesOfFiche($fiche->id);
        $categories = [];
        foreach ($data as $category) {
            $categories[] = $category->name;
        }

        return $categories;
    }

    /**
     * @param $fiche
     *
     * @return string[]
     */
    public function getCategoriesFicheGetIds($fiche): array
    {
        $data       = $this->bottinRepository->getCategoriesOfFiche($fiche->id);
        $categories = [];
        foreach ($data as $category) {
            $categories[] = $category->id;
        }

        return $categories;
    }
}
