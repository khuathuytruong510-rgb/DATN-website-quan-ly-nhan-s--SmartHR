<template>
  <div class="employee-management">
    <div class="section-header">
      <h1>Nhân viên</h1>
      <p>Quản lý thông tin nhân viên và vị trí.</p>
      <button-comp variant="primary" @click="showForm = true">
        Tạo nhân viên
      </button-comp>
    </div>

    <card>
      <table v-if="employees.length">
        <thead>
          <tr>
            <th>Tên</th>
            <th>Email</th>
            <th>Chức vụ</th>
            <th>Phòng ban</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in employees" :key="emp.id">
            <td><strong>{{ emp.name }}</strong></td>
            <td>{{ emp.email }}</td>
            <td>{{ emp.position }}</td>
            <td>{{ emp.department?.name }}</td>
            <td>
              <badge variant="success">{{ emp.status || 'Active' }}</badge>
            </td>
            <td class="actions">
              <button-comp size="sm" variant="primary" @click="viewEmployee(emp)">
                Xem
              </button-comp>
              <button-comp size="sm" @click="editEmployee(emp)">
                Sửa
              </button-comp>
              <button-comp size="sm" variant="danger" @click="deleteEmployee(emp.id)">
                Xóa
              </button-comp>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty">
        Không có nhân viên nào
      </div>
    </card>

    <modal :show="showForm" :title="editingId ? 'Sửa nhân viên' : 'Tạo nhân viên'" @close="closeForm">
      <form-comp @submit="submitForm" show-cancel @cancel="closeForm" :loading="submitting">
        <input-comp
          v-model="form.name"
          label="Tên"
          placeholder="Nhập tên nhân viên"
          required
        />
        <input-comp
          v-model="form.email"
          type="email"
          label="Email"
          placeholder="Nhập email"
          required
        />
        <input-comp
          v-model="form.position"
          label="Chức vụ"
          placeholder="Nhập chức vụ"
          required
        />
        <input-comp
          v-model="form.department_id"
          type="number"
          label="Phòng ban"
          placeholder="Chọn phòng ban"
          required
        />
      </form-comp>
    </modal>
  </div>
</template>

<script>
import Card from './Card.vue';
import Button from './Button.vue';
import Modal from './Modal.vue';
import Form from './Form.vue';
import Input from './Input.vue';
import Badge from './Badge.vue';

export default {
  components: {
    Card,
    ButtonComp: Button,
    Modal,
    FormComp: Form,
    InputComp: Input,
    Badge
  },
  props: {
    initialEmployees: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      employees: [],
      showForm: false,
      editingId: null,
      submitting: false,
      form: {
        name: '',
        email: '',
        position: '',
        department_id: ''
      }
    };
  },
  mounted() {
    this.employees = this.initialEmployees;
  },
  methods: {
    editEmployee(emp) {
      this.editingId = emp.id;
      this.form = {
        name: emp.name,
        email: emp.email,
        position: emp.position,
        department_id: emp.department_id
      };
      this.showForm = true;
    },
    viewEmployee(emp) {
      window.location.href = `/employees/${emp.id}`;
    },
    async deleteEmployee(id) {
      if (confirm('Bạn chắc chắn muốn xóa?')) {
        this.$emit('delete', id);
      }
    },
    async submitForm() {
      this.submitting = true;
      try {
        this.$emit('submit', {
          id: this.editingId,
          ...this.form
        });
        this.closeForm();
      } finally {
        this.submitting = false;
      }
    },
    closeForm() {
      this.showForm = false;
      this.editingId = null;
      this.form = {
        name: '',
        email: '',
        position: '',
        department_id: ''
      };
    }
  }
}
</script>

<style scoped>
.employee-management {
  display: grid;
  gap: 24px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.section-header h1 {
  margin: 0;
  font-size: 32px;
}

.section-header p {
  margin: 8px 0 0;
  color: var(--muted);
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #f8fafc;
}

th {
  padding: 14px 10px;
  text-align: left;
  font-weight: 700;
  font-size: 13px;
  text-transform: uppercase;
  color: var(--muted);
  border-bottom: 1px solid var(--line);
}

td {
  padding: 14px 10px;
  border-bottom: 1px solid var(--line);
  vertical-align: top;
}

.actions {
  display: flex;
  gap: 4px;
}

.empty {
  padding: 40px 20px;
  text-align: center;
  color: var(--muted);
}
</style>
