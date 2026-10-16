<?php

declare(strict_types=1);

namespace Farzai\Geonames\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var list<string> */
    private array $temporary = [];

    public function temporaryDirectory(): string
    {
        $dir = sys_get_temp_dir().'/geonames-test-'.bin2hex(random_bytes(6));
        mkdir($dir, 0775, true);
        $this->temporary[] = $dir;

        return $dir;
    }

    protected function tearDown(): void
    {
        foreach ($this->temporary as $dir) {
            $this->remove($dir);
        }
        parent::tearDown();
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        foreach (is_dir($path) ? (scandir($path) ?: []) : [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $this->remove($path.'/'.$entry);
            }
        }
        @rmdir($path);
    }
}
