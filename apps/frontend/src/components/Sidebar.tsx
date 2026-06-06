import { Link, useLocation } from 'react-router-dom';
import './Sidebar.css';

const links = [
  { path: '/', label: 'Dashboard' },
  { path: '/employees', label: 'Nhân viên' },
  { path: '/departments', label: 'Phòng ban' },
  { path: '/contracts', label: 'Hợp đồng' },
];

export default function Sidebar() {
  const location = useLocation();

  return (
    <aside className="sidebar">
      <div className="sidebar-brand">
        <h1>SmartHR</h1>
        <p>Quản lý nhân sự</p>
      </div>
      <nav className="sidebar-nav">
        {links.map((link) => (
          <Link
            key={link.path}
            to={link.path}
            className={location.pathname === link.path ? 'active' : ''}
          >
            {link.label}
          </Link>
        ))}
      </nav>
    </aside>
  );
}
