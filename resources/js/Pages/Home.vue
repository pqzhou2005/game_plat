<script setup>
import { Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import GameCard from '@/Components/GameCard.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  recommendedGames: Array,
  hotGames: Array,
  latestServers: Array,
  categories: Array,
  allGames: Object,
})
</script>

<template>
  <Default>
    <!-- Hero Banner -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-12">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold mb-4">欢迎来到 602 游戏平台</h1>
        <p class="text-lg text-gray-300 mb-6">海量精品游戏，畅享极致体验</p>
        <Link href="/games" class="inline-block bg-orange-500 text-white px-8 py-3 rounded-lg font-medium hover:bg-orange-600 transition">
          查看全部游戏
        </Link>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Recommended Games -->
      <section v-if="recommendedGames.length" class="mb-10">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">推荐游戏</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
          <GameCard v-for="game in recommendedGames" :key="game.id" :game="game" />
        </div>
      </section>

      <!-- Latest Servers -->
      <section v-if="latestServers.length" class="mb-10">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">最新开服</h2>
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div v-for="server in latestServers" :key="server.id"
            class="flex items-center justify-between px-6 py-3 border-b last:border-0 hover:bg-gray-50">
            <div class="flex items-center space-x-4">
              <span class="font-medium text-gray-900">{{ server.game?.name }}</span>
              <span class="text-gray-600">{{ server.name }}</span>
            </div>
            <div class="flex items-center space-x-3">
              <span class="text-sm text-gray-500">{{ server.open_time }}</span>
              <Link :href="`/games/${server.game_id}`"
                class="bg-orange-500 text-white text-sm px-4 py-1.5 rounded hover:bg-orange-600 transition">
                开始游戏
              </Link>
            </div>
          </div>
        </div>
      </section>

      <!-- Hot Games -->
      <section v-if="hotGames.length" class="mb-10">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">热门游戏</h2>
          <Link href="/games" class="text-orange-600 hover:underline text-sm">查看全部 →</Link>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <GameCard v-for="game in hotGames" :key="game.id" :game="game" />
        </div>
      </section>

      <!-- All Games -->
      <section>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">全部游戏</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <GameCard v-for="game in allGames.data" :key="game.id" :game="game" />
        </div>
        <Pagination :links="allGames.links" />
      </section>
    </div>
  </Default>
</template>
