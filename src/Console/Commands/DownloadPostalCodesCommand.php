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

/**
 * Console command for downloading and converting GeoNames postal code data.
 *
 * This command downloads postal code data from the GeoNames database and
 * converts it to either JSON format or imports it directly into MongoDB.
 *
 * Usage examples:
 *   geonames:download TH              # Download Thailand postal codes
 *   geonames:download all             # Download all countries
 *   geonames:download US -f mongodb   # Import US data to MongoDB
 */
class DownloadPostalCodesCommand extends Command
{
    /**
     * The default command name.
     *
     * @var string
     */
    protected static $defaultName = 'geonames:download';

    /**
     * The default command description.
     *
     * @var string
     */
    protected static $defaultDescription = 'Download and convert postal codes data from Geonames';

    /**
     * The postal code downloader instance.
     */
    private GeonamesDownloader $downloader;

    /**
     * The postal code converter instance.
     */
    private PostalCodeConverter $converter;

    /**
     * Create a new download postal codes command instance.
     *
     * @param  GeonamesDownloader|null  $downloader  Optional downloader instance for testing
     * @param  PostalCodeConverter|null  $converter  Optional converter instance for testing
     */
    public function __construct(?GeonamesDownloader $downloader = null, ?PostalCodeConverter $converter = null)
    {
        parent::__construct();

        $this->downloader = $downloader ?? new GeonamesDownloader;
        $this->converter = $converter ?? new PostalCodeConverter;
    }

    /**
     * Configure the command options and arguments.
     */
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

    /**
     * Execute the command to download and convert postal code data.
     *
     * @param  InputInterface  $input  The console input interface
     * @param  OutputInterface  $output  The console output interface
     * @return int The command exit code (SUCCESS or FAILURE)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $country = $input->getArgument('country') ?? 'all';
        $outputDir = $input->getOption('output');
        $format = $input->getOption('format');

        $this->ensureOutputDirectoryExists($outputDir);

        $this->downloader->setOutput($output);
        $this->converter->setOutput($output);

        $output->writeln('<info>Downloading postal codes data...</info>');

        $zipFile = $this->downloadData($country, $outputDir);

        return $this->processData($input, $output, $zipFile, $format, $outputDir);
    }

    /**
     * Ensure the output directory exists, creating it if necessary.
     *
     * @param  string  $outputDir  The output directory path
     */
    private function ensureOutputDirectoryExists(string $outputDir): void
    {
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
    }

    /**
     * Download postal code data for the specified country.
     *
     * @param  string  $country  The country code or 'all'
     * @param  string  $outputDir  The output directory path
     * @return string The path to the downloaded ZIP file
     */
    private function downloadData(string $country, string $outputDir): string
    {
        if ($country === 'all') {
            $this->downloader->downloadAll($outputDir);

            return $outputDir.'/allCountries.zip';
        }

        $this->downloader->download($country, $outputDir);

        return $outputDir.'/'.strtoupper($country).'.zip';
    }

    /**
     * Process the downloaded data based on the output format.
     *
     * @param  InputInterface  $input  The console input interface
     * @param  OutputInterface  $output  The console output interface
     * @param  string  $zipFile  The path to the ZIP file
     * @param  string  $format  The output format (json or mongodb)
     * @param  string  $outputDir  The output directory path
     * @return int The command exit code
     */
    private function processData(
        InputInterface $input,
        OutputInterface $output,
        string $zipFile,
        string $format,
        string $outputDir
    ): int {
        if ($format === 'json') {
            return $this->convertToJson($output, $zipFile);
        }

        if ($format === 'mongodb') {
            return $this->importToMongoDB($input, $output, $zipFile);
        }

        $output->writeln(sprintf('<error>Unsupported format: %s</error>', $format));

        return Command::FAILURE;
    }

    /**
     * Convert the postal code data to JSON format.
     *
     * @param  OutputInterface  $output  The console output interface
     * @param  string  $zipFile  The path to the ZIP file
     * @return int The command exit code
     */
    private function convertToJson(OutputInterface $output, string $zipFile): int
    {
        $output->writeln('<info>Converting to JSON format...</info>');
        $jsonFile = str_replace('.zip', '.json', $zipFile);
        $this->converter->convert($zipFile, $jsonFile);

        unlink($zipFile);

        $output->writeln('<info>Data has been downloaded and converted successfully!</info>');
        $output->writeln(sprintf('<info>Output file: %s</info>', $jsonFile));

        return Command::SUCCESS;
    }

    /**
     * Import the postal code data to MongoDB.
     *
     * @param  InputInterface  $input  The console input interface
     * @param  OutputInterface  $output  The console output interface
     * @param  string  $zipFile  The path to the ZIP file
     * @return int The command exit code
     */
    private function importToMongoDB(InputInterface $input, OutputInterface $output, string $zipFile): int
    {
        $output->writeln('<info>Converting to MongoDB format...</info>');

        $mongodbUri = $input->getOption('mongodb-uri');
        $mongodbDb = $input->getOption('mongodb-db');
        $mongodbCollection = $input->getOption('mongodb-collection');

        $mongoConverter = new MongoDBPostalCodeConverter(
            $mongodbUri,
            $mongodbDb,
            $mongodbCollection
        );
        $mongoConverter->setOutput($output);

        $dummyOutputFile = str_replace('.zip', '.json', $zipFile);
        $mongoConverter->convert($zipFile, $dummyOutputFile);

        unlink($zipFile);

        $output->writeln('<info>Data has been downloaded and imported to MongoDB successfully!</info>');
        $output->writeln(sprintf('<info>MongoDB: %s.%s</info>', $mongodbDb, $mongodbCollection));

        return Command::SUCCESS;
    }
}
