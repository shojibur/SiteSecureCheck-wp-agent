<template>
  <pre class="text-xs bg-gray-50 p-3 rounded overflow-auto max-h-80">{{ pretty }}</pre>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ data: { type: [Object, Array], default: ()=>({}) } })
const pretty = computed(()=> JSON.stringify(props.data ?? {}, null, 2))
</script>
