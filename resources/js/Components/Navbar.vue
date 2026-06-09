<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = page.props.auth?.user
const searchQuery = ref('')
const loginUrl = '/login?redirect=' + encodeURIComponent(page.url || window.location.pathname)
const registerUrl = '/register?redirect=' + encodeURIComponent(page.url || window.location.pathname)

const logout = () => {
  router.post('/logout')
}

const doSearch = () => {
  if (searchQuery.value.trim()) {
    router.visit('/games?search=' + encodeURIComponent(searchQuery.value.trim()))
  }
}

const isActive = (path) => {
  if (path === '/') return page.url === '/'
  return page.url.startsWith(path)
}
</script>

<template>
  <!-- TopBar -->
  <div class="bg-[#f5f5f5] border-b border-gray-200">
    <div class="w-[1200px] mx-auto h-[32px] flex items-center justify-between px-4 text-[12px]">
      <div class="flex items-center gap-4 text-gray-600">
        <Link href="/games" class="hover:text-[#ff7a00] transition-colors">所有游戏</Link>
        <span class="text-gray-300">|</span>
        <Link href="/servers" class="hover:text-[#ff7a00] transition-colors">开服表</Link>
      </div>
      <div class="flex items-center gap-3 text-gray-600">
        <template v-if="user">
          <span>欢迎您，<Link :href="'/user'" class="text-[#ff7a00] hover:underline">{{ user.username }}</Link></span>
          <span class="text-gray-300">|</span>
          <button @click="logout" class="hover:text-[#ff7a00] transition-colors">退出</button>
        </template>
        <template v-else>
          <Link :href="loginUrl" class="hover:text-[#ff7a00] transition-colors">登录</Link>
          <span class="text-gray-300">|</span>
          <Link :href="registerUrl" class="hover:text-[#ff7a00] transition-colors">注册</Link>
        </template>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="w-[1200px] mx-auto flex items-center justify-between h-[78px] px-4">
      <!-- Logo -->
      <Link href="/" class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-[#ff7a00] to-[#ff4a1f] rounded-lg flex items-center justify-center text-[20px] font-bold text-white shadow-md">
          602
        </div>
        <div>
          <div class="text-[22px] font-bold text-gray-800 leading-none">游戏平台</div>
          <div class="text-[11px] text-gray-400 mt-0.5">热血传奇 仙侠魔幻</div>
        </div>
      </Link>

      <!-- Navigation -->
      <nav class="flex items-center gap-8">
        <Link href="/"
          class="text-[17px] font-medium transition-colors relative"
          :class="isActive('/') && page.url === '/' ? 'text-gray-800' : 'text-gray-600 hover:text-[#ff7a00]'">
          首页
          <span v-if="isActive('/') && page.url === '/'"
            class="absolute bottom-[-28px] left-0 right-0 h-[3px] bg-[#ff7a00] rounded-t-full"></span>
        </Link>
        <Link href="/user"
          class="text-[16px] transition-colors"
          :class="isActive('/user') ? 'text-gray-800' : 'text-gray-600 hover:text-[#ff7a00]'">
          个人中心
        </Link>
        <Link href="/games"
          class="text-[16px] transition-colors"
          :class="isActive('/games') ? 'text-gray-800' : 'text-gray-600 hover:text-[#ff7a00]'">
          游戏中心
        </Link>
        <Link href="/servers"
          class="text-[16px] transition-colors"
          :class="isActive('/servers') ? 'text-gray-800' : 'text-gray-600 hover:text-[#ff7a00]'">
          开服表
        </Link>
        <Link href="/notices"
          class="text-[16px] transition-colors"
          :class="isActive('/notices') ? 'text-gray-800' : 'text-gray-600 hover:text-[#ff7a00]'">
          公告
        </Link>
      </nav>

      <!-- Search -->
      <div class="relative">
        <input v-model="searchQuery" type="text" placeholder="搜索游戏" @keyup.enter="doSearch"
          class="w-[200px] h-[36px] pl-4 pr-10 rounded-full border border-gray-300 text-[14px] focus:outline-none focus:border-[#ff7a00] transition-all" />
        <button @click="doSearch"
          class="absolute right-1 top-1/2 -translate-y-1/2 w-[28px] h-[28px] bg-gradient-to-r from-[#ff7a00] to-[#ff4a1f] rounded-full flex items-center justify-center hover:shadow-md transition-all">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </button>
      </div>
    </div>
  </header>
</template>
