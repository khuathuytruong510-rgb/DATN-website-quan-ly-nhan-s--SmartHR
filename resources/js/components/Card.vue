<template>
  <div class="card" :class="[variantClass]">
    <div v-if="$slots.header" class="card-header">
      <slot name="header"></slot>
    </div>
    <div class="card-body">
      <slot></slot>
    </div>
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    variant: {
      type: String,
      default: 'default',
      validator: (v) => ['default', 'primary', 'danger', 'success'].includes(v)
    }
  },
  computed: {
    variantClass() {
      return this.variant !== 'default' ? `card-${this.variant}` : '';
    }
  }
}
</script>

<style scoped>
.card {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(15, 23, 42, .06);
  overflow: hidden;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--line);
  font-weight: 700;
  background: #f8fafc;
}

.card-body {
  padding: 24px;
}

.card-footer {
  padding: 20px 24px;
  border-top: 1px solid var(--line);
  background: #f8fafc;
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.card-primary {
  border-color: var(--primary);
}

.card-danger {
  border-color: var(--danger);
}

.card-success {
  border-color: #22c55e;
}
</style>
