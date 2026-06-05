<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <footer class="bg-gray-900 text-gray-400 py-8 mt-auto">
    <div class="max-w-7xl mx-auto px-4 text-center text-sm">
      <div class="mb-4">
        <Link href="/about" class="hover:text-white mx-2">关于我们</Link>
        <span>|</span>
        <Link href="/agreement" class="hover:text-white mx-2">用户协议</Link>
        <span>|</span>
        <Link href="/parent" class="hover:text-white mx-2">家长监护</Link>
      </div>
      <p>上海钰讯信息科技有限公司 Copyright @ 2012-2026 602.com All Rights Reserved</p>
      <p class="mt-1">
        <a href="https://beian.miit.gov.cn" target="_blank" class="hover:text-white">沪ICP备14029528号-2</a>
        <span class="mx-2">|</span>
        <a href="http://www.beian.gov.cn" target="_blank" class="hover:text-white">沪公网安备 31011402001552号</a>
      </p>
    </div>
  </footer>
</template>
