<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

defineProps({
  reason: String,
})

const page = usePage()
const user = page.props.auth?.user
</script>

<template>
  <Default>
    <div class="min-h-[70vh] flex items-center justify-center px-4">
      <div class="text-center max-w-md">
        <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-3">无法进入游戏</h1>
        <p class="text-gray-600 mb-6">{{ reason }}</p>
        <div v-if="user" class="space-y-3">
          <Link href="/"
            class="inline-block bg-orange-500 text-white px-8 py-2.5 rounded-lg hover:bg-orange-600 transition font-medium">
            返回首页
          </Link>
        </div>
        <div v-else class="space-y-3">
          <Link href="/login"
            class="inline-block bg-orange-500 text-white px-8 py-2.5 rounded-lg hover:bg-orange-600 transition font-medium">
            登录
          </Link>
        </div>
      </div>
    </div>
  </Default>
</template>
