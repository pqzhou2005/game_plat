<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'

const page = usePage()
const user = page.props.auth?.user
const showMobileMenu = ref(false)
</script>

<template>
  <nav class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex items-center justify-between h-16">
        <Link href="/" class="flex items-center space-x-2">
          <span class="text-2xl font-bold text-orange-500">602</span>
          <span class="text-lg hidden sm:inline">游戏平台</span>
        </Link>

        <div class="hidden md:flex items-center space-x-6">
          <Link href="/" class="hover:text-orange-400 transition">首页</Link>
          <Link href="/games" class="hover:text-orange-400 transition">游戏中心</Link>
          <Link href="/servers" class="hover:text-orange-400 transition">开服列表</Link>
          <Link href="/recharge" class="hover:text-orange-400 transition">充值</Link>
        </div>

        <div class="flex items-center space-x-4">
          <template v-if="user">
            <Link :href="route('user.dashboard')" class="hover:text-orange-400">
              {{ user.username }}
            </Link>
            <Link :href="route('logout')" method="post" as="button"
              class="text-sm text-gray-400 hover:text-white">退出</Link>
          </template>
          <template v-else>
            <Link :href="route('login')" class="hover:text-orange-400">登录</Link>
            <Link :href="route('register')"
              class="bg-orange-500 px-4 py-2 rounded hover:bg-orange-600 transition">注册</Link>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>
