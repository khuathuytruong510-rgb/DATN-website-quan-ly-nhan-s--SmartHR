# SmartHR Workspace Setup Instructions

Dự án Quản lí nhân sự SmartHR - Hệ thống quản lý nhân sự tổng thể với backend API và frontend web.

## Project Overview

- **Backend**: Node.js + Express + TypeScript
- **Frontend**: React + Vite + TypeScript
- **Architecture**: Monorepo structure (apps/backend, apps/frontend)
- **Database**: PostgreSQL/MySQL ready
- **Authentication**: JWT-based authentication
- **Tools**: ESLint, Prettier, TypeScript

## Setup Progress

- [x] Create copilot-instructions.md
- [ ] Verify project structure
- [ ] Install dependencies
- [ ] Configure development environment
- [ ] Setup database
- [ ] Start development servers

## Development Commands

```bash
# Install all dependencies
npm install

# Development mode
npm run dev          # Run both backend and frontend

# Backend only
cd apps/backend
npm run dev

# Frontend only  
cd apps/frontend
npm run dev

# Build for production
npm run build

# Lint code
npm run lint

# Format code
npm run format
```

## Project Structure

```
website quản li nhân sự SmartHR/
├── apps/
│   ├── backend/              # Node.js Express API
│   │   ├── src/
│   │   ├── package.json
│   │   └── tsconfig.json
│   └── frontend/             # React + Vite
│       ├── src/
│       ├── package.json
│       └── tsconfig.json
├── .github/
│   └── copilot-instructions.md
├── package.json
└── README.md
```

## Important Notes

- Backend runs on: http://localhost:3001
- Frontend runs on: http://localhost:5173
- API documentation will be available after setup
