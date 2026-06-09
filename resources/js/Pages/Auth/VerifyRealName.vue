<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

const props = defineProps({
  user: Object,
  redirect: String,
})

const isVerified = !!props.user?.id_card_verified_at

const form = useForm({
  real_name: '',
  id_card: '',
})

const submit = () => {
  form.post('/user/verify-real-name', {
    preserveScroll: true,
    onSuccess: () => {
      if (props.redirect) {
        router.visit(props.redirect)
      }
    },
  })
}
</script>

<template>
  <Default>
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-8">
      <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow p-8">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">实名认证</h1>
          </div>

          <!-- Flash messages -->
          <div v-if="$page.props.flash?.success"
            class="bg-green-50 text-green-600 p-3 rounded-lg mb-4 text-sm text-center">
            {{ $page.props.flash.success }}
          </div>
          <div v-if="$page.props.flash?.error"
            class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm text-center">
            {{ $page.props.flash.error }}
          </div>

          <!-- Verified state -->
          <template v-if="isVerified">
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-4 rounded-lg mb-6 text-center">
              <p class="font-medium mb-1">✅ 已实名认证</p>
              <p class="text-xs text-green-500">认证时间：{{ user.id_card_verified_at }}</p>
            </div>
            <div v-if="redirect" class="text-center">
              <a :href="redirect"
                class="inline-block bg-orange-500 text-white px-8 py-2.5 rounded-lg hover:bg-orange-600 transition font-medium">
                开始游戏
              </a>
            </div>
            <div v-else class="text-center">
              <Link href="/"
                class="inline-block bg-orange-500 text-white px-8 py-2.5 rounded-lg hover:bg-orange-600 transition font-medium">
                返回首页
              </Link>
            </div>
          </template>

          <!-- Unverified form -->
          <template v-else>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-3 rounded-lg mb-6">
              实名认证后才能进入游戏。信息提交后将对接国家新闻出版署实名认证系统。
            </div>

            <form @submit.prevent="submit" class="space-y-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">真实姓名</label>
                <input v-model="form.real_name" type="text" required
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition" />
                <p v-if="form.errors.real_name" class="text-red-500 text-xs mt-1">{{ form.errors.real_name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">身份证号</label>
                <input v-model="form.id_card" type="text" required maxlength="18"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition" />
                <p v-if="form.errors.id_card" class="text-red-500 text-xs mt-1">{{ form.errors.id_card }}</p>
              </div>
              <button type="submit" :disabled="form.processing"
                class="w-full bg-orange-500 text-white py-2.5 rounded-lg hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium">
                {{ form.processing ? '提交中...' : '提交认证' }}
              </button>
            </form>

            <div class="mt-6 text-center">
              <Link href="/" class="text-sm text-gray-500 hover:text-gray-700 transition">
                返回首页
              </Link>
            </div>
          </template>
        </div>
      </div>
    </div>
  </Default>
</template>
