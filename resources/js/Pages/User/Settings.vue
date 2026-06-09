<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

const props = defineProps({ user: Object })

const form = useForm({
  mobile: props.user.mobile || '',
  email: props.user.email || '',
  real_name: '',
  id_card: '',
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const isVerified = props.user.id_card_verified_at

const submit = () => {
  form.put('/user/settings', { preserveScroll: true })
}
</script>

<template>
  <Default>
    <div class="max-w-7xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">账户设置</h1>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
          <div class="bg-white rounded-xl shadow p-6">
            <nav class="space-y-2">
              <Link :href="'/user'" class="block px-4 py-2 rounded hover:bg-gray-100 text-gray-700">个人主页</Link>
              <Link :href="'/user/settings'" class="block px-4 py-2 rounded bg-gray-100 text-orange-600 font-medium">账户设置</Link>
              <Link :href="'/user/orders'" class="block px-4 py-2 rounded hover:bg-gray-100 text-gray-700">充值记录</Link>
            </nav>
          </div>
        </div>
        <div class="md:col-span-2">
          <div class="bg-white rounded-xl shadow p-6">
            <div v-if="$page.props.flash?.success" class="bg-green-50 text-green-600 p-3 rounded mb-4 text-sm">
              {{ $page.props.flash.success }}
            </div>
            <form @submit.prevent="submit" class="space-y-4 max-w-md">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                <input :value="user.username" disabled
                  class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">手机号</label>
                <input v-model="form.mobile" type="tel"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
                <input v-model="form.email" type="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" />
              </div>
              <!-- 实名认证 -->
              <template v-if="!isVerified">
                <hr class="my-4" />
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-3 rounded mb-4">
                  实名认证后才能进入游戏。信息提交后将对接国家新闻出版署实名认证系统。
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">真实姓名</label>
                    <input v-model="form.real_name" type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">身份证号</label>
                    <input v-model="form.id_card" type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" />
                  </div>
                </div>
              </template>
              <div v-else class="bg-green-50 border border-green-200 text-green-700 text-sm p-3 rounded mb-4">
                ✅ 已实名认证 ({{ user.id_card_verified_at }})
              </div>

              <hr class="my-4" />
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">当前密码（修改密码时需要）</label>
                <input v-model="form.current_password" type="password"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">新密码</label>
                <input v-model="form.new_password" type="password"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">确认新密码</label>
                <input v-model="form.new_password_confirmation" type="password"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" />
              </div>
              <button type="submit" :disabled="form.processing"
                class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 disabled:opacity-50 transition">
                {{ form.processing ? '保存中...' : '保存设置' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </Default>
</template>
