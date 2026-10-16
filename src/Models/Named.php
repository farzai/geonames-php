<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

/**
 * Names are `{bcp47: name}` and hold only the languages the pipeline selected (th and
 * en first): a language with no name here is not an error, `name()` falls back.
 * The rule that picked each name is in the snapshot's llms-full.txt: the preferred name
 * of the language, or the first current, non-historic, non-colloquial one.
 */
trait Named
{
    /**
     * @return array<string, string>
     */
    public function names(): array
    {
        $names = $this->row['names'] ?? [];

        return is_array($names) ? array_filter($names, 'is_string') : [];
    }

    /**
     * The name in a language, or (with `$fallback`) GeoNames' default name.
     */
    public function name(?string $language = null, bool $fallback = true): ?string
    {
        if ($language !== null) {
            $found = $this->names()[strtolower($language)] ?? null;
            if ($found !== null || ! $fallback) {
                return $found;
            }
        }

        return $this->string('name');
    }

    public function nameTh(): ?string
    {
        return $this->name('th', false);
    }

    public function nameEn(): ?string
    {
        return $this->name('en');
    }

    public function asciiName(): ?string
    {
        return $this->string('ascii_name');
    }

    public function geonameId(): ?int
    {
        return $this->int('geoname_id');
    }

    public function wikidataId(): ?string
    {
        return $this->string('wikidata_id');
    }

    public function kind(): string
    {
        return Kind::from($this->row['kind'] ?? null);
    }
}
