<?php

namespace AcMarche\Bottin\Repository;

use AcMarche\Bottin\Bottin;
use AcMarche\Bottin\RouterBottin;
use AcMarche\Common\Env;
use AcMarche\Common\Mailer;
use Exception;
use PDO;
use PDOStatement;

class BottinRepository
{
    /**
     * @var PDO
     */
    private $dbh;

    public function __construct()
    {
        Env::loadEnv();
        $dsn      = 'mysql:host=localhost;dbname=bottin';
        $username = $_ENV['DB_BOTTIN_USER'];
        $password = $_ENV['DB_BOTTIN_PASS'];
        $options  = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        );

        $this->dbh = new PDO($dsn, $username, $password, $options);
    }

    public function getClassementsFiche(int $ficheId): array
    {
        $sql   = 'SELECT * FROM classements WHERE `fiche_id` = '.$ficheId.' ORDER BY `principal` DESC ';
        $query = $this->execQuery($sql);

        return $query->fetchAll();
    }

    public function getCategoriesOfFiche(int $ficheId): array
    {
        $categories  = [];
        $classements = $this->getClassementsFiche($ficheId);
        foreach ($classements as $classement) {
            $category = $this->getCategory($classement['category_id']);
            if ($category) {
                $category->principal = $classement['principal'];
                $categories[]        = $category;
            }
        }

        return $categories;
    }

    public function getCategoriePrincipale(object $fiche): ?object
    {
        $categories          = $this->getCategoriesOfFiche($fiche->id);
        $classementPrincipal = array_filter(
            $categories,
            function ($category) {
                if ($category->principal) {
                    return $category;
                }

                return null;
            }
        );
        if (count($classementPrincipal) > 0) {
            return $classementPrincipal[0];
        }
        if (count($categories) > 0) {
            return $categories[0];
        }

        return null;
    }

    /**
     * @param int $id
     *
     * @return object|bool
     * @throws Exception
     */
    public function getFicheById(int $id): ?object
    {
        $sql   = 'SELECT * FROM fiche WHERE `id` = '.$id;
        $query = $this->execQuery($sql);

        return $query->fetch(PDO::FETCH_OBJ);
    }

    /**
     * @param int $id
     *
     * @return object|bool
     * @throws Exception
     */
    public function getFicheBySlug(string $slug): ?object
    {
        $sql = 'SELECT * FROM fiche WHERE `slug` = :slug ';
        $sth = $this->dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $sth->execute(array(':slug' => $slug));
        if ( ! $data = $sth->fetch(PDO::FETCH_OBJ)) {
            return null;
        }

        return $data;
    }

    /**
     * @return object[]
     * @throws Exception
     */
    public function getFiches(): array
    {
        $sql   = 'SELECT * FROM fiche';
        $query = $this->execQuery($sql);

        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    public function getImagesFiche(int $id): array
    {
        $sql   = 'SELECT * FROM fiche_images WHERE `fiche_id` = '.$id.' ORDER BY `principale` DESC';
        $query = $this->execQuery($sql);

        return $query->fetchAll();
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    public function getDocuments(int $id): array
    {
        $sql   = 'SELECT * FROM document WHERE `fiche_id` = '.$id.' ORDER BY `name` DESC';
        $query = $this->execQuery($sql);

        return $query->fetchAll();
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    public function getSituations(int $id): array
    {
        $sql   = 'SELECT * FROM `fiche_situation` LEFT JOIN situation ON situation.id = fiche_situation.situation_id WHERE `fiche_id` = '.$id.' ORDER BY `name` DESC';
        $query = $this->execQuery($sql);

        return $query->fetchAll();
    }

    public function isCentreVille(int $id): bool
    {
        $situations = $this->getSituations($id);
        foreach ($situations as $situation) {
            if (in_array('Centre ville', $situation)) {
                return true;
            }
        }

        return false;
    }

    public function getLogo(int $id): ?string
    {
        $images = $this->getImagesFiche($id);
        $logo   = null;

        if (count($images) > 0) {
            $logo = Bottin::getUrlBottin().$id.DIRECTORY_SEPARATOR.$images[0]['image_name'];
        }

        return $logo;
    }

    /**
     * @param int $id
     *
     * @return object|null
     * @throws Exception
     */
    public function getCategory(?int $id): ?object
    {
        if ( ! $id) {
            return null;
        }
        $sql = 'SELECT * FROM category WHERE `id` = '.$id;
        $sth = $this->execQuery($sql);
        if ( ! $data = $sth->fetch(PDO::FETCH_OBJ)) {
            return null;
        }

        return $data;
    }

    public function getParentxxx(int $parentId): ?object
    {
        $sql = 'SELECT * FROM category WHERE `parent_id` = '.$id;
        $sth = $this->execQuery($sql);
        if ( ! $data = $sth->fetch(PDO::FETCH_OBJ)) {
            return null;
        }

        return $data;
    }

    /**
     * @param string $slug
     *
     * @return object|bool
     * @throws Exception
     */
    public function getCategoryBySlug(string $slug): ?object
    {
        $sql = 'SELECT * FROM category WHERE `slug` = :slug ';
        $sth = $this->dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $sth->execute(array(':slug' => $slug));
        if ( ! $data = $sth->fetch(PDO::FETCH_OBJ)) {
            return null;
        }

        return $data;
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    public function getCategories(?int $parentId): array
    {
        if ($parentId == null) {
            $sql = 'SELECT * FROM category WHERE `parent_id` IS NULL';
        } else {
            $sql = 'SELECT * FROM category WHERE `parent_id` = '.$parentId;
        }
        $query = $this->execQuery($sql);

        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws Exception
     */
    public function getAllCategories(): array
    {
        $sql   = 'SELECT * FROM category ORDER BY `name` ';
        $query = $this->execQuery($sql);

        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getFichesByCategories(array $ids): array
    {
        $fiches = [[]];
        foreach ($ids as $id) {
            $fiches[] = $this->getFichesByCategory($id);
        }

        $fiches = array_merge(...$fiches);
        $fiches = $this->sort($fiches);

        return $fiches;
    }

    public function getFichesByCategory(int $id): array
    {
        $category = $this->getCategory($id);
        if ( ! $category) {
            Mailer::sendError('fiche non trouvée', 'categorie id: '.$id);
        }

        $sql         = 'SELECT * FROM classements WHERE `category_id` = '.$id;
        $query       = $this->execQuery($sql);
        $classements = $query->fetchAll();

        $fiches = array_map(
            function ($classement) {
                return $this->getFicheById($classement['fiche_id']);
            },
            $classements
        );

        $fiches = array_unique($fiches, SORT_REGULAR);
        $fiches = $this->sort($fiches);

        return $fiches;
    }

    public function getTreeCategories()
    {
        $categories = [];
        $roots      = $this->getCategories(null);
        foreach ($roots as $root) {
            $categories[$root->id][0] = $root;
            $levels1                  = $this->getCategories($root->id);
            $categories[$root->id][1] = $levels1;

            foreach ($levels1 as $level) {
                $categories[$root->id][0]    = $level;
                $level2                      = $this->getCategories($level->id);
                $categories[$root->id][1][2] = $level2;
            }
        }

        return $categories;
    }

    public function getTree()
    {

    }

    /**
     * @param $sql
     *
     * @return false|PDOStatement
     * @throws Exception
     */
    public function execQuery($sql)
    {
        $query = $this->dbh->query($sql);
        $error = $this->dbh->errorInfo();
        if ($error[0] != '0000') {
            Mailer::sendError("wp error sql", $sql.' '.$error[2]);
            throw new Exception($error[2]);
        }

        return $query;
    }

    public function getRelations(int $ficheId, array $categories)
    {
        $ids             = array_map(
            function ($category) {
                return $category->id;
            },
            $categories
        );
        $recommandations = [];
        $fiches          = $this->getFichesByCategories($ids);
        foreach ($fiches as $fiche) {
            if ($fiche->id != $ficheId) {
                $recommandations[] = [
                    'title' => $fiche->societe,
                    'url'   => RouterBottin::getUrlFicheBottin($fiche),
                    'image' => $this->getLogo($fiche->id),
                ];
            }
        }

        return $recommandations;
    }

    private function sort(array $fiches): array
    {
        usort(
            $fiches,
            function ($a, $b) {
                {
                    if ($a->societe == $b->societe) {
                        return 0;
                    }

                    return ($a->societe < $b->societe) ? -1 : 1;
                }
            }
        );

        return $fiches;
    }

}
