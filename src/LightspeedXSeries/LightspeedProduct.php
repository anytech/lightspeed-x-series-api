<?php

/**
 * Lightspeed X-Series Product
 *
 * Represents a product in Lightspeed X-Series
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

class LightspeedProduct extends LightspeedObject
{
    /**
     * Save the product (create or update)
     *
     * @throws Exception If API instance not set or save fails
     */
    public function save(): void
    {
        if ($this->api === null) {
            throw new Exception('API instance required to save product');
        }

        $id = $this->getId();

        if ($id) {
            $result = $this->api->updateProduct($id, $this->getChangedProperties());
        } else {
            $result = $this->api->request('/api/2.0/products', $this->toArray(), 'POST');
        }

        if (is_object($result)) {
            $this->properties = (array) $result;
            $this->initialProperties = $this->properties;
        }
    }

    /**
     * Get inventory count for a specific outlet or total across all outlets
     *
     * @param string|null $outletName Specific outlet name, or null for total
     * @return float Inventory count
     */
    public function getInventory(?string $outletName = null): float
    {
        $inventory = $this->properties['inventory'] ?? [];

        if (!is_array($inventory)) {
            return 0;
        }

        $total = 0;
        foreach ($inventory as $item) {
            $item = (object) $item;
            $itemOutlet = $item->outlet_name ?? $item->outlet_id ?? null;

            if ($outletName !== null && $itemOutlet === $outletName) {
                return (float) ($item->count ?? $item->inventory_level ?? 0);
            }

            $total += (float) ($item->count ?? $item->inventory_level ?? 0);
        }

        return $total;
    }

    /**
     * Set inventory count for an outlet
     *
     * @param float $count Inventory count
     * @param string|null $outletName Outlet name (uses first outlet if null)
     */
    public function setInventory(float $count, ?string $outletName = null): void
    {
        $inventory = $this->properties['inventory'] ?? [];

        if (is_array($inventory)) {
            foreach ($inventory as $key => $item) {
                $item = (object) $item;
                $itemOutlet = $item->outlet_name ?? null;

                if ($itemOutlet === $outletName || $outletName === null) {
                    if (is_array($this->properties['inventory'][$key])) {
                        $this->properties['inventory'][$key]['count'] = $count;
                    } else {
                        $this->properties['inventory'][$key]->count = $count;
                    }
                    return;
                }
            }
        }

        $this->properties['inventory'] = [
            [
                'outlet_name' => $outletName ?? 'Main Outlet',
                'count' => $count,
            ]
        ];
    }

    /**
     * Get product name
     */
    public function getName(): ?string
    {
        return $this->properties['name'] ?? null;
    }

    /**
     * Get product SKU
     */
    public function getSku(): ?string
    {
        return $this->properties['sku'] ?? null;
    }

    /**
     * Get product handle
     */
    public function getHandle(): ?string
    {
        return $this->properties['handle'] ?? null;
    }

    /**
     * Get retail price (tax inclusive)
     */
    public function getPrice(): ?float
    {
        $price = $this->properties['price'] ?? $this->properties['price_including_tax'] ?? null;
        return $price !== null ? (float) $price : null;
    }

    /**
     * Get supply price (cost)
     */
    public function getSupplyPrice(): ?float
    {
        $price = $this->properties['supply_price'] ?? null;
        return $price !== null ? (float) $price : null;
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return (bool) ($this->properties['active'] ?? true);
    }

    /**
     * Get brand ID
     */
    public function getBrandId(): ?string
    {
        return $this->properties['brand_id'] ?? null;
    }

    /**
     * Get category IDs (product type IDs in API terminology)
     */
    public function getCategoryIds(): array
    {
        return $this->properties['product_type_id'] ?? $this->properties['category_ids'] ?? [];
    }

    /**
     * Get supplier ID
     */
    public function getSupplierId(): ?string
    {
        return $this->properties['supplier_id'] ?? null;
    }
}
