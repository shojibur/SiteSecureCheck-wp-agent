<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto">
      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
          <a href="/sites" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </a>
          <h1 class="text-3xl font-bold text-gray-900">Edit Site</h1>
        </div>
        <p class="text-sm text-gray-600 ml-8">Update your site configuration and security settings</p>
      </div>

      <!-- Edit Form Card -->
      <div class="bg-white rounded-xl shadow-soft border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h2 class="text-lg font-semibold text-gray-900">Site Configuration</h2>
        </div>

        <form @submit.prevent="submit" class="p-6">
          <div class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h3 class="text-sm font-semibold text-gray-900 mb-4">Basic Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                  <input
                    v-model="f.name"
                    placeholder="My Awesome Site"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                  />
                </div>
                <div class="col-span-2 sm:col-span-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
                  <input
                    v-model="f.domain"
                    placeholder="https://example.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                  />
                </div>
              </div>
            </div>

            <!-- WordPress Settings -->
            <div>
              <h3 class="text-sm font-semibold text-gray-900 mb-4">WordPress API Settings</h3>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">WordPress API Base URL</label>
                  <input
                    v-model="f.wp_api_base"
                    placeholder="https://example.com/wp-json"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">WordPress API Key</label>
                  <input
                    v-model="f.wp_api_key"
                    type="password"
                    placeholder="Enter your API key"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                  />
                  <p class="mt-1 text-xs text-gray-500">Your API key is encrypted and stored securely</p>
                </div>
              </div>
            </div>

            <!-- Configuration -->
            <div>
              <h3 class="text-sm font-semibold text-gray-900 mb-4">Configuration</h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                  <select
                    v-model="f.region_mode"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                  >
                    <option v-for="r in regions" :key="r" :value="r">{{ r }}</option>
                  </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Auto Fix</label>
                  <div class="flex items-center h-10">
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" v-model="f.auto_fix" class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                      <span class="ml-3 text-sm font-medium text-gray-700">{{ f.auto_fix ? 'Enabled' : 'Disabled' }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notifications -->
            <div>
              <h3 class="text-sm font-semibold text-gray-900 mb-4">Notifications (Optional)</h3>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Teams Webhook URL</label>
                  <input
                    v-model="f.teams_webhook"
                    placeholder="https://..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Notification Email</label>
                  <input
                    v-model="f.email"
                    type="email"
                    placeholder="admin@example.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="mt-8 pt-6 border-t border-gray-200 flex items-center justify-between">
            <a href="/sites" class="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium transition-colors">
              Cancel
            </a>
            <button
              type="submit"
              class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-sm hover:shadow-md transition-all duration-200 flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
const props = defineProps({ site: Object })
const regions = ['EU','MY','US','OTHER']
const f = reactive({ ...props.site })
function submit(){ router.put(`/sites/${f.id}`, f) }
</script>
