<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  gameId: { type: Number, required: true },
  payData: { type: Object, default: null },
})

const emit = defineEmits(['close', 'success'])

const amount = ref(100)
const orderNo = ref('')
const qrcodeImg = ref('')
const status = ref('idle') // idle, loading, qrcode, success, failed
const polling = ref(false)
const customAmount = ref('')
const selectedAmount = ref(100)
const channel = ref('alipay')

const amounts = [10, 50, 100, 200, 500, 1000]

const selectAmount = (val) => {
  selectedAmount.value = val
  customAmount.value = ''
  amount.value = val
}

const createOrder = async () => {
  status.value = 'loading'

  const body = {
    amount: amount.value,
    channel: channel.value,
    game_id: props.gameId,
  }

  if (props.payData) {
    body.server_id = props.payData.server_id
    body.role_id = props.payData.role_id
    body.role_name = props.payData.role_name
    body.product_id = props.payData.product_id
    body.product_name = props.payData.product_name
    body.product_desc = props.payData.product_desc
    body.ext = props.payData.ext
  }

  try {
    const res = await fetch('/api/payments/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    })
    const data = await res.json()

    orderNo.value = data.order.order_no
    const qrcodeUrl = data.qrcode_url

    // Generate QR code using qrcodejs or basic approach
    try {
      const QRCode = (await import('qrcode'))
      qrcodeImg.value = await QRCode.toDataURL(qrcodeUrl, { width: 250, margin: 2 })
    } catch (e) {
      qrcodeImg.value = '/api/qrcode?text=' + encodeURIComponent(qrcodeUrl)
    }

    status.value = 'qrcode'
    startPolling()
  } catch (e) {
    console.error('Order creation failed:', e)
    status.value = 'failed'
  }
}

const startPolling = () => {
  polling.value = true
  const interval = setInterval(async () => {
    try {
      const res = await fetch(`/api/payments/status/${orderNo.value}`)
      const data = await res.json()
      if (data.status === 'success') {
        clearInterval(interval)
        polling.value = false
        status.value = 'success'
        emit('success', data.order)
      }
    } catch (e) {
      console.error('Poll failed:', e)
    }
  }, 2000)
}

const close = () => emit('close')

onMounted(() => {
  if (props.payData) {
    amount.value = props.payData.money || amount.value
    createOrder()
  }
})
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="close">
    <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-sm relative">
      <button @click="close" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>

      <h2 class="text-xl font-bold text-gray-900 text-center mb-6">充值中心</h2>

      <!-- Amount selection -->
      <template v-if="!payData && status === 'idle'">
        <div class="grid grid-cols-3 gap-2 mb-4">
          <button v-for="amt in amounts" :key="amt" @click="selectAmount(amt)"
            :class="['py-2 rounded-lg border-2 text-sm font-medium transition',
              selectedAmount === amt ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-gray-200 text-gray-700']">
            ¥{{ amt }}
          </button>
        </div>
        <div class="mb-4">
          <input v-model="customAmount" type="number" placeholder="自定义金额" @focus="selectedAmount = 0"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none" />
        </div>
        <div class="grid grid-cols-2 gap-2 mb-6">
          <button @click="channel='alipay'"
            :class="['py-2 rounded-lg border-2 text-sm font-medium transition',
              channel==='alipay' ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-gray-200']">支付宝</button>
          <button @click="channel='wechat'"
            :class="['py-2 rounded-lg border-2 text-sm font-medium transition',
              channel==='wechat' ? 'border-green-500 bg-green-50 text-green-600' : 'border-gray-200']">微信支付</button>
        </div>
        <button @click="createOrder"
          class="w-full bg-orange-500 text-white py-2.5 rounded-lg hover:bg-orange-600 transition font-medium">
          立即支付 ¥{{ customAmount || selectedAmount }}
        </button>
      </template>

      <!-- QR Code -->
      <div v-if="status === 'loading'" class="text-center py-8">
        <p class="text-gray-500">生成支付二维码...</p>
      </div>

      <div v-if="status === 'qrcode'" class="text-center">
        <img v-if="qrcodeImg" :src="qrcodeImg" class="mx-auto w-56 h-56" alt="支付二维码" />
        <img v-else src="/images/loading.svg" class="mx-auto w-56 h-56" alt="加载中" />
        <p class="text-gray-600 text-sm mt-3">请使用微信或支付宝扫码支付</p>
        <p class="text-gray-400 text-xs mt-1">订单号: {{ orderNo }}</p>
        <p v-if="polling" class="text-orange-500 text-sm mt-2 animate-pulse">等待支付...</p>
      </div>

      <div v-if="status === 'success'" class="text-center py-4">
        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
          <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="text-green-600 font-medium">支付成功</p>
      </div>

      <div v-if="status === 'failed'" class="text-center py-4">
        <p class="text-red-500">支付失败，请重试</p>
        <button @click="createOrder" class="mt-3 text-orange-600 underline text-sm">重新支付</button>
      </div>
    </div>
  </div>
</template>
