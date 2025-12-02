<?php

namespace AcMarche\Bottin\SearchData\Data;

use AcMarche\Bottin\Bottin;
use AcMarche\Bottin\Mailer;
use AcMarche\Bottin\Repository\BottinRepository;
use AcMarche\Bottin\RouterBottin;
use AcMarche\Theme\Inc\RouterMarche;
use AcMarche\Theme\Inc\Theme;
use AcMarche\Theme\Lib\WpRepository;
use AcMarche\UrbaWeb\Entity\Permis;
use BottinCategoryMetaBox;
use Symfony\Component\Console\Style\SymfonyStyle;
use WP_Post;

class ElasticData
{
    private BottinRepository $bottinRepository;
    private WpRepository $wpRepository;
    private ElasticBottinData $bottinData;

    public function __construct()
    {
        $this->bottinRepository = new BottinRepository();
        $this->wpRepository = new WpRepository();
        $this->bottinData = new ElasticBottinData();
    }

    /**
     * @param int $siteId
     *
     * @return DocumentElastic[]
     */
    public function getCategoriesBySite(int $siteId): array
    {
        $args = array(
            'type' => 'post',
            'child_of' => 0,
            'parent' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => 0,
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'number' => '',
            'taxonomy' => 'category',
            'pad_counts' => true,
        );

        $categories = get_categories($args);
        $datas = [];
        $today = new \DateTime();

        foreach ($categories as $category) {

            $description = '';
            if ($category->description) {
                $description = Cleaner::cleandata($category->description);
            }

            $date = $today->format('Y-m-d');
            $content = $description;

            foreach ($this->getPosts($category->cat_ID, $siteId) as $documentElastic) {
                $content .= $documentElastic->name;
                $content .= $documentElastic->excerpt;
                $content .= $documentElastic->content;
            }

            $content .= $this->getContentFiches($category);
            $content .= $this->getContentEnquetes($category->cat_ID);
            $content .= $this->getPublications($category);
            if ($siteId === Theme::ADMINISTRATION) {
                if ($category->cat_ID === 77) {

                }
            }

            $children = $this->wpRepository->getChildrenOfCategory($category->cat_ID);
            $tags = [];
            foreach ($children as $child) {
                $tags[] = $child->name;
            }
            $parent = $this->wpRepository->getParentCategory($category->cat_ID);
            if ($parent) {
                $tags[] = $parent->name;
            }

            $document = new DocumentElastic();
            $document->id = $category->cat_ID;
            $document->name = Cleaner::cleandata($category->name);
            $document->excerpt = $description;
            $document->content = $content;
            $document->tags = $tags;
            $document->date = $date;
            $document->type = 'catégorie';
            $document->url = get_category_link($category->cat_ID);

            $datas[] = $document;
        }

        return $datas;
    }

    public function getContentEnquetes(int $categoryId): string
    {
        $content = '';
        if (get_current_blog_id() == Theme::ADMINISTRATION) {
            if ($categoryId == Theme::ENQUETE_DIRECTORY_URBA) {
                /*  foreach (Urba::getEnquetesPubliques() as $permis) {
                      $document = $this->createDocumentElasticFromPermis($permis);
                      $content  .= $document->name.' '.$document->excerpt.' '.$document->content;
                  }*/
                foreach (WpRepository::getEnquetesPubliques($categoryId) as $permis) {
                    $document = $this->createDocumentElasticFromEnquete($permis);
                    $content .= $document->name.' '.$document->excerpt.' '.$document->content;
                }
            }
        }

        return $content;
    }

    /**
     * @return array|DocumentElastic[]
     */
    public function getEnquetesDocumentElastic(): array
    {
        $data = [];
        /*foreach (Urba::getEnquetesPubliques() as $permis) {
            $data[] = $this->createDocumentElasticFromPermis($permis);
        }*/
        foreach (WpRepository::getEnquetesPubliques() as $enquete) {
            $data[] = $this->createDocumentElasticFromEnquete($enquete);
        }

        return $data;
    }

    private function createDocumentElasticFromEnquete(\stdClass $enquete): DocumentElastic
    {
        $categorieEnqueteNom = '';
        if ($categorieEnquete = WpRepository::getCategoryEnquete()) {
            $categorieEnqueteNom = $categorieEnquete->name;
        }

        $content = $categorieEnqueteNom.' '.$enquete->intitule.' '.$enquete->demandeur.' à '.$enquete->localite.' '.$enquete->description;

        $document = new DocumentElastic();
        $document->id = $enquete->id;
        $document->name = $enquete->intitule;
        $document->excerpt = $enquete->demandeur.' à '.$enquete->localite;
        $document->content = $content;
        $document->tags = [];//todo
        $document->date = $enquete->date_debut;
        $document->url = RouterMarche::getUrlEnquete($enquete->id);

        return $document;
    }

    /**
     * @param int|null $categoryId
     *
     * @return DocumentElastic[]
     */
    public function getPosts(int $categoryId = null, int $siteId): array
    {
        $args = array(
            'numberposts' => 5000,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post_status' => 'publish',
        );

        if ($categoryId) {
            $args ['category'] = $categoryId;
        }

        $posts = get_posts($args);
        $datas = [];

        foreach ($posts as $post) {
            if ($document = $this->postToDocumentElastic($post, $siteId)) {
                $datas[] = $document;
            } else {
                Mailer::sendError(
                    "update elastic error ",
                    "create document ".$post->post_title
                );
                //  var_dump($post);
            }
        }

        if ($siteId === Theme::ADMINISTRATION) {
            $publications = WpRepository::getAllPublications();
            foreach ($publications as $publication) {
                //$category = get_category($publication->category_wpCategoryId);
                $datas[] = $this->createDocumentElasticFromPublication($publication, $siteId);
            }
        }

        return $datas;
    }

    public function postToDocumentElastic(WP_Post $post, int $siteId): ?DocumentElastic
    {
        try {
            return $this->createDocumentElastic($post, $siteId);
        } catch (\Exception $exception) {
            Mailer::sendError("update elastic", "create document ".$post->post_title.' => '.$exception->getMessage());
        }

        return null;
    }

    /**
     * @return DocumentElastic[]
     */
    public function indexPagesSpecial(): array
    {
        switch_to_blog(Theme::ADMINISTRATION);

        return $this->getPages(Theme::ADMINISTRATION);
    }

    /**
     * @return DocumentElastic[]
     */
    private function getPages(int $siteId): array
    {
        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish',
        );
        $pages = get_pages($args);

        $datas = [];

        foreach ($pages as $post) {
            $datas[] = $this->createDocumentElastic($post, $siteId);
        }

        return $datas;
    }

    private function createDocumentElastic(WP_Post $post, int $siteId): DocumentElastic
    {
        list($date, $time) = explode(" ", $post->post_date);
        $categories = array();
        foreach (get_the_category($post->ID) as $category) {
            $categories[] = $category->cat_name;
        }

        $content = get_the_content(null, null, $post);
        $content = apply_filters('the_content', $content);

        $document = new DocumentElastic();
        $document->id = $this->createId($post->ID, "post", $siteId);
        $document->name = Cleaner::cleandata($post->post_title);
        $document->excerpt = Cleaner::cleandata($post->post_excerpt);
        $document->content = Cleaner::cleandata($content);
        $document->tags = $categories;
        $document->date = $date;
        $document->type = 'article';
        $document->url = get_permalink($post->ID);

        return $document;
    }


    private function createDocumentElasticFromPublication(\stdClass $publication, int $siteId): DocumentElastic
    {
        $document = new DocumentElastic();
        $document->id = $this->createId($publication->id, "publication", $siteId);
        $document->name = Cleaner::cleandata($publication->name);
        $document->excerpt = "";
        $document->content = "";
        $document->tags = [[$publication->category_name]];
        $document->date = $publication->createdAt;
        $document->type = 'publication';
        $document->url = $publication->url;

        return $document;
    }

    public function getContentFiches(object $category): string
    {
        $categoryBottinId = get_term_meta($category->cat_ID, BottinCategoryMetaBox::KEY_NAME, true);

        if ($categoryBottinId) {
            $fiches = $this->bottinRepository->getFichesByCategory($categoryBottinId);

            return $this->bottinData->getContentForCategory($fiches);
        }

        return '';
    }

    public function getPublications(\WP_Term $category): string
    {
        $txt = '';

        $publications = WpRepository::getPublications($category->term_id);
        foreach ($publications as $publication) {
            $txt .= $publication->title." ";
        }

        return $txt;
    }

    /**
     * @return DocumentElastic[]
     * @throws \Exception
     */
    public function getAllfiches(): array
    {
        $fiches = $this->bottinRepository->getFiches();
        $documents = [];
        foreach ($fiches as $fiche) {

            $categories = $this->bottinData->getCategoriesFiche($fiche);
            $categoriesIds = $this->bottinData->getCategoriesFicheGetIds($fiche);
            $idSite = $this->bottinRepository->findSiteFiche($fiche);

            $document = new DocumentElastic();
            $document->id = $fiche->id;
            $document->name = $fiche->societe;
            $document->excerpt = Bottin::getExcerpt($fiche);
            $document->content = $this->bottinData->getContentFiche($fiche);
            $document->tags = $categories;
            $document->ids = $categoriesIds;
            $document->type = 'bottin';
            list($date, $heure) = explode(' ', $fiche->created_at);
            $document->date = $date;
            $document->url = RouterBottin::getUrlFicheBottin($idSite, $fiche);
            $documents[] = $document;
        }

        return $documents;
    }

    /**
     * @return DocumentElastic[]
     *
     * @throws \Exception
     */
    public function getAllCategoriesBottin(?SymfonyStyle $outPut = null): array
    {
        $data = $this->bottinRepository->getAllCategories();
        $categories = [];
        foreach ($data as $category) {
            $created = explode(' ', $category->created_at);
            $document = new DocumentElastic();
            $document->id = $category->id;
            $document->name = $category->name;
            $document->excerpt = $category->description;
            $document->tags = [];//todo
            $document->date = $created[0];
            $document->type = '';
            $document->url = RouterBottin::getUrlCategoryBottin($category);
            $fiches = $this->bottinRepository->getFichesByCategory($category->id);
            $document->count = count($fiches);
            $document->content = $this->bottinData->getContentForCategory($fiches);
            $categories[] = $document;
        }

        return $categories;
    }

    private function createDocumentElasticFromPermis(Permis $permis): DocumentElastic
    {
        $categorieEnqueteNom = '';
        if ($categorieEnquete = WpRepository::getCategoryEnquete()) {
            $categorieEnqueteNom = $categorieEnquete->name;
        }

        $demandeur = $permis->demandeurs[0];
        $content = $categorieEnqueteNom.' '.$permis->intitule.' '.$permis->demandeur.' à '.$permis->localite.' '.$permis->description;

        $document = new DocumentElastic();
        $document->id = $permis->id;
        $document->name = $permis->urbain;
        $document->excerpt = $permis->demandeur.' à '.$permis->localite;
        $document->content = $content;
        $document->tags = [];//todo
        $document->date = $permis->date_debut;
        $document->url = RouterMarche::getUrlEnquete($permis->id);

        return $document;
    }

    public function createId(int $id, string $type, ?int $siteId = 0): string
    {
        $id = $type.'-'.$id;
        if ($siteId) {
            $id .= '-'.$siteId;
        }

        return $id;
    }
}
