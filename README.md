# Lightspeed X-Series API Client for PHP

A PHP client for the Lightspeed X-Series (formerly Vend) API with built-in OAuth2 support.

## Requirements

- PHP 8.0+
- cURL extension
- JSON extension

## Installation

```bash
composer require anytech/lightspeed-x-series-api
```

## Quick Start

### Using the API with an Access Token

```php
use LightspeedXSeries\LightspeedAPI;

$api = new LightspeedAPI(
    'https://mystore.retail.lightspeed.app',
    'Bearer',
    'your-access-token'
);

// Get products
$products = $api->getProducts();
foreach ($products->data as $product) {
    echo $product->name . "\n";
}

// Get a single product
$product = $api->getProduct('product-uuid');

// Update a product
$api->updateProduct('product-uuid', [
    'name' => 'Updated Product Name',
    'description' => 'New description',
]);
```

## OAuth2 Authentication

This library includes built-in OAuth2 support with no external dependencies.

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

// Update stored tokens
$accessToken = $newTokens['access_token'];
$refreshToken = $newTokens['refresh_token'];
```

## API Methods

### Products

```php
// Get all products (API 3.0)
$products = $api->getProducts(['page_size' => 50]);

// Get single product
$product = $api->getProduct('product-uuid');

// Update product (API 2.1)
$api->updateProduct('product-uuid', [
    'name' => 'New Name',
    'description' => 'New description',
]);
```

### Customers

```php
// Get all customers
$customers = $api->getCustomers(['email' => 'john@example.com']);

// Get single customer
$customer = $api->getCustomer('customer-uuid');

// Create customer
$newCustomer = $api->createCustomer([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
]);

// Update customer
$api->updateCustomer('customer-uuid', [
    'phone' => '+1234567890',
]);
```

### Sales

```php
// Get all sales
$sales = $api->getSales(['status' => 'CLOSED']);

// Get single sale
$sale = $api->getSale('sale-uuid');
echo $sale->getInvoiceNumber();
echo $sale->getTotalPrice();

// Get sale customer
$customer = $sale->getCustomer();

// Get sale products
$products = $sale->getProducts();
```

### Gift Cards

```php
// Get gift card by number
$giftCard = $api->getGiftCard('1234567890');

// Create and activate a gift card
$newCard = $api->createGiftCard([
    'number' => '1234567890',
    'initial_balance' => 100.00,
]);

// Redeem gift card
$api->redeemGiftCard('1234567890', 25.00);

// Reload gift card
$api->reloadGiftCard('1234567890', 50.00);

// Void gift card
$api->voidGiftCard('1234567890');
```

### Brands

```php
// Get all brands
$brands = $api->getBrands();

// Get single brand
$brand = $api->getBrand('brand-uuid');

// Update brand
$api->updateBrand('brand-uuid', ['name' => 'New Brand Name']);
```

### Categories

```php
// Get all categories
$categories = $api->getCategories();

// Update category
$api->updateCategory('category-uuid', ['name' => 'New Category Name']);
```

### Inventory

```php
// Get inventory records
$inventory = $api->getInventory(['product_id' => 'product-uuid']);

// Get product inventory
$productInventory = $api->getProductInventory('product-uuid');
```

### Outlets

```php
// Get all outlets
$outlets = $api->getOutlets();
```

### Consignments

```php
// Get all consignments
$consignments = $api->getConsignments();

// Get single consignment
$consignment = $api->getConsignment('consignment-uuid');
```

### Promotions

```php
// Get all promotions
$promotions = $api->getPromotions();

// Get promo codes
$codes = $api->getPromoCodes('promotion-uuid');
```

### Price Books

```php
// Get all price books
$priceBooks = $api->getPriceBooks();

// Get single price book
$priceBook = $api->getPriceBook('pricebook-uuid');
```

### Custom Requests

```php
// GET request
$result = $api->request('/api/2.0/custom-endpoint');

// POST request
$result = $api->postRequest('/api/2.0/custom-endpoint', ['data' => 'value']);

// PUT request
$result = $api->putRequest('/api/2.0/custom-endpoint', ['data' => 'value']);

// DELETE request
$result = $api->deleteRequest('/api/2.0/custom-endpoint');
```

## Entity Objects

### Working with Products

```php
use LightspeedXSeries\LightspeedProduct;

$product = $api->getProduct('product-uuid');

// Get inventory
$totalStock = $product->getInventory();
$outletStock = $product->getInventory('Main Outlet');

// Set inventory
$product->setInventory(100, 'Main Outlet');

// Access properties
echo $product->getName();
echo $product->getSku();
echo $product->getPrice();
```

### Working with Customers

```php
use LightspeedXSeries\LightspeedCustomer;

$customer = new LightspeedCustomer([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
], $api);

$customer->save();

echo $customer->getFullName();
echo $customer->getEmail();
```

### Working with Sales

```php
$sale = $api->getSale('sale-uuid');

echo $sale->getInvoiceNumber();
echo $sale->getStatus();
echo $sale->getTotalPrice();
echo $sale->getTotalPaid();
echo $sale->getBalanceDue();

// Check if complete
if ($sale->isComplete()) {
    // ...
}

// Get line items
$items = $sale->getLineItems();
```

## Debug Mode

Enable debug mode to see request/response details:

```php
$api->debug(true);
```

## Rate Limiting

The API client automatically handles rate limiting (HTTP 429). When rate limited, it will wait and retry.

```php
// Allow time slip for servers with clock drift
$api->allowTimeSlip = true;
```

## OAuth Scopes

From March 31, 2026, OAuth requests must include scopes. Common scopes:

- `products:read` - Read product data
- `products:write` - Create/update products
- `customers:read` - Read customer data
- `customers:write` - Create/update customers
- `sales:read` - Read sales data
- `sales:write` - Create sales

```php
$oauth = new LightspeedOAuth(
    $clientId,
    $clientSecret,
    $domainPrefix,
    $redirectUri,
    ['products:read', 'products:write', 'customers:read', 'customers:write']
);
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Links

- [Lightspeed X-Series API Documentation](https://x-series-api.lightspeedhq.com/)
- [API Changelog](https://x-series-api.lightspeedhq.com/changelog)
