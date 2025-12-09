<?php


namespace AcMarche\Bottin\Command;

use AcMarche\Bottin\SearchData\Cache;
use AcMarche\Theme\Lib\Pivot\Enums\ContentEnum;
use AcMarche\Theme\Lib\Pivot\Repository\PivotApi;
use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'pivot:cache',
    description: ' ',
)]
class PivotCommand extends Command
{
    private SymfonyStyle $io;

    protected function configure(): void
    {
        $this->addOption('purge', "purge", InputOption::VALUE_NONE, 'Update data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $purge = (bool)$input->getOption('purge');
        $cacheKey = Cache::generateKey(PivotRepository::$keyAll);
        $level = ContentEnum::LVL4->value;

        $pivotApi = new PivotApi();
        try {
            $response = $pivotApi->query($level);
            $content = $response?->getContent();
        } catch (\Exception|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->io->error('No content returned from Pivot API');

            return Command::FAILURE;
        }
        if ($content === null) {
            $this->io->error('No content returned from Pivot API');

            return Command::FAILURE;
        }

        if ($purge) {
            Cache::delete($cacheKey);
        }

        try {
            Cache::get($cacheKey, function () use ($content) {
                return $content;
            });
        } catch (\Exception $e) {
            $this->io->error('No content returned from Pivot API');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

}
