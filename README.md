# WebQuanLyQuanCafe

Ứng dụng quản lý quán cà phê xây dựng bằng PHP native, MySQL và mô hình MVC thủ công. Hệ thống xử lý danh mục sản phẩm, công thức pha chế, nguyên liệu, giỏ hàng, đơn hàng, mã giảm giá và các chức năng quản trị dữ liệu vận hành.

## Tech Stack

| Layer | Công nghệ |
| --- | --- |
| Backend | PHP native, mysqli |
| Database | MySQL |
| Frontend | PHP View, HTML, CSS, JavaScript |
| Architecture | MVC thủ công: `models`, `controllers`, `views` |
| Web Server | Apache/XAMPP hoặc PHP built-in server |

## Database Architecture

Database mặc định: `COFFESHOP`.

![ERD Diagram](duong_dan_anh_ERD_tai_day)

### Core Entities

| Entity | Vai trò | Quan hệ chính |
| --- | --- | --- |
| `ACCOUNTS` | Lưu thông tin đăng nhập và phân quyền | 1-1 với `USERS` |
| `USERS` | Hồ sơ người dùng | 1-n với `CARTS`, `ORDERS`, `PRODUCTREVIEWS` |
| `PRODUCTS` | Sản phẩm bán ra | n-1 với `RECIPES`, `UNITS`, `CATEGORIES` |
| `RECIPES` | Công thức sản phẩm | 1-n với `RECIPEDETAILS` |
| `INGREDIENTS` | Nguyên liệu tồn kho | n-1 với `PRODUCERS`, `UNITS`; 1-n với `RECIPEDETAILS`, `IMPORTDETAILS` |
| `CARTS` | Giỏ hàng theo người dùng | 1-n với `CARTDETAILS` |
| `ORDERS` | Đơn hàng | 1-n với `ORDERDETAILS`; n-1 với `USERS`, `DISCOUNTS` |
| `IMPORTS` | Phiếu nhập nguyên liệu | 1-n với `IMPORTDETAILS`; n-1 với `PRODUCERS` |
| `DISCOUNTS` | Mã giảm giá | 1-n với `ORDERS` |

### Main Data Flows

**Cart Flow**

1. Người dùng thêm sản phẩm qua `src/views/Components/Products/add_to_cart_handler.php`.
2. `ProductController::addToCart()` kiểm tra `CARTS` theo `USERID`.
3. Nếu chưa có giỏ hàng, hệ thống tạo bản ghi `CARTS`.
4. Sản phẩm được ghi vào `CARTDETAILS`.
5. Ràng buộc unique `UQ_CART_PRODUCT (CARTID, PRODUCTID)` cho phép cộng dồn số lượng bằng `ON DUPLICATE KEY UPDATE`.

**Checkout Flow**

1. `CartProcessor` lấy danh sách sản phẩm từ `CARTS` và `CARTDETAILS`.
2. `applyDiscount.php` kiểm tra mã giảm giá theo tên, ngày hiệu lực và giá trị tối thiểu.
3. `createOrder.php` mở transaction MySQL.
4. Hệ thống ghi `ORDERS`, sau đó ghi từng dòng vào `ORDERDETAILS`.
5. Sau khi commit thành công, giỏ hàng của người dùng được xóa khỏi `CARTS` và `CARTDETAILS`.

**Product Management Flow**

1. Admin tạo sản phẩm qua `src/views/Admin/php/Product/addProduct.php`.
2. File ảnh được upload vào thư mục public.
3. Metadata sản phẩm được ghi vào `PRODUCTS` kèm `RECIPEID`, `UNITID`, `CATEGORYID`.
4. Khi xóa sản phẩm, `ProductController::deleteProduct()` dùng transaction để xóa dữ liệu liên quan trong `PRODUCTREVIEWS`, `CARTDETAILS`, `ORDERDETAILS`, sau đó xóa `PRODUCTS`.

**Inventory and Recipe Flow**

1. `IMPORTS` lưu thông tin phiếu nhập theo nhà cung cấp.
2. `IMPORTDETAILS` lưu nguyên liệu, số lượng, đơn giá và tổng tiền từng dòng nhập.
3. `RECIPES` định nghĩa công thức sản phẩm.
4. `RECIPEDETAILS` ánh xạ công thức với nguyên liệu và định mức sử dụng.

## Installation & Setup

### 1. Clone repository

```bash
git clone <repository-url>
cd WebQuanLyQuanCafe
```

### 2. Cấu hình web server

Với XAMPP, đặt source code trong thư mục `htdocs`:

```bash
C:\xampp\htdocs\WebQuanLyQuanCafe
```

URL truy cập:

```text
http://localhost/WebQuanLyQuanCafe/src/index.php
```

Nếu dùng PHP built-in server:

```bash
php -S localhost:8000 -t src
```

URL truy cập:

```text
http://localhost:8000/index.php
```

## Environment Configuration

Tạo file `.env` từ file mẫu:

```bash
cp .env.example .env
```

Trên Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Cấu hình mặc định:

```env
APP_ENV=local
BASE_URL=http://localhost/WebQuanLyQuanCafe

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=COFFESHOP
DB_USERNAME=root
DB_PASSWORD=
```

Cấu hình được load bởi:

```text
src/config/Env.php
src/config/DatabaseConnection.php
src/config/configUrl.php
```

## Database Setup

### 1. Tạo schema

Chạy script khởi tạo database:

```bash
mysql -u root -p < src/config/sql/InitCoffeeShop.sql
```

Script này sẽ:

- Xóa và tạo lại database `COFFESHOP`.
- Tạo các bảng nghiệp vụ chính.
- Thiết lập khóa ngoại giữa account, user, product, recipe, cart, order, import và discount.

### 2. Seed dữ liệu mẫu

```bash
mysql -u root -p COFFESHOP < src/config/sql/InitInsertCoffeeShop.sql
```

Script seed dữ liệu mẫu cho:

- Account và user.
- Unit, producer, ingredient.
- Recipe, recipe detail.
- Category, product.
- Import, import detail.
- Discount, order, order detail.
- Cart, cart detail, product review.

## API Endpoints

Ứng dụng chưa dùng REST router tập trung. Các endpoint ghi dữ liệu hiện được triển khai bằng PHP script theo từng view/module.

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/src/views/Components/Products/add_to_cart_handler.php` | Thêm sản phẩm vào giỏ hàng, tự tạo `CARTS` nếu chưa tồn tại |
| `POST` | `/src/views/api/deleteCartDetail.php` | Xóa một sản phẩm khỏi `CARTDETAILS` và tính lại tổng tiền giỏ hàng |
| `POST` | `/src/views/Payment/applyDiscount.php` | Kiểm tra mã giảm giá theo thời gian hiệu lực và giá trị đơn hàng |
| `POST` | `/src/views/Payment/createOrder.php` | Tạo `ORDERS`, ghi `ORDERDETAILS`, xóa giỏ hàng sau khi đặt hàng thành công |

## Project Structure

```text
src/
├── config/
│   ├── DatabaseConnection.php
│   ├── Env.php
│   ├── configUrl.php
│   └── sql/
│       ├── InitCoffeeShop.sql
│       ├── InitInsertCoffeeShop.sql
│       └── ALTER.sql
├── controllers/
│   ├── AccountController.php
│   ├── CartController.php
│   ├── OrderController.php
│   ├── ProductController.php
│   └── ...
├── models/
│   ├── Account.php
│   ├── Cart.php
│   ├── Order.php
│   ├── Product.php
│   └── ...
├── views/
│   ├── Admin/
│   ├── Auth/
│   ├── Components/
│   ├── Payment/
│   └── api/
└── index.php
```

## Core Features

- Quản lý account, user profile và phân quyền `user/admin`.
- Quản lý sản phẩm, danh mục, đơn vị tính, công thức và nguyên liệu.
- Quản lý giỏ hàng với cơ chế cộng dồn sản phẩm theo `(CARTID, PRODUCTID)`.
- Áp dụng mã giảm giá theo thời gian hiệu lực và giá trị đơn hàng tối thiểu.
- Tạo đơn hàng bằng transaction, ghi chi tiết đơn hàng và dọn giỏ hàng sau checkout.
- Quản lý nhập nguyên liệu theo nhà cung cấp và chi tiết phiếu nhập.
- Quản trị sản phẩm, công thức, đơn vị, mã giảm giá và tài khoản.

## Prerequisites

- PHP 8.x
- MySQL 8.x hoặc MariaDB tương thích
- Apache/XAMPP hoặc PHP built-in server
- Extension PHP `mysqli`

## Notes

- Database connection dùng `mysqli`, `utf8mb4` và prepared statement ở các luồng chính.
- Thông tin kết nối database được đọc từ `.env`; không commit file `.env`.
- Chưa có Swagger/Postman collection trong repository.
