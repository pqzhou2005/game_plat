<script setup>
import { Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

defineProps({
  user: Object,
  recentOrders: Array,
})
</script>

<template>
  <Default>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">个人中心</h1>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
          <div class="bg-white rounded-xl shadow p-6">
            <div class="text-center mb-4">
              <img :src="user.avatar || '/default-avatar.png'"
                class="w-24 h-24 rounded-full mx-auto bg-gray-200 object-cover" />
              <h2 class="text-lg font-semibold mt-3">{{ user.username }}</h2>
              <p v-if="user.id_card_verified_at" class="text-green-600 text-sm mt-1">已实名认证</p>
            </div>
            <nav class="space-y-2">
              <Link :href="route('user.dashboard')"
                class="block px-4 py-2 rounded hover:bg-gray-100 text-gray-700">个人主页</Link>
              <Link :href="route('user.settings')"
                class="block px-4 py-2 rounded hover:bg-gray-100 text-gray-700">账户设置</Link>
              <Link :href="route('user.orders')"
                class="block px-4 py-2 rounded hover:bg-gray-100 text-gray-700">充值记录</Link>
            </nav>
          </div>
        </div>
        <div class="md:col-span-2">
          <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold text-gray-900 mb-3">最近充值</h3>
            <div v-if="recentOrders.length">
              <div v-for="order in recentOrders" :key="order.id"
                class="flex justify-between items-center py-2 border-b text-sm">
                <span class="text-gray-600">{{ order.created_at }}</span>
                <span class="font-medium">¥{{ order.amount }}</span>
                <span :class="order.status === 'success' ? 'text-green-600' : 'text-yellow-600'">
                  {{ { pending: '处理中', success: '成功', failed: '失败' }[order.status] || order.status }}
                </span>
              </div>
            </div>
            <p v-else class="text-gray-500 text-sm py-4">暂无充值记录</p>
            <div class="mt-4">
              <Link :href="route('user.orders')" class="text-orange-600 text-sm hover:underline">查看全部记录 →</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Default>
</template>
