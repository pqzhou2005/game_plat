<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import PayModal from '@/Components/PayModal.vue'

const props = defineProps({
  game: Object,
  server: Object,
  loginUrl: String,
  hasConfig: Boolean,
  error: String,
  ssoToken: Object,
})

const page = usePage()
const user = page.props.auth?.user
const iframeRef = ref(null)
const showPayModal = ref(false)
const payData = ref(null)
const loading = ref(true)
const loadError = ref(props.error || null)

const getAllowedGameOrigin = () => {
  const url = props.ssoToken?.login_url || props.loginUrl
  if (!url) return null

  try {
    return new URL(url).origin
  } catch {
    return null
  }
}

const backToGame = () => {
  router.visit(`/games/${props.game.id}`)
}

onMounted(() => {
  if (!user) {
    router.visit('/login')
    return
  }

  if (loadError.value) {
    loading.value = false
    return
  }

  const params = props.ssoToken
  if (!params) {
    loadError.value = 'SSO参数获取失败'
    loading.value = false
    return
  }

  const loginUrl = params.login_url
  delete params.login_url
  const query = new URLSearchParams(params).toString()
  const sep = loginUrl.includes('?') ? '&' : '?'

  // 先隐藏 loading，等 iframe 渲染出来后设置 src
  loading.value = false
  nextTick(() => {
    if (iframeRef.value) {
      iframeRef.value.src = loginUrl + sep + query
    }
  })

  window.addEventListener('message', handleGameMessage)
})

onUnmounted(() => {
  window.removeEventListener('message', handleGameMessage)
})

const handleGameMessage = (event) => {
  console.log('[Play] postMessage received:', { origin: event.origin, data: event.data })

  const allowedOrigin = getAllowedGameOrigin()
  if (allowedOrigin && event.origin !== allowedOrigin && event.origin !== 'null') {
    console.warn('[Play] message ignored: origin mismatch', { expected: allowedOrigin, actual: event.origin })
    return
  }

  const { type, data } = event.data || {}
  console.log('[Play] message type:', type, 'data:', data)

  if (type === 'pay') {
    payData.value = data
    showPayModal.value = true
  }

  if (type === 'role') {
    console.log('[Play] role上报:', data)
    fetch('/api/game/role', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(data),
    })
      .then(res => {
        console.log('[Play] role上报响应 status:', res.status)
        return res.text().then(t => ({ status: res.status, body: t }))
      })
      .then(({ status, body }) => {
        console.log('[Play] role上报结果:', { status, body })
      })
      .catch(err => {
        console.error('[Play] role上报失败:', err)
      })
  }

  if (type === 'batch_role') {
    console.log('[Play] batch_role上报:', data)
    fetch('/api/game/roles', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(data),
    })
      .then(res => {
        console.log('[Play] batch_role上报响应 status:', res.status)
        return res.text().then(t => ({ status: res.status, body: t }))
      })
      .then(({ status, body }) => {
        console.log('[Play] batch_role上报结果:', { status, body })
      })
      .catch(err => {
        console.error('[Play] batch_role上报失败:', err)
      })
  }
}

const handlePayClose = () => {
  showPayModal.value = false
}

const handlePaySuccess = (order) => {
  showPayModal.value = false
  if (iframeRef.value) {
    iframeRef.value.contentWindow.postMessage({
      type: 'pay_result',
      data: { status: 'success', order_no: order.order_no }
    }, '*')
  }
}
</script>

<template>
  <div class="flex flex-col h-screen bg-white">
    <!-- Top bar -->
    <div class="bg-gray-900 text-white px-4 py-2 flex items-center justify-between shrink-0 z-10">
      <div class="flex items-center space-x-4">
        <a href="/" class="font-bold text-orange-500 hover:text-orange-400">602游戏平台</a>
        <span class="text-gray-500">|</span>
        <span class="text-sm">{{ game.name }}</span>
        <span v-if="server" class="text-xs text-gray-400">{{ server.name }}</span>
      </div>
      <div class="flex items-center space-x-4">
        <span v-if="user" class="text-sm text-gray-300">{{ user.username }}</span>
        <button @click="backToGame"
          class="text-gray-400 hover:text-white text-sm transition px-2">
          返回
        </button>
        <button @click="showPayModal = true"
          class="bg-orange-500 text-white text-sm px-4 py-1 rounded hover:bg-orange-600 transition">
          充值
        </button>
      </div>
    </div>

    <!-- 维护/错误提示 -->
    <div v-if="loadError" class="flex-1 flex items-center justify-center bg-gray-100">
      <div class="text-center max-w-md">
        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" />
          </svg>
        </div>
        <p class="text-gray-700 text-lg mb-2">暂时无法进入游戏</p>
        <p class="text-gray-500 text-sm mb-6">{{ loadError }}</p>
        <button @click="backToGame"
          class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition">
          选择其他服务器
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-else-if="loading" class="flex-1 flex items-center justify-center bg-gray-100">
      <div class="text-center">
        <div class="w-10 h-10 border-4 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-gray-500 text-lg mb-2">游戏加载中...</p>
        <p class="text-gray-400 text-sm">正在获取登录信息</p>
      </div>
    </div>

    <!-- Game iframe -->
    <iframe v-else ref="iframeRef"
      class="flex-1 w-full border-0"
      allowfullscreen
      sandbox="allow-scripts allow-forms allow-popups allow-same-origin">
    </iframe>

    <!-- Pay Modal -->
    <PayModal
      v-if="showPayModal"
      :game-id="game.id"
      :pay-data="payData"
      @close="handlePayClose"
      @success="handlePaySuccess"
    />
  </div>
</template>
