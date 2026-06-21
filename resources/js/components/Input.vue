<template>
  <div class="input-group">
    <label v-if="label" :for="id" class="input-label">{{ label }}</label>
    <input
      :id="id"
      v-bind="$attrs"
      class="input"
      :type="type"
      :placeholder="placeholder"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="input-error">{{ error }}</p>
  </div>
</template>

<script>
import { v4 as uuid } from 'uuid';

export default {
  props: {
    type: {
      type: String,
      default: 'text'
    },
    label: String,
    placeholder: String,
    error: String,
    modelValue: {
      type: [String, Number],
      default: ''
    }
  },
  data() {
    return {
      id: `input-${uuid()}`
    };
  },
  emits: ['update:modelValue']
}
</script>

<style scoped>
.input-group {
  display: grid;
  gap: 7px;
  margin-bottom: 16px;
}

.input-label {
  font-weight: 700;
  font-size: 14px;
  color: var(--text);
}

.input {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 11px 12px;
  font: inherit;
  background: #fff;
  color: var(--text);
  transition: border-color 0.2s ease;
}

.input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-error {
  color: var(--danger);
  font-size: 13px;
  margin: 0;
}
</style>
