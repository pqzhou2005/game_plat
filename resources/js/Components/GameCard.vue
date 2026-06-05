<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  game: { type: Object, required: true }
})
</script>

<template>
  <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
    <Link :href="`/games/${game.id}`">
      <div class="aspect-[4/3] bg-gray-200 overflow-hidden">
        <img :src="game.logo || '/placeholder-game.png'" :alt="game.name"
          class="w-full h-full object-cover hover:scale-105 transition" />
      </div>
      <div class="p-3">
        <h3 class="font-semibold text-gray-900 truncate">{{ game.name }}</h3>
        <div class="flex items-center justify-between mt-1">
          <span class="text-xs text-gray-500">{{ game.game_type || '页游' }}</span>
          <div class="flex space-x-1">
            <span v-if="game.is_hot" class="bg-red-100 text-red-600 text-xs px-1.5 py-0.5 rounded">热门</span>
            <span v-if="game.is_new" class="bg-green-100 text-green-600 text-xs px-1.5 py-0.5 rounded">新游</span>
          </div>
        </div>
      </div>
    </Link>
  </div>
</template>
