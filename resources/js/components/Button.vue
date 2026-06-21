<template>
  <button
    class="btn"
    :class="[variantClass, sizeClass, { 'btn-block': block }]"
    :type="type"
    :disabled="disabled"
    @click="$emit('click')"
  >
    <slot></slot>
  </button>
</template>

<script>
export default {
  props: {
    variant: {
      type: String,
      default: 'default',
      validator: (v) => ['default', 'primary', 'danger', 'success', 'warning'].includes(v)
    },
    size: {
      type: String,
      default: 'md',
      validator: (v) => ['sm', 'md', 'lg'].includes(v)
    },
    type: {
      type: String,
      default: 'button'
    },
    disabled: Boolean,
    block: Boolean
  },
  computed: {
    variantClass() {
      return `btn-${this.variant}`;
    },
    sizeClass() {
      return `btn-${this.size}`;
    }
  },
  emits: ['click']
}
</script>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 8px;
  padding: 10px 14px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  background: #f8fafc;
  color: var(--text);
  font-size: 14px;
  transition: all 0.2s ease;
}

.btn:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-block {
  width: 100%;
}

.btn-sm {
  padding: 8px 12px;
  font-size: 12px;
}

.btn-lg {
  padding: 12px 16px;
  font-size: 16px;
}

.btn-default {
  background: #f8fafc;
  color: var(--text);
}

.btn-primary {
  background: var(--primary);
  color: #fff;
}

.btn-danger {
  background: #fee2e2;
  color: var(--danger);
}

.btn-success {
  background: #dcfce7;
  color: #166534;
}

.btn-warning {
  background: #fef3c7;
  color: #92400e;
}
</style>
