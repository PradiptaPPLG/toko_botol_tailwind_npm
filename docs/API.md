# 🔌 API Documentation

Dokumentasi endpoint API untuk Sistem POS Toko Botol.

## Overview

Aplikasi ini menggunakan **AJAX endpoints** untuk operasi dinamis tanpa reload halaman. Semua endpoint mengembalikan response dalam format **JSON**.

## Base URL

```
http://localhost:8080  (Docker)
http://localhost       (Manual)
```

---

## Authentication

Semua endpoint memerlukan **session authentication**. User harus login terlebih dahulu melalui halaman login.

**Session Variables:**
- `$_SESSION['admin_id']` — ID admin yang login
- `$_SESSION['admin_username']` — Username admin
- `$_SESSION['admin_nama']` — Nama lengkap admin
- `$_SESSION['admin_role']` — Role (admin/gudang)

---

## Endpoints

### 1. Get Stock per Cabang

Mendapatkan data stok produk per cabang.

**Endpoint:**
```
GET /api/get_stok_cabang.php
```

**Parameters:**

| Parameter  | Type    | Required | Description          |
|------------|---------|----------|----------------------|
| `cabang_id`| integer | Yes      | ID cabang            |

**Request Example:**
```javascript
fetch('/api/get_stok_cabang.php?cabang_id=1')
    .then(response => response.json())
    .then(data => console.log(data));
```

**Response Success (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": "1",
            "produk_id": "1",
            "kode_produk": "BTL001",
            "nama_produk": "Aqua 600ml",
            "jumlah": "150",
            "harga_jual": "3000"
        },
        {
            "id": "2",
            "produk_id": "2",
            "kode_produk": "BTL002",
            "nama_produk": "Coca Cola 1L",
            "jumlah": "80",
            "harga_jual": "8000"
        }
    ]
}
```

**Response Error (400):**
```json
{
    "success": false,
    "message": "Cabang ID tidak ditemukan"
}
```

---

### 2. Get Transaction Detail

Mendapatkan detail transaksi berdasarkan invoice number.

**Endpoint:**
```
GET /modules/admin/get_transaction_detail.php
```

**Parameters:**

| Parameter | Type   | Required | Description     |
|-----------|--------|----------|-----------------|
| `invoice` | string | Yes      | Invoice number  |

**Request Example:**
```javascript
fetch('/modules/admin/get_transaction_detail.php?invoice=INV-20260228-0001')
    .then(response => response.json())
    .then(data => console.log(data));
```

**Response Success (200):**
```json
{
    "success": true,
    "header": {
        "invoice_no": "INV-20260228-0001",
        "tipe": "pembeli",
        "nama_pelanggan": "John Doe",
        "total": "50000",
        "bayar": "100000",
        "kembalian": "50000",
        "tanggal": "2026-02-28 14:30:00",
        "nama_kasir": "Kasir 1",
        "cabang": "Cabang Barat"
    },
    "details": [
        {
            "nama_produk": "Aqua 600ml",
            "qty": "10",
            "harga": "3000",
            "subtotal": "30000"
        },
        {
            "nama_produk": "Coca Cola 1L",
            "qty": "2",
            "harga": "10000",
            "subtotal": "20000"
        }
    ]
}
```

**Response Error (404):**
```json
{
    "success": false,
    "message": "Transaksi tidak ditemukan"
}
```

---

## Form Submissions

### 1. Login Admin

**Endpoint:**
```
POST /login.php
```

**Content-Type:** `application/x-www-form-urlencoded`

**Parameters:**

| Parameter  | Type   | Required | Description |
|------------|--------|----------|-------------|
| `username` | string | Yes      | Username    |
| `password` | string | Yes      | Password    |

**Request Example:**
```html
<form method="POST" action="/login.php">
    <input type="text" name="username" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```

**Response Success:**
- Redirect ke dashboard dengan session terisi
- `$_SESSION['admin_id']` diset

**Response Error:**
- Redirect kembali ke login dengan error message

---

### 2. Add Product

**Endpoint:**
```
POST /modules/admin/tambah_stok.php
```

**Content-Type:** `application/x-www-form-urlencoded`

**Parameters:**

| Parameter      | Type    | Required | Description         |
|----------------|---------|----------|---------------------|
| `kode_produk`  | string  | Yes      | Product code        |
| `nama_produk`  | string  | Yes      | Product name        |
| `harga_beli`   | integer | Yes      | Purchase price      |
| `harga_jual`   | integer | Yes      | Selling price       |
| `stok_gudang`  | integer | Yes      | Initial stock       |

**Request Example:**
```html
<form method="POST" action="/modules/admin/tambah_stok.php">
    <input type="text" name="kode_produk" value="BTL004" required>
    <input type="text" name="nama_produk" value="Sprite 1L" required>
    <input type="number" name="harga_beli" value="7000" required>
    <input type="number" name="harga_jual" value="10000" required>
    <input type="number" name="stok_gudang" value="100" required>
    <button type="submit">Tambah Produk</button>
</form>
```

**Response:**
- Redirect dengan success message

---

### 3. Stock In (Stok Masuk)

**Endpoint:**
```
POST /modules/gudang/stok_masuk.php
```

**Content-Type:** `application/x-www-form-urlencoded`

**Parameters:**

| Parameter   | Type    | Required | Description     |
|-------------|---------|----------|-----------------|
| `produk_id` | integer | Yes      | Product ID      |
| `jumlah`    | integer | Yes      | Quantity        |
| `tanggal`   | date    | Yes      | Date (Y-m-d)    |
| `keterangan`| string  | No       | Notes           |

**Request Example:**
```html
<form method="POST" action="/modules/gudang/stok_masuk.php">
    <select name="produk_id" required>
        <option value="1">Aqua 600ml</option>
        <option value="2">Coca Cola 1L</option>
    </select>
    <input type="number" name="jumlah" required>
    <input type="date" name="tanggal" required>
    <textarea name="keterangan"></textarea>
    <button type="submit">Submit</button>
</form>
```

**Response:**
- Redirect dengan success message
- `produk.stok_gudang` updated automatically

---

### 4. Stock Transfer

**Endpoint:**
```
POST /modules/gudang/stok_transfer.php
```

**Content-Type:** `application/x-www-form-urlencoded`

**Parameters:**

| Parameter   | Type    | Required | Description         |
|-------------|---------|----------|---------------------|
| `produk_id` | integer | Yes      | Product ID          |
| `cabang_id` | integer | Yes      | Target branch ID    |
| `jumlah`    | integer | Yes      | Quantity            |
| `tanggal`   | date    | Yes      | Date (Y-m-d)        |
| `keterangan`| string  | No       | Notes               |

**Request Example:**
```html
<form method="POST" action="/modules/gudang/stok_transfer.php">
    <select name="produk_id" required>
        <option value="1">Aqua 600ml</option>
    </select>
    <select name="cabang_id" required>
        <option value="1">Cabang Barat</option>
        <option value="2">Cabang Pusat</option>
    </select>
    <input type="number" name="jumlah" required>
    <input type="date" name="tanggal" required>
    <button type="submit">Transfer</button>
</form>
```

**Response:**
- Insert ke `stok_keluar` dengan `jenis='transfer'`
- Update `produk.stok_gudang` (kurangi)
- Update `stok_cabang.jumlah` (tambah)

---

### 5. Stock Opname

**Endpoint:**
```
POST /modules/gudang/stok_opname.php
```

**Content-Type:** `application/x-www-form-urlencoded`

**Parameters:**

| Parameter    | Type    | Required | Description        |
|--------------|---------|----------|--------------------|
| `produk_id`  | integer | Yes      | Product ID         |
| `stok_fisik` | integer | Yes      | Physical stock     |
| `keterangan` | string  | No       | Notes              |

**Request Example:**
```html
<form method="POST" action="/modules/gudang/stok_opname.php">
    <select name="produk_id" required>
        <option value="1">Aqua 600ml</option>
    </select>
    <input type="number" name="stok_fisik" placeholder="Stok Fisik" required>
    <textarea name="keterangan" placeholder="Keterangan"></textarea>
    <button type="submit">Submit Opname</button>
</form>
```

**Process:**
1. Get `stok_sistem` from `produk.stok_gudang`
2. Calculate `selisih = stok_fisik - stok_sistem`
3. Determine `status`:
   - `HILANG` if selisih < 0
   - `KETEMU` if selisih > 0
4. Insert to `stock_opname`
5. **Trigger automatically updates** `produk.stok_gudang`

**Response:**
- Success message with selisih info

---

### 6. Create Transaction (Kasir)

**Endpoint:**
```
POST /modules/kasir/index.php
```

**Content-Type:** `application/json` (AJAX)

**Parameters:**

| Parameter        | Type    | Required | Description           |
|------------------|---------|----------|-----------------------|
| `session_id`     | integer | Yes      | Cashier session ID    |
| `tipe`           | string  | Yes      | `pembeli` or `penjual`|
| `nama_pelanggan` | string  | No       | Customer name         |
| `items`          | array   | Yes      | Array of items        |
| `total`          | integer | Yes      | Total price           |
| `bayar`          | integer | Yes      | Payment amount        |

**Items Structure:**
```json
[
    {
        "produk_id": 1,
        "qty": 5,
        "harga": 3000
    },
    {
        "produk_id": 2,
        "qty": 2,
        "harga": 10000
    }
]
```

**Request Example:**
```javascript
const data = {
    session_id: 1,
    tipe: 'pembeli',
    nama_pelanggan: 'John Doe',
    items: [
        { produk_id: 1, qty: 5, harga: 3000 },
        { produk_id: 2, qty: 2, harga: 10000 }
    ],
    total: 35000,
    bayar: 50000
};

fetch('/modules/kasir/index.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => console.log(result));
```

**Response Success (200):**
```json
{
    "success": true,
    "invoice_no": "INV-20260228-0001",
    "kembalian": 15000,
    "message": "Transaksi berhasil"
}
```

**Process:**
1. Generate unique invoice number
2. Insert to `transaksi_header`
3. Insert items to `transaksi_detail`
4. Update stock (`stok_cabang` for pembeli, `produk.stok_gudang` for penjual)

---

## Error Codes

| HTTP Code | Description              |
|-----------|--------------------------|
| 200       | Success                  |
| 400       | Bad Request              |
| 401       | Unauthorized (not login) |
| 404       | Not Found                |
| 500       | Internal Server Error    |

---

## Response Format

### Success Response

```json
{
    "success": true,
    "data": { ... },
    "message": "Optional success message"
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error (optional)"
}
```

---

## JavaScript Helper Functions

### Fetch with Error Handling

```javascript
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        alert('Error: ' + error.message);
        throw error;
    }
}
```

### Usage Example

```javascript
// GET request
const stokData = await apiRequest('/api/get_stok_cabang.php?cabang_id=1');
console.log(stokData.data);

// POST request
const transaksi = await apiRequest('/modules/kasir/index.php', {
    method: 'POST',
    body: JSON.stringify({
        session_id: 1,
        tipe: 'pembeli',
        items: [{ produk_id: 1, qty: 10, harga: 3000 }],
        total: 30000,
        bayar: 50000
    })
});
console.log('Invoice:', transaksi.invoice_no);
```

---

## Rate Limiting

Saat ini **tidak ada rate limiting**. Untuk production, disarankan implementasi rate limiting di Nginx atau aplikasi level.

---

## CORS

Aplikasi ini tidak menggunakan CORS karena frontend dan backend dalam satu domain. Jika diperlukan API untuk external client, tambahkan CORS headers:

```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

## Security Notes

1. **Authentication:** Gunakan session-based auth
2. **SQL Injection:** Gunakan prepared statements
3. **XSS Protection:** Escape output dengan `htmlspecialchars()`
4. **CSRF Protection:** Implementasi CSRF token untuk form submissions
5. **Input Validation:** Validasi semua input di server-side

**Example CSRF Protection:**

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Verify token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}
```

---

## Changelog

### Version 1.0.0 (2026-02-28)
- Initial API documentation
- GET /api/get_stok_cabang.php
- GET /modules/admin/get_transaction_detail.php
- POST endpoints for stock management
- POST endpoint for transactions

---

**Last Updated:** 2026-02-28
