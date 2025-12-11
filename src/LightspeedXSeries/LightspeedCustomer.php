<?php

/**
 * Lightspeed X-Series Customer
 *
 * Represents a customer in Lightspeed X-Series
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

class LightspeedCustomer extends LightspeedObject
{
    /**
     * Save the customer (create or update)
     *
     * @throws Exception If API instance not set or save fails
     */
    public function save(): void
    {
        if ($this->api === null) {
            throw new Exception('API instance required to save customer');
        }

        $id = $this->getId();

        if ($id) {
            $result = $this->api->updateCustomer($id, $this->getChangedProperties());
        } else {
            $result = $this->api->createCustomer($this->toArray());
        }

        if (is_object($result)) {
            $this->properties = (array) $result;
            $this->initialProperties = $this->properties;
        }
    }

    /**
     * Get customer code
     */
    public function getCustomerCode(): ?string
    {
        return $this->properties['customer_code'] ?? null;
    }

    /**
     * Get first name
     */
    public function getFirstName(): ?string
    {
        return $this->properties['first_name'] ?? null;
    }

    /**
     * Get last name
     */
    public function getLastName(): ?string
    {
        return $this->properties['last_name'] ?? null;
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        $firstName = $this->getFirstName() ?? '';
        $lastName = $this->getLastName() ?? '';

        return trim($firstName . ' ' . $lastName);
    }

    /**
     * Get email address
     */
    public function getEmail(): ?string
    {
        return $this->properties['email'] ?? null;
    }

    /**
     * Get phone number
     */
    public function getPhone(): ?string
    {
        return $this->properties['phone'] ?? null;
    }

    /**
     * Get mobile phone number
     */
    public function getMobile(): ?string
    {
        return $this->properties['mobile'] ?? null;
    }

    /**
     * Get company name
     */
    public function getCompanyName(): ?string
    {
        return $this->properties['company_name'] ?? null;
    }

    /**
     * Get customer group ID
     */
    public function getCustomerGroupId(): ?string
    {
        return $this->properties['customer_group_id'] ?? null;
    }

    /**
     * Check if customer accepts marketing
     */
    public function acceptsMarketing(): bool
    {
        return (bool) ($this->properties['do_not_email'] ?? false) === false;
    }

    /**
     * Get loyalty balance
     */
    public function getLoyaltyBalance(): float
    {
        return (float) ($this->properties['loyalty_balance'] ?? 0);
    }

    /**
     * Get store credit balance
     */
    public function getStoreCreditBalance(): float
    {
        return (float) ($this->properties['balance'] ?? 0);
    }

    /**
     * Get physical address
     */
    public function getPhysicalAddress(): array
    {
        return [
            'street' => $this->properties['physical_address1'] ?? null,
            'street2' => $this->properties['physical_address2'] ?? null,
            'city' => $this->properties['physical_city'] ?? null,
            'state' => $this->properties['physical_state'] ?? null,
            'postcode' => $this->properties['physical_postcode'] ?? null,
            'country' => $this->properties['physical_country_id'] ?? null,
        ];
    }

    /**
     * Get postal address
     */
    public function getPostalAddress(): array
    {
        return [
            'street' => $this->properties['postal_address1'] ?? null,
            'street2' => $this->properties['postal_address2'] ?? null,
            'city' => $this->properties['postal_city'] ?? null,
            'state' => $this->properties['postal_state'] ?? null,
            'postcode' => $this->properties['postal_postcode'] ?? null,
            'country' => $this->properties['postal_country_id'] ?? null,
        ];
    }
}
