<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

const props = defineProps({ game_id: Number })

const amounts = [10, 50, 100, 200, 500, 1000]
const selectedAmount = ref(100)
const customAmount = ref('')
const channel = ref('alipay')

const form = useForm({
  amount: 100,
  channel: 'alipay',
  game_id: props.game_id || null,
})

const selectAmount = (val) => {
  selectedAmount.value = val
  customAmount.value = ''
  form.amount = val
}

const submit = () => {
  if (customAmount.value && !selectedAmount.value) {
    form.amount = parseFloat(customAmount.value)
  }
  form.post(route('recharge.store'))
}
</script>

<template>
  <Default>
    <div class="max-w-2xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">充值中心</h1>
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">选择充值金额</h2>
        <div class="grid grid-cols-3 gap-3 mb-4">
          <button v-for="amt in amounts" :key="amt"
            @click="selectAmount(amt)"
            :class="[
              'py-3 rounded-lg border-2 font-medium transition text-center',
              selectedAmount === amt
                ? 'border-orange-500 bg-orange-50 text-orange-600'
                : 'border-gray-200 hover:border-gray-300 text-gray-700'
            ]">
            ¥{{ amt }}
          </button>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">自定义金额</label>
          <div class="flex items-center border border-gray-300 rounded-lg px-4">
            <span class="text-gray-500">¥</span>
            <input v-model="customAmount" type="number" min="1" max="99999" placeholder="输入金额"
              @focus="selectedAmount = 0"
              class="flex-1 py-2.5 ml-2 outline-none" />
          </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-900 mb-4">选择支付方式</h2>
        <div class="grid grid-cols-2 gap-3 mb-6">
          <button @click="channel = 'alipay'"
            :class="[
              'flex items-center justify-center space-x-2 py-3 rounded-lg border-2 transition',
              channel === 'alipay' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
            ]">
            <span class="text-blue-600 font-medium">支付宝</span>
          </button>
          <button @click="channel = 'wechat'"
            :class="[
              'flex items-center justify-center space-x-2 py-3 rounded-lg border-2 transition',
              channel === 'wechat' ? 'border-green-500 bg-green-50' : 'border-gray-200'
            ]">
            <span class="text-green-600 font-medium">微信支付</span>
          </button>
        </div>

        <form @submit.prevent="submit">
          <input type="hidden" v-model="form.channel" :value="channel" />
          <button type="submit" :disabled="form.processing"
            class="w-full bg-orange-500 text-white py-3 rounded-lg hover:bg-orange-600 disabled:opacity-50 transition text-lg font-medium">
            {{ form.processing ? '处理中...' : `立即支付 ¥${form.amount}` }}
          </button>
        </form>
      </div>
    </div>
  </Default>
</template>
