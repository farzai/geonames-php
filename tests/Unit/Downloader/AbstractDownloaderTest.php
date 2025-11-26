<?php

use Farzai\Geonames\Downloader\AbstractDownloader;
use Farzai\Geonames\Exceptions\GeonamesException;
use Farzai\Geonames\Tests\Helpers\MockHttpClient;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Testable wrapper for AbstractDownloader that exposes protected methods.
 */
class TestableAbstractDownloader extends AbstractDownloader
{
    protected function getBaseUrl(): string
    {
        return 'https://test.example.com/';
    }

    public function testDownloadWithProgress(string $url, string $destination): void
    {
        $this->downloadWithProgress($url, $destination);
    }

    public function testCreateProgressBar(int $totalSize): ?ProgressBar
    {
        return $this->createProgressBar($totalSize);
    }

    public function testFinishProgressBar(?ProgressBar $progressBar): void
    {
        $this->finishProgressBar($progressBar);
    }

    public function testGetBaseUrl(): string
    {
        return $this->getBaseUrl();
    }
}

describe('AbstractDownloader', function () {
    describe('setOutput', function () {
        it('returns self for method chaining', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new TestableAbstractDownloader($transport);

            $result = $downloader->setOutput(new NullOutput);

            expect($result)->toBe($downloader);
        });
    });

    describe('downloadWithProgress', function () {
        it('saves file correctly', function () {
            $content = 'test file content';
            $transport = MockHttpClient::createTransport([
                ['content' => $content, 'headers' => ['Content-Length' => [(string) strlen($content)]]],
            ]);

            $downloader = new TestableAbstractDownloader($transport);
            $downloader->testDownloadWithProgress(
                'https://test.example.com/file.txt',
                $this->getTestDataPath('downloaded.txt')
            );

            expect(file_exists($this->getTestDataPath('downloaded.txt')))->toBeTrue();
            expect(file_get_contents($this->getTestDataPath('downloaded.txt')))->toBe($content);
        });

        it('shows progress when output set', function () {
            $content = str_repeat('x', 10000);
            $transport = MockHttpClient::createTransport([
                ['content' => $content, 'headers' => ['Content-Length' => [(string) strlen($content)]]],
            ]);

            $output = new BufferedOutput;
            $downloader = new TestableAbstractDownloader($transport);
            $downloader->setOutput($output);
            $downloader->testDownloadWithProgress(
                'https://test.example.com/file.txt',
                $this->getTestDataPath('downloaded.txt')
            );

            $display = $output->fetch();
            expect($display)->toContain('%');
        });

        it('works without output set', function () {
            $content = 'test content';
            $transport = MockHttpClient::createTransport([
                ['content' => $content, 'headers' => ['Content-Length' => [(string) strlen($content)]]],
            ]);

            $downloader = new TestableAbstractDownloader($transport);
            $downloader->testDownloadWithProgress(
                'https://test.example.com/file.txt',
                $this->getTestDataPath('downloaded.txt')
            );

            expect(file_exists($this->getTestDataPath('downloaded.txt')))->toBeTrue();
        });

        it('handles empty Content-Length header', function () {
            $content = 'test content';
            $transport = MockHttpClient::createTransport([
                ['content' => $content, 'headers' => []],
            ]);

            $downloader = new TestableAbstractDownloader($transport);
            $downloader->testDownloadWithProgress(
                'https://test.example.com/file.txt',
                $this->getTestDataPath('downloaded.txt')
            );

            expect(file_exists($this->getTestDataPath('downloaded.txt')))->toBeTrue();
            expect(file_get_contents($this->getTestDataPath('downloaded.txt')))->toBe($content);
        });

        it('handles zero Content-Length header', function () {
            $content = 'test content';
            $transport = MockHttpClient::createTransport([
                ['content' => $content, 'headers' => ['Content-Length' => ['0']]],
            ]);

            $downloader = new TestableAbstractDownloader($transport);
            $downloader->setOutput(new BufferedOutput);
            $downloader->testDownloadWithProgress(
                'https://test.example.com/file.txt',
                $this->getTestDataPath('downloaded.txt')
            );

            expect(file_exists($this->getTestDataPath('downloaded.txt')))->toBeTrue();
        });

        it('throws on write failure', function () {
            $content = 'test content';
            $transport = MockHttpClient::createTransport([
                ['content' => $content, 'headers' => ['Content-Length' => [(string) strlen($content)]]],
            ]);

            $downloader = new TestableAbstractDownloader($transport);

            set_error_handler(fn () => true);
            try {
                $downloader->testDownloadWithProgress(
                    'https://test.example.com/file.txt',
                    '/nonexistent/path/file.txt'
                );
            } finally {
                restore_error_handler();
            }
        })->throws(GeonamesException::class);

        it('downloads large files in chunks', function () {
            // Create content larger than CHUNK_SIZE (8192)
            $content = str_repeat('x', 20000);
            $transport = MockHttpClient::createTransport([
                ['content' => $content, 'headers' => ['Content-Length' => [(string) strlen($content)]]],
            ]);

            $downloader = new TestableAbstractDownloader($transport);
            $downloader->testDownloadWithProgress(
                'https://test.example.com/large.txt',
                $this->getTestDataPath('large.txt')
            );

            expect(file_exists($this->getTestDataPath('large.txt')))->toBeTrue();
            expect(strlen(file_get_contents($this->getTestDataPath('large.txt'))))->toBe(20000);
        });
    });

    describe('createProgressBar', function () {
        it('returns null when no output set', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new TestableAbstractDownloader($transport);

            $result = $downloader->testCreateProgressBar(100);

            expect($result)->toBeNull();
        });

        it('returns null when size is zero', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new TestableAbstractDownloader($transport);
            $downloader->setOutput(new BufferedOutput);

            $result = $downloader->testCreateProgressBar(0);

            expect($result)->toBeNull();
        });

        it('returns ProgressBar with output and size', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new TestableAbstractDownloader($transport);
            $downloader->setOutput(new BufferedOutput);

            $result = $downloader->testCreateProgressBar(100);

            expect($result)->toBeInstanceOf(ProgressBar::class);
        });

        it('configures progress bar format', function () {
            $transport = MockHttpClient::createTransport([]);
            $output = new BufferedOutput;
            $downloader = new TestableAbstractDownloader($transport);
            $downloader->setOutput($output);

            $progressBar = $downloader->testCreateProgressBar(100);
            $progressBar->setProgress(50);
            $progressBar->finish();

            $display = $output->fetch();
            expect($display)->toContain('%');
        });
    });

    describe('finishProgressBar', function () {
        it('handles null gracefully', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new TestableAbstractDownloader($transport);

            $downloader->testFinishProgressBar(null);

            expect(true)->toBeTrue(); // No exception thrown
        });

        it('finishes and adds newline', function () {
            $transport = MockHttpClient::createTransport([]);
            $output = new BufferedOutput;
            $downloader = new TestableAbstractDownloader($transport);
            $downloader->setOutput($output);

            $progressBar = $downloader->testCreateProgressBar(100);
            $downloader->testFinishProgressBar($progressBar);

            $display = $output->fetch();
            expect($display)->toContain("\n");
        });
    });

    describe('getBaseUrl', function () {
        it('returns correct base URL', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new TestableAbstractDownloader($transport);

            expect($downloader->testGetBaseUrl())->toBe('https://test.example.com/');
        });
    });
});
