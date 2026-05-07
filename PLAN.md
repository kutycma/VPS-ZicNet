# PROJECT CONTEXT: LÂRAVEL 8 VPS RESELLER SYSTEM (PHP 7.4)

## 1. TỔNG QUAN DỰ ÁN
Xây dựng một hệ thống Reseller VPS tự động bằng Laravel 8 (chạy trên PHP 7.4). 
Yêu cầu cốt lõi: Clean Code, dễ mở rộng, bảo mật tài chính (Atomic Locks/DB Transactions), tuân thủ Service Pattern. Tính toán giá bán tự động dựa trên giá vốn từ nhà cung cấp API.

## 2. QUY TẮC KIẾN TRÚC (ARCHITECTURE RULES)
- **Service Pattern:** TUYỆT ĐỐI KHÔNG viết logic API/Thanh toán vào Controller.
- **Vps Provider Contract:** Mọi kết nối API (H2Cloud, Proxmox...) phải implement `VpsProviderInterface`.
- **Bảo mật tài chính:** Mọi thao tác cộng/trừ `balance` phải bọc trong `DB::transaction()`.
- **Pricing Logic:** Giá bán của VPS (selling price) phải được tính linh hoạt dựa trên cấu hình nhà cung cấp (tăng % cố định, tăng tiền cố định, hoặc set tay từng gói).
- **Đa ngôn ngữ:** Dùng helper `__()` cho text giao diện.

## 3. THIẾT KẾ DATABASE (DATABASE SCHEMA)
Cập nhật thiết kế Database để quản lý nhà cung cấp và giá:
- `users`: name, email, password, role (enum: admin, user), balance (decimal), status.
- `vps_providers` (MỚI): name, api_endpoint, api_key, markup_type (enum: 'percentage', 'fixed', 'manual'), markup_value (decimal), status. (Bảng quản lý các nhà cung cấp như H2Cloud).
- `vps_plans`: provider_id, provider_plan_id (ID trên API của NCC), name, cpu_cores, ram_mb, disk_gb, provider_price (giá vốn), selling_price (giá bán ra), status.
- `vps_instances`: user_id, plan_id, vps_provider_id (string, ID từ API của NCC), ip_address, os, status, auto_renew.
- `orders`: user_id, vps_plan_id, vps_instance_id, amount, duration_months, status.
- `transactions`: user_id, amount, balance_before, balance_after, type, description, status.
- `payment_methods`: name, api_key, secret_key, is_active.
- `settings`: key, value.

## 4. CẤU TRÚC THƯ MỤC CẦN KHỞI TẠO (DIRECTORY STRUCTURE)

app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── UserController.php
│   │   │   ├── VpsProviderController.php  // MỚI: Quản lý API NCC & Cấu hình % giá bán
│   │   │   ├── VpsPlanController.php      // MỚI: Quản lý chi tiết từng gói, giá vốn/giá bán
│   │   │   ├── VpsManagerController.php   
│   │   │   ├── SettingController.php      
│   │   │   ├── PaymentConfigController.php
│   │   │   └── TransactionController.php  
│   │   ├── Client/
│   │   │   ├── UserController.php         // MỚI: Quản lý Profile, đổi mật khẩu của khách
│   │   │   ├── VpsController.php          
│   │   │   ├── OrderController.php        
│   │   │   └── BillingController.php      
│   │   └── Webhook/
│   │       └── PaymentWebhookController.php 
│   └── Middleware/
│       ├── SetLocale.php                
│       └── IsAdmin.php                  
├── Models/
│   ├── User.php
│   ├── VpsProvider.php                    // MỚI
│   ├── VpsPlan.php
│   ├── VpsInstance.php
│   ├── Order.php
│   ├── Transaction.php
│   ├── PaymentMethod.php
│   └── Setting.php
├── Services/
│   ├── Vps/                             
│   │   ├── Contracts/
│   │   │   └── VpsProviderInterface.php 
│   │   ├── Providers/
│   │   │   ├── H2CloudProvider.php      // MỚI: Tích hợp API H2Cloud
│   │   │   └── BaseApiProvider.php      // Class cha chứa các hàm call API chung (HTTP/Curl)
│   │   └── VpsManager.php               
│   ├── Billing/                         
│   │   └── PaymentService.php           
│   └── Setting/
│       └── SystemSettingService.php     

## 5. LUỒNG NGHIỆP VỤ YÊU CẦU AI THỰC HIỆN (EXECUTION STEPS)
AI thực hiện code theo các bước sau. **Chú ý đọc kỹ Step 5**:

* **Step 1:** Setup Migration & Models. Cấu hình relation (Provider hasMany Plans, Plan hasMany Instances).
* **Step 2:** Tạo Auth (Admin/User). Làm `UserController` cho Client (cập nhật thông tin, đổi pass).
* **Step 3:** Code `VpsProviderController` và `VpsPlanController` trong Admin. 
    * *Logic quan trọng:* Khi Admin thêm/sửa `VpsPlan`, tự động tính toán `selling_price` dựa trên `provider_price` và `markup_type` của `VpsProvider` tương ứng.
* **Step 4:** Code `PaymentService` & `TransactionController` (Luồng nạp tiền và ghi log số dư).
* **Step 5 (TÍCH HỢP H2CLOUD API):** * *Lệnh cho AI:* Hãy đọc file `h2cloud-api.html` nằm ở thư mục gốc dự án.
    * Dựa vào tài liệu đó, viết class `H2CloudProvider implements VpsProviderInterface` nằm trong thư mục `Services/Vps/Providers/`. 
    * Viết đầy đủ các method: `createVps`, `startVps`, `stopVps`, `rebootVps`, `getStatus` map chính xác với Endpoint và Payload yêu cầu trong tài liệu HTML.
* **Step 6:** Code luồng Khách hàng mua VPS (Kiểm tra balance -> Trừ tiền (Transaction) -> Gọi `VpsManager` -> Gọi `H2CloudProvider` tạo VPS thực tế).
* **Step 7 & 8:** Hoàn thiện UI/UX Admin (xem toàn bộ VPS, cấu hình web) và UI/UX User.