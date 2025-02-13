<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class GazetteerConverter
{
    private ?OutputInterface $output = null;

    private array $admin1Codes = [];

    private array $admin2Codes = [];

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Convert ZIP file to JSON
     */
    public function convert(string $zipFile, string $outputFile, string $adminCodesDir): void
    {
        // Load admin codes first
        $this->loadAdminCodes($adminCodesDir);

        $zip = new ZipArchive;

        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('Failed to open ZIP file: '.$zipFile);
        }

        // Extract to temporary directory
        $tempDir = sys_get_temp_dir().'/geonames_'.uniqid();
        mkdir($tempDir);
        $zip->extractTo($tempDir);
        $zip->close();

        // Find the txt file
        $files = glob($tempDir.'/*.txt');
        if (empty($files)) {
            throw new \RuntimeException('No .txt file found in ZIP archive');
        }
        $txtFile = $files[0];

        if ($this->output) {
            $this->output->writeln('<info>Reading file: '.$txtFile.'</info>');
            $content = file_get_contents($txtFile);
            $this->output->writeln('<info>File content:</info>');
            $this->output->writeln($content);
        }

        // Convert to JSON with progress bar
        $this->processTextFile($txtFile, $outputFile);

        // Cleanup all files in temp directory
        $files = new \DirectoryIterator($tempDir);
        foreach ($files as $file) {
            if (! $file->isDot()) {
                unlink($file->getPathname());
            }
        }
        rmdir($tempDir);

        if ($this->output) {
            $this->output->writeln('<info>Output file content:</info>');
            $this->output->writeln(file_get_contents($outputFile));
        }
    }

    /**
     * Load admin codes from files
     */
    private function loadAdminCodes(string $adminCodesDir): void
    {
        if ($this->output) {
            $this->output->writeln('<info>Loading administrative codes...</info>');
        }

        // Load admin1 codes
        $admin1File = $adminCodesDir.'/admin1CodesASCII.txt';
        if (file_exists($admin1File)) {
            $handle = fopen($admin1File, 'r');
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", trim($line));
                if (count($parts) >= 2) {
                    $code = $parts[0];
                    $name = $parts[1];
                    $this->admin1Codes[$code] = $name;
                }
            }
            fclose($handle);
        }

        // Load admin2 codes
        $admin2File = $adminCodesDir.'/admin2Codes.txt';
        if (file_exists($admin2File)) {
            $handle = fopen($admin2File, 'r');
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", trim($line));
                if (count($parts) >= 2) {
                    $code = $parts[0];
                    $name = $parts[1];
                    $this->admin2Codes[$code] = $name;
                }
            }
            fclose($handle);
        }

        if ($this->output) {
            $this->output->writeln('<info>Admin1 codes:</info>');
            $this->output->writeln(print_r($this->admin1Codes, true));
            $this->output->writeln('<info>Admin2 codes:</info>');
            $this->output->writeln(print_r($this->admin2Codes, true));
        }
    }

    /**
     * Process the text file and convert it to JSON
     */
    private function processTextFile(string $txtFile, string $outputFile): void
    {
        if ($this->output) {
            $this->output->writeln('<info>Reading file: '.$txtFile.'</info>');
            $content = file_get_contents($txtFile);
            $this->output->writeln('<info>File content:</info>');
            $this->output->writeln($content);
        }

        $data = [];
        $lines = file($txtFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip empty lines
            if (empty(trim($line))) {
                continue;
            }

            // Split the line by tabs
            $fields = array_map('trim', explode("\t", $line));

            // Check if we have enough fields
            if (count($fields) < 19) {
                if ($this->output) {
                    $this->output->writeln('<error>Invalid data line: '.$line.'</error>');
                    $this->output->writeln('<error>Field count: '.count($fields).'</error>');
                }

                continue;
            }

            // Process the fields
            $item = [
                'geoname_id' => (int) $fields[0],
                'name' => $fields[1],
                'ascii_name' => $fields[2],
                'alternate_names' => explode(',', $fields[3]),
                'latitude' => (float) $fields[4],
                'longitude' => (float) $fields[5],
                'feature_class' => $fields[6],
                'feature_code' => $fields[7],
                'country_code' => $fields[8],
                'cc2' => array_filter(explode(',', $fields[9] ?? '')),
                'admin1_code' => $fields[10],
                'admin1_name' => $this->getAdmin1Name($fields[8], $fields[10]),
                'admin2_code' => $fields[11],
                'admin2_name' => $this->getAdmin2Name($fields[8], $fields[10], $fields[11]),
                'admin3_code' => $fields[12],
                'admin4_code' => $fields[13],
                'population' => (int) ($fields[14] ?? 0),
                'elevation' => (int) ($fields[15] ?? 0),
                'dem' => (int) ($fields[16] ?? 0),
                'timezone' => $fields[17],
                'modification_date' => $fields[18],
            ];

            $data[] = $item;
        }

        // Write the JSON file
        file_put_contents($outputFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($this->output) {
            $this->output->writeln('<info>Output file content:</info>');
            $this->output->writeln(file_get_contents($outputFile));
        }
    }

    /**
     * Get admin1 name from codes
     */
    private function getAdmin1Name(string $countryCode, string $admin1Code): string
    {
        return $this->admin1Codes[$countryCode.'.'.$admin1Code] ?? '';
    }

    /**
     * Get admin2 name from codes
     */
    private function getAdmin2Name(string $countryCode, string $admin1Code, string $admin2Code): string
    {
        return $this->admin2Codes[$countryCode.'.'.$admin1Code.'.'.$admin2Code] ?? '';
    }
}
