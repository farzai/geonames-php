<?php

declare(strict_types=1);

namespace Farzai\Geonames\Console\Commands;

use Farzai\Geonames\Converter\MongoDBPostalCodeConverter;
use Farzai\Geonames\Converter\PostalCodeConverter;
use Farzai\Geonames\Downloader\GeonamesDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadPostalCodesCommand extends Command
{
    protected static $defaultName = 'geonames:download';

    protected static $defaultDescription = 'Download and convert postal codes data from Geonames';

    private GeonamesDownloader $downloader;

    private PostalCodeConverter $converter;

    public function __construct(?GeonamesDownloader $downloader = null, ?PostalCodeConverter $converter = null)
    {
        parent::__construct();

        $this->downloader = $downloader ?? new GeonamesDownloader;
        $this->converter = $converter ?? new PostalCodeConverter;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('country', InputArgument::OPTIONAL, 'Country code (e.g., TH, US) or "all" for all countries')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output directory', getcwd().'/data')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (json, mongodb)', 'json')
            ->addOption('mongodb-uri', null, InputOption::VALUE_REQUIRED, 'MongoDB connection URI', 'mongodb://localhost:27017')
            ->addOption('mongodb-db', null, InputOption::VALUE_REQUIRED, 'MongoDB database name', 'geonames')
            ->addOption('mongodb-collection', null, InputOption::VALUE_REQUIRED, 'MongoDB collection name', 'postal_codes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $country = $input->getArgument('country') ?? 'all';
        $outputDir = $input->getOption('output');
        $format = $input->getOption('format');

        // Create output directory if it doesn't exist
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Set output for progress bars
        $this->downloader->setOutput($output);
        $this->converter->setOutput($output);

        $output->writeln('<info>Downloading postal codes data...</info>');

        // Download the data
        if ($country === 'all') {
            $this->downloader->downloadAll($outputDir);
            $zipFile = $outputDir.'/allCountries.zip';
        } else {
            $this->downloader->download($country, $outputDir);
            $zipFile = $outputDir.'/'.strtoupper($country).'.zip';
        }

        if ($format === 'json') {
            $output->writeln('<info>Converting to JSON format...</info>');
            $jsonFile = str_replace('.zip', '.json', $zipFile);
            $this->converter->convert($zipFile, $jsonFile);

            // Remove ZIP file after conversion
            unlink($zipFile);

            $output->writeln('<info>Data has been downloaded and converted successfully!</info>');
            $output->writeln(sprintf('<info>Output file: %s</info>', $jsonFile));
        } elseif ($format === 'mongodb') {
            $output->writeln('<info>Converting to MongoDB format...</info>');

            // Create MongoDB converter
            $mongodbUri = $input->getOption('mongodb-uri');
            $mongodbDb = $input->getOption('mongodb-db');
            $mongodbCollection = $input->getOption('mongodb-collection');

            $mongoConverter = new MongoDBPostalCodeConverter(
                $mongodbUri,
                $mongodbDb,
                $mongodbCollection
            );
            $mongoConverter->setOutput($output);

            // Convert and import to MongoDB
            $jsonFile = str_replace('.zip', '.json', $zipFile); // Dummy file name, not used
            $mongoConverter->convert($zipFile, $jsonFile);

            // Remove ZIP file after conversion
            unlink($zipFile);

            $output->writeln('<info>Data has been downloaded and imported to MongoDB successfully!</info>');
            $output->writeln(sprintf('<info>MongoDB: %s.%s</info>', $mongodbDb, $mongodbCollection));
        } else {
            $output->writeln(sprintf('<error>Unsupported format: %s</error>', $format));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
