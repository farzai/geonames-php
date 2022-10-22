<?php

namespace Farzai\Geonames\Entities\Traits;

trait HasAttributesTrait
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Cast attributes
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            if (isset($this->casts[$name])) {
                return $this->castAttribute($name, $this->attributes[$name]);
            }

            return $this->attributes[$name];
        }

        if ($name === 'id') {
            return $this->getIdentifier();
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

    protected function castAttribute(string $name, $value)
    {
        switch ($this->casts[$name]) {
            case 'string':
                return (string) $value;
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return (bool) $value;
            case 'array':
                return (array) $value;
            case 'object':
                return (object) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
