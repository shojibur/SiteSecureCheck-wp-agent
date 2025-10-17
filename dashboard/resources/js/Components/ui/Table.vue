<template>
  <div class="overflow-hidden border rounded-lg">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr><slot name="head" /></tr>
      </thead>
      <tbody class="divide-y"><slot /></tbody>
    </table>
  </div>
</template>

