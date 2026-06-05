<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import GameCard from '@/Components/GameCard.vue'
import ServerSelectModal from '@/Components/ServerSelectModal.vue'

const props = defineProps({
  game: Object,
  recommended: Array,
  recentServerIds: Array,
})

const page = usePage()
const user = page.props.auth?.user
const showServerSelect = ref(false)

const formatDate = (date) => new Date(date).toLocaleDateString('zh-CN')

const handleStartGame = () => {
  if (!user) {
    router.visit('/login')
    return
  }
  if (props.game.servers?.length) {
    showServerSelect.value = true
  } else {
    router.visit(`/game/play/${props.game.id}`)
  }
}
</script>

<template>
  <Default>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="md:flex">
          <div class="md:w-80 p-6">
            <img :src="game.logo || '/placeholder-game.png'" :alt="game.name"
              class="w-full aspect-[4/3] object-cover rounded-lg" />
          </div>
          <div class="flex-1 p-6">
            <div class="flex items-start justify-between">
              <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ game.name }}</h1>
                <div class="flex items-center space-x-3 mt-2">
                  <span class="bg-gray-100 text-gray-600 text-sm px-2 py-0.5 rounded">{{ game.game_type }}</span>
                  <span class="bg-gray-100 text-gray-600 text-sm px-2 py-0.5 rounded">{{ game.category?.name }}</span>
                  <span v-if="game.is_hot" class="bg-red-100 text-red-600 text-sm px-2 py-0.5 rounded">热门</span>
                  <span v-if="game.is_new" class="bg-green-100 text-green-600 text-sm px-2 py-0.5 rounded">新游</span>
                </div>
              </div>
            </div>

            <p class="text-gray-600 mt-4 line-clamp-3">{{ game.description || '暂无介绍' }}</p>

            <div class="flex items-center space-x-4 mt-6">
              <button @click="handleStartGame"
                class="bg-orange-500 text-white px-8 py-2.5 rounded-lg hover:bg-orange-600 transition font-medium text-lg">
                开始游戏
              </button>
              <Link :href="`/recharge?game_id=${game.id}`"
                class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-200 transition font-medium">
                充值
              </Link>
            </div>
            <p v-if="game.servers?.length" class="text-xs text-gray-400 mt-2">
              共 {{ game.servers.length }} 个区服 · 点击「开始游戏」选择服务器进入
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">区服列表</h2>
        <div v-if="game.servers.length" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b text-left text-sm text-gray-500">
                <th class="py-3 px-4">区服名称</th>
                <th class="py-3 px-4">开服时间</th>
                <th class="py-3 px-4">状态</th>
                <th class="py-3 px-4">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="server in game.servers" :key="server.id" class="border-b hover:bg-gray-50">
                <td class="py-3 px-4 font-medium">{{ server.name }}</td>
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
                  <a :href="game.entries?.[0]?.entry_url || '#'" target="_blank"
                    class="bg-orange-500 text-white text-sm px-4 py-1.5 rounded hover:bg-orange-600 transition">
                    开始游戏
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-8 text-gray-500">
          暂无区服信息
        </div>
      </div>

      <div v-if="recommended.length">
        <h2 class="text-xl font-bold text-gray-900 mb-4">推荐游戏</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <GameCard v-for="g in recommended" :key="g.id" :game="g" />
        </div>
      </div>
    </div>
  </Default>

  <!-- Server Select Modal -->
  <ServerSelectModal
    v-if="showServerSelect"
    :game="game"
    :servers="game.servers"
    :recent-server-ids="recentServerIds || []"
    @close="showServerSelect = false"
  />
</template>
