![Build Status](https://github.com/anytech/lightspeed-x-series-api/actions/workflows/tests.yml/badge.svg)
![License](https://img.shields.io/github/license/anytech/lightspeed-x-series-api)
![PHP Version](https://img.shields.io/packagist/php-v/anytech/lightspeed-x-series-api)
![Latest Version](https://img.shields.io/packagist/v/anytech/lightspeed-x-series-api)
![Downloads](https://img.shields.io/packagist/dt/anytech/lightspeed-x-series-api)

# Lightspeed X-Series API Client for PHP

A PHP client for the Lightspeed X-Series (formerly Vend) API with built-in OAuth2 support.

**Updated for Lightspeed's new date-based API versioning (January 2026+)**

## Requirements

- PHP 8.0+
- cURL extension
- JSON extension

## Installation

```bash
composer require anytech/lightspeed-x-series-api
```

## Quick Start

```php
use LightspeedXSeries\LightspeedAPI;

$api = new LightspeedAPI(
    'https://mystore.retail.lightspeed.app',
    'Bearer',
    'your-access-token'
);

// Get all products
$products = $api->apiRequest('products', 'get');
foreach ($products->data as $product) {
    echo $product->name . "\n";
}

// Get a single product
$product = $api->apiRequest('products/abc-123-uuid', 'get');

// Create a customer
$customer = $api->apiRequest('customers', 'post', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);

// Update a product
$api->apiRequest('products/abc-123-uuid', 'put', [
    'name' => 'Updated Product Name',
]);

// Delete a product
$api->apiRequest('products/abc-123-uuid', 'delete');
```

## API Versioning (Date-Based)

As of January 2026, Lightspeed uses date-based API versioning instead of semantic versioning.

### Version Format

Versions are identified by their release date in `YYYY-MM` format:
- `2026-01` (January 2026)
- `2026-04` (April 2026)
- etc.

Each version includes the **full set of APIs** - you no longer need to manage different versions for different endpoints. Your application can reference a single version string to access all capabilities.

### Release Schedule

Lightspeed publishes a new API release every **3 months** (quarterly). Each version is supported for a minimum of **12 months**.

### Setting the Default Version

Set the API version once at application startup:

```php
use LightspeedXSeries\LightspeedAPI;

// Set the default version for your entire application
// Do this once at application bootstrap/initialization
LightspeedAPI::setDefaultVersion('2026-01');

// All subsequent API instances will use this version
$api = new LightspeedAPI($url, 'Bearer', $token);
$products = $api->apiRequest('products', 'get');  // Uses 2026-01
```

### Version Configuration Options

```php
use LightspeedXSeries\LightspeedAPI;

// Option 1: Set global default (recommended for site-wide configuration)
LightspeedAPI::setDefaultVersion('2026-01');

// Option 2: Set version per instance via constructor
$api = new LightspeedAPI($url, 'Bearer', $token, '2026-04');

// Option 3: Set version on existing instance
$api = new LightspeedAPI($url, 'Bearer', $token);
$api->setVersion('2026-04');

// Option 4: Override version for a single request
$api->apiRequest('products', 'get', null, '2026-04');

// Check current versions
echo LightspeedAPI::getDefaultVersion();  // Global default
echo $api->getVersion();                  // Instance version (or default if not set)
```

### Upgrading Your Application

When Lightspeed releases a new API version:

1. Review the [API Changelog](https://x-series-api.lightspeedhq.com/changelog) for breaking changes
2. Test your application against the new version
3. Update your default version in one place:

```php
// Before (using 2026-01)
LightspeedAPI::setDefaultVersion('2026-01');

// After (upgrading to 2026-04)
LightspeedAPI::setDefaultVersion('2026-04');
```

## The apiRequest Method

All API calls use the single `apiRequest()` method:

```php
$api->apiRequest(
    string $endpoint,       // The endpoint path (e.g., 'products', 'customers/123')
    string $method,         // HTTP method: 'get', 'post', 'put', 'delete'
    ?array $data = null,    // Request body (POST/PUT) or query params (GET)
    ?string $version = null // Version override (YYYY-MM format, optional)
): object
```

### GET Requests with Query Parameters

```php
// With pagination
$products = $api->apiRequest('products', 'get', [
    'page_size' => 100,
    'after' => 'cursor-value',
]);

// With filters
$customers = $api->apiRequest('customers', 'get', [
    'email' => 'john@example.com',
]);
```

### POST Requests

```php
$customer = $api->apiRequest('customers', 'post', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);
```

### PUT Requests

```php
$api->apiRequest('customers/abc-123', 'put', [
    'phone' => '+1234567890',
]);
```

### DELETE Requests

```php
$api->apiRequest('customers/abc-123', 'delete');
```

## OAuth2 Authentication

### Step 1: Create OAuth Client

```php
use LightspeedXSeries\LightspeedOAuth;

$oauth = new LightspeedOAuth(
    'your-client-id',
    'your-client-secret',
    'mystore',                              // Your store prefix
    'https://yoursite.com/oauth/callback',  // Redirect URI
    ['products:read', 'products:write']     // Scopes (required from March 2026)
);
```

### Step 2: Generate Authorization URL

```php
$auth = $oauth->getAuthorizationUrl();

// Store state in session for CSRF protection
$_SESSION['oauth_state'] = $auth['state'];

// Redirect user to Lightspeed
header('Location: ' . $auth['url']);
```

### Step 3: Handle Callback

```php
// Verify state matches
if ($_GET['state'] !== $_SESSION['oauth_state']) {
    throw new Exception('Invalid state');
}

// Exchange code for tokens
$tokens = $oauth->exchangeCodeForToken($_GET['code']);

// Store tokens securely
$accessToken = $tokens['access_token'];
$refreshToken = $tokens['refresh_token'];
$expiresIn = $tokens['expires_in'];
```

### Step 4: Refresh Tokens

```php
$newTokens = $oauth->refreshToken($refreshToken);

$accessToken = $newTokens['access_token'];
$refreshToken = $newTokens['refresh_token'];
```

## API Endpoints Reference

All endpoints use the `apiRequest()` method. Replace `{id}` with the actual UUID.

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `products` | List all products |
| GET | `products/{id}` | Get single product |
| POST | `products` | Create product |
| PUT | `products/{id}` | Update product |
| DELETE | `products/{id}` | Delete product |
| DELETE | `products/families/{id}` | Delete product family |
| GET | `products/{id}/inventory` | Get product inventory |
| POST | `products/{id}/images` | Upload product image |
| GET | `products/{id}/price_books` | Get product price books |
| POST | `products/{id}/variants` | Create variant |

```php
// List products with pagination
$products = $api->apiRequest('products', 'get', ['page_size' => 100]);

// Get single product
$product = $api->apiRequest('products/abc-123', 'get');

// Create product
$product = $api->apiRequest('products', 'post', [
    'name' => 'New Product',
    'sku' => 'SKU001',
    'retail_price' => 99.99,
]);

// Update product
$api->apiRequest('products/abc-123', 'put', [
    'common' => [
        'name' => 'Updated Name',
        'description' => 'New description',
    ]
]);

// Delete product
$api->apiRequest('products/abc-123', 'delete');

// Upload image
$api->apiRequest('products/abc-123/images', 'post', [
    'url' => 'https://example.com/image.jpg',
]);
```

### Product Images

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `product_images/{id}` | Get image |
| PUT | `product_images/{id}` | Update image (position) |
| DELETE | `product_images/{id}` | Delete image |

```php
// Set image position
$api->apiRequest('product_images/abc-123', 'put', ['position' => 1]);

// Delete image
$api->apiRequest('product_images/abc-123', 'delete');
```

### Product Types

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `product_types` | List all product types |
| GET | `product_types/{id}` | Get single product type |

### Variant Attributes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `variant_attributes` | List all attributes |
| GET | `variant_attributes/{id}` | Get single attribute |
| POST | `variant_attributes` | Create attribute |
| PUT | `variant_attributes/{id}` | Update attribute |
| DELETE | `variant_attributes/{id}` | Delete attribute |

### Customers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `customers` | List all customers |
| GET | `customers/{id}` | Get single customer |
| POST | `customers` | Create customer |
| PUT | `customers/{id}` | Update customer |
| DELETE | `customers/{id}` | Delete customer |

```php
// Search by email
$customers = $api->apiRequest('customers', 'get', [
    'email' => 'john@example.com',
]);

// Create customer
$customer = $api->apiRequest('customers', 'post', [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890',
]);

// Update customer
$api->apiRequest('customers/abc-123', 'put', [
    'phone' => '+0987654321',
]);
```

### Customer Groups

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `customer_groups` | List all groups |
| GET | `customer_groups/{id}` | Get single group |
| POST | `customer_groups` | Create group |
| PUT | `customer_groups/{id}` | Update group |
| DELETE | `customer_groups/{id}` | Delete group |
| GET | `customer_groups/{id}/customers` | Get customers in group |
| POST | `customer_groups/{id}/customers` | Add customers to group |
| DELETE | `customer_groups/{id}/customers` | Remove customers from group |

```php
// Add customers to group
$api->apiRequest('customer_groups/abc-123/customers', 'post', [
    'customer_ids' => ['cust-1', 'cust-2', 'cust-3'],
]);
```

### Brands

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `brands` | List all brands |
| GET | `brands/{id}` | Get single brand |
| POST | `brands` | Create brand |
| PUT | `brands/{id}` | Update brand |
| DELETE | `brands/{id}` | Delete brand |

### Tags

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `tags` | List all tags |
| GET | `tags/{id}` | Get single tag |
| POST | `tags` | Create tag |
| PUT | `tags/{id}` | Update tag |
| DELETE | `tags/{id}` | Delete tag |

### Product Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `product_categories` | List all categories |
| GET | `product_categories/{id}` | Get single category |
| POST | `product_categories` | Create category |
| POST | `product_categories/bulk` | Bulk update categories |
| DELETE | `product_categories/{id}` | Delete category |

```php
// Bulk update categories
$api->apiRequest('product_categories/bulk', 'post', [
    'categories' => [
        ['id' => 'cat-1', 'name' => 'Updated Name 1'],
        ['id' => 'cat-2', 'name' => 'Updated Name 2'],
    ]
]);
```

### Inventory

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `inventory` | List inventory records |
| POST | `inventory` | Create inventory adjustment |

```php
// Create inventory adjustment
$api->apiRequest('inventory', 'post', [
    'product_id' => 'prod-123',
    'outlet_id' => 'outlet-456',
    'adjustment' => 10,
]);
```

### Suppliers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `suppliers` | List all suppliers |
| GET | `suppliers/{id}` | Get single supplier |
| POST | `suppliers` | Create supplier |
| PUT | `suppliers/{id}` | Update supplier |
| DELETE | `suppliers/{id}` | Delete supplier |

### Price Books

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `price_books` | List all price books |
| GET | `price_books/{id}` | Get single price book |
| POST | `price_books` | Create price book |
| PUT | `price_books/{id}` | Update price book |
| DELETE | `price_books/{id}` | Delete price book |
| GET | `price_books/{id}/products` | Get products in price book |
| POST | `price_books/{id}/products` | Add products to price book |
| PUT | `price_books/{id}/products` | Update products in price book |
| DELETE | `price_books/{id}/products` | Delete products from price book |

### Price Book Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `price_book_products` | List all price book product entries |
| POST | `price_book_products` | Create price book product entry |
| PUT | `price_book_products/{id}` | Update price book product entry |
| DELETE | `price_book_products/{id}` | Delete price book product entry |

### Promotions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `promotions` | List all promotions |
| GET | `promotions/{id}` | Get single promotion |
| POST | `promotions` | Create promotion |
| PUT | `promotions/{id}` | Update promotion |
| DELETE | `promotions/{id}` | Delete promotion |
| GET | `promotions/search` | Search promotions |
| GET | `promotions/{id}/products` | Get promotion products |
| GET | `promotions/{id}/promocodes` | Get promo codes |
| POST | `promotions/{id}/promocodes` | Create promo code |
| DELETE | `promotions/{id}/promocodes/{code_id}` | Delete promo code |

### Promo Codes (Bulk)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `promocode/bulk/active` | Get active status of promo codes |
| DELETE | `promocode/bulk` | Bulk delete promo codes |

### Discount

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `discount` | Apply discounts to sale object (read-only) |

### Sales

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `sales` | List all sales |
| GET | `sales/{id}` | Get single sale |
| DELETE | `sales/{id}` | Delete sale |
| GET | `sales/{id}/fulfillments` | Get sale fulfillments |
| POST | `sales/{id}/fulfillments` | Create fulfillment for sale |

```php
// Get sales with filters
$sales = $api->apiRequest('sales', 'get', [
    'status' => 'CLOSED',
    'page_size' => 50,
]);
```

### Fulfillments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `fulfillments` | List all fulfillments |
| GET | `fulfillments/{id}` | Get single fulfillment |
| PUT | `fulfillments/{id}` | Update fulfillment |
| POST | `fulfillments/fulfill` | Fulfill a sale (complete all) |
| POST | `fulfillments/{id}/fulfill` | Fulfill specific line items |

```php
// Fulfill entire sale
$api->apiRequest('fulfillments/fulfill', 'post', [
    'sale_id' => 'sale-123',
]);

// Update fulfillment status
$api->apiRequest('fulfillments/abc-123', 'put', [
    'status' => 'SHIPPED',
    'tracking_number' => 'TRACK123',
]);
```

### Registers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `registers` | List all registers |
| GET | `registers/{id}` | Get single register |
| POST | `registers/{id}/open` | Open register |
| POST | `registers/{id}/close` | Close register |
| GET | `registers/{id}/payments_summary` | Get payments summary |

### Register Closures

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `register_closures` | List register closures |
| GET | `register_closures/{id}` | Get single closure |

### Outlets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `outlets` | List all outlets |
| GET | `outlets/{id}` | Get single outlet |

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `payments` | List all payments |
| GET | `payments/{id}` | Get single payment |

### Payment Types

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `payment_types` | List all payment types |
| GET | `payment_types/{id}` | Get single payment type |

### Taxes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `taxes` | List all taxes |
| GET | `taxes/{id}` | Get single tax |
| POST | `customer_taxes/bulk` | Bulk update customer taxes |

### Consignments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `consignments` | List all consignments |
| GET | `consignments/{id}` | Get single consignment |
| POST | `consignments` | Create consignment |
| PUT | `consignments/{id}` | Update consignment |
| DELETE | `consignments/{id}` | Delete consignment |

### Consignment Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `consignment_products` | List consignment products |
| POST | `consignment_products` | Create consignment product |
| PUT | `consignment_products/{id}` | Update consignment product |
| DELETE | `consignment_products/{id}` | Delete consignment product |

### Service Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `service_orders` | List all service orders |
| GET | `service_orders/{id}` | Get single service order |
| POST | `service_orders` | Create service order |
| PUT | `service_orders/{id}` | Update service order |
| DELETE | `service_orders/{id}` | Delete service order |

### Gift Cards

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `gift_cards` | List all gift cards |
| GET | `gift_cards/{number}` | Get gift card by number |
| GET | `gift_cards/{id}` | Get gift card by ID |
| POST | `gift_cards` | Create and activate gift card |
| DELETE | `gift_cards/{number}` | Void gift card |
| GET | `gift_cards/{number}/transactions` | Get gift card transactions |
| POST | `gift_cards/{number}/transactions` | Create transaction (redeem/reload) |

```php
// Create gift card
$api->apiRequest('gift_cards', 'post', [
    'number' => '1234567890',
    'initial_balance' => 100.00,
]);

// Redeem gift card
$api->apiRequest('gift_cards/1234567890/transactions', 'post', [
    'type' => 'REDEEMING',
    'amount' => -25.00,  // Negative for redemption
    'client_id' => 'unique-transaction-id',
]);

// Reload gift card
$api->apiRequest('gift_cards/1234567890/transactions', 'post', [
    'type' => 'RELOADING',
    'amount' => 50.00,  // Positive for reload
]);
```

### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `users` | List all users |
| GET | `users/{id}` | Get single user |

### Webhooks

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `webhooks` | List all webhooks |
| GET | `webhooks/{id}` | Get single webhook |
| POST | `webhooks` | Create webhook |
| DELETE | `webhooks/{id}` | Delete webhook |

```php
// Create webhook
$api->apiRequest('webhooks', 'post', [
    'url' => 'https://yoursite.com/webhook',
    'type' => 'sale.update',
]);

// Webhook types:
// - sale.update
// - inventory.update
// - customer.update
// - product.update
```

### Search

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `search` | Search products or entities |

```php
$results = $api->apiRequest('search', 'post', [
    'query' => 'blue shirt',
    'type' => 'products',
]);
```

### Loyalty

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `loyalty_adjustments` | List loyalty adjustments |
| POST | `loyalty_adjustments` | Create loyalty adjustment |

### Channel Requests (Ecommerce)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `channel_request_templates` | List templates |
| GET | `channel_requests` | List channel requests |
| GET | `channel_requests/{id}` | Get single request |
| POST | `channel_requests` | Create channel request |
| PUT | `channel_requests/{id}` | Update channel request |

### Store Credit

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `store_credit_transactions` | List transactions |
| POST | `store_credit_transactions` | Create transaction |

### Line Items

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `line_items` | List line items |

## Common Query Parameters

Most GET endpoints support these query parameters:

| Parameter | Description |
|-----------|-------------|
| `page_size` | Number of results per page (max 200) |
| `after` | Cursor for pagination (from `version.max` in response) |
| `before` | Cursor for reverse pagination |
| `deleted` | Include deleted records (`true`/`false`) |

## Debug Mode

Enable debug mode to see request/response details:

```php
$api->debug(true);

// Make requests...

// Access debug data
$rawResponse = $api->getLastResultRaw();
$httpCode = $api->getLastHttpCode();
```

## Rate Limiting

The API client automatically handles rate limiting (HTTP 429). When rate limited, it will wait and retry.

```php
// Allow time slip for servers with clock drift
$api->allowTimeSlip = true;
```

## OAuth Scopes

From March 31, 2026, OAuth requests must include scopes. Request only the scopes your application needs.

### Selecting Scopes

1. Review all endpoints used by your application
2. Check the required scope(s) for each endpoint
3. Combine and remove duplicates to form your scope list

```php
$oauth = new LightspeedOAuth(
    $clientId,
    $clientSecret,
    $domainPrefix,
    $redirectUri,
    ['products:read', 'sales:read', 'customers:read']  // Space-delimited in URL
);
```

### Available Scopes

| Scope | Description |
|-------|-------------|
| `audit:read` | Read audit and security events |
| `billing:partner_subscription:read` | Read Billing Partner Subscription |
| `billing:partner_subscription:write` | Write Billing Partner Subscription |
| `business_rules:read` | Read business rules |
| `business_rules:write` | Create and delete business rules |
| `channels:read` | Read e-commerce channel information |
| `consignments:read` | Read in-progress inventory counts and historical stock consignments |
| `consignments:write:inventory_count` | Perform Inventory Counts |
| `consignments:write:stock_order` | Process stock orders and stock returns |
| `consignments:write:stock_transfer` | Create, send and receive stock transfers between outlets |
| `customers:read` | Read customers and customer groups |
| `customers:write` | Create, update and delete customers and customer groups |
| `custom_fields:read` | Read custom fields |
| `custom_fields:write` | Create, update and delete custom fields |
| `fulfillments:read` | Read sale order fulfillments |
| `fulfillments:write` | Create and update sale order fulfillments |
| `gift_cards:read` | Read gift cards and gift card transactions |
| `gift_cards:write:issue` | Issue a gift card to a customer |
| `gift_cards:write:redeem` | Redeem or reload an amount against a customers gift card |
| `inventory:read` | Read current and historical product inventory levels |
| `outlets:read` | Read outlets |
| `payments:read` | Read Payments |
| `payment_types:read` | Read payment types, excluding internal payment types |
| `products:read` | Read products, product types, product images, brands and tags (excluding costs) |
| `products:read:price_books` | Read product price books |
| `products:write` | Create/update products (excluding costs), delete products, upload images, create types |
| `products:write:price_books` | Write Products Price Books |
| `promotions:read` | Read promotions, get promotion products, find best promotion for sale |
| `promotions:write` | Create, update and archive any promotion |
| `register:close` | Close a register and reconcile payments |
| `register:open` | Open a register to create sales and payments |
| `registers:read` | Read registers |
| `remote_rules:read` | Read remote rules |
| `remote_rules:write` | Create and delete remote rules |
| `retailer:read` | Read account configuration (loyalty ratio, timezone, country, currency) |
| `sales:read` | Read all sales and payments in your account |
| `sales:write` | Create sales and payments, adjust, void or return sales |
| `serial_numbers:read` | Read serial numbers |
| `serial_numbers:write` | Add and delete serial numbers |
| `services:read` | Read services |
| `services:write` | Create, edit services |
| `store_credits:read` | Read store credit transactions |
| `store_credits:write:issue` | Issue store credits to a customer for a return |
| `suppliers:read` | Read suppliers |
| `suppliers:write` | Create, update and delete suppliers |
| `taxes:read` | Read tax rates and tax rules (and tax groups if tax exclusive) |
| `taxes:write` | Create, update and delete taxes |
| `users:read` | Read user information (except passwords) |
| `users:write` | Create, update and delete users and customise groups |
| `webhooks` | Manage webhooks created by the application |

## License

MIT License. See [LICENSE](LICENSE) for details.

## Links

- [Lightspeed X-Series API Documentation](https://x-series-api.lightspeedhq.com/)
- [API Changelog](https://x-series-api.lightspeedhq.com/changelog)
- [API Versioning Strategy](https://x-series-api.lightspeedhq.com/docs/versioning-strategy)
