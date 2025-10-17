<template>
  <div class="rounded-lg border bg-white shadow-sm">
    <div v-if="$slots.header" class="p-4 border-b"><slot name="header"/></div>
    <div class="p-4"><slot /></div>
    <div v-if="$slots.footer" class="p-3 border-t bg-gray-50"><slot name="footer"/></div>
  </div>
</template>

