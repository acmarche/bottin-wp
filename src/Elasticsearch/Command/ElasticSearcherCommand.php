<?php


namespace AcMarche\Bottin\Elasticsearch\Command;

use AcMarche\Bottin\Elasticsearch\Searcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ElasticSearcherCommand extends Command
{
    protected static $defaultName = 'elastic:search';

    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure()
    {
        $this
            ->setDescription('Effectuer une recherche')
            ->addArgument('query', InputArgument::REQUIRED, 'mot clef pour la description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $query = $input->getArgument('query');
        if ( ! $query) {
            $this->io->error('Entrez un mot clef');

            return Command::FAILURE;
        }

           $this->search($query);
        //$this->suggest($query);

        return Command::SUCCESS;
    }

    protected function suggest(string $query)
    {
        $searcher = new Searcher();
        $result   = $searcher->suggest($query);

     //   var_dump($result->getQuery()->toArray());
        foreach ($result->getSuggests() as $suggest) {

            foreach ($suggest as $suggest2) {
                //  var_dump($suggest2);
                foreach ($suggest2['options'] as $option) {
                    $this->io->writeln($option['text']);
                }
            }
        }

        /*   foreach ($result->getResults() as $result) {
               $hit    = $result->getHit();
               $source = $hit['_source'];
               $this->io->writeln($source['name']);
           }*/
    }

    protected function search(string $query)
    {
        $this->io->section("Search2");
        $searcher = new Searcher();
        $result   = $searcher->search2($query);
        $this->io->writeln("Found: ".$result->count());
        $this->io->writeln('-------------------');
        foreach ($result->getResults() as $result) {
            $hit    = $result->getHit();
            $source = $hit['_source'];
            $this->io->writeln($source['name']);
        }
        $this->io->section("Search");
        $result   = $searcher->search($query);
        $this->io->writeln("Found: ".$result->count());
        $this->io->writeln('-------------------');
        foreach ($result->getResults() as $result) {
            $hit    = $result->getHit();
            $source = $hit['_source'];
            $this->io->writeln($source['name']);
        }
    }
}
