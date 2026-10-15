<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

use RuntimeException;

/**
 * Base exception class for all GeoNames library errors.
 *
 * This exception serves as the parent class for all domain-specific
 * exceptions thrown by the GeoNames library. It provides a consistent
 * exception hierarchy for error handling.
 */
class GeonamesException extends RuntimeException
{
    /**
     * Create a new exception for file operation failures.
     *
     * @param  string  $operation  The operation that failed (e.g., 'open', 'read', 'write')
     * @param  string  $path  The file path involved in the operation
     * @param  string|null  $reason  Optional reason for the failure
     */
    public static function fileOperationFailed(string $operation, string $path, ?string $reason = null): self
    {
        $message = sprintf('Failed to %s file: %s', $operation, $path);

        if ($reason !== null) {
            $message .= ' - '.$reason;
        }

        return new self($message);
    }

    /**
     * Create a new exception for ZIP archive failures.
     *
     * @param  string  $zipFile  The ZIP file path
     * @param  string|null  $reason  Optional reason for the failure
     */
    public static function zipOperationFailed(string $zipFile, ?string $reason = null): self
    {
        $message = sprintf('Failed to open ZIP file: %s', $zipFile);

        if ($reason !== null) {
            $message .= ' - '.$reason;
        }

        return new self($message);
    }

    /**
     * A readable description of a ZipArchive::open() error code.
     */
    public static function describeZipError(int $code): string
    {
        return match ($code) {
            \ZipArchive::ER_NOZIP => 'not a ZIP archive',
            \ZipArchive::ER_INCONS => 'the archive is inconsistent or damaged',
            \ZipArchive::ER_CRC => 'a checksum does not match; the archive is damaged',
            \ZipArchive::ER_MEMORY => 'out of memory',
            \ZipArchive::ER_NOENT => 'the file does not exist',
            \ZipArchive::ER_OPEN => 'the file cannot be opened',
            \ZipArchive::ER_READ => 'the file cannot be read',
            \ZipArchive::ER_SEEK => 'a seek in the file failed',
            default => 'ZipArchive error '.$code,
        };
    }

    /**
     * Create a new exception when required data is not found.
     *
     * @param  string  $dataType  The type of data not found (e.g., 'txt file', 'admin codes')
     * @param  string  $location  Where the data was expected
     */
    public static function dataNotFound(string $dataType, string $location): self
    {
        return new self(sprintf('No %s found in %s', $dataType, $location));
    }

    /**
     * Create a new exception for missing dependencies.
     *
     * @param  string  $dependency  The missing dependency name
     * @param  string  $installCommand  The command to install the dependency
     */
    public static function dependencyMissing(string $dependency, string $installCommand): self
    {
        return new self(sprintf(
            '%s not found. Please install it using: %s',
            $dependency,
            $installCommand
        ));
    }
}
