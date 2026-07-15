<template>
  <div class="attendance">
    <div class="attendance-header">
      <h2>Chấm công</h2>
      <button-comp variant="primary" @click="showCreateModal = true">
        Thêm chấm công
      </button-comp>
    </div>

    <card>
      <table v-if="attendances.length">
        <thead>
          <tr>
            <th>Ngày</th>
            <th>Giờ vào</th>
            <th>Giờ ra</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="attendance in attendances" :key="attendance.id">
            <td>{{ formatDate(attendance.date) }}</td>
            <td>{{ attendance.check_in }}</td>
            <td>{{ attendance.check_out || '-' }}</td>
            <td>
              <badge :variant="getStatusVariant(attendance.status)">
                {{ attendance.status }}
              </badge>
            </td>
            <td>
              <button-comp size="sm" variant="primary" @click="editAttendance(attendance)">
                Sửa
              </button-comp>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty">
        Không có dữ liệu chấm công
      </div>
    </card>

    <modal :show="showCreateModal" title="Thêm chấm công" @close="showCreateModal = false">
      <form-comp @submit="submitAttendance" show-cancel @cancel="showCreateModal = false">
        <input-comp
          v-model="form.date"
          type="date"
          label="Ngày"
          required
        />
        <input-comp
          v-model="form.check_in"
          type="time"
          label="Giờ vào"
          required
        />
        <input-comp
          v-model="form.check_out"
          type="time"
          label="Giờ ra"
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
    initialAttendances: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      attendances: [],
      showCreateModal: false,
      form: {
        date: '',
        check_in: '',
        check_out: '',
        status: 'present',
        notes: ''
      },
      editingId: null,
      submitting: false,
      loading: false,
      errors: null,
      csrfToken: document.querySelector('meta[name="csrf-token"]').content
    };
  },
  mounted() {
    this.attendances = this.initialAttendances;
    this.loadAttendances();
  },
  methods: {
    async loadAttendances() {
      this.loading = true;
      try {
        const response = await fetch('/me/attendance', {
          headers: {
            Accept: 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error('Không thể tải dữ liệu chấm công');
        }

        const result = await response.json();
        this.attendances = result.attendances || result.data || [];
      } catch (error) {
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('vi-VN');
    },
    getStatusVariant(status) {
      return status === 'present' ? 'success' : status === 'late' ? 'warning' : status === 'leave' ? 'info' : 'danger';
    },
    editAttendance(attendance) {
      this.editingId = attendance.id;
      this.form = {
        date: attendance.date,
        check_in: attendance.check_in,
        check_out: attendance.check_out,
        status: attendance.status || 'present',
        notes: attendance.notes || ''
      };
      this.errors = null;
      this.showCreateModal = true;
    },
    async submitAttendance() {
      this.submitting = true;
      this.errors = null;

      const url = this.editingId ? `/me/attendance/${this.editingId}` : '/me/attendance';
      const method = this.editingId ? 'PUT' : 'POST';

      try {
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
            Accept: 'application/json'
          },
          body: JSON.stringify(this.form)
        });

        if (response.ok) {
          await this.loadAttendances();
          this.closeForm();
          return;
        }

        if (response.status === 422) {
          const result = await response.json();
          this.errors = result.errors || { general: 'Dữ liệu không hợp lệ' };
          return;
        }

        throw new Error('Lưu chấm công thất bại');
      } catch (error) {
        console.error(error);
        this.errors = { general: error.message };
      } finally {
        this.submitting = false;
      }
    },
    closeForm() {
      this.showCreateModal = false;
      this.editingId = null;
      this.errors = null;
      this.form = {
        date: '',
        check_in: '',
        check_out: '',
        status: 'present',
        notes: ''
      };
    }
  }
};
</script>

<style scoped>
.attendance {
  display: grid;
  gap: 20px;
}

.attendance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.attendance-header h2 {
  margin: 0;
  font-size: 24px;
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
}

.empty {
  padding: 40px 20px;
  text-align: center;
  color: var(--muted);
}
</style>
