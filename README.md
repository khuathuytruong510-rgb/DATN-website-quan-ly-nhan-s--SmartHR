# SmartHR - Hệ Thống Quản Lí Nhân Sự

Dự án Quản lí nhân sự SmartHR - Hệ thống quản lý nhân sự tổng thể với backend API và frontend web.

## 📋 Tổng Quan Dự Án

- **Backend**: Node.js + Express + TypeScript
- **Frontend**: React + Vite + TypeScript  
- **Architecture**: Monorepo structure
- **Database**: PostgreSQL/MySQL ready
- **Authentication**: JWT-based
- **Tools**: ESLint, Prettier, TypeScript
- **UI Design**: Modern Dark Theme with Beautiful Components

## ✨ Tính Năng

### 📊 Dashboard
- Tổng quan thống kê nhân viên, phòng ban, yêu cầu nghỉ phép
- Hoạt động gần đây
- Hành động nhanh

### 👥 Quản Lí Nhân Viên
- Danh sách nhân viên với thông tin chi tiết
- Tìm kiếm và lọc theo phòng ban
- Quản lý hồ sơ nhân viên

### 🏢 Quản Lí Phòng Ban
- Danh sách các phòng ban
- Thông tin trưởng phòng
- Số lượng nhân viên mỗi phòng

### 📅 Quản Lí Nghỉ Phép
- Tạo yêu cầu nghỉ phép
- Duyệt/từ chối yêu cầu
- Tra cứu lịch sử nghỉ phép

### ⏰ Chấm Công
- Quản lý chấm công nhân viên
- Theo dõi giờ vào/ra
- Báo cáo chấm công

### 💰 Quản Lí Lương (Coming Soon)
- Tính toán lương
- Quản lý thưởng
- Báo cáo lương

## 🚀 Quick Start

### Yêu cầu
- Node.js (v18+)
- npm hoặc yarn
- PostgreSQL/MySQL (tuỳ chọn)

### Cài đặt

```bash
# Clone repository
git clone <repo-url>
cd "website quản li nhân sự SmartHR"

# Install dependencies
npm install

# Cấu hình môi trường
cp apps/backend/.env.example apps/backend/.env
cp apps/frontend/.env.example apps/frontend/.env

# Chỉnh sửa .env files nếu cần (tùy chọn)
```

### Chạy Development Server

```bash
# Run both backend và frontend
npm run dev

# Hoặc chạy riêng lẻ:

# Backend only
cd apps/backend
npm run dev

# Frontend only
cd apps/frontend
npm run dev
```

**Truy cập:**
- 🎨 Frontend: http://localhost:5173
- 🔌 Backend: http://localhost:3001
- ✅ API Health: http://localhost:3001/api/health

## 📁 Cấu Trúc Dự Án

```
website quản li nhân sự SmartHR/
├── apps/
│   ├── backend/                 # Node.js Express API
│   │   ├── src/
│   │   │   ├── controllers/     # Business logic
│   │   │   ├── routes/          # API routes
│   │   │   ├── middleware/      # Express middleware
│   │   │   └── index.ts         # Server entry point
│   │   ├── db/
│   │   │   ├── init.sql         # Database schema
│   │   │   └── seed.sql         # Sample data
│   │   ├── dist/                # Compiled output
│   │   ├── .env                 # Environment variables
│   │   ├── package.json
│   │   └── tsconfig.json
│   │
│   └── frontend/                # React + Vite
│       ├── src/
│       │   ├── components/      # Reusable UI components
│       │   │   ├── Layout.tsx
│       │   │   ├── Header.tsx
│       │   │   ├── Sidebar.tsx
│       │   │   ├── Button.tsx
│       │   │   ├── Card.tsx
│       │   │   ├── Modal.tsx
│       │   │   ├── StatCard.tsx
│       │   │   ├── Badge.tsx
│       │   │   └── Input.tsx
│       │   ├── pages/           # Page components
│       │   │   ├── Dashboard.tsx
│       │   │   ├── Employees.tsx
│       │   │   ├── Departments.tsx
│       │   │   ├── Leaves.tsx
│       │   │   ├── Attendance.tsx
│       │   │   └── Home.tsx
│       │   ├── services/        # API services
│       │   ├── hooks/           # Custom hooks
│       │   ├── App.tsx
│       │   ├── main.tsx
│       │   └── index.css        # Global styles
│       ├── index.html
│       ├── .env                 # Environment variables
│       ├── package.json
│       ├── tsconfig.json
│       └── vite.config.ts
│
├── .github/
│   └── copilot-instructions.md
├── .gitignore
├── package.json
└── README.md
```

## 📝 Các Lệnh Hữu Ích

```bash
# Development
npm run dev              # Chạy backend + frontend
npm run dev -w backend  # Chạy backend only
npm run dev -w frontend # Chạy frontend only

# Build
npm run build            # Build cả backend và frontend

# Code Quality
npm run lint             # Kiểm tra linting
npm run format           # Format code
```

## 🎨 Design System

### Màu Sắc
- **Primary**: #2196f3 (Blue)
- **Secondary**: #ff9800 (Orange)
- **Success**: #4caf50 (Green)
- **Danger**: #f44336 (Red)
- **Warning**: #ff9800 (Orange)

### Thành Phần UI
- **Buttons**: primary, secondary, outline variants
- **Cards**: Hoverable cards với gradient background
- **Badges**: Status indicators
- **Modal**: Responsive modals
- **Forms**: Stylish input fields
- **Tables**: Clean data tables

### Typography
- **Font**: System font stack
- **Headings**: Gradient text effects
- **Size Scale**: Consistent sizing system

## 🔧 Cấu Hình Backend

Backend được cấu hình thông qua file `.env`:

```env
PORT=3001
NODE_ENV=development
DB_HOST=localhost
DB_PORT=5432
DB_USER=smarthr
DB_PASSWORD=smarthr123
DB_NAME=smarthr_db
JWT_SECRET=your_jwt_secret_key_here_change_in_production
JWT_EXPIRES_IN=7d
CORS_ORIGIN=http://localhost:5173
```

## 🎨 Cấu Hình Frontend

Frontend được cấu hình thông qua file `.env`:

```env
VITE_API_URL=http://localhost:3001/api
```

## 🗄️ Database Setup

### PostgreSQL (Khuyến nghị)

```bash
# Windows - Using psql
psql -U postgres
CREATE DATABASE smarthr_db;
CREATE USER smarthr WITH PASSWORD 'smarthr123';
GRANT ALL PRIVILEGES ON DATABASE smarthr_db TO smarthr;
```

### Import Schema

```bash
psql -U smarthr -d smarthr_db -f apps/backend/db/init.sql
psql -U smarthr -d smarthr_db -f apps/backend/db/seed.sql
```

## 📚 API Documentation

### Health Check
```
GET /api/health
```

Tài liệu API chi tiết sẽ được cập nhật trong folder `docs/` (sắp tới)

## 🐛 Troubleshooting

### Port đã được sử dụng
```bash
# Windows - Kill process using port 3001 or 5173
netstat -ano | findstr :3001
taskkill /PID <PID> /F
```

### Dependencies issues
```bash
# Clear cache và reinstall
rm -r node_modules
npm cache clean --force
npm install
```

### Frontend không kết nối được Backend
1. Kiểm tra Backend đang chạy: http://localhost:3001/api/health
2. Kiểm tra CORS_ORIGIN trong `.env` backend
3. Kiểm tra VITE_API_URL trong `.env` frontend

## 🚀 Production Build

```bash
# Build cả backend và frontend
npm run build

# Backend
cd apps/backend
npm run build
npm start

# Frontend
cd apps/frontend
npm run build
npm run preview
```

## 📄 License

MIT

## 👨‍💻 Development Tips

### Code Style
- Sử dụng ESLint để kiểm tra code
- Sử dụng Prettier để format code
- Tuân theo TypeScript strict mode

### Component Development
- Tách UI components vào `src/components`
- Tách page components vào `src/pages`
- Sử dụng CSS modules hoặc CSS-in-JS

### Backend Development
- Tách business logic vào controllers
- Tách routes vào files riêng
- Sử dụng middleware cho auth/validation

### Testing (Coming Soon)
- Unit tests với Jest
- Integration tests cho APIs
- Component tests với React Testing Library

## 📞 Support

Cho bất kì câu hỏi hoặc đề xuất, vui lòng tạo issue trên repository.

---

**Developed with ❤️ using Node.js, Express, React, and TypeScript**

*Last Updated: May 26, 2026*
