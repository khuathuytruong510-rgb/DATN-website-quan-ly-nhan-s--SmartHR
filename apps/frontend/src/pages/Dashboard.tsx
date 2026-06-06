import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import apiClient from '../services/api';

interface Employee {
  id: number;
  name: string;
  position: string;
  department?: { name: string };
}

interface Contract {
  id: number;
  title: string;
  employee?: { name: string };
  status: string;
}

export default function Dashboard() {
  const [departmentsCount, setDepartmentsCount] = useState(0);
  const [employeesCount, setEmployeesCount] = useState(0);
  const [contractsCount, setContractsCount] = useState(0);
  const [latestEmployees, setLatestEmployees] = useState<Employee[]>([]);
  const [latestContracts, setLatestContracts] = useState<Contract[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    async function fetchDashboard() {
      try {
        const [departmentsRes, employeesRes, contractsRes] = await Promise.all([
          apiClient.get('/departments', { params: { per_page: 1 } }),
          apiClient.get('/employees', { params: { per_page: 5 } }),
          apiClient.get('/contracts', { params: { per_page: 5 } }),
        ]);

        setDepartmentsCount(departmentsRes.data.total ?? 0);
        setEmployeesCount(employeesRes.data.total ?? 0);
        setContractsCount(contractsRes.data.total ?? 0);
        setLatestEmployees(employeesRes.data.data ?? []);
        setLatestContracts(contractsRes.data.data ?? []);
      } catch (err: any) {
        setError('Không tải được dữ liệu dashboard.');
      }
    }

    fetchDashboard();
  }, []);

  return (
    <div className="page-container">
      <div className="page-header">
        <h1>Dashboard</h1>
        <p>Chào mừng đến SmartHR, hệ thống quản lý nhân sự.</p>
      </div>

      <div className="dashboard-grid">
        <article className="dashboard-card">
          <h3>Phòng ban</h3>
          <strong>{departmentsCount}</strong>
          <p>Tổng số phòng ban đang quản lý.</p>
          <Link to="/departments" className="dashboard-link">
            Xem phòng ban
          </Link>
        </article>

        <article className="dashboard-card">
          <h3>Nhân viên</h3>
          <strong>{employeesCount}</strong>
          <p>Tổng số nhân viên đã được nhập.</p>
          <Link to="/employees" className="dashboard-link">
            Xem nhân viên
          </Link>
        </article>

        <article className="dashboard-card">
          <h3>Hợp đồng</h3>
          <strong>{contractsCount}</strong>
          <p>Tổng số hợp đồng đang quản lý.</p>
          <Link to="/contracts" className="dashboard-link">
            Xem hợp đồng
          </Link>
        </article>
      </div>

      {error && <div className="form-error">{error}</div>}

      <div className="dashboard-grid dashboard-summary">
        <section className="dashboard-card dashboard-summary-card">
          <h2>Nhân viên mới nhất</h2>
          <ul>
            {latestEmployees.length > 0 ? (
              latestEmployees.map((employee) => (
                <li key={employee.id}>
                  <strong>{employee.name}</strong>
                  <span>{employee.position}</span>
                  <span>{employee.department?.name || 'Không rõ phòng ban'}</span>
                </li>
              ))
            ) : (
              <li>Không có nhân viên mới.</li>
            )}
          </ul>
        </section>

        <section className="dashboard-card dashboard-summary-card">
          <h2>Hợp đồng gần nhất</h2>
          <ul>
            {latestContracts.length > 0 ? (
              latestContracts.map((contract) => (
                <li key={contract.id}>
                  <strong>{contract.title}</strong>
                  <span>{contract.employee?.name || 'Không rõ nhân viên'}</span>
                  <span>{contract.status}</span>
                </li>
              ))
            ) : (
              <li>Không có hợp đồng mới.</li>
            )}
          </ul>
        </section>
      </div>
    </div>
  );
}
