<?php

/**
 * Lightspeed X-Series API Client
 *
 * A PHP client for the Lightspeed X-Series (formerly Vend) API
 *
 * @package    LightspeedXSeries
 * @author     Anytech
 * @copyright  2025 Anytech
 * @license    MIT
 * @link       https://github.com/anytech/lightspeed-x-series-api
 */

namespace LightspeedXSeries;

class LightspeedAPI
{
    private string $url;
    private LightspeedRequest $request;
    private bool $debug = false;
    private mixed $lastResultRaw = null;
    private mixed $lastResult = null;

    public bool $automaticDepage = false;
    public string $defaultOutlet = 'Main Outlet';
    public bool $allowTimeSlip = false;

    /**
     * @param string $url URL of your store (e.g., https://mystore.retail.lightspeed.app)
     * @param string $tokenType Token type (usually 'Bearer')
     * @param string $accessToken Access token for API
     * @param string $requestClass Request class for dependency injection/testing
     */
    public function __construct(
        string $url,
        string $tokenType,
        string $accessToken,
        string $requestClass = LightspeedRequest::class
    ) {
        $this->url = rtrim($url, '/');
        $this->request = new $requestClass($url, $tokenType, $accessToken);
    }

    /**
     * Enable debug mode
     */
    public function debug(bool $status = true): void
    {
        $this->request->setOpt('debug', $status);
        $this->debug = $status;
    }

    // ========================================================================
    // PRODUCTS (API 3.0 / 2.0)
    // ========================================================================

    /**
     * Get all products (API 3.0)
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getProducts(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/3.0/products' . $path);
    }

    /**
     * Get a single product by ID (API 3.0)
     *
     * @param string $id Product UUID
     * @return object API response with product data
     */
    public function getProduct(string $id): object
    {
        return $this->requestApi('/api/3.0/products/' . $id);
    }

    /**
     * Update a product (API 2.1)
     * Note: name/description go in 'common' section for product families
     *
     * @param string $productId Product UUID
     * @param array $data Update data (e.g., ['name' => '...', 'description' => '...'])
     * @return object API response
     */
    public function updateProduct(string $productId, array $data): object
    {
        $payload = ['common' => $data];
        return $this->putRequest('/api/2.1/products/' . $productId, $payload);
    }

    // ========================================================================
    // CUSTOMERS (API 2.0)
    // ========================================================================

    /**
     * Get all customers
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getCustomers(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/customers' . $path);
    }

    /**
     * Get a single customer by ID
     *
     * @param string $id Customer UUID
     * @return object API response with customer data
     */
    public function getCustomer(string $id): object
    {
        return $this->requestApi('/api/2.0/customers/' . $id);
    }

    /**
     * Create a new customer
     *
     * @param array $data Customer data
     * @return object API response
     */
    public function createCustomer(array $data): object
    {
        return $this->postRequest('/api/2.0/customers', $data);
    }

    /**
     * Update a customer
     *
     * @param string $id Customer UUID
     * @param array $data Update data
     * @return object API response
     */
    public function updateCustomer(string $id, array $data): object
    {
        return $this->putRequest('/api/2.0/customers/' . $id, $data);
    }

    // ========================================================================
    // BRANDS (API 2.0)
    // ========================================================================

    /**
     * Get all brands
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getBrands(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/brands' . $path);
    }

    /**
     * Get a single brand by ID
     *
     * @param string $id Brand UUID
     * @return object API response with brand data
     */
    public function getBrand(string $id): object
    {
        return $this->requestApi('/api/2.0/brands/' . $id);
    }

    /**
     * Update a brand
     *
     * @param string $brandId Brand UUID
     * @param array $data Update data (e.g., ['name' => '...'])
     * @return object API response
     */
    public function updateBrand(string $brandId, array $data): object
    {
        return $this->putRequest('/api/2.0/brands/' . $brandId, $data);
    }

    // ========================================================================
    // TAGS (API 2.0)
    // ========================================================================

    /**
     * Get all tags
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getTags(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/tags' . $path);
    }

    // ========================================================================
    // CATEGORIES (API 2.0)
    // ========================================================================

    /**
     * Get all product categories
     *
     * @return object API response with data array
     */
    public function getCategories(): object
    {
        return $this->requestApi('/api/2.0/product_categories');
    }

    /**
     * Update a category (uses bulk endpoint)
     *
     * @param string $categoryId Category UUID
     * @param array $data Update data (e.g., ['name' => '...'])
     * @return object API response
     */
    public function updateCategory(string $categoryId, array $data): object
    {
        $payload = [
            'categories' => [
                array_merge(['id' => $categoryId], $data)
            ]
        ];
        return $this->postRequest('/api/2.0/product_categories/bulk', $payload);
    }

    // ========================================================================
    // INVENTORY (API 2.0)
    // ========================================================================

    /**
     * Get inventory records
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getInventory(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/inventory' . $path);
    }

    /**
     * Get inventory for a specific product
     *
     * @param string $productId Product UUID
     * @return object|null API response or null if product not found
     */
    public function getProductInventory(string $productId): ?object
    {
        $result = $this->requestApi('/api/2.0/products/' . $productId . '/inventory');
        return isset($result->data) ? $result : null;
    }

    // ========================================================================
    // PRICING (API 2.0)
    // ========================================================================

    /**
     * Get price book products
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getPriceBookProducts(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/price_book_products' . $path);
    }

    /**
     * Get a single price book
     *
     * @param string $id Price book UUID
     * @return object API response
     */
    public function getPriceBook(string $id): object
    {
        return $this->requestApi('/api/2.0/price_books/' . $id);
    }

    /**
     * Get all price books
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getPriceBooks(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/price_books' . $path);
    }

    // ========================================================================
    // PROMOTIONS (API 2.0)
    // ========================================================================

    /**
     * Get all promotions
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getPromotions(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/promotions' . $path);
    }

    /**
     * Get a single promotion
     *
     * @param string $id Promotion UUID
     * @return object API response
     */
    public function getPromotion(string $id): object
    {
        return $this->requestApi('/api/2.0/promotions/' . $id);
    }

    /**
     * Get promo codes for a promotion
     *
     * @param string $promotionId Promotion UUID
     * @return object API response
     */
    public function getPromoCodes(string $promotionId): object
    {
        return $this->requestApi('/api/2.0/promotions/' . $promotionId . '/promocodes');
    }

    // ========================================================================
    // SALES (API 2.0 / 0.9)
    // ========================================================================

    /**
     * Get all sales
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getSales(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/sales' . $path);
    }

    /**
     * Get a single sale by ID
     *
     * @param string $id Sale UUID
     * @return LightspeedSale
     */
    public function getSale(string $id): LightspeedSale
    {
        $result = $this->requestApi('/api/2.0/sales/' . $id);
        if (!isset($result->data)) {
            throw new Exception('Unexpected result for sale request');
        }
        return new LightspeedSale($result->data, $this);
    }

    /**
     * Create a new sale (API 0.9 - no 2.0 equivalent yet)
     *
     * @param LightspeedSale $sale Sale object
     * @return LightspeedSale Created sale
     */
    public function createSale(LightspeedSale $sale): LightspeedSale
    {
        $result = $this->requestLegacy('/api/register_sales', $sale->toArray());
        return new LightspeedSale($result->register_sale, $this);
    }

    /**
     * Get sale fulfillments
     *
     * @param string $saleId Sale UUID
     * @return object API response
     */
    public function getSaleFulfillments(string $saleId): object
    {
        return $this->requestApi('/api/2.0/sales/' . $saleId . '/fulfillments');
    }

    /**
     * Create a fulfillment for a sale
     *
     * @param string $saleId Sale UUID
     * @param array $data Fulfillment data
     * @return object API response
     */
    public function createFulfillment(string $saleId, array $data): object
    {
        return $this->postRequest('/api/2.0/sales/' . $saleId . '/fulfillments', $data);
    }

    // ========================================================================
    // REGISTERS (API 0.9)
    // ========================================================================

    /**
     * Get all registers (API 0.9)
     *
     * @param array $options Query parameters
     * @return object API response
     */
    public function getRegisters(array $options = []): object
    {
        $path = '';
        foreach ($options as $k => $v) {
            $path .= '/' . $k . '/' . urlencode($v);
        }
        return $this->requestLegacy('/api/registers' . $path);
    }

    // ========================================================================
    // OUTLETS (API 2.0)
    // ========================================================================

    /**
     * Get all outlets
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getOutlets(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/outlets' . $path);
    }

    // ========================================================================
    // PAYMENTS (API 2.0)
    // ========================================================================

    /**
     * Get payment types
     *
     * @return object API response with data array
     */
    public function getPaymentTypes(): object
    {
        return $this->requestApi('/api/2.0/payment_types');
    }

    /**
     * Get a single payment by ID
     *
     * @param string $paymentId Payment UUID
     * @return object API response
     */
    public function getPayment(string $paymentId): object
    {
        return $this->requestApi('/api/2.0/payments/' . $paymentId);
    }

    // ========================================================================
    // CUSTOMER TAXES (API 2.0)
    // ========================================================================

    /**
     * Bulk update customer taxes
     *
     * @param array $data Bulk tax update data
     * @return object API response
     */
    public function bulkUpdateCustomerTaxes(array $data): object
    {
        return $this->postRequest('/api/2.0/customer_taxes/bulk', $data);
    }

    // ========================================================================
    // CONSIGNMENTS (API 2.0)
    // ========================================================================

    /**
     * Get all consignments
     *
     * @param array $options Query parameters
     * @return object API response with data array
     */
    public function getConsignments(array $options = []): object
    {
        $path = $this->buildQueryString($options);
        return $this->requestApi('/api/2.0/consignments' . $path);
    }

    /**
     * Get a single consignment
     *
     * @param string $id Consignment UUID
     * @return object API response
     */
    public function getConsignment(string $id): object
    {
        return $this->requestApi('/api/2.0/consignments/' . $id);
    }

    // ========================================================================
    // SERVICE ORDERS (API 2.0 - new 2025)
    // ========================================================================

    /**
     * Create a service order
     *
     * @param array $data Service order data
     * @return object API response
     */
    public function createServiceOrder(array $data): object
    {
        return $this->postRequest('/api/2.0/service_orders', $data);
    }

    // ========================================================================
    // GIFT CARDS (API 2.0 / 3.0)
    // ========================================================================

    /**
     * Get a gift card by number
     *
     * @param string $number Gift card number
     * @return object API response with gift card data and balance
     */
    public function getGiftCard(string $number): object
    {
        return $this->requestApi('/api/2.0/gift_cards/' . $number);
    }

    /**
     * Get a gift card by ID (API 3.0)
     *
     * @param string $id Gift card UUID
     * @return object API response
     */
    public function getGiftCardById(string $id): object
    {
        return $this->requestApi('/api/3.0/gift_cards/' . $id);
    }

    /**
     * Create and activate a new gift card
     *
     * @param array $data Gift card data (initial balance, etc.)
     * @return object API response
     */
    public function createGiftCard(array $data): object
    {
        return $this->postRequest('/api/2.0/gift_cards', $data);
    }

    /**
     * Redeem a gift card (reduce balance)
     *
     * @param string $number Gift card number
     * @param float $amount Amount to redeem (positive number, will be made negative)
     * @param string|null $clientId Unique transaction ID to prevent duplicates
     * @return object API response
     */
    public function redeemGiftCard(string $number, float $amount, ?string $clientId = null): object
    {
        $data = [
            'type' => 'REDEEMING',
            'amount' => -abs($amount),
        ];

        if ($clientId) {
            $data['client_id'] = $clientId;
        }

        return $this->postRequest('/api/2.0/gift_cards/' . $number . '/transactions', $data);
    }

    /**
     * Reload a gift card (add to balance)
     *
     * @param string $number Gift card number
     * @param float $amount Amount to add
     * @param string|null $clientId Unique transaction ID
     * @return object API response
     */
    public function reloadGiftCard(string $number, float $amount, ?string $clientId = null): object
    {
        $data = [
            'type' => 'RELOADING',
            'amount' => abs($amount),
        ];

        if ($clientId) {
            $data['client_id'] = $clientId;
        }

        return $this->postRequest('/api/2.0/gift_cards/' . $number . '/transactions', $data);
    }

    /**
     * Void a gift card
     *
     * @param string $number Gift card number
     * @return object API response
     */
    public function voidGiftCard(string $number): object
    {
        return $this->deleteRequest('/api/2.0/gift_cards/' . $number);
    }

    // ========================================================================
    // SEARCH (API 2.0)
    // ========================================================================

    /**
     * Search for products or other entities
     *
     * @param string $query Search query
     * @param string $type Entity type to search
     * @return object API response
     */
    public function search(string $query, string $type = 'products'): object
    {
        return $this->postRequest('/api/2.0/search', [
            'query' => $query,
            'type' => $type,
        ]);
    }

    // ========================================================================
    // GENERIC REQUEST METHODS
    // ========================================================================

    /**
     * Make a custom GET request
     *
     * @param string $path API path
     * @return object API response
     */
    public function request(string $path): object
    {
        return $this->requestApi($path);
    }

    /**
     * Make a custom POST request
     *
     * @param string $path API path
     * @param array|null $data POST data
     * @return object API response
     */
    public function postRequest(string $path, ?array $data = null): object
    {
        $rawResult = $this->request->post($path, json_encode($data));
        $result = json_decode($rawResult);

        if ($result === null) {
            throw new Exception('Received null result from API');
        }

        if ($this->request->httpCode >= 400) {
            $error = $result->error ?? 'Unknown error';
            throw new Exception('HTTP ' . $this->request->httpCode . ': ' . $error . ' - ' . $rawResult);
        }

        return $result;
    }

    /**
     * Make a custom PUT request
     *
     * @param string $path API path
     * @param array|null $data PUT data
     * @return object API response
     */
    public function putRequest(string $path, ?array $data = null): object
    {
        $rawResult = $this->request->put($path, json_encode($data));
        $result = json_decode($rawResult);

        if ($result === null) {
            throw new Exception('Received null result from API');
        }

        if ($this->request->httpCode >= 400) {
            $error = $result->error ?? 'Unknown error';
            throw new Exception('HTTP ' . $this->request->httpCode . ': ' . $error . ' - ' . $rawResult);
        }

        return $result;
    }

    /**
     * Make a custom DELETE request
     *
     * @param string $path API path
     * @return object API response
     */
    public function deleteRequest(string $path): object
    {
        $rawResult = $this->request->delete($path);
        $result = json_decode($rawResult);

        if ($result === null) {
            throw new Exception('Received null result from API');
        }

        if ($this->request->httpCode >= 400) {
            $error = $result->error ?? 'Unknown error';
            throw new Exception('HTTP ' . $this->request->httpCode . ': ' . $error . ' - ' . $rawResult);
        }

        return $result;
    }

    // ========================================================================
    // INTERNAL METHODS
    // ========================================================================

    /**
     * Build query string from options array
     */
    private function buildQueryString(array $options): string
    {
        if (empty($options)) {
            return '';
        }

        return '?' . http_build_query($options);
    }

    /**
     * Make API 2.0/3.0 request
     */
    private function requestApi(string $path): object
    {
        $rawResult = $this->request->get($path);
        $result = json_decode($rawResult);

        if ($result === null) {
            throw new Exception('Received null result from API');
        }

        // Handle rate limiting (HTTP 429)
        if ($this->request->httpCode === 429) {
            $retryAfter = isset($result->{'retry-after'}) ? strtotime($result->{'retry-after'}) : time() + 60;

            if ($retryAfter < time()) {
                if ($this->allowTimeSlip) {
                    sleep(60);
                } else {
                    throw new Exception('Rate limit hit and retry-after is in the past. Check system time or set allowTimeSlip = true');
                }
            }

            if ($this->debug) {
                echo "Rate limit hit. Sleeping until " . date('r', $retryAfter) . "\n";
            }

            while (time() < $retryAfter) {
                sleep(1);
            }

            return $this->requestApi($path);
        }

        if ($this->request->httpCode >= 400) {
            $error = $result->error ?? 'Unknown error';
            throw new Exception('HTTP ' . $this->request->httpCode . ': ' . $error);
        }

        if (isset($result->error)) {
            throw new Exception($result->error . (isset($result->details) ? ': ' . $result->details : ''));
        }

        if ($this->debug) {
            $this->lastResultRaw = $rawResult;
            $this->lastResult = $result;
        }

        return $result;
    }

    /**
     * Make legacy API 0.9/1.0 request
     */
    private function requestLegacy(string $path, ?array $data = null): object
    {
        if ($data !== null) {
            $rawResult = $this->request->post($path, json_encode($data));
        } else {
            $rawResult = $this->request->get($path);
        }

        $result = json_decode($rawResult);

        if ($result === null) {
            throw new Exception('Received null result from API');
        }

        // Handle rate limiting
        if ($this->request->httpCode === 429) {
            $retryAfter = isset($result->{'retry-after'}) ? strtotime($result->{'retry-after'}) : time() + 60;

            if ($retryAfter < time() && $this->allowTimeSlip) {
                sleep(60);
            }

            while (time() < $retryAfter) {
                sleep(1);
            }

            return $this->requestLegacy($path, $data);
        }

        if ($this->request->httpCode >= 400) {
            throw new Exception('HTTP ' . $this->request->httpCode . ' error from API');
        }

        if (isset($result->error)) {
            throw new Exception($result->error . (isset($result->details) ? ': ' . $result->details : ''));
        }

        return $result;
    }
}
