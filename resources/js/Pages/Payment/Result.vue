<script setup>
import { Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

const props = defineProps({
  order: Object,
  payData: Object,
})

const isSuccess = props.order?.status === 'success'
</script>

<template>
  <Default>
    <div class="max-w-lg mx-auto px-4 py-16 text-center">
      <div class="bg-white rounded-xl shadow-lg p-8">
        <template v-if="isSuccess">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-900 mb-2">支付成功</h1>
          <p class="text-gray-600 mb-6">充值金额 ¥{{ order.amount }} 已到账</p>
        </template>
        <template v-else>
          <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-900 mb-2">支付处理中</h1>
          <p class="text-gray-600 mb-6">订单已提交，请等待支付结果</p>
        </template>

        <div class="space-y-3">
          <div v-if="order" class="text-sm text-gray-500 mb-4">
            订单号：{{ order.order_no }}<br/>
            金额：¥{{ order.amount }}
          </div>
          <Link :href="route('user.orders')"
            class="block bg-gray-100 text-gray-700 py-2.5 rounded-lg hover:bg-gray-200 transition">
            查看订单
          </Link>
          <Link href="/"
            class="block bg-orange-500 text-white py-2.5 rounded-lg hover:bg-orange-600 transition">
            返回首页
          </Link>
        </div>
      </div>
    </div>
  </Default>
</template>
