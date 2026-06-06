import { useNavigate } from 'react-router-dom';
import apiClient from '../services/api';
import Button from './Button';
import './Header.css';

export default function Header() {
  const navigate = useNavigate();
  const user = typeof window !== 'undefined' && localStorage.getItem('smarthr_user') ? JSON.parse(localStorage.getItem('smarthr_user') as string) : null;

  const handleLogout = async () => {
    try {
      await apiClient.post('/auth/logout');
    } catch (error) {
      // Even if logout request fails, clear token and redirect
    }

    localStorage.removeItem('smarthr_token');
    navigate('/login');
  };

  return (
    <header className="header-bar">
      <div className="header-left">
        <img src="/images/logo.svg" alt="SmartHR" className="app-logo" />
        <h2>SmartHR Dashboard</h2>
      </div>
      <div className="header-right">
        {user && (
          <div className="user-info">
            <img src={user.avatar || '/images/avatars/truong.svg'} alt={user.name} className="user-avatar" />
            <span className="user-name">{user.name}</span>
          </div>
        )}
        <Button variant="secondary" onClick={handleLogout}>
          Đăng xuất
        </Button>
      </div>
    </header>
  );
}
