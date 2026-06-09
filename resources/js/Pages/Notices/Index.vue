<script setup>
import { Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import Pagination from '@/Components/Pagination.vue'

defineProps({ notices: Object, types: Object })

const typeColor = (t) => ({
  'platform': 'bg-gray-100 text-gray-700',
  'game': 'bg-blue-100 text-blue-700',
  'maintenance': 'bg-red-100 text-red-700',
  'activity': 'bg-green-100 text-green-700',
  'merge': 'bg-purple-100 text-purple-700',
}[t] || 'bg-gray-100 text-gray-700')

const typeLabel = (t) => ({
  'platform': '平台公告', 'game': '游戏公告', 'maintenance': '维护公告',
  'activity': '活动公告', 'merge': '合服公告',
}[t] || t)
</script>

<template>
  <Default>
    <div class="max-w-4xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-6">公告列表</h1>

      <div class="space-y-3">
        <div v-for="notice in notices.data" :key="notice.id"
          class="bg-white rounded-lg shadow-sm border hover:shadow-md transition">
          <Link :href="`/notices/${notice.id}`" class="block p-5">
            <div class="flex items-start gap-3">
              <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium shrink-0 mt-0.5', typeColor(notice.type)]">
                {{ typeLabel(notice.type) }}
              </span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <h2 class="text-lg font-semibold text-gray-900 truncate">{{ notice.title }}</h2>
                  <span v-if="notice.is_top" class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded shrink-0">置顶</span>
                </div>
                <p v-if="notice.summary" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ notice.summary }}</p>
                <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                  <span>{{ notice.published_at }}</span>
                  <span v-if="notice.game">· {{ notice.game.name }}</span>
                </div>
              </div>
            </div>
          </Link>
        </div>
        <div v-if="!notices.data?.length" class="text-center py-12 text-gray-400">暂无公告</div>
      </div>

      <div class="mt-6"><Pagination :links="notices.links" /></div>
    </div>
  </Default>
</template>
