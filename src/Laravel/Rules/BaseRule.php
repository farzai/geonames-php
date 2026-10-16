<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Closure;
use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Models\Country;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Base of the rules: one error per mistake. A parent field that is empty or not valid
 * passes the child's check, so only the parent's own rule reports it.
 */
abstract class BaseRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    protected ?string $countryField = null;

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /** The field that holds the country (an ISO 3166-1 alpha-2, alpha-3 or numeric code). */
    public function forCountryField(string $field): static
    {
        $this->countryField = $field;

        return $this;
    }

    /**
     * Data that was not fetched is a failed check with a message that names the command,
     * never an exception out of validation.
     */
    final public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $this->check($attribute, $value, $fail);
        } catch (NotFetchedException) {
            $this->message($fail, 'not_fetched', $attribute);
        }
    }

    abstract protected function check(string $attribute, mixed $value, Closure $fail): void;

    protected function repository(): GeonamesRepository
    {
        return app(GeonamesRepository::class);
    }

    /**
     * The country named by the parent field, or null when it is empty or unknown.
     */
    protected function parentCountry(string $attribute): ?Country
    {
        $value = ParentField::value($attribute, $this->countryField, $this->data);
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        return $value === '' ? null : $this->repository()->country($value);
    }

    protected function message(Closure $fail, string $key, string $attribute): void
    {
        $fail(trans('geonames::validation.'.$key, ['attribute' => $attribute, 'other' => (string) $this->countryField]));
    }
}
