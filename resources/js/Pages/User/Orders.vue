<script setup>
import { Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import Pagination from '@/Components/Pagination.vue'
import { PaymentOrderStatus } from '@/statusMaps'

defineProps({ orders: Object })
</script>

<template>
  <Default>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">充值记录</h1>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
          <div class="bg-white rounded-xl shadow p-6">
            <nav class="space-y-2">
              <Link :href="'/user'" class="block px-4 py-2 rounded hover:bg-gray-100 text-gray-700">个人主页</Link>
              <Link :href="'/user/settings'" class="block px-4 py-2 rounded hover:bg-gray-100 text-gray-700">账户设置</Link>
              <Link :href="'/user/orders'" class="block px-4 py-2 rounded bg-gray-100 text-orange-600 font-medium">充值记录</Link>
            </nav>
          </div>
        </div>
        <div class="md:col-span-2">
          <div class="bg-white rounded-xl shadow overflow-hidden">
            <table v-if="orders.data.length" class="w-full">
              <thead>
                <tr class="border-b text-left text-sm text-gray-500">
                  <th class="py-3 px-4">订单号</th>
                  <th class="py-3 px-4">金额</th>
                  <th class="py-3 px-4">状态</th>
                  <th class="py-3 px-4">时间</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in orders.data" :key="order.id" class="border-b hover:bg-gray-50 text-sm">
                  <td class="py-3 px-4 text-gray-600">{{ order.order_no }}</td>
                  <td class="py-3 px-4 font-medium">¥{{ order.amount }}</td>
                  <td class="py-3 px-4">
                    <span :class="'text-' + PaymentOrderStatus.color(order.status) + '-600'">
                      {{ PaymentOrderStatus.label(order.status) }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-gray-500">{{ order.created_at }}</td>
                </tr>
              </tbody>
            </table>
            <div v-else class="text-center py-8 text-gray-500">暂无充值记录</div>
            <div class="p-4 border-t">
              <Pagination :links="orders.links" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </Default>
</template>
