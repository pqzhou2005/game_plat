<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <footer class="bg-white text-gray-500 py-6 mt-8 border-t border-gray-200">
    <div class="w-[1200px] mx-auto text-center">
      <div class="flex items-center justify-center gap-2 mb-2">
        <div class="w-7 h-7 bg-gradient-to-br from-[#ff7a00] to-[#ff4a1f] rounded flex items-center justify-center text-[13px] font-bold text-white">
          602
        </div>
        <span class="text-[15px] font-bold text-gray-700">游戏平台</span>
      </div>
      <p class="text-[12px] mb-1.5 text-gray-500">
        602 游戏平台 © {{ new Date().getFullYear() }} 版权所有 | 抵制不良游戏 拒绝盗版游戏
      </p>
      <p class="text-[11px] text-gray-400 leading-relaxed">
        注意自我保护 谨防受骗上当 适度游戏益脑 沉迷游戏伤身 合理安排时间 享受健康生活
      </p>
      <div class="mt-3 text-[11px] text-gray-400">
        <Link href="/about" class="hover:text-[#ff7a00] mx-2">关于我们</Link>
        <span>|</span>
        <Link href="/agreement" class="hover:text-[#ff7a00] mx-2">用户协议</Link>
        <span>|</span>
        <Link href="/parent" class="hover:text-[#ff7a00] mx-2">家长监护</Link>
      </div>
    </div>
  </footer>
</template>
