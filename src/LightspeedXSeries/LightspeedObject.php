<?php

/**
 * Lightspeed X-Series Base Object
 *
 * Base class for all Lightspeed X-Series entities
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

abstract class LightspeedObject
{
    protected ?LightspeedAPI $api = null;
    protected array $properties = [];
    protected array $initialProperties = [];

    /**
     * @param object|array|null $data Initial data
     * @param LightspeedAPI|null $api API instance for save operations
     */
    public function __construct(object|array|null $data = null, ?LightspeedAPI $api = null)
    {
        $this->api = $api;

        if ($data !== null) {
            $dataArray = is_object($data) ? (array) $data : $data;
            foreach ($dataArray as $key => $value) {
                $this->properties[$key] = $value;
            }
            $this->initialProperties = $this->properties;
        }
    }

    public function __set(string $key, mixed $value): void
    {
        $this->properties[$key] = $value;
    }

    public function __get(string $key): mixed
    {
        return $this->properties[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->properties[$key]);
    }

    /**
     * Clear all properties
     */
    public function clear(): void
    {
        $this->properties = [];
    }

    /**
     * Get all properties as array
     */
    public function toArray(): array
    {
        return $this->properties;
    }

    /**
     * Get only changed properties (plus ID)
     * Useful for PATCH/PUT operations
     */
    public function getChangedProperties(): array
    {
        $output = [];

        foreach ($this->properties as $key => $value) {
            if ($key === 'id') {
                $output[$key] = $value;
                continue;
            }

            if (!isset($this->initialProperties[$key]) || $value !== $this->initialProperties[$key]) {
                $output[$key] = $value;
            }
        }

        return $output;
    }

    /**
     * Check if property has changed
     */
    public function hasChanged(string $key): bool
    {
        if (!isset($this->initialProperties[$key])) {
            return isset($this->properties[$key]);
        }

        return $this->properties[$key] !== $this->initialProperties[$key];
    }

    /**
     * Check if any properties have changed
     */
    public function isDirty(): bool
    {
        foreach ($this->properties as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            if ($this->hasChanged($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the entity ID
     */
    public function getId(): ?string
    {
        return $this->properties['id'] ?? null;
    }
}
