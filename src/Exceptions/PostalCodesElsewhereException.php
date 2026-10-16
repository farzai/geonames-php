<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

/**
 * The snapshot has no postal-code file for the country because another Farzai
 * dataset is the authority for it (Thailand: farzai/thailand-address).
 */
final class PostalCodesElsewhereException extends GeonamesException
{
    public static function thailand(): self
    {
        return new self(
            'Thai postal codes are not part of the GeoNames snapshot. Use farzai/thailand-address '
            .'(Thailand::postalCode(), https://data.farzai.com/thailand-address/), which is the authority for them.'
        );
    }
}
