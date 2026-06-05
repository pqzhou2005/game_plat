<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import GameCard from '@/Components/GameCard.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  games: Object,
  categories: Array,
  filters: Object,
})

const form = useForm({
  category: props.filters.category || '',
  search: props.filters.search || '',
  type: props.filters.type || '',
})

const applyFilters = () => {
  form.get('/games', { preserveState: true })
}
</script>

<template>
  <Default>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-6">游戏中心</h1>

      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
          <select v-model="form.category" @change="applyFilters"
            class="border border-gray-300 rounded px-3 py-2 text-sm">
            <option value="">全部分类</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
          </select>

          <select v-model="form.type" @change="applyFilters"
            class="border border-gray-300 rounded px-3 py-2 text-sm">
            <option value="">全部类型</option>
            <option value="页游">页游</option>
            <option value="微端">微端</option>
            <option value="手游">手游</option>
          </select>

          <div class="flex-1 min-w-[200px]">
            <input v-model="form.search" type="text" placeholder="搜索游戏..."
              @keyup.enter="applyFilters"
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
          </div>

          <button @click="applyFilters"
            class="bg-orange-500 text-white px-6 py-2 rounded hover:bg-orange-600 transition text-sm">
            搜索
          </button>
        </div>
      </div>

      <div v-if="games.data.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
        <GameCard v-for="game in games.data" :key="game.id" :game="game" />
      </div>
      <div v-else class="text-center py-12 text-gray-500">
        没有找到匹配的游戏
      </div>

      <Pagination :links="games.links" />
    </div>
  </Default>
</template>
