<script setup>
import { Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

defineProps({ notice: Object })

const typeLabel = (t) => ({
  'platform': '平台公告', 'game': '游戏公告', 'maintenance': '维护公告',
  'activity': '活动公告', 'merge': '合服公告',
}[t] || t)
</script>

<template>
  <Default>
    <div class="max-w-3xl mx-auto px-4 py-8">
      <Link href="/notices" class="text-orange-600 hover:underline text-sm mb-4 inline-block">← 返回公告列表</Link>

      <article class="bg-white rounded-xl shadow-sm border p-8">
        <div class="mb-6">
          <div class="flex items-center gap-2 mb-2">
            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ typeLabel(notice.type) }}</span>
            <span v-if="notice.is_top" class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded">置顶</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">{{ notice.title }}</h1>
          <div class="flex items-center gap-3 mt-2 text-sm text-gray-400">
            <span>{{ notice.published_at }}</span>
            <span v-if="notice.game">· {{ notice.game.name }}</span>
          </div>
        </div>

        <div v-if="notice.summary" class="bg-gray-50 border rounded-lg p-4 mb-6 text-gray-600 text-sm">
          {{ notice.summary }}
        </div>

        <div class="prose prose-gray max-w-none" v-html="notice.content || ''"></div>
      </article>
    </div>
  </Default>
</template>
