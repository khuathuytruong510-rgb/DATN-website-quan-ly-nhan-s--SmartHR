<template>
  <span class="badge" :class="badgeClass">
    <slot></slot>
  </span>
</template>

<script>
export default {
  props: {
    variant: {
      type: String,
      default: 'default',
      validator: (v) => ['default', 'success', 'danger', 'warning', 'info'].includes(v)
    }
  },
  computed: {
    badgeClass() {
      return `badge-${this.variant}`;
    }
  }
}
</script>

<style scoped>
.badge {
  display: inline-flex;
  border-radius: 999px;
  padding: 5px 9px;
  font-size: 12px;
  font-weight: 700;
  background: #e0f2fe;
  color: #0369a1;
}

.badge-default {
  background: #e0f2fe;
  color: #0369a1;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-info {
  background: #e0f2fe;
  color: #0369a1;
}
</style>
