<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;
use Generator;

class PostalCodeConverter
{
    private ?OutputInterface $output = null;

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Convert ZIP file to JSON
     */
    public function convert(string $zipFile, string $outputFile): void
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('Failed to open ZIP file: ' . $zipFile);
        }

        // Extract to temporary directory
        $tempDir = sys_get_temp_dir() . '/geonames_' . uniqid();
        mkdir($tempDir);
        $zip->extractTo($tempDir);
        $zip->close();

        // Find the txt file
        $files = glob($tempDir . '/*.txt');
        if (empty($files)) {
            throw new \RuntimeException('No .txt file found in ZIP archive');
        }
        $txtFile = $files[0];
        
        // Convert to JSON with progress bar
        $this->convertToJson($txtFile, $outputFile);

        // Cleanup all files in temp directory
        $files = new \DirectoryIterator($tempDir);
        foreach ($files as $file) {
            if (!$file->isDot()) {
                unlink($file->getPathname());
            }
        }
        rmdir($tempDir);
    }

    /**
     * Convert TXT file to JSON with memory-efficient streaming
     */
    private function convertToJson(string $txtFile, string $outputFile): void
    {
        $totalLines = $this->countLines($txtFile);
        
        $progressBar = null;
        if ($this->output) {
            $progressBar = new ProgressBar($this->output, $totalLines);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
            $progressBar->start();
        }

        $handle = fopen($outputFile, 'wb');
        fwrite($handle, "[\n");
        
        $first = true;
        foreach ($this->streamRecords($txtFile) as $index => $record) {
            if (!$first) {
                fwrite($handle, ",\n");
            }
            fwrite($handle, json_encode($record, JSON_UNESCAPED_UNICODE));
            $first = false;

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        fwrite($handle, "\n]");
        fclose($handle);

        if ($progressBar) {
            $progressBar->finish();
            $this->output->writeln('');
        }
    }

    /**
     * Stream records from TXT file
     */
    private function streamRecords(string $txtFile): Generator
    {
        $handle = fopen($txtFile, 'r');
        
        while (($line = fgets($handle)) !== false) {
            $data = str_getcsv(trim($line), "\t", '"', "\\");
            
            if (count($data) < 9) {
                continue;
            }

            yield [
                'country_code' => $data[0],
                'postal_code' => $data[1],
                'place_name' => $data[2],
                'admin_name1' => $data[3],
                'admin_code1' => $data[4],
                'admin_name2' => $data[5] ?? '',
                'admin_code2' => $data[6] ?? '',
                'admin_name3' => $data[7] ?? '',
                'admin_code3' => $data[8] ?? '',
                'latitude' => isset($data[9]) ? (float) $data[9] : null,
                'longitude' => isset($data[10]) ? (float) $data[10] : null,
                'accuracy' => isset($data[11]) ? (int) $data[11] : null,
            ];
        }

        fclose($handle);
    }

    /**
     * Count lines in file efficiently
     */
    private function countLines(string $file): int
    {
        $handle = fopen($file, 'r');
        $lines = 0;

        while (!feof($handle)) {
            $lines += substr_count(fread($handle, 8192), "\n");
        }

        fclose($handle);
        return $lines;
    }
} 