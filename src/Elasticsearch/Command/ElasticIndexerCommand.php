<?php


namespace AcMarche\Bottin\Elasticsearch\Command;

use AcMarche\Bottin\Elasticsearch\ElasticIndexer;
use AcMarche\Common\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ElasticIndexerCommand extends Command
{
    protected static $defaultName = 'elastic:indexer';

    private  SymfonyStyle$io;

    protected function configure()
    {
        $this
            ->setDescription('Mise à jour des données [all, posts, categories, bottin, enquetes]')
            ->addArgument('action', InputArgument::REQUIRED, 'all, posts, categories, bottin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Cache::initLoaderWp();
        $action   = $input->getArgument('action');
        $this->io = new SymfonyStyle($input, $output);
        $elastic  = new ElasticIndexer($this->io);

        switch ($action) {
            case 'posts':
                $this->io->section("POSTS");
                $elastic->indexAllPosts();
                //     $elastic->indexPagesSpecial();
                break;
            case 'categories':
                $this->io->section("CATEGORIES");
                $elastic->indexAllCategories();
                break;
            case 'bottin':
                $this->io->section("BOTTIN");
                $elastic->indexAllBottin();
                break;
            case 'enquetes':
                $this->io->section("ENQUETES");
                $elastic->indexEnquetes();
                break;
            case 'all':
                $this->io->section("POSTS");
                $elastic->indexAllPosts();
                //  $elastic->indexPagesSpecial();
                $this->io->section("CATEGORIES");
                $elastic->indexAllCategories();
                $this->io->section("BOTTIN");
                $elastic->indexAllBottin();
                $this->io->section("ENQUETES");
                $elastic->indexEnquetes();
        }

        return Command::SUCCESS;
    }
}
