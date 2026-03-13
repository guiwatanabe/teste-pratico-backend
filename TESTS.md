# Overview

![Tests](https://img.shields.io/badge/tests-passing-brightgreen)
![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)

```
tests/
├── Feature/
│   ├── Auth/
│   │   └── LoginTest.php
│   ├── Users/
│   │   ├── ListUsersTest.php
│   │   ├── CreateUserTest.php
│   │   ├── UpdateUserTest.php
│   │   └── DeleteUserTest.php
│   ├── Products/
│   │   ├── ListProductsTest.php
│   │   ├── CreateProductsTest.php
│   │   ├── UpdateProductsTest.php
│   │   └── DeleteProductsTest.php
│   ├── Clients/
│   │   ├── ListClientsTest.php
│   │   └── ShowClientTest.php
│   ├── Transactions/
│   │   ├── ListTransactionsTest.php
│   │   ├── ShowTransactionTest.php
│   │   └── RefundTransactionTest.php
│   ├── Purchase/
│   │   ├── PurchaseValidationTest.php
│   │   ├── PurchaseSuccessTest.php
│   │   └── PurchaseGatewayTest.php
│   └── Gateways/
│       ├── ActivateDeactivateGatewayTest.php
│       └── GatewayPriorityTest.php
└── Unit/
│   ├── Gateway/Drivers
│   │   ├── Gateway1DriverTest.php
│   │   ├── Gateway2DriverTest.php
│   ├── Models/
│   │   ├── GatewayModelTest.php
│   │   ├── UserModelTest.php
│   ├── Purchase/
│   │   ├── GatewayFallbackTest.php
│   │   ├── AmountCalculationTest.php
```

# Feature

### Auth/LoginTest

- returns a token on valid credentials ✓
- generates valid token and returns user data ✓
- returns 401 on wrong password ✓
- returns 401 on non-existent email ✓
- returns 422 when required fields are missing ✓
- returns 401 when accessing protected route without token ✓
- returns 401 when accessing protected route with a malformed token ✓
- rate limits after 5 requests per minute ✓

---

### Users/ListUsersTest

- allows ADMIN and MANAGER to list users ✓
- returns correct structure for user ✓
- prevents FINANCE and USER from listing users ✓
- returns 401 for unauthenticated request ✓
- does not include soft-deleted users in the list ✓
- does not expose sensitive information in the response ✓
- paginates results with 10 users per page ✓

### Users/CreateUserTest

- allows ADMIN to create a user with any role ✓
- allows MANAGER to create a user with role USER ✓
- prevents MANAGER from creating a user with a privileged role ✓
- prevents FINANCE and USER from creating users ✓
- returns 422 on invalid payload ✓
- returns 422 when email is already taken ✓
- persists created user to database ✓

### Users/UpdateUserTest

- allows ADMIN to update any user ✓
- allows MANAGER to update a user with role USER ✓
- prevents MANAGER from updating an ADMIN user ✓
- prevents FINANCE and USER from updating users ✓
- returns 404 for non-existent or soft-deleted user ✓
- returns 422 on invalid payload (bad email format, taken email) ✓
- prevents changing own role via update endpoint ✓
- returns updated fields in the response ✓
- persists the update to the database ✓

### Users/DeleteUserTest

- allows ADMIN to delete any user ✓
- allows MANAGER to delete a user with role USER ✓
- prevents MANAGER from deleting an ADMIN ✓
- prevents MANAGER from deleting MANAGER and FINANCE users ✓
- prevents FINANCE and USER from deleting users ✓
- soft-deletes the user (record still exists in DB) ✓
- prevents deleting own account ✓
- returns 404 for a non-existent user ✓
- returns 404 for a soft-deleted user ✓

---

### Products/ListProductsTest

- returns product list for any authenticated user ✓
- returns 401 for unauthenticated request ✓
- does not include soft-deleted products in the list ✓
- paginates results with 10 results per page ✓

### Products/CreateProductsTest

- returns 401 for unauthenticated request ✓
- allows ADMIN, MANAGER, and FINANCE to create a product ✓
- prevents USER from creating products ✓
- returns 422 on invalid payload ✓

### Products/UpdateProductsTest

- returns 401 for unauthenticated request ✓
- allows ADMIN, MANAGER, and FINANCE to update a product ✓
- prevents USER from updating products ✓
- returns 404 for non-existent or soft-deleted product ✓
- returns 422 on invalid payload ✓

### Products/DeleteProductsTest

- returns 401 for unauthenticated request ✓
- allows ADMIN, MANAGER, and FINANCE to delete a product ✓
- prevents USER from deleting products ✓
- soft-deletes the product (record still exists in DB) ✓
- returns 404 for a non-existent product ✓

---

### Clients/ListClientsTest

- returns 401 for unauthenticated request ✓
- returns client list for any authenticated user ✓
- paginates results with 10 results per page ✓

### Clients/ShowClientTest

- returns 401 for unauthenticated request ✓
- returns client detail with transaction history for any authenticated user ✓
- returns an empty array for a client with no transactions ✓
- transaction entries include product details ✓
- returns 404 for a non-existent client ✓

---

### Transactions/ListTransactionsTest

- returns 401 for unauthenticated request ✓
- returns transaction list for any authenticated user ✓

### Transactions/ShowTransactionTest

- returns 401 for unauthenticated request ✓
- returns transaction detail with related products and quantities ✓
- returns 404 for a non-existent transaction ✓

### Transactions/RefundTransactionTest

- returns 401 for unauthenticated request ✓
- allows ADMIN and FINANCE to issue a refund ✓
- prevents MANAGER and USER from issuing a refund ✓
- returns 404 for a non-existent transaction ✓
- returns 409 when transaction is already refunded ✓
- calls the correct gateway refund API based on which gateway processed the transaction ✓
- updates transaction status to refunded on success ✓
- does NOT update transaction status if the gateway refund API fails ✓

---

### Purchase/PurchaseValidationTest

- returns 422 when required buyer fields are missing or invalid ✓
- returns 422 when products array is missing or empty ✓
- returns 422 when a product_id does not exist or is soft-deleted ✓
- returns 422 when quantity is zero, negative, or non-integer ✓
- returns 422 when required card fields are missing or invalid ✓
- returns 422 when card expiry is in the past ✓
- does not return card expiry error when expiry is in the future ✓
- returns 422 when product has no stock available ✓

### Purchase/PurchaseSuccessTest

- stores only the last 4 digits of the card number ✓
- creates a new client record when the buyer email is new ✓
- reuses an existing client when the buyer email already exists ✓
- links all purchased products with correct quantity and unit price in transaction_products ✓
- decrements product stock after a successful purchase ✓
- calculates total correctly for multiple products with different quantities ✓
- response includes transaction id, status, amount, and gateway used ✓

### Purchase/PurchaseGatewayTest

- processes the purchase through Gateway 1 first (lowest priority number) ✓
- falls back to Gateway 2 when Gateway 1 fails (cvv=100) ✓
- skips deactivated gateways and uses the next active one ✓
- returns an error when both gateways fail (cvv=200) ✓
- does NOT save the transaction to the database when all gateways fail ✓
- returns an appropriate error when no gateways are active ✓
- succeeds via Gateway 1 when only Gateway 2 would fail (cvv=300) ✓

---

### Gateways/ActivateDeactivateGatewayTest

- returns 401 for unauthenticated request ✓
- allows ADMIN to activate and deactivate a gateway ✓
- prevents non-ADMIN from managing gateways ✓
- returns 404 for a non-existent gateway ✓

### Gateways/GatewayPriorityTest

- returns 401 for unauthenticated request ✓
- allows ADMIN to change a gateway priority ✓
- prevents non-ADMIN from changing priority ✓
- returns 404 for a non-existent gateway ✓
- returns 422 when priority is not a positive integer ✓
- a lower priority number is tried first on purchase (integration check) ✓

# Unit

### Unit/Purchase/GatewayFallbackTest

- uses the first prioritized gateway driver ✓
- skips inactive gateways and continues to the next ✓
- tries the next gateway when the current one throws an exception ✓
- stops and returns success as soon as one gateway succeeds ✓
- throws after exhausting all gateways ✓
- handles an empty active gateway list gracefully ✓
- throws when all active gateways have no registered driver ✓

### Unit/Purchase/AmountCalculationTest

- calculates total as unit_price multiplied by quantity for a single product ✓
- sums correctly across multiple products with different quantities ✓
- returns the amount in cents ✓
- handles large quantities without floating point errors ✓
- throws if a requested product id does not exist ✓

### Unit/Gateway/Drivers/Gateway1DriverTest

- throws when gateway 1 config is missing ✓
- throws when gateway 1 auth_type is wrong ✓
- throws when gateway 1 login returns non-JSON body ✓
- throws when gateway 1 login returns non-2xx ✓
- charge returns success data on 201 ✓
- charge throws on non-201 response ✓
- charge throws on 200 response (not 201) ✓
- refund returns success data on 201 ✓
- refund throws on non-201 response ✓
- refund throws Transaction not found when statusCode is 404 in body ✓
- listTransactions returns success data ✓
- listTransactions throws on non-2xx response ✓

### Unit/Gateway/Drivers/Gateway2DriverTest

- throws when gateway 2 config is missing ✓
- throws when gateway 2 auth_type is wrong ✓
- charge returns success data on 201 ✓
- charge throws on non-201 response ✓
- charge throws on 200 response (not 201) ✓
- charge surfaces card decline message from erros array ✓
- charge hides validation error details when erros has rule field ✓
- refund returns success data on 201 ✓
- refund throws on non-201 response ✓
- refund throws Invalid transaction ID on uuid rule error ✓
- listTransactions returns success data ✓
- listTransactions throws on non-2xx response ✓