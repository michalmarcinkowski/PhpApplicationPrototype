# PhpApplicationPrototype

## API Documentation

### Base URL

```
/api/products
```

---

## Endpoints

### 1. Create Product

- **Endpoint:** `POST /api/products/`
- **Description:** Creates a new product.

#### Request Body

```
{
  "title": "string",     // Title of the product (required, min 2 chars, max 255 chars)
  "price": integer       // Price of the product (required, must be positive or zero)
}
```

#### Response

- **201 Created**: On success. Returns product data including ID.
- **422 Unprocessable Entity**: On validation errors.

---

### 2. Update Product

- **Endpoint:** `PUT /api/products/{id}`
- **Description:** Updates an existing product by ID.

#### Request Body

```
{
  "title": "string",  // Updated title (optional)
  "price": integer    // Updated price (optional, must be positive or zero)
}
```

#### Response

- **200 OK**: On success, returns updated product data.
- **404 Not Found**: If product with ID does not exist.
- **422 Unprocessable Entity**: On validation errors (e.g., duplicate title).

---

### 3. Delete Product

- **Endpoint:** `DELETE /api/products/{id}`
- **Description:** Deletes a product by ID.

#### Response

- **204 No Content**: On success.
- **404 Not Found**: If product with ID does not exist.

---

## Validation Errors

All endpoints that create or update a product will return `422 Unprocessable Entity` with the following structure:

```
{
  "message": "Validation Failed",
  "errors": {
    "field_name": ["Error message for this field."]
  }
}
```

---

## Example Requests

### Create Product Example

```
curl -X POST http://0.0.0.0:5000/api/products/ \
  -H "Content-Type: application/json" \
  -d '{"title": "New Product", "price": 199}'
```

### Update Product Example

```
curl -X PUT http://0.0.0.0:5000/api/products/1 \
  -H "Content-Type: application/json" \
  -d '{"title": "Updated Product", "price": 299}'
```

### Delete Product Example

```
curl -X DELETE http://0.0.0.0:5000/api/products/1
```

---

This documentation provides a clear overview of how to interact with the product management endpoints in your API.  
*Adjustments may be necessary depending on specific implementation details.*


# Cart API Documentation

## Base URL

```
/api/carts
```

---

## Endpoints

### 1. Create Cart

- **Endpoint:** `POST /api/carts/`
- **Description:** Creates a new cart.

#### Response

- **201 Created**: On success, returns the created cart data including ID.

---

### 2. Get Cart

- **Endpoint:** `GET /api/carts/{id}`
- **Description:** Retrieves the specified cart by ID.

#### Response

- **200 OK**: On success, returns the cart data.
- **404 Not Found**: If the cart does not exist.

---

### 3. Add Item to Cart

- **Endpoint:** `POST /api/carts/{cartId}/items/`
- **Description:** Adds an item to the specified cart.

#### Request Body

```
{
  "cartId": integer,    // ID of the cart (required)
  "productId": integer, // ID of the product to add (required)
  "quantity": integer   // Quantity of the product (required, must be between 1 and 10)
}
```

#### Response

- **201 Created**: On success, returns the updated cart data.
- **422 Unprocessable Entity**: On validation errors.

---

## Validation Errors

Any endpoint that involves adding to the cart will return `422 Unprocessable Entity` with a structure like:

```
{
  "message": "Validation Failed",
  "errors": {
    "field_name": ["Error message for this field."]
  }
}
```

---

## Example Requests

### Create Cart Example

```
curl -X POST http://0.0.0.0:5000/api/carts/ -H "Content-Type: application/json"
```

### Get Cart Example

```
curl -X GET http://0.0.0.0:5000/api/carts/1
```

### Add Item to Cart Example

```
curl -X POST http://0.0.0.0:5000/api/carts/1/items/ \
  -H "Content-Type: application/json" \
  -d '{"cartId": 1, "productId": 2, "quantity": 3}'
```
```

