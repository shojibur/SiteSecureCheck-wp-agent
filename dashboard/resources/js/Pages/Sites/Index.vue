<template>
  <AppLayout>
    <template #default>
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Sites</h1>
            <p class="mt-2 text-sm text-gray-600">Manage and monitor your website security</p>
          </div>
          <button @click="openCreate = true" class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Site
          </button>
        </div>
      </div>

      <!-- Sites Card -->
      <div class="bg-white rounded-xl shadow-soft border border-gray-200 overflow-hidden">
        <!-- Card Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">All Sites</h2>
            <span class="text-sm text-gray-500">{{ sites.data?.length || 0 }} total</span>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Domain</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Score</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Auto Fix</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="s in sites.data" :key="s.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <div class="font-medium text-gray-900">{{ s.name || '-' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-600 font-mono">{{ s.domain }}</div>
                </td>
                <td class="px-6 py-4 text-center">
                  <button @click="checkConnection(s)" :disabled="checkingId===s.id" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full transition-all" :class="{
                    'bg-success-50 text-success-700 hover:bg-success-100': s.connection_status === 'connected',
                    'bg-danger-50 text-danger-700 hover:bg-danger-100': s.connection_status === 'error',
                    'bg-gray-100 text-gray-600 hover:bg-gray-200': s.connection_status === 'unknown' || !s.connection_status,
                    'opacity-50 cursor-not-allowed': checkingId===s.id
                  }" :title="s.connection_error || 'Click to check connection'">
                    <svg v-if="checkingId===s.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else-if="s.connection_status === 'connected'" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="s.connection_status === 'error'" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ s.connection_status === 'connected' ? 'Connected' : s.connection_status === 'error' ? 'Error' : 'Unknown' }}
                  </button>
                </td>
                <td class="px-6 py-4 text-center">
                  <SiteScoreBadge :score="s.last_score" />
                </td>
                <td class="px-6 py-4 text-center">
                  <span v-if="s.auto_fix" class="inline-flex items-center gap-1 px-2.5 py-1 bg-success-50 text-success-700 text-xs font-medium rounded-full">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    On
                  </span>
                  <span v-else class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                    Off
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex gap-2 justify-end items-center">
                    <a :href="`/sites/${s.id}`" class="px-3 py-1.5 text-sm font-medium text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors">
                      View
                    </a>
                    <a :href="`/sites/${s.id}/edit`" class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                      Edit
                    </a>
                    <button @click="confirmDelete(s)" class="px-3 py-1.5 text-sm font-medium text-danger-600 hover:text-danger-700 hover:bg-danger-50 rounded-lg transition-colors">
                      Delete
                    </button>
                    <button :disabled="busyId===s.id" @click="queueScan(s)" :class="busyId===s.id ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-md'" class="px-4 py-1.5 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-all duration-200">
                      {{ busyId===s.id ? 'Scanning...' : 'Scan' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="!sites.data || sites.data.length === 0" class="px-6 py-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No sites</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by creating a new site.</p>
          <div class="mt-6">
            <button @click="openCreate = true" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              New Site
            </button>
          </div>
        </div>
      </div>

      <ConfirmDialog v-model="showConfirm" :title="'Delete Site'" :message="'Are you sure?'" @confirm="doDelete" />

      <UiDialog v-model="openCreate" title="Create New Site">
        <form id="create-form" @submit.prevent="submitCreate" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 sm:col-span-1">
              <UiLabel>Site Name</UiLabel>
              <UiInput v-model="form.name" placeholder="My Awesome Site" class="mt-1" />
            </div>
            <div class="col-span-2 sm:col-span-1">
              <UiLabel>Domain</UiLabel>
              <UiInput v-model="form.domain" placeholder="https://example.com" class="mt-1" />
            </div>
            <div class="col-span-2">
              <UiLabel>WordPress API Base URL</UiLabel>
              <UiInput v-model="form.wp_api_base" placeholder="https://example.com/wp-json" class="mt-1" />
            </div>
            <div class="col-span-2">
              <UiLabel>WordPress API Key</UiLabel>
              <UiInput v-model="form.wp_api_key" type="password" placeholder="Enter your API key" class="mt-1" />
            </div>
            <div class="col-span-2 sm:col-span-1">
              <UiLabel>Region</UiLabel>
              <UiSelect v-model="form.region_mode" class="mt-1">
                <option v-for="r in regions" :key="r" :value="r">{{ r }}</option>
              </UiSelect>
            </div>
            <div class="col-span-2 sm:col-span-1">
              <UiLabel>Auto Fix</UiLabel>
              <div class="mt-2 flex items-center gap-2">
                <UiSwitch v-model="form.auto_fix" />
                <span class="text-sm text-gray-600">{{ form.auto_fix ? 'Enabled' : 'Disabled' }}</span>
              </div>
            </div>
            <div class="col-span-2">
              <UiLabel>Teams Webhook URL (Optional)</UiLabel>
              <UiInput v-model="form.teams_webhook" placeholder="https://..." class="mt-1" />
            </div>
            <div class="col-span-2">
              <UiLabel>Notification Email (Optional)</UiLabel>
              <UiInput v-model="form.email" type="email" placeholder="admin@example.com" class="mt-1" />
            </div>
          </div>
        </form>
        <template #footer>
          <div class="flex gap-3">
            <button type="submit" form="create-form" class="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-sm transition-colors">
              Create Site
            </button>
            <button type="button" @click="openCreate=false" class="px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors">
              Cancel
            </button>
          </div>
        </template>
      </UiDialog>
    </template>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SiteScoreBadge from '@/components/SiteScoreBadge.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import UiButton from '@/components/ui/Button.vue'
import UiCard from '@/components/ui/Card.vue'
import UiDialog from '@/components/ui/Dialog.vue'
import UiInput from '@/components/ui/Input.vue'
import UiSelect from '@/components/ui/Select.vue'
import UiSwitch from '@/components/ui/Switch.vue'
import UiTable from '@/components/ui/Table.vue'
import UiLabel from '@/components/ui/Label.vue'

const props = defineProps({ sites: Object })
const regions = ['EU','MY','US','OTHER']
const openCreate = ref(false)
const showConfirm = ref(false)
const toDeleteId = ref(null)
const busyId = ref(null)
const checkingId = ref(null)
const form = reactive({ name:'', domain:'', wp_api_base:'', wp_api_key:'', region_mode:'OTHER', auto_fix:true, teams_webhook:'', email:'' })

function submitCreate(){ router.post('/sites', form, { onSuccess:()=>{ openCreate.value=false } }) }
function confirmDelete(s){ toDeleteId.value=s.id; showConfirm.value=true }
function doDelete(){ router.delete(`/sites/${toDeleteId.value}`) }
function queueScan(s){
  busyId.value=s.id;
  router.post(`/sites/${s.id}/scan`, {}, {
    onFinish: () => { busyId.value=null },
    preserveScroll: true
  });
}
function checkConnection(s){
  checkingId.value=s.id;
  router.post(`/sites/${s.id}/check-connection`, {}, {
    onFinish: () => { checkingId.value=null },
    preserveScroll: true
  });
}
</script>
