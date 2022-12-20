<?php

namespace Farzai\Geonames\BodyParsers;

class FromText implements BodyParserInterface
{
    /**
     * @var int
     */
    protected $startAtLine = 0;

    /**
     * @var int
     */
    protected $endAtLine;

    /**
     * Start at line
     *
     * @param  int  $index
     * @return $this
     */
    public function startAt(int $index)
    {
        $this->startAtLine = $index;

        return $this;
    }

    /**
     * End at line
     *
     * @param  int  $index
     * @return $this
     */
    public function endAt(int $index)
    {
        $this->endAtLine = $index;

        return $this;
    }

    public function parse($body)
    {
        // Delete comments
        $body = preg_replace('/^#.*$/m', '', $body);

        // Delete empty lines and trim
        $lines = array_filter(explode(PHP_EOL, $body), fn ($line) => !empty(trim($line)));

        $rowItems = array_map(function ($rawItem) {
            return $this->normalizeItem(explode("\t", $rawItem));
        }, $lines);

        if ($this->startAtLine) {
            $rowItems = array_slice($rowItems, $this->startAtLine);
        }

        if ($this->endAtLine) {
            $rowItems = array_slice($rowItems, 0, $this->endAtLine);
        }

        return array_values(array_filter($rowItems));
    }

    /**
     * Normalize item
     *
     * @param  array  $item
     * @return array
     */
    protected function normalizeItem(array $rawItem): array
    {
        return array_map(function ($value) {
            if (is_null($value)) {
                return null;
            }

            if (is_numeric($value)) {
                return (int) $value;
            }

            return trim((string)$value);
        }, $rawItem);
    }
}
