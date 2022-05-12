<?php


namespace AcMarche\Bottin\Category;

use AcMarche\Bottin\Repository\BottinRepository;
use AcMarche\Bottin\Repository\WpBottinRepository;
use AcMarche\Theme\Inc\Theme;
use stdClass;

class CategorySynchronizer
{
    private WpBottinRepository $wpRepository;
    private BottinRepository $bottinRepository;
    private CategoryCreator $categoryCreator;

    public function __construct(private int $categoryId)
    {
        $this->bottinRepository = new BottinRepository();
        $this->wpRepository = new WpBottinRepository();
        $this->categoryCreator = new CategoryCreator();
    }

    public function synchronize(): void
    {
        $category = $this->bottinRepository->getCategory($this->categoryId);
        foreach (Theme::SITES as $site) {
            switch_to_blog($site);
            $this->execute($category);
        }
    }

    private function execute(stdClass $category): void
    {
        foreach ($this->wpRepository->getCategoriesWp() as $categoryWp) {
            if ($this->categoryId == $categoryWp->bottinId) {
                $result = $this->categoryCreator->updateCategory($categoryWp, $category);
            }
        }
    }
}
