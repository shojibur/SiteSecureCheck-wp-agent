<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-xl font-semibold mb-4">Create Site</h1>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-2 gap-3">
          <input v-model="f.name" placeholder="Name" class="border p-2" />
          <input v-model="f.domain" placeholder="Domain URL" class="border p-2" />
          <input v-model="f.wp_api_base" placeholder="WP API Base URL" class="border p-2 col-span-2" />
          <input v-model="f.wp_api_key" placeholder="WP API Key" class="border p-2 col-span-2" />
          <select v-model="f.region_mode" class="border p-2">
            <option v-for="r in regions" :key="r" :value="r">{{ r }}</option>
          </select>
          <label class="flex items-center gap-2"><input type="checkbox" v-model="f.auto_fix"/> Auto Fix</label>
          <input v-model="f.teams_webhook" placeholder="Teams Webhook" class="border p-2 col-span-2" />
          <input v-model="f.email" placeholder="Email" class="border p-2 col-span-2" />
        </div>
        <div class="mt-3"><button class="px-3 py-2 bg-black text-white rounded">Save</button></div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
const regions = ['EU','MY','US','OTHER']
const f = reactive({ name:'', domain:'', wp_api_base:'', wp_api_key:'', region_mode:'OTHER', auto_fix:true, teams_webhook:'', email:'' })
function submit(){ router.post('/sites', f) }
</script>
