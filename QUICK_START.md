# 🚀 SmartHR - Hướng Dẫn Chạy Dự Án

## 📋 Yêu Cầu

- ✅ Node.js v18+ (Đã kiểm tra)
- ✅ npm (Đã kiểm tra)
- ⚠️ PostgreSQL/MySQL (Tùy chọn, để test database)

## 🚀 Bước 1: Khởi Động

### Option A: Chạy Cả Backend + Frontend (Khuyến Nghị)

```bash
cd "C:\laragon\www\website quản li nhân sự SmartHR"
npm run dev
```

**Output mong đợi:**
```
Backend: 🚀 SmartHR Backend running on http://localhost:3001
Frontend: ➜  Local:   http://localhost:5173/
```

✅ **Truy cập Frontend:** http://localhost:5173
✅ **Test Backend:** http://localhost:3001/api/health

---

### Option B: Chạy Backend Riêng Lẻ

```bash
cd "C:\laragon\www\website quản li nhân sự SmartHR\apps\backend"
npm run dev
```

**Output:**
```
SmartHR Backend running on http://localhost:3001
API Health: http://localhost:3001/api/health
```

---

### Option C: Chạy Frontend Riêng Lẻ

```bash
cd "C:\laragon\www\website quản li nhân sự SmartHR\apps\frontend"
npm run dev
```

**Output:**
```
➜  Local:   http://localhost:5173/
➜  press h + enter to show help
```

---

## 🌐 Truy Cập Giao Diện

### 📱 Frontend Application
**URL:** http://localhost:5173

**Các trang có sẵn:**
1. **Home Page** - Trang giới thiệu
2. **Dashboard** - Trang chủ hệ thống
3. **Employees** - Quản lý nhân viên
4. **Departments** - Quản lý phòng ban
5. **Leaves** - Quản lý nghỉ phép
6. **Attendance** - Chấm công

### 🔌 Backend API
**URL:** http://localhost:3001/api/health

**Response:**
```json
{
  "status": "OK",
  "timestamp": "2026-05-26T13:00:00.000Z"
}
```

---

## 🎨 Khám Phá Giao Diện

### Dashboard
- 📊 Statistics cards với các con số
- 📋 Recent activities list
- ⚡ Quick action buttons
- 📈 Department summary table

### Features Khác
- 🔍 Search functionality (Header)
- 🎯 Filter by department (Employees)
- 📌 Status badges (Leaves, Attendance)
- 👥 Employee cards with avatars

### Responsive Design
- Desktop: Full layout
- Tablet: Adjusted grid
- Mobile: Stacked layout

---

## 🔧 Build & Production

### Build Dự Án

```bash
cd "C:\laragon\www\website quản li nhân sự SmartHR"
npm run build
```

**Output:**
```
✓ Backend compiled
✓ Frontend built (dist/)
```

### Serve Frontend Production Build

```bash
cd apps/frontend
npm run preview
```

---

## 📋 Kiểm Tra Mã

### Format Code (Prettier)

```bash
# Format tất cả files
npm run format

# Backend
npm run format -w backend

# Frontend
npm run format -w frontend
```

### Lint Code (ESLint)

```bash
# Check linting issues
npm run lint

# Backend
npm run lint -w backend

# Frontend
npm run lint -w frontend
```

---

## 🔄 Troubleshooting

### Frontend không load
**Giải pháp:**
1. Restart frontend server: `npm run dev -w frontend`
2. Clear browser cache: Ctrl+Shift+Delete
3. Check port 5173 có available không

### Backend không respond
**Giải pháp:**
1. Check backend đang chạy: http://localhost:3001/api/health
2. Restart backend: Ctrl+C trong terminal, sau đó `npm run dev -w backend`
3. Check port 3001 có available không

### Port đã được sử dụng
**Windows:**
```bash
# Tìm process sử dụng port 3001
netstat -ano | findstr :3001

# Kill process (thay PID bằng số từ kết quả trên)
taskkill /PID <PID> /F
```

### npm install issues
**Giải pháp:**
```bash
# Clear npm cache
npm cache clean --force

# Delete node_modules
rmdir /s /q node_modules

# Reinstall
npm install
```

---

## 📚 File Quan Trọng

| File | Mục Đích |
|------|----------|
| `README.md` | Main documentation |
| `PROJECT_SUMMARY.md` | Project overview |
| `FRONTEND_STRUCTURE.md` | Frontend details |
| `DESIGN_GUIDE.md` | Design system guide |
| `apps/backend/.env` | Backend config |
| `apps/frontend/.env` | Frontend config |

---

## 🎓 Development Tips

### Chỉnh Sửa CSS
- Tìm `.css` files tương ứng trong `src/components` hoặc `src/pages`
- CSS sẽ hot-reload (không cần restart)

### Thêm Component Mới
```bash
# Create component file
apps/frontend/src/components/MyComponent.tsx

# Create styles
apps/frontend/src/components/MyComponent.css

# Export in index.ts
apps/frontend/src/components/index.ts
```

### Chỉnh Sửa Backend
- Chỉnh files trong `apps/backend/src/`
- Server sẽ auto-restart nếu dùng `ts-node`
- Nếu không, restart thủ công

---

## 📱 Các Trang Có Sẵn

### 1. Home Page
```
URL: http://localhost:5173/home
- Landing page
- Feature showcase
- Call to action
```

### 2. Dashboard
```
URL: http://localhost:5173/
- Statistics overview
- Recent activities
- Quick actions
- Summary tables
```

### 3. Employees
```
URL: http://localhost:5173/employees
- Employee grid view
- Search & filter
- Employee cards
- Status indicators
```

### 4. Departments
```
URL: http://localhost:5173/departments
- Department cards
- Manager info
- Employee count
- Hover effects
```

### 5. Leaves
```
URL: http://localhost:5173/leaves
- Leave requests
- Status badges
- Approve/Reject buttons
- Leave details
```

### 6. Attendance
```
URL: http://localhost:5173/attendance
- Attendance table
- Check-in/out times
- Status indicators
- Color-coded rows
```

---

## 🎯 Chi Tiết Giao Diện

### Sidebar Navigation
```
✓ Dashboard    (📊)
✓ Employees    (👥)
✓ Departments  (🏢)
✓ Leaves       (📅)
✓ Attendance   (⏰)
✓ Payroll      (💰)
✓ Settings     (⚙️)
```

### Header Features
```
- Search bar
- Notification badge (3)
- Message badge (5)
- User profile with avatar
```

### Statistics Cards
```
- Total Employees: 234
- Departments: 12
- Leave Requests: 8
- Absent Today: 5
```

---

## 🎨 Màu Sắc Chính

```
Blue Primary:    #2196f3 (Button, Links)
Success Green:   #4caf50 (✓ Badges)
Danger Red:      #f44336 (✕ Badges)
Warning Orange:  #ff9800 (⏳ Badges)
Dark Background: #121212
Card Background: #1e1e1e
```

---

## ✅ Checklist

- [x] Backend running on http://localhost:3001
- [x] Frontend running on http://localhost:5173
- [x] All pages accessible
- [x] Responsive design working
- [x] CSS styling applied
- [x] Components interactive
- [x] No console errors
- [x] Project builds successfully

---

## 🆘 Cần Giúp?

### Common Issues

**Q: Frontend says "Cannot find module"**
A: Run `npm install` again in root folder

**Q: Port already in use**
A: Kill existing process or use different port

**Q: Changes not showing**
A: Hard refresh browser (Ctrl+Shift+R) or clear cache

**Q: Backend crashes on startup**
A: Check .env file configuration

---

## 📞 Quick Commands

```bash
# Install
npm install

# Development
npm run dev              # Both
npm run dev -w backend   # Backend only
npm run dev -w frontend  # Frontend only

# Build
npm run build

# Code Quality
npm run lint
npm run format

# Serve Production
npm run preview -w frontend
```

---

**Mọi thứ đã sẵn sàng! Hãy bắt đầu khám phá giao diện đẹp mắt của SmartHR! 🎉**

---

*Last Updated: May 26, 2026*
*Status: ✅ READY TO USE*
