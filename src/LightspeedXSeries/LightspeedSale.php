<?php

/**
 * Lightspeed X-Series Sale
 *
 * Represents a sale/order in Lightspeed X-Series
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

class LightspeedSale extends LightspeedObject
{
    /**
     * Save the sale (create or update)
     *
     * @throws Exception If API instance not set or save fails
     */
    public function save(): void
    {
        if ($this->api === null) {
            throw new Exception('API instance required to save sale');
        }

        $result = $this->api->createSale($this->toArray());

        if (is_object($result)) {
            $this->properties = (array) $result;
            $this->initialProperties = $this->properties;
        }
    }

    /**
     * Get the customer associated with this sale
     *
     * @return LightspeedCustomer
     * @throws Exception If API not set or customer not found
     */
    public function getCustomer(): LightspeedCustomer
    {
        if ($this->api === null) {
            throw new Exception('API instance required to fetch customer');
        }

        $customerId = $this->getCustomerId();

        if (!$customerId) {
            throw new Exception('Sale has no customer ID');
        }

        return $this->api->getCustomer($customerId);
    }

    /**
     * Get products associated with this sale
     *
     * @return LightspeedProduct[]
     * @throws Exception If API not set
     */
    public function getProducts(): array
    {
        if ($this->api === null) {
            throw new Exception('API instance required to fetch products');
        }

        $products = [];
        $lineItems = $this->properties['register_sale_products']
            ?? $this->properties['line_items']
            ?? [];

        foreach ($lineItems as $item) {
            $item = (object) $item;
            $productId = $item->product_id ?? null;

            if ($productId) {
                $products[] = $this->api->getProduct($productId);
            }
        }

        return $products;
    }

    /**
     * Get customer ID
     */
    public function getCustomerId(): ?string
    {
        return $this->properties['customer_id'] ?? null;
    }

    /**
     * Get sale number/invoice number
     */
    public function getInvoiceNumber(): ?string
    {
        return $this->properties['invoice_number'] ?? null;
    }

    /**
     * Get sale status
     */
    public function getStatus(): ?string
    {
        return $this->properties['status'] ?? null;
    }

    /**
     * Check if sale is complete
     */
    public function isComplete(): bool
    {
        return $this->getStatus() === 'CLOSED';
    }

    /**
     * Check if sale is on account (layby/on account)
     */
    public function isOnAccount(): bool
    {
        $status = $this->getStatus();
        return in_array($status, ['LAYBY', 'ONACCOUNT', 'LAYBY_CLOSED', 'ONACCOUNT_CLOSED']);
    }

    /**
     * Get total price (tax inclusive)
     */
    public function getTotalPrice(): float
    {
        return (float) ($this->properties['total_price'] ?? $this->properties['totals']['total_payment'] ?? 0);
    }

    /**
     * Get total tax
     */
    public function getTotalTax(): float
    {
        return (float) ($this->properties['total_tax'] ?? $this->properties['totals']['total_tax'] ?? 0);
    }

    /**
     * Get line items
     */
    public function getLineItems(): array
    {
        return $this->properties['register_sale_products']
            ?? $this->properties['line_items']
            ?? [];
    }

    /**
     * Get payments
     */
    public function getPayments(): array
    {
        return $this->properties['register_sale_payments']
            ?? $this->properties['payments']
            ?? [];
    }

    /**
     * Get total paid amount
     */
    public function getTotalPaid(): float
    {
        $total = 0;
        foreach ($this->getPayments() as $payment) {
            $payment = (object) $payment;
            $total += (float) ($payment->amount ?? 0);
        }
        return $total;
    }

    /**
     * Get balance due
     */
    public function getBalanceDue(): float
    {
        return $this->getTotalPrice() - $this->getTotalPaid();
    }

    /**
     * Get outlet ID
     */
    public function getOutletId(): ?string
    {
        return $this->properties['outlet_id'] ?? null;
    }

    /**
     * Get register ID
     */
    public function getRegisterId(): ?string
    {
        return $this->properties['register_id'] ?? null;
    }

    /**
     * Get user ID (salesperson)
     */
    public function getUserId(): ?string
    {
        return $this->properties['user_id'] ?? null;
    }

    /**
     * Get sale date
     */
    public function getSaleDate(): ?string
    {
        return $this->properties['sale_date'] ?? null;
    }

    /**
     * Get note
     */
    public function getNote(): ?string
    {
        return $this->properties['note'] ?? null;
    }
}
