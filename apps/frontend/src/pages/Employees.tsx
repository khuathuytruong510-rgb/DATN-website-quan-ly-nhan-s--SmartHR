import { FormEvent, useEffect, useState } from 'react';
import apiClient from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';

interface Department {
  id: number;
  name: string;
}

interface Employee {
  id: number;
  name: string;
  email: string;
  position: string;
  department_id: number;
  status: string;
  department?: Department;
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
  email: '',
  position: '',
  department_id: 0,
  status: 'active',
};

export default function Employees() {
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState(initialFormState);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    fetchEmployees();
    fetchDepartments();
  }, [page, search]);

  const fetchEmployees = async () => {
    try {
      const response = await apiClient.get<PaginatedResponse<Employee>>('/employees', {
        params: {
          q: search || undefined,
          page,
          per_page: 5,
        },
      });

      setEmployees(response.data.data);
      setPage(response.data.current_page);
      setLastPage(response.data.last_page);
    } catch (err: any) {
      setError('Không tải được danh sách nhân viên.');
    }
  };

  const fetchDepartments = async () => {
    try {
      const response = await apiClient.get<PaginatedResponse<Department>>('/departments', {
        params: { per_page: 100 },
      });
      setDepartments(response.data.data);
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
        await apiClient.put(`/employees/${editingId}`, form);
        setMessage('Cập nhật nhân viên thành công.');
      } else {
        await apiClient.post('/employees', form);
        setMessage('Tạo nhân viên thành công.');
      }

      resetForm();
      fetchEmployees();
    } catch (err: any) {
      setError(err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(' ') : 'Lưu nhân viên thất bại.');
    }
  };

  const handleEdit = (employee: Employee) => {
    setEditingId(employee.id);
    setForm({
      name: employee.name,
      email: employee.email,
      position: employee.position,
      department_id: employee.department_id,
      status: employee.status || 'active',
    });
    setMessage('');
    setError('');
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Bạn có chắc muốn xóa nhân viên này?')) {
      return;
    }

    try {
      await apiClient.delete(`/employees/${id}`);
      setMessage('Xóa nhân viên thành công.');
      fetchEmployees();
    } catch (err: any) {
      setError('Xóa nhân viên thất bại.');
    }
  };

  return (
    <div className="page-container">
      <div className="page-header">
        <h1>Nhân viên</h1>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>Danh sách nhân viên</h2>
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

        <table className="data-table">
          <thead>
            <tr>
              <th>Họ và tên</th>
              <th>Email</th>
              <th>Chức vụ</th>
              <th>Phòng ban</th>
              <th>Trạng thái</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody>
            {employees.map((employee) => (
              <tr key={employee.id}>
                <td>{employee.name}</td>
                <td>{employee.email}</td>
                <td>{employee.position}</td>
                <td>{employee.department?.name || '-'}</td>
                <td>{employee.status}</td>
                <td className="table-actions">
                  <Button variant="secondary" onClick={() => handleEdit(employee)}>
                    Sửa
                  </Button>
                  <Button variant="danger" onClick={() => handleDelete(employee.id)}>
                    Xóa
                  </Button>
                </td>
              </tr>
            ))}
            {employees.length === 0 && (
              <tr>
                <td colSpan={6}>Chưa có nhân viên nào.</td>
              </tr>
            )}
          </tbody>
        </table>

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
        <h2>{editingId ? 'Chỉnh sửa nhân viên' : 'Tạo nhân viên mới'}</h2>
        <Input
          label="Họ và tên"
          type="text"
          value={form.name}
          onChange={(e) => setForm({ ...form, name: e.target.value })}
          required
        />
        <Input
          label="Email"
          type="email"
          value={form.email}
          onChange={(e) => setForm({ ...form, email: e.target.value })}
          required
        />
        <Input
          label="Chức vụ"
          type="text"
          value={form.position}
          onChange={(e) => setForm({ ...form, position: e.target.value })}
          required
        />
        <label className="input-group">
          <span className="input-label">Phòng ban</span>
          <select
            className="input-field"
            value={form.department_id}
            onChange={(e) => setForm({ ...form, department_id: Number(e.target.value) })}
            required
          >
            <option value={0}>Chọn phòng ban</option>
            {departments.map((department) => (
              <option key={department.id} value={department.id}>
                {department.name}
              </option>
            ))}
          </select>
        </label>
        <label className="input-group">
          <span className="input-label">Trạng thái</span>
          <select
            className="input-field"
            value={form.status}
            onChange={(e) => setForm({ ...form, status: e.target.value })}
          >
            <option value="active">Hoạt động</option>
            <option value="inactive">Ngừng</option>
          </select>
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
