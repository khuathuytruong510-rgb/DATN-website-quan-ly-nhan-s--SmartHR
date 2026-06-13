import { FormEvent, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import apiClient from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import './Auth.css';

export default function Login() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');

    try {
      const response = await apiClient.post('/auth/login', { email, password });
      localStorage.setItem('smarthr_token', response.data.token);
      localStorage.setItem('smarthr_user', JSON.stringify(response.data.user));
      navigate('/');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Đăng nhập thất bại.');
    }
  };

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1>Đăng nhập</h1>
        <form onSubmit={handleSubmit} className="auth-form">
          <Input label="Email" name="email" type="email" autoComplete="username" value={email} onChange={(e) => setEmail(e.target.value)} />
          <Input label="Mật khẩu" name="password" type="password" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} />
          {error && <div className="auth-error">{error}</div>}
          <Button type="submit" fullWidth>
            Đăng nhập
          </Button>
        </form>
        <div className="auth-footer">
          <span>Chưa có tài khoản?</span>
          <Link to="/register">Đăng ký</Link>
        </div>
      </div>
    </div>
  );
}
