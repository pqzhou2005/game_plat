<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  servers: Object,
  games: Array,
})

const form = useForm({
  game_id: '',
})

const filterServer = () => {
  form.get('/servers', { preserveState: true })
}

const formatDate = (date) => new Date(date).toLocaleDateString('zh-CN')
</script>

<template>
  <Default>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-6">开服列表</h1>

      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex gap-4 items-center">
          <select v-model="form.game_id" @change="filterServer"
            class="border border-gray-300 rounded px-3 py-2 text-sm">
            <option value="">全部游戏</option>
            <option v-for="g in games" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="border-b text-left text-sm text-gray-500 bg-gray-50">
              <th class="py-3 px-4">游戏</th>
              <th class="py-3 px-4">区服</th>
              <th class="py-3 px-4">开服时间</th>
              <th class="py-3 px-4">状态</th>
              <th class="py-3 px-4">操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="server in servers.data" :key="server.id" class="border-b hover:bg-gray-50">
              <td class="py-3 px-4 font-medium">{{ server.game?.name }}</td>
              <td class="py-3 px-4">{{ server.name }}</td>
              <td class="py-3 px-4 text-sm text-gray-600">{{ formatDate(server.open_time) }}</td>
              <td class="py-3 px-4">
                <span :class="{
                  'text-green-600': server.status === 1,
                  'text-orange-600': server.status === 2,
                  'text-gray-400': server.status >= 3
                }" class="text-sm">
                  {{ { 1: '火爆', 2: '推荐', 3: '维护', 4: '已满' }[server.status] || '未知' }}
                </span>
              </td>
              <td class="py-3 px-4">
                <Link :href="`/games/${server.game_id}`"
                  class="bg-orange-500 text-white text-sm px-4 py-1.5 rounded hover:bg-orange-600 transition">
                  开始游戏
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="p-4">
          <Pagination :links="servers.links" />
        </div>
      </div>
    </div>
  </Default>
</template>
