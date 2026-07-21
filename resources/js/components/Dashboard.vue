<template>
  <div class="dashboard">
    <div class="page-head">
      <div>
        <h1>Dashboard</h1>
        <p class="muted">Chào mừng đến SmartHR, hệ thống quản lý nhân sự.</p>
      </div>
    </div>

    <div class="stats-grid">
      <card>
        <template #header>Phòng ban</template>
        <div class="stat-value">{{ stats.departments }}</div>
        <p class="stat-desc">Tổng số phòng ban đang quản lý.</p>
        <button-comp variant="primary" size="sm" @click="navigate('departments')">
          Xem phòng ban
        </button-comp>
      </card>

      <card>
        <template #header>Nhân viên</template>
        <div class="stat-value">{{ stats.employees }}</div>
        <p class="stat-desc">Tổng số nhân viên đã được nhập.</p>
        <button-comp variant="primary" size="sm" @click="navigate('employees')">
          Xem nhân viên
        </button-comp>
      </card>

      <card>
        <template #header>Hợp đồng</template>
        <div class="stat-value">{{ stats.contracts }}</div>
        <p class="stat-desc">Tổng số hợp đồng đang quản lý.</p>
        <button-comp variant="primary" size="sm" @click="navigate('contracts')">
          Xem hợp đồng
        </button-comp>
      </card>
    </div>

    <div class="content-grid">
      <card>
        <template #header>Nhân viên mới nhất</template>
        <div v-for="emp in stats.latestEmployees" :key="emp.id" class="list-item">
          <strong>{{ emp.name }}</strong>
          <p>{{ emp.position }} - {{ emp.department?.name || 'Chưa có phòng ban' }}</p>
        </div>
      </card>

      <card>
        <template #header>Hợp đồng gần nhất</template>
        <div v-for="contract in stats.latestContracts" :key="contract.id" class="list-item">
          <strong>{{ contract.title }}</strong>
          <p>{{ contract.employee?.name }} - {{ formatCurrency(contract.salary) }}</p>
        </div>
      </card>

      <card>
        <template #header>Hợp đồng sắp hết hạn</template>
        <div v-for="contract in stats.expiringContracts" :key="contract.id" class="list-item">
          <strong>{{ contract.employee?.name }}</strong>
          <p>{{ contract.employee?.department?.name }} - {{ contract.employee?.position }} - Còn {{ daysRemaining(contract.end_date) }} ngày</p>
        </div>
      </card>
    </div>
  </div>
</template>

<script>
import Card from './Card.vue';
import Button from './Button.vue';

export default {
  components: {
    Card,
    ButtonComp: Button
  },
  props: {
    stats: {
      type: Object,
      required: true
    }
  },
  methods: {
    navigate(route) {
      window.location.href = `/${route}`;
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
      }).format(value || 0);
    },
    daysRemaining(value) {
      if (!value) return '—';
      const end = new Date(value);
      const today = new Date();
      const diff = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
      return Math.max(diff, 0);
    }
  }
}
</script>

<style scoped>
.dashboard {
  display: grid;
  gap: 24px;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 22px;
}

.page-head h1 {
  margin: 0 0 8px;
  font-size: 32px;
}

.page-head p {
  margin: 0;
  color: var(--muted);
}

.stats-grid {
  display: grid;
  gap: 20px;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.stat-value {
  font-size: 40px;
  font-weight: 800;
  margin: 18px 0 10px;
  color: var(--primary);
}

.stat-desc {
  color: var(--muted);
  margin: 0 0 12px;
  font-size: 14px;
}

.content-grid {
  display: grid;
  gap: 20px;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  margin-top: 22px;
}

.list-item {
  padding: 12px 0;
  border-bottom: 1px solid var(--line);
}

.list-item:last-child {
  border-bottom: none;
}

.list-item strong {
  display: block;
  margin-bottom: 4px;
}

.list-item p {
  margin: 0;
  color: var(--muted);
  font-size: 13px;
}
</style>
