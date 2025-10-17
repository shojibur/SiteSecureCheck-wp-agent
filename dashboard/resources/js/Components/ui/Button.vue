<template>
  <button :class="classes" v-bind="$attrs"><slot /></button>
</template>
<script setup>
const props = defineProps({ variant: { type: String, default: 'default' }, size: { type: String, default: 'md' } })
const base = 'inline-flex items-center justify-center rounded-md font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none'
const variants = {
  default: 'bg-black text-white hover:bg-gray-900',
  outline: 'border border-gray-300 hover:bg-gray-50',
  ghost: 'hover:bg-gray-100',
  danger: 'bg-red-600 text-white hover:bg-red-700'
}
const sizes = { sm: 'h-8 px-3 text-sm', md: 'h-9 px-4', lg: 'h-10 px-6' }
const classes = `${base} ${variants[props.variant]||variants.default} ${sizes[props.size]||sizes.md}`
</script>

