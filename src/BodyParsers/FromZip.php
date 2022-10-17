<?php

namespace Farzai\Geonames\BodyParsers;

use ZipArchive;

class FromZip implements BodyParserInterface
{
    protected string $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Parse the body.
     *
     * @param  string  $body
     * @return string
     */
    public function parse($body)
    {
        $body = (string) $body;

        $tempName = tempnam(sys_get_temp_dir(), 'geonames');

        @file_put_contents($tempName, $body);

        if (! file_exists($tempName)) {
            throw new \RuntimeException('Cannot create temp file');
        }

        $zip = new ZipArchive();

        if ($zip->open($tempName) === true) {
            $file = $zip->getFromName($this->filename);
            $zip->close();

            // Clean up
            unlink($tempName);

            return $file;
        }

        throw new \Exception('Could not open zip file');
    }
}
