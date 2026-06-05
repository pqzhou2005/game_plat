<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  links: { type: Array, required: true }
})
</script>

<template>
  <div class="flex justify-center space-x-1 mt-6" v-if="links.length > 3">
    <template v-for="link in links" :key="link.label">
      <Link :href="link.url || '#'" v-html="link.label"
        :class="[
          'px-3 py-1.5 text-sm rounded',
          link.active ? 'bg-orange-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100',
          !link.url && 'opacity-50 cursor-not-allowed'
        ]" />
    </template>
  </div>
</template>
