import { FormEvent, useEffect, useState } from 'react';
import apiClient from '../services/api';
import Button from '../components/Button';
import Input from '../components/Input';

interface Employee {
  id: number;
  name: string;
}

interface Contract {
  id: number;
  employee_id: number;
  title: string;
  salary: number;
  start_date: string;
  end_date: string;
  status: string;
  employee?: Employee;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const initialFormState = {
  employee_id: 0,
  title: '',
  salary: 0,
  start_date: '',
  end_date: '',
  status: 'active',
};

export default function Contracts() {
  const [contracts, setContracts] = useState<Contract[]>([]);
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState(initialFormState);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    fetchContracts();
    fetchEmployees();
  }, [page, search]);

  const fetchContracts = async () => {
    try {
      const response = await apiClient.get<PaginatedResponse<Contract>>('/contracts', {
        params: {
          q: search || undefined,
          page,
          per_page: 5,
        },
      });

      setContracts(response.data.data);
      setPage(response.data.current_page);
      setLastPage(response.data.last_page);
    } catch (err: any) {
      setError('Không tải được danh sách hợp đồng.');
    }
  };

  const fetchEmployees = async () => {
    try {
      const response = await apiClient.get<PaginatedResponse<Employee>>('/employees', {
        params: { per_page: 100 },
      });
      setEmployees(response.data.data);
    } catch (err: any) {
      setError('Không tải được danh sách nhân viên.');
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
        await apiClient.put(`/contracts/${editingId}`, form);
        setMessage('Cập nhật hợp đồng thành công.');
      } else {
        await apiClient.post('/contracts', form);
        setMessage('Tạo hợp đồng thành công.');
      }

      resetForm();
      fetchContracts();
    } catch (err: any) {
      setError(err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(' ') : 'Lưu hợp đồng thất bại.');
    }
  };

  const handleEdit = (contract: Contract) => {
    setEditingId(contract.id);
    setForm({
      employee_id: contract.employee_id,
      title: contract.title,
      salary: contract.salary,
      start_date: contract.start_date,
      end_date: contract.end_date,
      status: contract.status || 'active',
    });
    setMessage('');
    setError('');
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Bạn có chắc muốn xóa hợp đồng này?')) {
      return;
    }

    try {
      await apiClient.delete(`/contracts/${id}`);
      setMessage('Xóa hợp đồng thành công.');
      fetchContracts();
    } catch (err: any) {
      setError('Xóa hợp đồng thất bại.');
    }
  };

  return (
    <div className="page-container">
      <div className="page-header">
        <h1>Hợp đồng</h1>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>Danh sách hợp đồng</h2>
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
              <th>Nhân viên</th>
              <th>Tiêu đề</th>
              <th>Lương</th>
              <th>Bắt đầu</th>
              <th>Kết thúc</th>
              <th>Trạng thái</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody>
            {contracts.map((contract) => (
              <tr key={contract.id}>
                <td>{contract.employee?.name || '-'}</td>
                <td>{contract.title}</td>
                <td>{contract.salary}</td>
                <td>{contract.start_date}</td>
                <td>{contract.end_date || '-'}</td>
                <td>{contract.status}</td>
                <td className="table-actions">
                  <Button variant="secondary" onClick={() => handleEdit(contract)}>
                    Sửa
                  </Button>
                  <Button variant="danger" onClick={() => handleDelete(contract.id)}>
                    Xóa
                  </Button>
                </td>
              </tr>
            ))}
            {contracts.length === 0 && (
              <tr>
                <td colSpan={7}>Chưa có hợp đồng nào.</td>
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
        <h2>{editingId ? 'Chỉnh sửa hợp đồng' : 'Tạo hợp đồng mới'}</h2>
        <label className="input-group">
          <span className="input-label">Nhân viên</span>
          <select
            className="input-field"
            value={form.employee_id}
            onChange={(e) => setForm({ ...form, employee_id: Number(e.target.value) })}
            required
          >
            <option value={0}>Chọn nhân viên</option>
            {employees.map((employee) => (
              <option key={employee.id} value={employee.id}>
                {employee.name}
              </option>
            ))}
          </select>
        </label>
        <Input
          label="Tiêu đề hợp đồng"
          type="text"
          value={form.title}
          onChange={(e) => setForm({ ...form, title: e.target.value })}
          required
        />
        <Input
          label="Mức lương"
          type="number"
          value={form.salary}
          onChange={(e) => setForm({ ...form, salary: Number(e.target.value) })}
          required
        />
        <Input
          label="Ngày bắt đầu"
          type="date"
          value={form.start_date}
          onChange={(e) => setForm({ ...form, start_date: e.target.value })}
          required
        />
        <Input
          label="Ngày kết thúc"
          type="date"
          value={form.end_date}
          onChange={(e) => setForm({ ...form, end_date: e.target.value })}
        />
        <label className="input-group">
          <span className="input-label">Trạng thái</span>
          <select
            className="input-field"
            value={form.status}
            onChange={(e) => setForm({ ...form, status: e.target.value })}
          >
            <option value="active">Hoạt động</option>
            <option value="pending">Chờ duyệt</option>
            <option value="expired">Hết hạn</option>
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
