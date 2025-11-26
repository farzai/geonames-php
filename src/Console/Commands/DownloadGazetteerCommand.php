<?php

declare(strict_types=1);

namespace Farzai\Geonames\Console\Commands;

use Farzai\Geonames\Converter\GazetteerConverter;
use Farzai\Geonames\Converter\MongoDBGazetteerConverter;
use Farzai\Geonames\Downloader\GazetteerDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command for downloading and converting GeoNames gazetteer data.
 *
 * This command downloads geographical feature data from the GeoNames database
 * and converts it to either JSON format or imports it directly into MongoDB.
 *
 * Feature classes available for filtering:
 *   A - Administrative boundaries
 *   H - Hydrographic features (streams, lakes)
 *   L - Parks, areas
 *   P - Populated places (cities, villages)
 *   R - Roads, railroads
 *   S - Spots, buildings, farms
 *   T - Mountains, hills, rocks
 *   U - Undersea features
 *   V - Forest, heath
 *
 * Usage examples:
 *   geonames:gazetteer:download TH                  # Download Thailand data
 *   geonames:gazetteer:download all -c P            # All populated places
 *   geonames:gazetteer:download US -f mongodb       # Import US data to MongoDB
 */
class DownloadGazetteerCommand extends Command
{
    /**
     * Admin code files to clean up after processing.
     */
    private const ADMIN_CODE_FILES = [
        'admin1CodesASCII.txt',
        'admin2Codes.txt',
    ];

    /**
     * The default command name.
     *
     * @var string
     */
    protected static $defaultName = 'geonames:gazetteer:download';

    /**
     * The default command description.
     *
     * @var string
     */
    protected static $defaultDescription = 'Download and convert Geonames Gazetteer data';

    /**
     * The gazetteer downloader instance.
     */
    private GazetteerDownloader $downloader;

    /**
     * The gazetteer converter instance.
     */
    private GazetteerConverter $converter;

    /**
     * Create a new download gazetteer command instance.
     *
     * @param  GazetteerDownloader|null  $downloader  Optional downloader instance for testing
     * @param  GazetteerConverter|null  $converter  Optional converter instance for testing
     */
    public function __construct(?GazetteerDownloader $downloader = null, ?GazetteerConverter $converter = null)
    {
        parent::__construct();

        $this->downloader = $downloader ?? new GazetteerDownloader;
        $this->converter = $converter ?? new GazetteerConverter;
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
            ->addOption('feature-class', 'c', InputOption::VALUE_REQUIRED, 'Filter by feature class (A,H,L,P,R,S,T,U,V)', 'P')
            ->addOption('mongodb-uri', null, InputOption::VALUE_REQUIRED, 'MongoDB connection URI', 'mongodb://localhost:27017')
            ->addOption('mongodb-db', null, InputOption::VALUE_REQUIRED, 'MongoDB database name', 'geonames')
            ->addOption('mongodb-collection', null, InputOption::VALUE_REQUIRED, 'MongoDB collection name', 'gazetteer');
    }

    /**
     * Execute the command to download and convert gazetteer data.
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

        $output->writeln('<info>Downloading Gazetteer data...</info>');

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
     * Download gazetteer data for the specified country.
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
            return $this->convertToJson($output, $zipFile, $outputDir);
        }

        if ($format === 'mongodb') {
            return $this->importToMongoDB($input, $output, $zipFile, $outputDir);
        }

        $output->writeln(sprintf('<error>Unsupported format: %s</error>', $format));

        return Command::FAILURE;
    }

    /**
     * Convert the gazetteer data to JSON format.
     *
     * @param  OutputInterface  $output  The console output interface
     * @param  string  $zipFile  The path to the ZIP file
     * @param  string  $outputDir  The output directory path
     * @return int The command exit code
     */
    private function convertToJson(OutputInterface $output, string $zipFile, string $outputDir): int
    {
        $output->writeln('<info>Converting to JSON format...</info>');
        $jsonFile = str_replace('.zip', '.json', $zipFile);
        $this->converter->convertWithAdminCodes($zipFile, $jsonFile, $outputDir);

        $this->cleanup($zipFile, $outputDir);

        $output->writeln('<info>Data has been downloaded and converted successfully!</info>');
        $output->writeln(sprintf('<info>Output file: %s</info>', $jsonFile));

        return Command::SUCCESS;
    }

    /**
     * Import the gazetteer data to MongoDB.
     *
     * @param  InputInterface  $input  The console input interface
     * @param  OutputInterface  $output  The console output interface
     * @param  string  $zipFile  The path to the ZIP file
     * @param  string  $outputDir  The output directory path
     * @return int The command exit code
     */
    private function importToMongoDB(
        InputInterface $input,
        OutputInterface $output,
        string $zipFile,
        string $outputDir
    ): int {
        $output->writeln('<info>Converting to MongoDB format...</info>');

        $mongodbUri = $input->getOption('mongodb-uri');
        $mongodbDb = $input->getOption('mongodb-db');
        $mongodbCollection = $input->getOption('mongodb-collection');

        $mongoConverter = new MongoDBGazetteerConverter(
            $mongodbUri,
            $mongodbDb,
            $mongodbCollection
        );
        $mongoConverter->setOutput($output);

        $dummyOutputFile = str_replace('.zip', '.json', $zipFile);
        $mongoConverter->convertWithAdminCodes($zipFile, $dummyOutputFile, $outputDir);

        $this->cleanup($zipFile, $outputDir);

        $output->writeln('<info>Data has been downloaded and imported to MongoDB successfully!</info>');
        $output->writeln(sprintf('<info>MongoDB: %s.%s</info>', $mongodbDb, $mongodbCollection));

        return Command::SUCCESS;
    }

    /**
     * Clean up temporary files after processing.
     *
     * @param  string  $zipFile  The ZIP file to remove
     * @param  string  $outputDir  The output directory containing admin code files
     */
    private function cleanup(string $zipFile, string $outputDir): void
    {
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }

        foreach (self::ADMIN_CODE_FILES as $file) {
            $filePath = $outputDir.'/'.$file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
