<template>
  <AppLayout>
    <div v-if="site" class="space-y-6">
      <!-- Site Header Card -->
      <div class="bg-white rounded-xl shadow-soft border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-8">
          <div class="flex justify-between items-start">
            <div class="text-white">
              <h1 class="text-2xl font-bold mb-2">{{ site?.name || site?.domain || 'Unknown Site' }}</h1>
              <div class="flex items-center gap-4 text-primary-100 text-sm">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                  </svg>
                  {{ site?.domain }}
                </span>
                <span>{{ site?.region_mode }}</span>
                <span class="flex items-center gap-1">
                  <svg v-if="site?.auto_fix" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  Auto Fix: {{ site?.auto_fix ? 'On' : 'Off' }}
                </span>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="queueScan" class="px-4 py-2 bg-white hover:bg-gray-50 text-primary-600 font-medium rounded-lg shadow-sm transition-all duration-200 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Scan Now
              </button>
              <a :href="`/sites/${site?.id}/edit`" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-medium rounded-lg backdrop-blur-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
              </a>
            </div>
          </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Security Score:</span>
            <SiteScoreBadge :score="site?.last_score"/>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="bg-white rounded-xl shadow-soft border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200">
          <nav class="flex gap-0">
            <button @click="tab='scans'" :class="tab==='scans' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'" class="flex-1 px-6 py-4 text-sm font-medium transition-colors">
              <div class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Scans
              </div>
            </button>
            <button @click="tab='issues'" :class="tab==='issues' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'" class="flex-1 px-6 py-4 text-sm font-medium transition-colors">
              <div class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Issues
              </div>
            </button>
            <button @click="tab='actions'" :class="tab==='actions' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'" class="flex-1 px-6 py-4 text-sm font-medium transition-colors">
              <div class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Actions
              </div>
            </button>
          </nav>
        </div>
        <!-- Scans Tab -->
        <div v-if="tab==='scans'" class="p-6">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Scan ID</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Score</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Applied</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="s in scans" :key="s.id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-4 py-4">
                    <span class="font-mono text-sm text-gray-900">#{{ s.id }}</span>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <SiteScoreBadge :score="s.score"/>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <span :class="{
                      'bg-success-50 text-success-700': s.status === 'complete',
                      'bg-warning-50 text-warning-700': s.status === 'pending',
                      'bg-gray-100 text-gray-700': s.status === 'processing'
                    }" class="px-2.5 py-1 text-xs font-medium rounded-full capitalize">
                      {{ s.status }}
                    </span>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <span v-if="s.applied" class="inline-flex items-center gap-1 text-success-700">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      <span class="text-xs font-medium">Yes</span>
                    </span>
                    <span v-else class="text-xs font-medium text-gray-500">No</span>
                  </td>
                  <td class="px-4 py-4">
                    <div class="flex gap-2 justify-end">
                      <button @click="viewPlan(s)" class="px-3 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                        View Plan
                      </button>
                      <button v-if="s.status === 'done'" @click="applyFixes(s)" class="px-3 py-1.5 text-sm font-medium text-success-600 hover:bg-success-50 rounded-lg transition-colors">
                        Apply Fixes
                      </button>
                      <button @click="deleteScan(s)" class="px-3 py-1.5 text-sm font-medium text-danger-600 hover:bg-danger-50 rounded-lg transition-colors">
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Issues Tab -->
        <div v-else-if="tab==='issues'" class="p-6">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Severity</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Description</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(i, idx) in latestIssues" :key="idx" class="hover:bg-gray-50 transition-colors">
                  <td class="px-4 py-4">
                    <span class="font-mono text-sm text-gray-900">{{ i.fix_type || i.id || idx }}</span>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <span :class="{
                      'bg-danger-50 text-danger-700': i.severity === 'high' || i.severity === 'critical',
                      'bg-warning-50 text-warning-700': i.severity === 'medium',
                      'bg-gray-100 text-gray-700': i.severity === 'low'
                    }" class="px-2.5 py-1 text-xs font-medium rounded-full capitalize">
                      {{ i.severity || 'Unknown' }}
                    </span>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-700">
                    {{ i.message || i.why || i.reason || 'No description' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Actions Tab -->
        <div v-else class="p-6">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action Type</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Timestamp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="a in actions" :key="a.created_at" class="hover:bg-gray-50 transition-colors">
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                      <span class="text-sm font-medium text-gray-900 capitalize">{{ a.type }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-4 text-right text-sm text-gray-600">
                    {{ a.created_at }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Plan Dialog -->
      <dialog v-if="planDialog" open class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[80vh] overflow-hidden animate-fade-in">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Scan Plan Details</h3>
              <button @click="planDialog=false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <div class="p-6 overflow-y-auto max-h-[60vh]">
            <JsonViewer :data="currentPlan" />
          </div>
          <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
            <button @click="planDialog=false" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">
              Close
            </button>
          </div>
        </div>
      </dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SiteScoreBadge from '@/components/SiteScoreBadge.vue'
import JsonViewer from '@/components/JsonViewer.vue'

const props = defineProps({ siteData: Object, scans: Array, latestIssues: Array, actions: Array })
const site = props.siteData || {}
const scans = props.scans || []
const latestIssues = props.latestIssues || []
const actions = props.actions || []
const tab = ref('scans')
const planDialog = ref(false)
const currentPlan = ref({})

function queueScan(){
  if (!site?.id) return
  fetch(`/sites/${site.id}/scan`,{method:'POST'})
}

function viewPlan(s){
  currentPlan.value = s.plan || {}
  planDialog.value = true
}

function applyFixes(s){
  if (!site?.id) return
  fetch(`/sites/${site.id}/scans/${s.id}/apply`,{method:'POST'})
}

function deleteScan(s){
  console.log('deleteScan called', {site, scan: s})
  if (!site?.id) {
    console.error('No site ID')
    return
  }
  if (!confirm('Are you sure you want to delete this scan?')) {
    console.log('User cancelled')
    return
  }
  const url = `/sites/${site.id}/scans/${s.id}`
  console.log('Deleting scan at:', url)
  router.delete(url, {
    preserveScroll: true,
    onSuccess: () => {
      console.log('Delete successful')
      window.location.reload()
    },
    onError: (errors) => {
      console.error('Delete failed:', errors)
    }
  })
}
</script>
