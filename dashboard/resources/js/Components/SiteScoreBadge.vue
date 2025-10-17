<template>
  <span :class="klass">{{ score ?? '-' }}</span>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ score: Number })
const score = props.score
const klass = computed(()=>{
  if (score == null) return 'px-2 py-1 rounded bg-gray-200'
  if (score >= 90) return 'px-2 py-1 rounded bg-green-200'
  if (score >= 70) return 'px-2 py-1 rounded bg-yellow-200'
  return 'px-2 py-1 rounded bg-red-200'
})
</script>
