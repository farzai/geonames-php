<?php

declare(strict_types=1);

namespace Farzai\Geonames\Console\Commands;

use Farzai\Geonames\Downloader\GeonamesDownloader;
use Farzai\Geonames\Converter\PostalCodeConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DownloadPostalCodesCommand extends Command
{
    protected static $defaultName = 'geonames:download';
    protected static $defaultDescription = 'Download and convert postal codes data from Geonames';

    private GeonamesDownloader $downloader;
    private PostalCodeConverter $converter;

    public function __construct(?GeonamesDownloader $downloader = null, ?PostalCodeConverter $converter = null)
    {
        parent::__construct();
        
        $this->downloader = $downloader ?? new GeonamesDownloader();
        $this->converter = $converter ?? new PostalCodeConverter();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('country', InputArgument::OPTIONAL, 'Country code (e.g., TH, US) or "all" for all countries')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output directory', getcwd() . '/data')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (json)', 'json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $country = $input->getArgument('country') ?? 'all';
        $outputDir = $input->getOption('output');
        $format = $input->getOption('format');

        // Create output directory if it doesn't exist
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Set output for progress bars
        $this->downloader->setOutput($output);
        $this->converter->setOutput($output);

        $output->writeln('<info>Downloading postal codes data...</info>');

        // Download the data
        if ($country === 'all') {
            $this->downloader->downloadAll($outputDir);
            $zipFile = $outputDir . '/allCountries.zip';
        } else {
            $this->downloader->download($country, $outputDir);
            $zipFile = $outputDir . '/' . strtoupper($country) . '.zip';
        }

        if ($format === 'json') {
            $output->writeln('<info>Converting to JSON format...</info>');
            $jsonFile = str_replace('.zip', '.json', $zipFile);
            $this->converter->convert($zipFile, $jsonFile);
            
            // Remove ZIP file after conversion
            unlink($zipFile);
            
            $output->writeln('<info>Data has been downloaded and converted successfully!</info>');
            $output->writeln(sprintf('<info>Output file: %s</info>', $jsonFile));
        }

        return Command::SUCCESS;
    }
} 