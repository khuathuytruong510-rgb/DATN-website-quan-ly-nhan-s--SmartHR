<template>
  <form @submit.prevent="handleSubmit">
    <slot></slot>
    <div class="form-actions">
      <button-comp type="submit" variant="primary" :disabled="loading">
        {{ submitLabel }}
      </button-comp>
      <button-comp
        v-if="showCancel"
        type="button"
        @click="$emit('cancel')"
      >
        {{ cancelLabel }}
      </button-comp>
    </div>
  </form>
</template>

<script>
import Button from './Button.vue';

export default {
  components: {
    ButtonComp: Button
  },
  props: {
    submitLabel: {
      type: String,
      default: 'Gửi'
    },
    cancelLabel: {
      type: String,
      default: 'Hủy'
    },
    showCancel: Boolean,
    loading: Boolean
  },
  methods: {
    handleSubmit() {
      this.$emit('submit');
    }
  },
  emits: ['submit', 'cancel']
}
</script>

<style scoped>
form {
  display: grid;
  gap: 20px;
}

.form-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}
</style>
