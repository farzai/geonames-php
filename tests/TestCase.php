<?php

namespace Farzai\Geonames\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create data directory if it doesn't exist
        $dataDir = __DIR__ . '/data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        // Ensure directory is writable
        if (!is_writable($dataDir)) {
            chmod($dataDir, 0777);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Cleanup test data
        $this->cleanupDirectory(__DIR__ . '/data');
    }

    protected function getTestDataPath(string $path = ''): string
    {
        return __DIR__ . '/data/' . ltrim($path, '/');
    }

    private function cleanupDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \DirectoryIterator($dir);
        foreach ($files as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->isDir()) {
                $this->cleanupDirectory($file->getPathname());
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
    }
} 