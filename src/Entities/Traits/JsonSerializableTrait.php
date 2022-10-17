<?php

namespace Farzai\Geonames\Entities\Traits;

trait JsonSerializableTrait
{
    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson($options = 0)
    {
        return json_encode($this, $options);
    }
}
