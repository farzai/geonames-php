<?php

namespace Farzai\Geonames\Entities\Traits;

trait HasAttributesTrait
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'id') {
            return $this->getIdentifier();
        }

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
    }

    /**
     * @param  string  $name
     * @param  mixed  $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @param  string  $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @param  string  $name
     */
    public function __unset($name)
    {
        unset($this->attributes[$name]);
    }
}
