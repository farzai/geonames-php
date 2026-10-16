<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

use RuntimeException;

/**
 * Base class of everything this package throws on purpose, so callers can catch
 * one type.
 */
class GeonamesException extends RuntimeException {}
