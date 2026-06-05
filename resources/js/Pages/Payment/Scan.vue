<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  order: Object,
  qrcode_url: String,
})

const qrcodeImg = ref('')
const polling = ref(false)
const status = ref('waiting')

const generateQR = async () => {
  if (!props.qrcode_url) return
  try {
    const QRCode = (await import('qrcode'))
    qrcodeImg.value = await QRCode.toDataURL(props.qrcode_url, { width: 280, margin: 2 })
    startPolling()
  } catch (e) {
    // fallback: 直接显示链接
    qrcodeImg.value = ''
  }
}

const startPolling = () => {
  polling.value = true
  const interval = setInterval(async () => {
    try {
      const res = await fetch(`/api/payments/status/${props.order.order_no}`)
      const data = await res.json()
      if (data.status === 'success') {
        clearInterval(interval)
        polling.value = false
        status.value = 'success'
      }
    } catch (e) {
      // ignore
    }
  }, 2000)
}

onMounted(() => {
  generateQR()
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-10 text-center max-w-sm w-full mx-4">
      <h2 class="text-xl font-bold text-gray-900 mb-2">扫码支付</h2>
      <p class="text-gray-500 text-sm mb-6">请使用微信或支付宝扫码完成支付</p>

      <!-- QR Code -->
      <div v-if="status === 'waiting'" class="mb-4">
        <img v-if="qrcodeImg" :src="qrcodeImg" class="mx-auto w-[280px] h-[280px]" alt="支付二维码" />
        <div v-else class="w-[280px] h-[280px] bg-gray-100 rounded-lg flex items-center justify-center mx-auto">
          <p class="text-gray-400 text-sm">二维码加载中...</p>
        </div>
      </div>

      <!-- Success -->
      <div v-if="status === 'success'" class="py-8">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="text-green-600 font-medium text-lg">支付成功</p>
      </div>

      <!-- Info -->
      <div class="mt-4 space-y-1 text-sm text-gray-400">
        <p>订单号: {{ order?.order_no }}</p>
        <p>金额: ¥{{ order?.amount }}</p>
        <p v-if="polling && status === 'waiting'" class="text-orange-500 animate-pulse mt-2">等待支付...</p>
      </div>
    </div>
  </div>
</template>
