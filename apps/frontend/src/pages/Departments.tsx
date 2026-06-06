import { FormEvent, useEffect, useState } from 'react';
import apiClient from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';

interface Department {
  id: number;
  name: string;
  manager: string | null;
  description: string | null;
  employee_count: number;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const initialFormState = {
  name: '',
  manager: '',
  description: '',
};

export default function Departments() {
  const [departments, setDepartments] = useState<Department[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState(initialFormState);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    fetchDepartments();
  }, [page, search]);

  const fetchDepartments = async () => {
    try {
      const response = await apiClient.get<PaginatedResponse<Department>>('/departments', {
        params: {
          q: search || undefined,
          page,
          per_page: 5,
        },
      });

      setDepartments(response.data.data);
      setPage(response.data.current_page);
      setLastPage(response.data.last_page);
    } catch (err: any) {
      setError('Không tải được danh sách phòng ban.');
    }
  };

  const resetForm = () => {
    setForm(initialFormState);
    setEditingId(null);
    setError('');
    setMessage('');
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError('');
    setMessage('');

    try {
      if (editingId) {
        await apiClient.put(`/departments/${editingId}`, form);
        setMessage('Cập nhật phòng ban thành công.');
      } else {
        await apiClient.post('/departments', form);
        setMessage('Tạo phòng ban thành công.');
      }

      resetForm();
      fetchDepartments();
    } catch (err: any) {
      setError(err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(' ') : 'Lưu phòng ban thất bại.');
    }
  };

  const handleEdit = (department: Department) => {
    setEditingId(department.id);
    setForm({
      name: department.name,
      manager: department.manager || '',
      description: department.description || '',
    });
    setMessage('');
    setError('');
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Bạn có chắc muốn xóa phòng ban này?')) {
      return;
    }

    try {
      await apiClient.delete(`/departments/${id}`);
      setMessage('Xóa phòng ban thành công.');
      fetchDepartments();
    } catch (err: any) {
      setError('Xóa phòng ban thất bại.');
    }
  };

  return (
    <div className="page-container">
      <div className="page-header">
        <h1>Phòng ban</h1>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>Danh sách phòng ban</h2>
          <Input
            label="Tìm kiếm"
            type="text"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
          />
        </div>

        <div className="department-grid">
          {departments.length > 0 ? (
            <div className="department-grid-list">
              {departments.map((department) => (
                <div key={department.id} className="department-card">
                  <div className="department-card-header">
                    <h3>{department.name}</h3>
                    <span className="department-count">{department.employee_count} nhân viên</span>
                  </div>
                  <p className="department-manager">Quản lý: {department.manager || '-'}</p>
                  <p className="department-desc">{department.description || 'Chưa có mô tả.'}</p>
                  <div className="department-card-actions">
                    <Button variant="secondary" onClick={() => handleEdit(department)}>
                      Sửa
                    </Button>
                    <Button variant="danger" onClick={() => handleDelete(department.id)}>
                      Xóa
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="empty-state">Chưa có phòng ban nào.</div>
          )}
        </div>

        <div className="pagination">
          <Button type="button" variant="secondary" onClick={() => setPage((p) => Math.max(p - 1, 1))} disabled={page <= 1}>
            Trước
          </Button>
          <span>
            Trang {page} / {lastPage}
          </span>
          <Button type="button" variant="secondary" onClick={() => setPage((p) => Math.min(p + 1, lastPage))} disabled={page >= lastPage}>
            Sau
          </Button>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="section-card">
        <h2>{editingId ? 'Chỉnh sửa phòng ban' : 'Tạo phòng ban mới'}</h2>
        <Input
          label="Tên phòng ban"
          type="text"
          value={form.name}
          onChange={(e) => setForm({ ...form, name: e.target.value })}
          required
        />
        <Input
          label="Quản lý"
          type="text"
          value={form.manager}
          onChange={(e) => setForm({ ...form, manager: e.target.value })}
        />
        <label className="input-group">
          <span className="input-label">Mô tả</span>
          <textarea
            className="input-field"
            rows={3}
            value={form.description}
            onChange={(e) => setForm({ ...form, description: e.target.value })}
          />
        </label>
        {error && <div className="form-error">{error}</div>}
        {message && <div className="form-success">{message}</div>}
        <div className="form-actions">
          <Button type="submit">{editingId ? 'Cập nhật' : 'Tạo mới'}</Button>
          {editingId && (
            <Button type="button" variant="secondary" onClick={resetForm}>
              Hủy
            </Button>
          )}
        </div>
      </form>
    </div>
  );
}
