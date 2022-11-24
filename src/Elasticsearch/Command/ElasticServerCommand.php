<?php


namespace AcMarche\Bottin\Elasticsearch\Command;

use AcMarche\Bottin\Elasticsearch\ElasticServer;
use AcMarche\Common\Cache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'elastic:server',
    description: 'Raz l\'index',
)]
class ElasticServerCommand extends Command
{
    private SymfonyStyle $io;

    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $helper   = $this->getHelper('question');
        $question = new ConfirmationQuestion('Raz. Êtes vous sur ? (Y,N) ', false);

        if (! $helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        define('ABSPATH', dirname(__DIR__).'/../../../../../');
        Cache::initLoaderWp();
        $elastic = new ElasticServer();
        $elastic->createIndex();
        $elastic->setMapping();

        $this->io->success('Index vidé');

        return Command::SUCCESS;
    }
}
