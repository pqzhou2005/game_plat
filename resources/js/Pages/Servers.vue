<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import Pagination from '@/Components/Pagination.vue'
import { GameServerDynamicStatus } from '@/statusMaps'

const props = defineProps({
  todayServers: Array,
  upcomingServers: Array,
  servers: Object,
  games: Array,
  currentTab: String,
  filters: Object,
})

const selectedGame = ref(props.filters?.game_id || '')
const activeTab = ref(props.currentTab || 'today')

const switchTab = (tab) => {
  activeTab.value = tab
  router.get('/servers', { tab, game_id: selectedGame.value }, { preserveState: true })
}

const filterGame = () => {
  router.get('/servers', { tab: activeTab.value, game_id: selectedGame.value }, { preserveState: true })
}

const formatDate = (date) => {
  const d = new Date(date)
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hour = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  return `${month}-${day} ${hour}:${min}`
}

const isToday = (date) => {
  const d = new Date(date)
  const n = new Date()
  return d.getDate() === n.getDate() && d.getMonth() === n.getMonth() && d.getFullYear() === n.getFullYear()
}
</script>

<template>
  <Default>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-6">开服列表</h1>

      <!-- Filter bar -->
      <div class="bg-white rounded-lg shadow p-4 mb-6 flex gap-4 items-center">
        <select v-model="selectedGame" @change="filterGame"
          class="border border-gray-300 rounded px-3 py-2 text-sm">
          <option value="">全部游戏</option>
          <option v-for="g in games" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
      </div>

      <!-- Tabs -->
      <div class="flex space-x-1 rounded-lg bg-gray-100 p-1 mb-6 w-fit">
        <button @click="switchTab('today')"
          :class="activeTab === 'today' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
          class="px-4 py-2 text-sm font-medium rounded-md transition">
          今日开服
          <span v-if="todayServers.length" class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
            {{ todayServers.length }}
          </span>
        </button>
        <button @click="switchTab('upcoming')"
          :class="activeTab === 'upcoming' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
          class="px-4 py-2 text-sm font-medium rounded-md transition">
          即将开服
          <span v-if="upcomingServers.length" class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
            {{ upcomingServers.length }}
          </span>
        </button>
        <button @click="switchTab('all')"
          :class="activeTab === 'all' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
          class="px-4 py-2 text-sm font-medium rounded-md transition">
          全部开服
        </button>
      </div>

      <!-- Server list table -->
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
              <td class="py-3 px-4 text-sm whitespace-nowrap">
                <span :class="isToday(server.open_time) ? 'text-orange-600 font-medium' : 'text-gray-600'">
                  {{ formatDate(server.open_time) }}
                </span>
              </td>
              <td class="py-3 px-4">
                <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', GameServerDynamicStatus(server).color]">
                  {{ GameServerDynamicStatus(server).label }}
                </span>
              </td>
              <td class="py-3 px-4">
                <Link :href="`/games/${server.game_id}`"
                  class="bg-orange-500 text-white text-sm px-4 py-1.5 rounded hover:bg-orange-600 transition inline-block">
                  开始游戏
                </Link>
              </td>
            </tr>
            <tr v-if="!servers.data?.length">
              <td colspan="5" class="px-4 py-12 text-center text-gray-400">暂无开服信息</td>
            </tr>
          </tbody>
        </table>
        <div v-if="servers.links?.length > 3" class="p-4 border-t">
          <Pagination :links="servers.links" />
        </div>
      </div>
    </div>
  </Default>
</template>
