import { FormEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import apiClient from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';
import './Auth.css';

export default function Register() {
  const navigate = useNavigate();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');

    try {
      const response = await apiClient.post('/auth/register', { name, email, password });
      localStorage.setItem('smarthr_token', response.data.token);
      navigate('/');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Đăng ký thất bại.');
    }
  };

  return (
    <div className="auth-page">
      <div className="auth-card">
        <h1>Đăng ký</h1>
        <form onSubmit={handleSubmit} className="auth-form">
          <Input label="Họ và tên" type="text" value={name} onChange={(e) => setName(e.target.value)} />
          <Input label="Email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          <Input label="Mật khẩu" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          {error && <div className="auth-error">{error}</div>}
          <Button type="submit" fullWidth>
            Đăng ký
          </Button>
        </form>
        <div className="auth-footer">
          <span>Đã có tài khoản?</span>
          <Link to="/login">Đăng nhập</Link>
        </div>
      </div>
    </div>
  );
}
