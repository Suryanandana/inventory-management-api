# Payment API Documentation

## Overview
API untuk membuat dan mengelola pembayaran menggunakan Xendit sebagai payment gateway.

## Base URL
```
http://localhost:8000/api
```

## Authentication
Semua endpoint payment (kecuali webhook) memerlukan authentication dengan Bearer Token dari Sanctum.

```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Create Invoice (Create Payment)

**Endpoint:** `POST /payments/create-invoice`

**Authentication:** Required

**Description:** Membuat invoice untuk pembayaran produk

**Request Body:**
```json
{
  "product_id": 1,
  "variant_id": 2,
  "quantity": 1
}
```

**Parameters:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| product_id | integer | ✓ | ID produk yang akan dibeli |
| variant_id | integer | - | ID varian produk (opsional) |
| quantity | integer | ✓ | Jumlah pembelian (min: 1, max: 100) |

**Response (201):**
```json
{
  "message": "Invoice created successfully",
  "data": {
    "payment_id": 1,
    "invoice_id": "60e87da49f98f8024bf81e16",
    "external_id": "payment-1-1708851203-aB1cDe",
    "amount": 50000,
    "currency": "IDR",
    "invoice_url": "https://invoice.xendit.co/web/invoices/60e87da49f98f8024bf81e16",
    "status": "PENDING",
    "expired_date": "2024-02-28T10:20:03Z"
  }
}
```

**Error Response (400):**
```json
{
  "message": "Failed to create invoice",
  "error": "Product not found"
}
```

---

### 2. Get Payment List

**Endpoint:** `GET /payments`

**Authentication:** Required

**Description:** Mendapatkan daftar semua pembayaran user

**Query Parameters:**
```
?page=1
```

**Response (200):**
```json
{
  "message": "Payments retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "product": {
        "id": 1,
        "name": "T-Shirt",
        "price": 50000
      },
      "variant": {
        "id": 2,
        "size": "M",
        "color": "Black",
        "price": null
      },
      "external_id": "payment-1-1708851203-aB1cDe",
      "invoice_id": "60e87da49f98f8024bf81e16",
      "description": "T-Shirt (M / Black) x 1",
      "amount": 50000,
      "currency": "IDR",
      "status": "PENDING",
      "created_at": "2024-02-27T10:20:03Z",
      "updated_at": "2024-02-27T10:20:03Z"
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1
  }
}
```

---

### 3. Get Payment Detail

**Endpoint:** `GET /payments/{invoiceId}`

**Authentication:** Required

**Description:** Mendapatkan detail pembayaran tertentu beserta status terbaru dari Xendit

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | string | ✓ | Invoice ID dari Xendit |

**Response (200):**
```json
{
  "message": "Invoice retrieved successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "product": {
      "id": 1,
      "name": "T-Shirt"
    },
    "external_id": "payment-1-1708851203-aB1cDe",
    "invoice_id": "60e87da49f98f8024bf81e16",
    "amount": 50000,
    "status": "PENDING"
  },
  "xendit_status": {
    "id": "60e87da49f98f8024bf81e16",
    "status": "PENDING",
    "amount": 50000,
    "paid_amount": 0,
    "currency": "IDR",
    "created_at": "2024-02-27T10:20:03Z",
    "updated_at": "2024-02-27T10:20:03Z",
    "expired_date": "2024-02-28T10:20:03Z"
  }
}
```

---

### 4. Expire Invoice

**Endpoint:** `POST /payments/{invoiceId}/expire`

**Authentication:** Required

**Description:** Membatalkan/kadaluarsa invoice pembayaran

**Path Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | string | ✓ | Invoice ID dari Xendit |

**Response (200):**
```json
{
  "message": "Invoice expired successfully",
  "data": {
    "id": 1,
    "status": "EXPIRED"
  }
}
```

---

### 5. Webhook Handler (Xendit Callback)

**Endpoint:** `POST /webhooks/xendit`

**Authentication:** Not Required

**Description:** Endpoint untuk menerima callback dari Xendit ketika status invoice berubah

**Request Body (dari Xendit):**
```json
{
  "id": "60e87da49f98f8024bf81e16",
  "external_id": "payment-1-1708851203-aB1cDe",
  "status": "PAID",
  "amount": 50000,
  "paid_amount": 50000,
  "currency": "IDR",
  "payment_method": "BCA",
  "payment_channel": "BANK_TRANSFER",
  "payer_email": "user@example.com",
  "created": "2024-02-27T10:20:03Z",
  "updated": "2024-02-27T10:25:15Z"
}
```

**Response (200):**
```json
{
  "message": "Webhook processed successfully",
  "data": {
    "id": 1,
    "status": "PAID"
  }
}
```

---

## Status Payment

Status pembayaran dapat memiliki nilai berikut:

| Status | Keterangan |
|--------|-----------|
| PENDING | Invoice dibuat, menunggu pembayaran |
| PAID | Pembayaran berhasil diterima |
| EXPIRED | Invoice kadaluarsa |
| FAILED | Pembayaran gagal |

---

## Contoh Implementation (Frontend/Mobile)

### Step 1: Create Invoice
```bash
curl -X POST http://localhost:8000/api/payments/create-invoice \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "variant_id": 2,
    "quantity": 1
  }'
```

### Step 2: Redirect to Invoice URL
Setelah mendapatkan `invoice_url` dari response, redirect user ke URL tersebut untuk melakukan pembayaran.

### Step 3: Handle Payment Notification
Setelah user selesai membayar, Xendit akan mengirim webhook ke `/webhooks/xendit` untuk update status pembayaran.

### Step 4: Check Payment Status
```bash
curl -X GET http://localhost:8000/api/payments/{invoiceId} \
  -H "Authorization: Bearer {token}"
```

---

## Error Handling

### Common Error Codes

**400 - Bad Request**
```json
{
  "message": "Failed to create invoice",
  "error": "The product_id field is required"
}
```

**404 - Not Found**
```json
{
  "message": "Invoice not found",
  "error": "Payment not found"
}
```

**422 - Validation Error**
```json
{
  "message": "The given data was invalid",
  "errors": {
    "product_id": ["The product_id field is required"]
  }
}
```

---

## Notes

- Durasi invoice adalah 48 jam (172800 detik)
- Reminder akan dikirim 1 jam sebelum invoice expired
- Currency default adalah IDR
- Minimal kuantitas adalah 1 dan maksimal 100
- Harga akan menggunakan varian price jika ada, jika tidak menggunakan product price
