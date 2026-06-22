# Code Review - SmartHR Sistema (2026-06-22)

## Executive Summary
✅ **Overall Status**: Cấu trúc tốt, nhưng có một số vấn đề cần được khắc phục ngay lập tức.

### Danh Sách Kiểm Tra Tổng Quan
- ✅ Database schema được thiết kế tốt
- ⚠️ Các model placeholder chưa được triển khai
- ⚠️ Lỗi validation và missing fields
- ⚠️ Routes inconsistency
- ⚠️ Authorization/Permission chưa được triển khai
- ✅ Attendance system được triển khai hoàn chỉnh

---

## 1. 🔴 CÁC VẤN ĐỀ QUAN TRỌNG (URGENT)

### 1.1 Placeholder Models Chưa Triển Khai
**Mức độ**: 🔴 CRITICAL
**File**: `app/Models/Position.php`, `app/Models/Recruitment.php`, `app/Models/Notification.php`

```php
// ❌ Hiện tại: Placeholder models (không có tác dụng)
class Position extends Model
{
    // placeholder model
}
```

**Giải pháp**:
- Xóa hoặc triển khai các model này
- Nếu không dùng, xóa model và remove import khỏi codebase

### 1.2 Employee Model - Missing Fillable Fields
**Mức độ**: 🔴 CRITICAL
**File**: `app/Models/Employee.php`

**Vấn đề**: Fillable array không đầy đủ
```php
protected $fillable = [
    'user_id',
    'name',
    'email',
    'position',
    'department_id',
    'status',
    'avatar',
];
```

**Thiếu fields** từ migration `2026_06_17_000001_add_employee_profile_fields.php`:
- `employee_code`
- `gender`
- `dob`
- `cccd`
- `phone`
- `address`
- `start_date`
- `education`
- `experience`
- `leave_balance`

**Giải pháp**: Cập nhật fillable array:
```php
protected $fillable = [
    'user_id',
    'name',
    'email',
    'position',
    'department_id',
    'status',
    'avatar',
    'employee_code',
    'gender',
    'dob',
    'cccd',
    'phone',
    'address',
    'start_date',
    'education',
    'experience',
    'leave_balance',
];

protected $casts = [
    'dob' => 'date',
    'start_date' => 'date',
];
```

### 1.3 Contract Model - Missing Fillable Fields
**Mức độ**: 🔴 CRITICAL
**File**: `app/Models/Contract.php`

**Fillable hiện tại**:
```php
protected $fillable = [
    'employee_id',
    'title',
    'salary',
    'start_date',
    'end_date',
    'status',
];
```

**Thiếu fields** từ migration `2026_06_16_000000_update_contracts_add_metadata_fields.php`:
- `base_salary`
- `allowance`
- `probation_salary`
- `company_representative`
- `signer`
- `notes`
- `pdf_file`
- `scan_file`

**Giải pháp**: Cập nhật fillable và casts:
```php
protected $fillable = [
    'employee_id',
    'title',
    'salary',
    'base_salary',
    'allowance',
    'probation_salary',
    'start_date',
    'end_date',
    'status',
    'company_representative',
    'signer',
    'notes',
    'pdf_file',
    'scan_file',
];

protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
];
```

### 1.4 Database Default Connection Issue
**Mức độ**: ⚠️ WARNING
**File**: `config/database.php`

```php
'default' => env('DB_CONNECTION', 'sqlite'),
```

Ứng dụng sử dụng MySQL nhưng `.env` có thể default sang SQLite.

**Giải pháp**: Đảm bảo `.env` có:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_hr
DB_USERNAME=root
DB_PASSWORD=
```

---

## 2. 🟡 CÁC VẤN ĐỀ TRUNG BÌNH

### 2.1 SmartHrController - Missing Authorization
**Mức độ**: 🟡 HIGH
**File**: `app/Http/Controllers/Web/SmartHrController.php`

**Vấn đề**: Không có role-based access control (RBAC)
- Không kiểm tra xem user có phải HR/Admin không
- Employee có thể truy cập `approveLeaveRequest()` v.v.

**Giải pháp**: Thêm middleware authorization:
```php
class SmartHrController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:manage-hr-system']);
    }

    public function approveLeaveRequest(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('approve', $leaveRequest);
        // ...
    }
}
```

Hoặc sử dụng route group:
```php
Route::middleware(['auth', 'role:hr,admin'])->group(function () {
    Route::get('/leave-requests', [SmartHrController::class, 'leaveRequests']);
    // ...
});
```

### 2.2 LeaveRequest Model - Incomplete Migration
**Mức độ**: 🟡 HIGH
**File**: `database/migrations/2026_06_16_000001_add_approval_to_leave_requests_table.php`

**Vấn đề**: Model không có relationship tới approved_by user
```php
public function approver(): BelongsTo
{
    return $this->belongsTo(User::class, 'approved_by');
}
```

✅ **Đã đúng**, nhưng validation logic chưa check approved_at:
```php
// Vấn đề: approved_at không có trong $fillable
$leaveRequest->update([
    'status' => 'approved',
    'approved_by' => auth()->id(),
    'approved_at' => now(),  // ← Có thể không lưu được
]);
```

**Giải pháp**: Cập nhật LeaveRequest model:
```php
protected $fillable = [
    'employee_id',
    'start_date',
    'end_date',
    'days',
    'type',
    'reason',
    'status',
    'approved_by',
    'approved_at',  // ← Thêm này
];

protected $dates = [
    'start_date',
    'end_date',
    'approved_at',  // ← Thêm này
];
```

### 2.3 Attendance Model - Missing Foreign Key Check
**Mức độ**: 🟡 HIGH
**File**: `app/Models/Attendance.php`

**Vấn đề**: Không validate `approved_by` foreign key
```php
// Thiếu relationship
public function approver(): BelongsTo
{
    return $this->belongsTo(User::class, 'approved_by');
}
```

**Giải pháp**: Thêm vào Attendance model:
```php
public function approver(): BelongsTo
{
    return $this->belongsTo(User::class, 'approved_by');
}
```

### 2.4 Department Model - No Relationships
**Mức độ**: 🟡 MEDIUM
**File**: `app/Models/Department.php`

**Vấn đề**: Department model không có relationship tới Employee
```php
// ❌ Hiện tại
class Department extends Model
{
    // Chỉ có fillable, không có relationships
}
```

**Giải pháp**:
```php
public function employees(): HasMany
{
    return $this->hasMany(Employee::class);
}
```

---

## 3. 🟠 CÁC VẤN ĐỀ NHỎ

### 3.1 Routes Inconsistency
**Mức độ**: 🟠 MEDIUM
**File**: `routes/web.php`, `routes/admin.php`, `routes/hr.php`

**Vấn đề**: 
- Web routes sử dụng model binding (string name)
- API routes sử dụng ID

```php
// Web routes - model binding
Route::get('/departments/{department}', [SmartHrController::class, 'showDepartment']);

// API routes - ID
Route::get('/departments/{id}', [DepartmentController::class, 'show']);
```

**Giải pháp**: Standardize route model binding:
```php
// Cách 1: Sử dụng implicit binding cho web routes (tốt hơn)
Route::get('/departments/{department}', [SmartHrController::class, 'showDepartment']);

// Cách 2: Cập nhật API routes để dùng implicit binding:
Route::get('/departments/{department}', [DepartmentController::class, 'show']);
```

### 3.2 SmartHrController - Validation Logic
**Mức độ**: 🟠 MEDIUM
**File**: `app/Http/Controllers/Web/SmartHrController.php` (line 378-387)

**Vấn đề**: `validateContract()` không validate salary format
```php
'salary' => ['required', 'integer', 'min:0'],
```

Nên kiểm tra xem salary có hợp lệ hay không:
```php
'salary' => ['required', 'numeric', 'min:0', 'max:999999999'],
'base_salary' => ['required_if:contract_type,full', 'numeric', 'min:0'],
'allowance' => ['numeric', 'min:0'],
```

### 3.3 SmartHrController - Missing Edit Route
**Mức độ**: 🟠 MEDIUM
**File**: `app/Http/Controllers/Web/SmartHrController.php`

**Vấn đề**: Không có `editLeaveRequest` route
```php
// routes/web.php - Thiếu
Route::get('/leave-requests/{leaveRequest}/edit', [SmartHrController::class, 'editLeaveRequest']);
Route::put('/leave-requests/{leaveRequest}', [SmartHrController::class, 'updateLeaveRequest']);
```

Chỉ có create/store/approve/reject/destroy, nhưng không edit.

**Giải pháp**: Thêm edit/update methods.

### 3.4 Payroll Model - Missing Relationships
**Mức độ**: 🟠 MEDIUM
**File**: `app/Models/Payroll.php`

**Current**:
```php
public function employee(): BelongsTo
{
    return $this->belongsTo(Employee::class);
}
```

Nên thêm accessor hoặc casts:
```php
protected $casts = [
    'paid_at' => 'datetime',
];

// Accessor để kiểm tra trạng thái thanh toán
public function getIsPaidAttribute(): bool
{
    return $this->status === 'paid' && $this->paid_at !== null;
}
```

### 3.5 User Model - Missing Employee Relationship
**Mức độ**: 🟠 MEDIUM
**File**: `app/Models/User.php`

**Vấn đề**: User không có relationship tới Employee

**Giải pháp**:
```php
public function employee(): HasOne
{
    return $this->hasOne(Employee::class);
}

public function leaveRequestsApproved(): HasMany
{
    return $this->hasMany(LeaveRequest::class, 'approved_by');
}

public function attendancesApproved(): HasMany
{
    return $this->hasMany(Attendance::class, 'approved_by');
}
```

---

## 4. ✅ CÁC ĐIỂM TỐT

### 4.1 Database Migrations
✅ Tất cả migrations được tổ chức tốt
✅ Foreign keys được set up đúng
✅ Cascade delete được sử dụng hợp lý
✅ Migration idempotency (sử dụng `hasColumn()`)

### 4.2 Attendance System
✅ Service layer được triển khai (AttendanceCalculationService)
✅ Geolocation tracking được implement
✅ Complex calculations được handle
✅ API endpoints tổ chức theo resource

### 4.3 Routes Organization
✅ Route groups được sử dụng tốt
✅ Middleware được apply đúng cách
✅ RESTful conventions được tuân theo

---

## 5. 📋 DANH SÁCH KIỂM TRA SỬA CHỮA

### Priority 1 (NGAY LẬP TỨC)
- [ ] Cập nhật Employee model fillable fields
- [ ] Cập nhật Contract model fillable fields
- [ ] Cập nhật LeaveRequest model fillable fields
- [ ] Thêm approver relationship tới Attendance model
- [ ] Xóa hoặc triển khai Position/Recruitment/Notification models
- [ ] Cập nhật .env với đúng DB_CONNECTION

### Priority 2 (TRONG TUẦN)
- [ ] Thêm role-based authorization vào SmartHrController
- [ ] Thêm Department -> Employee relationship
- [ ] Thêm User -> Employee relationship
- [ ] Thêm edit/update leave request endpoints
- [ ] Standardize route model binding (web + API)

### Priority 3 (TỐI ƯU HÓA)
- [ ] Thêm validation rules cho salary fields
- [ ] Thêm casts cho Payroll model
- [ ] Thêm accessors cho status checking
- [ ] Thêm query scopes cho common queries
- [ ] Thêm tests cho business logic

---

## 6. 🔧 LỖI CÓ THỂ GẶP PHẢI

### Issue 1: "Mass assignment" exception
```
BadMethodCallException: Call to undefined method App\Models\Employee::updateOrCreate()
```
**Nguyên nhân**: Fillable fields không đầy đủ
**Giải pháp**: Update fillable array

### Issue 2: "Column not found" exception
```
QueryException: SQLSTATE[42S22]: Column not found: ... 'employee_code'
```
**Nguyên nhân**: Migration chưa chạy hoặc fillable chưa cập nhật
**Giải pháp**: `php artisan migrate`

### Issue 3: Authorization failures
```
Unauthorized action on model [LeaveRequest]
```
**Nguyên nhân**: Không có policies/authorization
**Giải pháp**: Thêm authorization checks

---

## 7. 📚 HƯỚNG DẪN TỰA HÀNH

### Chạy code review fixes:

**Step 1: Backup database**
```bash
php artisan backup:run
```

**Step 2: Cập nhật models** (xem section 1.2-1.4)

**Step 3: Chạy migrations nếu chưa**
```bash
php artisan migrate
```

**Step 4: Seed dữ liệu**
```bash
php artisan db:seed
```

**Step 5: Test API**
```bash
php artisan test
```

---

## 8. 🎯 KẾT LUẬN

**Tổng Điểm**: 7.5/10

### Ưu điểm:
- ✅ Kiến trúc Laravel tốt
- ✅ Database schema hợp lý
- ✅ Attendance system hoàn chỉnh
- ✅ Route organization tốt

### Nhược điểm:
- ❌ Model fillable arrays không đầy đủ
- ❌ Missing relationships
- ❌ Chưa có authorization
- ❌ Placeholder models

**Cách sửa dự kiến**: 3-4 tiếng
**Độ khó**: Easy to Medium

---

*Generated: 2026-06-22*
*Last Updated: 2026-06-22 - Comprehensive Review*
