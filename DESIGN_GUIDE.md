# 🖼️ SmartHR - Giao Diện Preview

## Màu Sắc Chính (Color Palette)

```
Primary Blue:       #2196f3  🔵
Dark Background:    #121212  ⬛
Card Background:    #1e1e1e  ⬜
Text Primary:       #ffffff  ⚪
Text Secondary:     #b0bec5  ⚫
Success Green:      #4caf50  🟢
Danger Red:         #f44336  🔴
Warning Orange:     #ff9800  🟠
```

## Components Layout

### 1. Header Component
```
┌─────────────────────────────────────────┐
│  [SEARCH]  [🔔] [💬]  [Avatar] [Name]  │
│  Hệ Thống Quản Lí Nhân Sự               │
└─────────────────────────────────────────┘
```

Features:
- Search bar with icon
- Notification badges
- User profile with avatar
- Sticky positioning

### 2. Sidebar Component
```
┌─────────────────┐
│   SmartHR       │
│   HR Management │
├─────────────────┤
│ 📊 Dashboard    │
│ 👥 Employees    │
│ 🏢 Departments  │
│ 📅 Leaves       │
│ ⏰ Attendance    │
│ 💰 Payroll      │
├─────────────────┤
│ ⚙️  Settings     │
└─────────────────┘
```

Features:
- Logo with branding
- Navigation menu
- Active state indicators
- Fixed width (260px)
- Gradient background

### 3. Dashboard Page

#### Statistics Section
```
┌───────────┬───────────┬───────────┬───────────┐
│ 👥 Emp.   │ 🏢 Dept   │ 📅 Leave  │ ⏰ Absent │
│ 234       │ 12        │ 8         │ 5         │
│ Active    │ Active    │ Pending   │ Today     │
└───────────┴───────────┴───────────┴───────────┘
```

#### Main Content
```
┌─────────────────────────────────────────┐
│ Recent Activities    │ Quick Actions    │
│ • New employee      │ ✓ Add Employee   │
│ • Leave approved    │ ✓ Add Leave      │
│ • Meeting sched.    │ ✓ New Dept       │
│                     │ ✓ Reports        │
└─────────────────────────────────────────┘

Department Summary Table
┌──────────┬───┬────┬────┬────┐
│ Dept     │Total│Present│Absent│Leave│
├──────────┼───┼────┼────┼────┤
│ IT       │ 45│ 42 │ 1  │ 2  │
│ HR       │ 25│ 24 │ 0  │ 1  │
│ Sales    │ 60│ 55 │ 2  │ 3  │
│ Finance  │ 30│ 29 │ 0  │ 1  │
└──────────┴───┴────┴────┴────┘
```

### 4. Employees Page

```
┌─────────────────────────┐
│ Search [___________]    │
│ Filter [Departments ▼]  │
└─────────────────────────┘

┌──────────────┬──────────────┬──────────────┐
│ Employee 1   │ Employee 2   │ Employee 3   │
│ [Avatar]     │ [Avatar]     │ [Avatar]     │
│ Position     │ Position     │ Position     │
│ ✓ Active     │ ✓ Active     │ ✓ Active     │
│ email@       │ email@       │ email@       │
│ IT Dept      │ HR Dept      │ Sales Dept   │
│ [Details]    │ [Details]    │ [Details]    │
└──────────────┴──────────────┴──────────────┘
```

### 5. Departments Page

```
┌──────────────────────┬──────────────────────┐
│ 🏢 IT Department     │ 🏢 HR Department     │
│ Tech Division        │ Human Resources      │
│                      │                      │
│ Manager: John Doe    │ Manager: Jane Smith  │
│ Employees: 45        │ Employees: 25        │
│ [View Details]       │ [View Details]       │
└──────────────────────┴──────────────────────┘

┌──────────────────────┬──────────────────────┐
│ 🏢 Sales Dept        │ 🏢 Finance Dept      │
│ Sales & Marketing    │ Finance & Acct       │
│                      │                      │
│ Manager: Bob Wilson  │ Manager: Alice Brown │
│ Employees: 60        │ Employees: 30        │
│ [View Details]       │ [View Details]       │
└──────────────────────┴──────────────────────┘
```

### 6. Leaves Page

```
┌─────────────────────────────────────────┐
│ Nguyễn Văn A                ✓ Approved  │
│ Annual Leave • 5 days                   │
│ 📅 Jun 1, 2026 - Jun 5, 2026            │
│ Reason: Personal business               │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Trần Thị B                  ⏳ Pending  │
│ Medical • 2 days                        │
│ 📅 May 25, 2026 - May 26, 2026          │
│ Reason: Sick leave                      │
│ [✓ Approve] [✕ Reject]                 │
└─────────────────────────────────────────┘
```

### 7. Attendance Page

```
┌─────────────────────────────────────────┐
│ Attendance - May 26, 2026                │
├─────────────────────────────────────────┤
│ Name        │ Date    │ In    │ Out    │Status
├─────────────────────────────────────────┤
│ Nguyễn Văn A│ May 26  │ 08:15 │ 17:45  │✓ Present
│ Trần Thị B  │ May 26  │ 08:45 │ 18:00  │⏰ Late
│ Lê Văn C    │ May 26  │ -     │ -      │✕ Absent
│ Phạm Thị D  │ May 26  │ 08:00 │ -      │⏳ Working
└─────────────────────────────────────────┘
```

## Visual Effects

### Buttons
```
Primary:    [✓ Button] - Blue gradient
Secondary:  [✓ Button] - Card color with border
Outline:    [✓ Button] - Transparent with border
```

### Cards
- Gradient backgrounds (top to bottom)
- 1px border with primary color on hover
- Shadow effect on hover
- Smooth transitions (300ms)
- Transform: translateY(-2px) on hover

### Badges
```
✓ Active    - Green background
⏳ Pending   - Orange background
✕ Rejected  - Red background
⏰ Late      - Orange background
```

### Animations
- Fade in: 300ms ease
- Hover effects: 300ms cubic-bezier
- Slide up: 300ms ease
- Transform effects: 300ms smooth

## Typography

- **Headings**: Large (2rem), Bold (700)
- **Subheadings**: Medium (1.25rem), Bold (600)
- **Body Text**: Normal (1rem), Regular (400)
- **Labels**: Small (0.85rem), Bold (600)
- **Helpers**: Extra small (0.8rem), Regular (400)

## Responsive Breakpoints

```
Desktop:  1200px+ (full layout)
Tablet:   768px - 1199px (adjusted layout)
Mobile:   < 768px (stacked layout)
```

## Icon Set

- 📊 Dashboard
- 👥 Employees
- 🏢 Departments
- 📅 Leaves
- ⏰ Attendance
- 💰 Payroll
- ⚙️ Settings
- 🔍 Search
- 🔔 Notifications
- 💬 Messages
- ✓ Approved
- ✕ Rejected
- ⏳ Pending

## Design Principles

1. **Clean & Modern** - Minimalist design with modern accents
2. **Consistent** - Same colors, spacing, and effects throughout
3. **Responsive** - Works on all screen sizes
4. **Accessible** - Good color contrast, readable text
5. **Interactive** - Smooth animations and hover effects
6. **Professional** - Dark theme suitable for business apps

## Spacing System

- Small: 0.5rem (8px)
- Medium: 1rem (16px)
- Large: 1.5rem (24px)
- Extra Large: 2rem (32px)

## Shadow System

- Small: `0 2px 4px rgba(0,0,0,0.1)`
- Medium: `0 4px 12px rgba(0,0,0,0.15)`
- Large: `0 8px 24px rgba(0,0,0,0.2)`

---

**All components are built with production-quality code and CSS!**
