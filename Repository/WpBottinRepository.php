<?php


namespace AcMarche\Bottin\Repository;

use WP_Query;
use WP_Post;
use WP_Term;

class WpBottinRepository
{
    public const DATA_TYPE = 'bottin_fiche';
    public const DATA_KEY = 'bottin_fiche_id';

    public static function set_table_meta(): string
    {
        global $wpdb;
        $wpdb->bottin_fichemeta = $wpdb->prefix.self::DATA_TYPE.'meta';

        return $wpdb->bottin_fichemeta;
    }

    /**
     * Retourne les categories ayant une reference du bottin
     * @return WP_Term[]
     */
    public function getCategoriesWp(): array
    {
        $cats = [];

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

        foreach ($categories as $cat) {
            $bottinId = get_term_meta($cat->cat_ID, 'bottin_refrubrique', true);
            if ($bottinId) {
                $cat->bottinId = $bottinId;
                $cats[] = $cat;
            }
        }

        return $cats;
    }

    /**
     * @return \WP_Post[]
     */
    public function getFichesWp(): array
    {
        $query = new WP_Query(
            array(
                'post_status' => 'publish',
                'post_type' => self::DATA_TYPE,
                'orderby' => 'title',
                'order' => 'ASC',
                'posts_per_page' => -1,
            )
        );

        return $query->get_posts();
    }

    public function getFichesWpToDelete(array $bottinIds): array
    {
        $posts = $this->getFichesWp();
        static::set_table_meta();
        $postsId = [];

        foreach ($posts as $post) {
            $postId = get_metadata(self::DATA_TYPE, $post->ID, 'id', true);
            if ($postId) {
                $postsId[$post->ID] = $postId;
            }
        }

        return array_diff($postsId, $bottinIds);
    }

    public function getPostIdByFicheId(int $ficheId): int
    {
        $result = [];
        global $wpdb;
        $table = static::set_table_meta();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE `meta_key` = '%s' AND `meta_value` = %d",
                'id',
                $ficheId
            )
        );

        if ((is_countable($results) ? count($results) : 0) > 0) {
            $key = WpBottinRepository::DATA_KEY;
            $result['status'] = "updated";

            return $results[0]->$key;
        }

        return 0;
    }

    public function deleteMetaData(int $postId): bool|int
    {
        global $wpdb;
        $table = static::set_table_meta();

        return $wpdb->delete(
            $table,
            [WpBottinRepository::DATA_KEY => $postId],
            ['%d']
        );
    }

    public function getFicheIdByPostId(int $postId): int
    {
        $this::set_table_meta();

        return get_metadata(WpBottinRepository::DATA_TYPE, $postId, 'id', true);
    }

    public function getPostByFicheId(int $ficheId): ?WP_Post
    {
        if (($postId = $this->getPostIdByFicheId($ficheId)) !== 0) {
            return get_post($postId);
        }

        return null;
    }

    /**
     * Retourne toutes les metas datas d'une fiche
     * @param $idFiche
     * @return array|null|object
     */
    public function getFicheMetaDatas(int $idFiche)
    {
        global $wpdb;
        $table = static::set_table_meta();
        $key = self::DATA_KEY;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE `$key` = '%d'",
                (int)$idFiche
            )
        );
    }
}
