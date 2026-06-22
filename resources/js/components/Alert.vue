<template>
  <div v-if="show" class="alert" :class="type">
    <slot></slot>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
  props: {
    message: String,
    type: {
      type: String,
      default: 'success'
    },
    duration: {
      type: Number,
      default: 5000
    }
  },
  setup(props) {
    const show = ref(true);
    
    onMounted(() => {
      if (props.duration > 0) {
        setTimeout(() => {
          show.value = false;
        }, props.duration);
      }
    });
    
    return { show };
  }
}
</script>

<style scoped>
.alert {
  border-radius: 8px;
  padding: 13px 14px;
  margin-bottom: 16px;
  background: #dcfce7;
  color: #166534;
  animation: slideIn 0.3s ease-out;
}

.alert.error {
  background: #fee2e2;
  color: #991b1b;
}

.alert.warning {
  background: #fef3c7;
  color: #92400e;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
