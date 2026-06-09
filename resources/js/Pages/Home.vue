<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  banners: Array,
  hotItems: Array,
  newItems: Array,
  featuredItems: Array,
  recommendedGames: Array,
  hotGames: Array,
  latestServers: Array,
  notices: Array,
  gameNotices: Array,
  categories: Array,
  allGames: Object,
  recentRoleReports: Array,
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

// ============ 登录表单 ============
const loginForm = useForm({ username: '', password: '', remember: false })
const showPassword = ref(false)
const submitLogin = () => { loginForm.post('/login?redirect=/') }

// ============ Banner 轮播 ============
const currentSlide = ref(0)
const slideTimer = ref(null)
const bannerList = computed(() => props.banners?.length ? props.banners : [])

const getBannerUrl = (item) => {
  if (item.target_type === 'url' && item.url) return item.url
  if (item.target_type === 'game' && item.game) return `/games/${item.game.id}`
  return '#'
}

const startSlide = () => {
  slideTimer.value = setInterval(() => {
    if (bannerList.value.length) {
      currentSlide.value = (currentSlide.value + 1) % bannerList.value.length
    }
  }, 4000)
}
const stopSlide = () => { if (slideTimer.value) clearInterval(slideTimer.value) }
const goToSlide = (i) => { currentSlide.value = i }

onMounted(() => { if (bannerList.value.length > 1) startSlide() })
onUnmounted(() => stopSlide())

// ============ 开服表 Tab ============
const serverTab = ref('today')

// ============ 全部游戏 Tab ============
const gameTab = ref('all')

const filteredGames = computed(() => {
  if (gameTab.value === 'all') return props.allGames.data || []
  if (gameTab.value === 'new') return props.recommendedGames?.slice(0, 12) || []
  if (gameTab.value === 'recommend') return props.hotGames?.slice(0, 12) || []
  return []
})

// ============ 显示更多全部游戏 ============
const gameLimit = ref(12)
const displayedGames = computed(() => filteredGames.value.slice(0, gameLimit.value))
const hasMore = computed(() => gameLimit.value < filteredGames.value.length)

const loadMore = () => { gameLimit.value += 12 }

// Tab 切换时重置显示数量
const switchGameTab = (tab) => {
  gameTab.value = tab
  gameLimit.value = 12
}

// ============ 获取游戏标签 ============
const gameTag = (game) => {
  if (game.is_hot) return { text: '火爆', class: 'from-red-500 to-orange-500' }
  if (game.is_new) return { text: '新游', class: 'from-green-500 to-emerald-500' }
  if (game.is_recommend) return { text: '推荐', class: 'from-purple-500 to-pink-500' }
  if (game.game_type === '微端') return { text: '微端', class: 'from-blue-500 to-cyan-500' }
  return { text: '页游', class: 'from-orange-500 to-red-500' }
}
</script>

<template>
  <Default>
    <!-- ==================== Banner ==================== -->
    <div class="relative bg-gradient-to-b from-[#2a1a0f] via-[#1a1410] to-[#0f0a08] overflow-hidden">
      <!-- 背景装饰 -->
      <div class="absolute inset-0 opacity-20 pointer-events-none">
        <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-gradient-to-br from-[#ff7a00] to-[#ff4a1f] rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-gradient-to-tl from-[#ffd700] to-[#ff7a00] rounded-full blur-3xl"></div>
      </div>

      <div class="w-[1200px] mx-auto py-4 relative z-10">
        <div class="relative h-[500px] rounded-lg overflow-hidden shadow-2xl">
          <!-- Banner slides -->
          <a v-for="(item, i) in bannerList" :key="i" :href="getBannerUrl(item)"
            class="absolute inset-0 transition-opacity duration-700"
            :class="i === currentSlide ? 'opacity-100' : 'opacity-0'">
            <img v-if="item.image" :src="item.image" :alt="item.title" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full bg-gradient-to-r from-gray-800 to-gray-700"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-black/40"></div>
          </a>

          <!-- 登录浮层 - 左侧 -->
          <div class="absolute left-8 top-1/2 -translate-y-1/2 w-[270px] z-20">
            <div class="bg-gradient-to-br from-black/70 to-black/60 backdrop-blur-md rounded-lg overflow-hidden shadow-2xl border border-white/20">
              <!-- 未登录 -->
              <div v-if="!user">
                <div class="p-5">
                  <div class="text-[17px] font-bold text-white mb-5 text-center">账号登录</div>
                  <form @submit.prevent="submitLogin" class="space-y-3 mb-5">
                    <input v-model="loginForm.username" type="text" required placeholder="请输入账号"
                      class="w-full h-[42px] px-3 rounded bg-white/10 border border-white/30 text-white text-[14px] placeholder-gray-300 focus:outline-none focus:border-[#ff7a00] focus:bg-white/15 transition-all" />
                    <div class="relative">
                      <input :type="showPassword ? 'text' : 'password'" v-model="loginForm.password" required placeholder="请输入密码"
                        class="w-full h-[42px] px-3 pr-10 rounded bg-white/10 border border-white/30 text-white text-[14px] placeholder-gray-300 focus:outline-none focus:border-[#ff7a00] focus:bg-white/15 transition-all" />
                      <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white transition-colors text-xs">{{
                          showPassword ? '隐藏' : '显示' }}</button>
                    </div>
                    <div v-if="loginForm.errors.username || loginForm.errors.password"
                      class="bg-red-500/20 text-red-300 text-xs p-2 rounded">
                      {{ loginForm.errors.username || loginForm.errors.password }}
                    </div>
                    <button type="submit" :disabled="loginForm.processing"
                      class="w-full bg-gradient-to-r from-[#ff7a00] to-[#ff4a1f] hover:from-[#ff8a10] hover:to-[#ff5a2f] text-white text-[16px] font-bold py-3.5 rounded-lg transition-all shadow-lg hover:shadow-xl disabled:opacity-50">
                      {{ loginForm.processing ? '登录中...' : '立即登录' }}
                    </button>
                  </form>
                  <div class="flex items-center justify-between text-[13px]">
                    <Link href="/register" class="text-gray-300 hover:text-[#ff7a00] transition-colors">注册账号</Link>
                    <span class="text-gray-500">|</span>
                    <Link href="/forgot-password" class="text-gray-300 hover:text-[#ff7a00] transition-colors">忘记密码？</Link>
                  </div>
                </div>
              </div>

              <!-- 已登录 -->
              <div v-else>
                <div class="p-5 border-b border-white/20 bg-gradient-to-r from-[#ff7a00]/20 to-transparent">
                  <div class="flex items-center gap-3 mb-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#ff7a00] to-[#ff4a1f] flex items-center justify-center ring-2 ring-[#ffd700]">
                      <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                    <div class="flex-1">
                      <div class="text-[17px] font-bold text-white mb-1">{{ user.username }}</div>
                      <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        <span class="text-[13px] text-green-400">已实名认证</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 最近在玩 -->
                <div class="p-4 border-b border-white/20">
                  <div class="text-[13px] font-bold text-white mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#ff7a00]" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z" />
                    </svg>
                    最近在玩
                  </div>
                  <div v-if="props.recentRoleReports?.length" class="space-y-2">
                    <a v-for="r in props.recentRoleReports.slice(0, 3)" :key="r.id"
                      :href="'/game/play/' + r.game_id + '?server_id=' + r.server_id"
                      class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-white/10 transition-all group">
                      <div v-if="r.game?.logo" class="w-8 h-8 rounded overflow-hidden flex-shrink-0">
                        <img :src="r.game.logo" :alt="r.game.name" class="w-full h-full object-cover" />
                      </div>
                      <div v-else class="w-8 h-8 bg-gradient-to-br from-red-500 to-orange-500 rounded flex-shrink-0"></div>
                      <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-medium text-white truncate">{{ r.game?.name || '未知游戏' }}</div>
                        <div class="text-[11px] text-gray-300">{{ r.server_name || '区服未知' }}</div>
                      </div>
                    </a>
                  </div>
                  <div v-else class="text-[12px] text-gray-400 text-center py-2">最近还没有玩过游戏</div>
                </div>

                <!-- 快捷入口 -->
                <div class="p-4">
                  <div class="grid grid-cols-2 gap-2 text-[12px] mb-2">
                    <Link href="/user" class="text-center py-2 rounded bg-white/10 text-white hover:bg-white/20 hover:text-[#ff7a00] transition-colors">个人中心</Link>
                    <Link href="/user/orders" class="text-center py-2 rounded bg-white/10 text-white hover:bg-white/20 hover:text-[#ff7a00] transition-colors">充值记录</Link>
                  </div>
                  <button @click="router.post('/logout')"
                    class="w-full text-center py-2 rounded bg-white/10 text-gray-300 hover:bg-white/20 hover:text-red-400 transition-colors text-[12px]">
                    退出登录
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 主推游戏列表 - 右侧 -->
          <div class="absolute right-6 top-6 bottom-6 w-[200px] bg-black/50 backdrop-blur-md rounded-lg border border-white/20 overflow-hidden z-10">
            <div class="p-3 border-b border-white/20 bg-gradient-to-r from-[#ff7a00]/30 to-transparent">
              <div class="text-[14px] font-bold text-white flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#ffd700]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                </svg>
                主推游戏
              </div>
            </div>
            <div class="p-2 space-y-1">
              <a v-for="(game, i) in (props.hotGames || []).slice(0, 6)" :key="game.id" :href="'/games/' + game.id"
                :class="['block px-3 py-2.5 rounded transition-all', i === currentSlide ? 'bg-gradient-to-r from-[#ff7a00] to-[#ff4a1f] text-white shadow-lg' : 'hover:bg-white/10 text-white/80 hover:text-white']">
                <div class="flex items-center justify-between">
                  <span class="text-[13px] font-medium">{{ game.name }}</span>
                  <span :class="['text-[10px] px-2 py-0.5 rounded', i === currentSlide ? 'bg-white/30 text-white' : 'bg-red-500/80 text-white']">
                    {{ game.is_hot ? '火爆' : '热门' }}
                  </span>
                </div>
              </a>
            </div>
          </div>

          <!-- 指示器 -->
          <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-20">
            <button v-for="(_, i) in bannerList" :key="i" @click="goToSlide(i)"
              :class="['h-2 rounded-full transition-all', i === currentSlide ? 'bg-[#ff7a00] w-8' : 'bg-white/50 hover:bg-white/80 w-2']" />
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== 主体内容 ==================== -->
    <div class="bg-[#ebebeb]">
      <div class="w-[1200px] mx-auto pt-2 pb-3">

        <!-- ======== 精品推荐 ======== -->
        <div v-if="hotGames?.length" class="mb-3">
          <div class="bg-white rounded-t-lg px-4 py-2.5 border-l-4 border-[#ff7a00] flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-[#ff7a00]" fill="currentColor" viewBox="0 0 24 24">
                <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67z" />
              </svg>
              <h2 class="text-[18px] font-bold text-gray-800">精品推荐</h2>
            </div>
            <Link href="/games" class="text-[13px] text-gray-500 hover:text-[#ff7a00] transition-colors">更多 →</Link>
          </div>
          <div class="bg-white rounded-b-lg p-3 shadow-sm">
            <div class="grid grid-cols-6 gap-2">
              <div v-for="game in hotGames.slice(0, 6)" :key="game.id" class="group cursor-pointer">
                <Link :href="'/games/' + game.id">
                  <div class="relative h-[105px] overflow-hidden rounded-lg mb-1.5">
                    <img :src="game.logo || ''" :alt="game.name"
                      class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute top-1 right-1">
                      <div class="text-white text-[9px] font-bold px-1.5 py-0.5 rounded"
                        :class="'bg-gradient-to-r ' + gameTag(game).class">
                        {{ gameTag(game).text }}
                      </div>
                    </div>
                    <div class="absolute bottom-1.5 left-1.5 right-1.5">
                      <h3 class="text-white text-[12px] font-bold drop-shadow-lg mb-0.5 truncate">{{ game.name }}</h3>
                      <p class="text-white/90 text-[9px] drop-shadow-lg truncate">{{ game.game_type }} · {{ game.category?.name || '热血PK' }}</p>
                    </div>
                  </div>
                  <div class="block w-full text-center text-[11px] text-gray-600 hover:text-[#ff7a00] font-medium py-1 border border-gray-200 rounded hover:border-[#ff7a00] hover:bg-[#fff5f0] transition-all">
                    进入游戏
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- ======== 最新开服 + 热门推荐 ======== -->
        <div class="grid grid-cols-[340px_1fr] gap-3 mb-3">
          <!-- 左侧：开服表 -->
          <div>
            <div class="bg-white rounded-lg shadow-sm">
              <div class="bg-white border-l-4 border-[#ff7a00] rounded-t-lg px-3 py-2.5">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5">
                    <svg class="w-5 h-5 text-[#ff7a00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-[17px] font-bold text-gray-800">最新开服</h3>
                  </div>
                  <div class="flex gap-0.5 bg-gray-100 rounded p-0.5">
                    <button @click="serverTab = 'today'"
                      :class="['px-3 py-0.5 text-[12px] font-medium rounded transition-all', serverTab === 'today' ? 'bg-[#ff7a00] text-white' : 'text-gray-600 hover:text-gray-800']">
                      今日开服
                    </button>
                    <button @click="serverTab = 'upcoming'"
                      :class="['px-3 py-0.5 text-[12px] font-medium rounded transition-all', serverTab === 'upcoming' ? 'bg-[#ff7a00] text-white' : 'text-gray-600 hover:text-gray-800']">
                      新服预告
                    </button>
                  </div>
                </div>
              </div>
              <div>
                <!-- 今日开服 -->
                <div v-if="serverTab === 'today'">
                  <div v-for="server in latestServers" :key="server.id"
                    class="flex items-center gap-2.5 px-3 py-2.5 border-b border-gray-100 last:border-b-0 hover:bg-[#fff5f0] transition-all group">
                    <div class="w-[48px] text-[14px] font-medium text-gray-600 flex-shrink-0">
                      {{ new Date(server.open_time).toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }) }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-[14px] font-bold text-gray-800 group-hover:text-[#ff7a00] transition-colors truncate">
                        {{ server.game?.name }}
                      </div>
                      <div class="text-[12px] text-gray-500">{{ server.name }}</div>
                    </div>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                      :class="server.status === 1 ? 'bg-red-100 text-red-600' : server.is_recommend ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'">
                      {{ server.status === 1 ? '火爆' : server.is_recommend ? '推荐' : '新服' }}
                    </span>
                    <Link :href="'/game/play/' + server.game_id + '?server_id=' + server.id"
                      class="text-[11px] text-white font-medium px-3 py-1 bg-gradient-to-r from-[#ff7a00] to-[#ff4a1f] rounded hover:shadow-md transition-all flex-shrink-0">
                      进入
                    </Link>
                  </div>
                  <div v-if="!latestServers?.length" class="text-center py-6 text-gray-400 text-[13px]">暂无开服信息</div>
                </div>
                <!-- 新服预告 -->
                <div v-else>
                  <div v-for="(server, i) in (latestServers || []).slice(0, 8)" :key="i"
                    class="flex items-center gap-2.5 px-3 py-2.5 border-b border-gray-100 last:border-b-0 hover:bg-[#fff5f0] transition-all group">
                    <div class="w-[48px] text-[11px] text-gray-500 flex-shrink-0">
                      <div>{{ i === 0 ? '明日' : i === 1 ? '后天' : '即将' }}</div>
                      <div class="font-medium text-gray-700">{{ new Date(server.open_time).toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' }) }}</div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-[14px] font-bold text-gray-800 group-hover:text-[#ff7a00] transition-colors truncate">{{ server.game?.name }}</div>
                      <div class="text-[12px] text-gray-500">{{ server.name }}</div>
                    </div>
                    <div class="text-[11px] bg-[#ffd700]/20 text-[#ff7a00] px-2 py-0.5 rounded font-medium flex-shrink-0">开服礼包</div>
                    <Link :href="'/games/' + server.game_id"
                      class="text-[11px] text-[#ff7a00] font-medium px-3 py-1 border border-[#ff7a00] rounded hover:bg-[#ff7a00] hover:text-white transition-all flex-shrink-0">
                      预约
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 右侧：热门推荐 -->
          <div class="flex-1 bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="bg-white border-l-4 border-[#ff7a00] rounded-t-lg px-3 py-2.5 flex items-center justify-between">
              <div class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#ff7a00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <h3 class="text-[15px] font-bold text-gray-800">热门推荐</h3>
              </div>
              <Link href="/games" class="text-[11px] text-gray-500 hover:text-[#ff7a00] transition-colors">查看全部 →</Link>
            </div>
            <div class="p-2 grid grid-cols-4 gap-2">
              <div v-for="game in (hotGames || []).slice(0, 8)" :key="game.id" class="group cursor-pointer">
                <Link :href="'/games/' + game.id">
                  <div class="relative h-[95px] rounded overflow-hidden mb-1.5">
                    <img :src="game.logo || ''" :alt="game.name"
                      class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute top-1 right-1 text-white text-[8px] font-bold px-1.5 py-0.5 rounded-full"
                      :class="game.is_hot ? 'bg-red-500/90' : game.is_new ? 'bg-green-500/90' : game.is_recommend ? 'bg-purple-500/90' : 'bg-orange-500/90'">
                      {{ game.is_hot ? '火爆' : game.is_new ? '新游' : game.is_recommend ? '推荐' : '热门' }}
                    </div>
                    <div class="absolute bottom-1.5 left-1.5 right-1.5">
                      <div class="text-white text-[11px] font-bold drop-shadow-lg mb-0.5">{{ game.name }}</div>
                      <div class="text-white/90 text-[9px] drop-shadow-lg truncate">{{ game.game_type }} · 热血PK</div>
                    </div>
                  </div>
                  <div class="flex items-center justify-between px-0.5">
                    <span class="text-[9px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ game.game_type }}</span>
                    <span class="text-[9px] text-gray-500 hover:text-[#ff7a00] font-medium transition-colors">进入 →</span>
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- ======== 全部游戏 ======== -->
        <div class="mb-3">
          <div class="bg-white rounded-t-lg px-4 py-2.5 border-l-4 border-[#ff7a00] flex items-center justify-between">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-[#ff7a00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              <h2 class="text-[18px] font-bold text-gray-800">全部游戏</h2>
            </div>
          </div>
          <div class="bg-white rounded-b-lg p-3 shadow-sm">
            <!-- Tab 导航 -->
            <div class="border-b border-gray-200">
              <div class="flex">
                <button @click="switchGameTab('all')"
                  :class="['flex items-center gap-1.5 px-5 py-3 text-[16px] font-bold transition-all relative', gameTab === 'all' ? 'text-[#ff7a00]' : 'text-gray-600 hover:text-gray-800']">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                  </svg>
                  全部游戏
                  <div v-if="gameTab === 'all'" class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-[#ff7a00] to-[#ff4a1f] rounded-t-full"></div>
                </button>
                <button @click="switchGameTab('new')"
                  :class="['flex items-center gap-1.5 px-5 py-3 text-[16px] font-bold transition-all relative', gameTab === 'new' ? 'text-[#ff7a00]' : 'text-gray-600 hover:text-gray-800']">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                  </svg>
                  新游上线
                  <div v-if="gameTab === 'new'" class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-[#ff7a00] to-[#ff4a1f] rounded-t-full"></div>
                </button>
                <button @click="switchGameTab('recommend')"
                  :class="['flex items-center gap-1.5 px-5 py-3 text-[16px] font-bold transition-all relative', gameTab === 'recommend' ? 'text-[#ff7a00]' : 'text-gray-600 hover:text-gray-800']">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67z" />
                  </svg>
                  精品推荐
                  <div v-if="gameTab === 'recommend'" class="absolute bottom-0 left-0 right-0 h-[3px] bg-gradient-to-r from-[#ff7a00] to-[#ff4a1f] rounded-t-full"></div>
                </button>
              </div>
            </div>

            <!-- 游戏网格 -->
            <div class="p-3 grid grid-cols-4 gap-3">
              <div v-for="game in displayedGames" :key="game.id" class="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:border-[#ff7a00] hover:shadow-lg transition-all cursor-pointer">
                <Link :href="'/games/' + game.id">
                  <div class="relative h-[140px] overflow-hidden">
                    <img :src="game.logo || ''" :alt="game.name"
                      class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute top-0 right-0">
                      <div class="text-white text-[11px] font-bold px-2.5 py-0.5 rounded-bl-lg"
                        :class="'bg-gradient-to-r ' + gameTag(game).class">
                        {{ gameTag(game).text }}
                      </div>
                    </div>
                    <div class="absolute bottom-2 left-2 right-2">
                      <h4 class="text-white text-[15px] font-bold mb-0.5 drop-shadow-lg">{{ game.name }}</h4>
                      <p class="text-white/90 text-[12px] drop-shadow-lg truncate">{{ game.description || game.game_type + ' · 热血PK' }}</p>
                    </div>
                  </div>
                  <div class="p-2 bg-[#fafafa]">
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-[12px] bg-white text-gray-600 px-2 py-0.5 rounded border border-gray-200">{{ game.game_type }}</span>
                    </div>
                    <span class="block w-full text-center text-[13px] text-gray-600 hover:text-[#ff7a00] font-medium py-1.5 border border-gray-200 rounded hover:border-[#ff7a00] transition-all">
                      进入游戏
                    </span>
                  </div>
                </Link>
              </div>
            </div>

            <!-- 加载更多 -->
            <div v-if="hasMore" class="px-3 pb-3">
              <button @click="loadMore"
                class="w-full text-[14px] text-gray-600 hover:text-[#ff7a00] py-2.5 border border-gray-200 rounded hover:border-[#ff7a00] transition-all font-medium">
                加载更多游戏
              </button>
            </div>
            <Pagination v-else-if="allGames.links?.length > 3" :links="allGames.links" />
          </div>
        </div>

        <!-- ======== 平台公告 ======== -->
        <div v-if="notices?.length" class="mb-3">
          <div class="bg-white rounded-t-lg px-4 py-2.5 border-l-4 border-[#ff7a00] flex items-center justify-between">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-[#ff7a00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <h2 class="text-[18px] font-bold text-gray-800">平台公告</h2>
            </div>
            <Link href="/notices" class="text-[13px] text-gray-500 hover:text-[#ff7a00] transition-colors">查看全部 →</Link>
          </div>
          <div class="bg-white rounded-b-lg shadow-sm divide-y">
            <Link v-for="notice in notices" :key="notice.id" :href="'/notices/' + notice.id"
              class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
              <span v-if="notice.is_top" class="text-xs bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded shrink-0">置顶</span>
              <span class="text-sm font-medium text-gray-900 truncate flex-1">{{ notice.title }}</span>
              <span class="text-xs text-gray-400 shrink-0">{{ notice.published_at }}</span>
            </Link>
          </div>
        </div>

      </div>
    </div>
  </Default>
</template>
