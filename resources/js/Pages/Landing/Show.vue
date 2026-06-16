<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  promote: { type: Object, required: true },
})

const themeColor = computed(() => props.promote.landing_theme_color || '#ff7a00')

const bgStyle = computed(() => {
  const bg = props.promote.landing_background
  if (!bg) return { background: '#ffffff' }
  if (bg.startsWith('#')) return { backgroundColor: bg }
  return { backgroundImage: `url(${bg})`, backgroundSize: 'cover', backgroundPosition: 'center' }
})

const page = usePage()
const csrfToken = computed(() => page.props.csrf_token ?? '')

const form = useForm({
  username: '',
  password: '',
  password_confirmation: '',
  mobile: '',
  real_name: '',
  id_card: '',
  promote_code: props.promote.promote_code,
})

const submit = () => {
  form.post('/register')
}

const featureIcons = {
  '🎮': '🎮',
  '🚀': '🚀',
  '⚡': '⚡',
  '🔥': '🔥',
  '💰': '💰',
  '🎯': '🎯',
  '🏆': '🏆',
  '🛡️': '🛡️',
}
</script>

<template>
  <div class="min-h-screen flex flex-col" :style="bgStyle">
    <!-- Hero Section -->
    <div class="flex-1 flex flex-col lg:flex-row items-center justify-center gap-8 px-4 py-12 max-w-6xl mx-auto w-full">
      <!-- Left: Content -->
      <div class="flex-1 w-full max-w-lg">
        <!-- Game name -->
        <div v-if="promote.game_name" class="mb-2">
          <span class="text-sm font-medium px-3 py-1 rounded-full text-white"
                :style="{ backgroundColor: themeColor }">
            {{ promote.game_name }}
          </span>
        </div>

        <!-- Title -->
        <h1 v-if="promote.landing_title" class="text-3xl lg:text-4xl font-bold text-gray-900 mb-3">
          {{ promote.landing_title }}
        </h1>
        <h1 v-else class="text-3xl lg:text-4xl font-bold text-gray-900 mb-3">
          {{ promote.promote_name }}
        </h1>

        <!-- Subtitle -->
        <p v-if="promote.landing_subtitle" class="text-lg text-gray-600 mb-6">
          {{ promote.landing_subtitle }}
        </p>

        <!-- Features -->
        <div v-if="promote.landing_features && promote.landing_features.length" class="space-y-3 mb-8">
          <div v-for="(feature, idx) in promote.landing_features" :key="idx"
               class="flex items-start gap-3">
            <span class="text-xl flex-shrink-0">{{ feature.icon || '🎯' }}</span>
            <div>
              <p class="font-medium text-gray-900">{{ feature.title }}</p>
              <p v-if="feature.description" class="text-sm text-gray-500">{{ feature.description }}</p>
            </div>
          </div>
        </div>

        <!-- Hero Image (mobile, below content) -->
        <img v-if="promote.landing_hero_image"
             :src="promote.landing_hero_image"
             :alt="promote.landing_title || '推广页'"
             class="lg:hidden w-full max-w-sm rounded-xl shadow-lg mb-8" />
      </div>

      <!-- Right: Hero Image (desktop) -->
      <div class="hidden lg:block flex-1 w-full max-w-lg">
        <img v-if="promote.landing_hero_image"
             :src="promote.landing_hero_image"
             :alt="promote.landing_title || '推广页'"
             class="w-full rounded-xl shadow-lg" />
      </div>
    </div>

    <!-- Registration Section -->
    <div class="w-full bg-white py-12 px-4">
      <div class="max-w-md mx-auto">
        <h2 class="text-2xl font-bold text-center text-gray-900 mb-2">注册账号</h2>
        <p class="text-sm text-gray-500 text-center mb-6">注册后即可开始游戏</p>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Server errors -->
          <div v-if="Object.keys(form.errors).length" class="bg-red-50 text-red-600 text-sm p-3 rounded">
            <div v-for="(err, key) in form.errors" :key="key">{{ err }}</div>
          </div>

          <!-- Hidden promote_code -->
          <input type="hidden" name="promote_code" :value="promote.promote_code" />

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">用户名 *</label>
            <input v-model="form.username" type="text" required maxlength="50"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none transition"
                   :class="`focus:ring-[${themeColor}]`"
                   placeholder="3-50位字母或数字" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">密码 *</label>
              <input v-model="form.password" type="password" required minlength="6"
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none transition"
                     :class="`focus:ring-[${themeColor}]`"
                     placeholder="至少6位" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">确认密码 *</label>
              <input v-model="form.password_confirmation" type="password" required
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none transition"
                     :class="`focus:ring-[${themeColor}]`"
                     placeholder="再次输入" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">手机号</label>
            <input v-model="form.mobile" type="tel"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none transition"
                   :class="`focus:ring-[${themeColor}]`"
                   placeholder="选填，可用于找回密码" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">真实姓名</label>
              <input v-model="form.real_name" type="text"
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none transition"
                     :class="`focus:ring-[${themeColor}]`"
                     placeholder="选填" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">身份证号</label>
              <input v-model="form.id_card" type="text"
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none transition"
                     :class="`focus:ring-[${themeColor}]`"
                     placeholder="选填" />
            </div>
          </div>

          <button type="submit" :disabled="form.processing"
                  class="w-full text-white py-3 rounded-lg font-medium text-lg transition disabled:opacity-50 hover:opacity-90"
                  :style="{ backgroundColor: themeColor }">
            {{ form.processing ? '注册中...' : promote.landing_button_text || '立即注册' }}
          </button>

          <p class="text-center text-sm text-gray-500">
            已有账号？
            <a :href="`/login?redirect=${encodeURIComponent('/p/' + promote.promote_code)}`"
               class="hover:underline font-medium"
               :style="{ color: themeColor }">立即登录</a>
          </p>

          <p class="text-xs text-gray-400 text-center mt-4">
            注册即表示同意 <a href="/" class="underline">服务条款</a> 和 <a href="/" class="underline">隐私政策</a>
          </p>
        </form>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-100 py-6 px-4 text-center text-xs text-gray-400">
      <p>602 游戏平台 &copy; {{ new Date().getFullYear() }}</p>
    </footer>
  </div>
</template>
