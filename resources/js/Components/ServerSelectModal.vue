```vue
<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { GameServerStatus } from '@/statusMaps'

const props = defineProps({
  game: { type: Object, required: true },
  servers: { type: Array, required: true },
  recentServerIds: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])

const searchQuery = ref('')
const selectedServerId = ref(null)

const recentServers = computed(() =>
  props.servers.filter(s => props.recentServerIds.includes(s.id))
)

const filteredServers = computed(() => {
  if (!searchQuery.value) return props.servers
  return props.servers.filter(s =>
    s.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const enterGame = () => {
  if (!selectedServerId.value) return
  router.visit(`/game/play/${props.game.id}?server_id=${selectedServerId.value}`)
}

const isMaintenance = props.game.status === 0
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="emit('close')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
      <!-- Header -->
      <div class="p-6 border-b shrink-0">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-900">选择区服</h2>
            <p class="text-sm text-gray-500 mt-1">请选择一个服务器进入「{{ game.name }}」</p>
          </div>
          <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <!-- 游戏维护提示 -->
        <div v-if="isMaintenance" class="mt-3 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-2 rounded-lg">
          该游戏目前已下架，无法进入
        </div>
      </div>

      <!-- Search -->
      <div class="px-6 py-3 shrink-0">
        <input v-model="searchQuery" type="text" placeholder="搜索服务器..."
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-orange-400" />
      </div>

      <!-- Server list -->
      <div class="flex-1 overflow-y-auto px-6 pb-4">
        <!-- 最近玩过 -->
        <div v-if="recentServers.length && !searchQuery" class="mb-4">
          <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">最近玩过</h3>
          <div class="space-y-1">
            <button v-for="server in recentServers" :key="server.id"
              @click="selectedServerId = server.id"
              :class="['w-full flex items-center justify-between px-4 py-2.5 rounded-lg border text-sm transition',
                selectedServerId === server.id
                  ? 'border-orange-500 bg-orange-50'
                  : 'border-gray-200 hover:border-gray-300']">
              <span class="font-medium text-gray-900">{{ server.name }}</span>
              <span :class="['text-xs px-2 py-0.5 rounded-full', GameServerStatus.badgeClass(server.status)]">
                {{ GameServerStatus.label(server.status) }}
              </span>
            </button>
          </div>
        </div>

        <!-- 推荐服 -->
        <div v-if="!searchQuery">
          <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">推荐服务器</h3>
          <div class="space-y-1 mb-4">
            <button v-for="server in filteredServers.filter(s => s.is_recommend && s.status !== 3)"
              :key="server.id"
              @click="selectedServerId = server.id"
              :class="['w-full flex items-center justify-between px-4 py-2.5 rounded-lg border text-sm transition',
                selectedServerId === server.id
                  ? 'border-orange-500 bg-orange-50 ring-1 ring-orange-200'
                  : 'border-gray-200 hover:border-gray-300']">
              <div class="flex items-center space-x-2">
                <span class="font-medium text-gray-900">{{ server.name }}</span>
                <span class="bg-orange-100 text-orange-600 text-xs px-1.5 py-0.5 rounded">推荐</span>
              </div>
              <span :class="['text-xs px-2 py-0.5 rounded-full', GameServerStatus.badgeClass(server.status)]">
                {{ GameServerStatus.label(server.status) }}
              </span>
            </button>
            <div v-if="!filteredServers.filter(s => s.is_recommend && s.status !== 3).length"
              class="text-center py-4 text-gray-400 text-sm">
              暂无推荐服务器
            </div>
          </div>
        </div>

        <!-- 全部服 -->
        <div>
          <h3 class="text-xs font-semibold text-gray-400 uppercase mb-2">
            {{ searchQuery ? '搜索结果' : '全部服务器' }}
          </h3>
          <div class="space-y-1">
            <button v-for="server in filteredServers" :key="server.id"
              @click="selectedServerId = server.id"
              :disabled="server.status === 3"
              :class="['w-full flex items-center justify-between px-4 py-2.5 rounded-lg border text-sm transition',
                selectedServerId === server.id
                  ? 'border-orange-500 bg-orange-50'
                  : server.status === 3
                    ? 'border-gray-100 bg-gray-50 cursor-not-allowed opacity-60'
                    : 'border-gray-200 hover:border-gray-300']">
              <span class="font-medium" :class="server.status === 3 ? 'text-gray-400' : 'text-gray-900'">
                {{ server.name }}
              </span>
              <span :class="['text-xs px-2 py-0.5 rounded-full', GameServerStatus.badgeClass(server.status)]">
                {{ GameServerStatus.label(server.status) }}
              </span>
            </button>
            <div v-if="!filteredServers.length" class="text-center py-8 text-gray-400 text-sm">
              暂无服务器
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-6 border-t shrink-0">
        <button @click="enterGame"
          :disabled="!selectedServerId || isMaintenance"
          class="w-full bg-orange-500 text-white py-2.5 rounded-lg hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium">
          {{ isMaintenance ? '游戏已下架' : selectedServerId ? '进入游戏' : '请选择服务器' }}
        </button>
      </div>
    </div>
  </div>
</template>
```