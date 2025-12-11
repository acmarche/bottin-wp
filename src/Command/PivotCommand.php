<?php


namespace AcMarche\Bottin\Command;

use AcMarche\Bottin\SearchData\Cache;
use AcMarche\Theme\Lib\Pivot\Enums\ContentEnum;
use AcMarche\Theme\Lib\Pivot\Parser\EventParser;
use AcMarche\Theme\Lib\Pivot\Repository\PivotApi;
use AcMarche\Theme\Lib\Pivot\Repository\PivotRepository;
use Psr\Cache\InvalidArgumentException;
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
    private PivotApi $pivotApi;
    private EventParser $parser;

    protected function configure(): void
    {
        $this->addOption('purge', "purge", InputOption::VALUE_NONE, 'Update data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $purge = (bool)$input->getOption('purge');

        $this->pivotApi = new PivotApi();
        $this->parser = new EventParser();

        $this->cacheAll($purge);

        return Command::SUCCESS;
    }

    private function cacheAll(bool $purge): void
    {
        $level = ContentEnum::LVL4->value;
        $cacheKey = Cache::generateKey(PivotRepository::$keyAll);

        try {
            $response = $this->pivotApi->query($level);
            $content = $response?->getContent();
        } catch (\Exception|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->io->error('No content returned from Pivot API' . $e->getMessage());
            $content = null;
        }

        if ($content === null) {
            $content = $this->readFile();
            if (!$content)
                return;
        }

        if ($purge) {
            Cache::delete($cacheKey);
        }

        try {
            $events = $this->parser->parseJsonFile($content);
        } catch (\JsonException $e) {
            $this->io->error('Parse error ' . $e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->io->error('Parse error ' . $e->getMessage());

            return;
        }

        $this->saveToFile($content);

        try {
            Cache::get($cacheKey, function () use ($content) {
                return $content;
            });
        } catch (\Exception|InvalidArgumentException $e) {
            $this->io->error('Error cache' . $e->getMessage());

            return;
        }

        foreach ($events as $event) {
            $this->fetchAll($event->codeCgt, $level);
        }

    }

    private function fetchAll(string $codeCgt, int $level = ContentEnum::LVL4->value): void
    {
        try {
            $response = $this->pivotApi->loadEvent($codeCgt, $level);
        } catch (TransportExceptionInterface $e) {
            $this->io->error('No content returned from Pivot API' . $e->getMessage());

            return;
        }
        try {
            $content = $response?->getContent();
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->io->error('No content returned from Pivot API' . $e->getMessage());

            return;
        }

        if ($content === null) {
            $this->io->error('Empty content returned ');

            return;
        }

        try {
            $data = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->io->error('Error parse event code ' . $codeCgt . ' error ' . $e->getMessage());
        }

        try {
            $this->parser->parseEvent($data['offre'][0]);
        } catch (\Exception $exception) {
            $this->io->error('Error parse event code ' . $codeCgt . ' ' . $exception->getMessage());
        }

        $cacheKey = Cache::generateKey(PivotRepository::$keyAll) . '-' . $codeCgt;
        try {
            Cache::get($cacheKey, function () use ($content) {
                return $content;
            });
        } catch (\Exception $e) {
            $this->io->error('Event Error cache' . $codeCgt . ' ' . $e->getMessage());

            return;
        }

    }

    private function saveToFile(string $content): void
    {
        $dataDir = $_ENV['APP_CACHE_DIR'] . '/../data';

        if (!is_dir($dataDir)) {
            if (!mkdir($dataDir, 0755, true)) {
                $this->io->error('Failed to create directory: ' . $dataDir);
                return;
            }
        }

        $filename = $dataDir . '/pivot.json';
        $handle = fopen($filename, 'w');

        if ($handle === false) {
            $this->io->error('Failed to open file for writing: ' . $filename);
            return;
        }

        if (!flock($handle, LOCK_EX)) {
            $this->io->error('Failed to acquire lock on file: ' . $filename);
            fclose($handle);
            return;
        }

        $bytesToWrite = strlen($content);
        $bytesWritten = fwrite($handle, $content);

        flock($handle, LOCK_UN);
        fclose($handle);

        if ($bytesWritten === false) {
            $this->io->error('Failed to write to file: ' . $filename);
            return;
        }

        if ($bytesWritten !== $bytesToWrite) {
            $this->io->error(sprintf(
                'Incomplete write to %s: %d of %d bytes written',
                $filename,
                $bytesWritten,
                $bytesToWrite
            ));
            return;
        }
    }

    private function readFile(): ?string
    {
        if (is_readable($filename = $_ENV['APP_CACHE_DIR'] . '/../data/pivot.json')) {
            return file_get_contents($filename);
        }
        $this->io->error('File ' . $filename . ' not found');
        return null;
    }

}
