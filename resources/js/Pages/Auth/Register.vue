<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

const form = useForm({
  username: '',
  password: '',
  password_confirmation: '',
  mobile: '',
  real_name: '',
  id_card: '',
})

const submit = () => {
  form.post(route('register.store'))
}
</script>

<template>
  <Default>
    <div class="min-h-[80vh] flex items-center justify-center px-4 py-8">
      <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-gray-900 mb-8">注册 602 账号</h2>

        <form @submit.prevent="submit" class="space-y-4">
          <div v-if="form.errors.username" class="bg-red-50 text-red-600 text-sm p-3 rounded">
            {{ form.errors.username }}
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">用户名 *</label>
            <input v-model="form.username" type="text" required maxlength="50"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
              placeholder="3-50位字母或数字" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">密码 *</label>
            <input v-model="form.password" type="password" required minlength="6"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
              placeholder="至少6位密码" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">确认密码 *</label>
            <input v-model="form.password_confirmation" type="password" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
              placeholder="再次输入密码" />
          </div>

          <div class="border-t pt-4 mt-4">
            <p class="text-xs text-gray-500 mb-3">实名信息（用于防沉迷系统）</p>
            <div class="mb-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">手机号</label>
              <input v-model="form.mobile" type="tel"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                placeholder="选填，用于找回密码" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">真实姓名</label>
                <input v-model="form.real_name" type="text"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                  placeholder="选填" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">身份证号</label>
                <input v-model="form.id_card" type="text"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                  placeholder="选填" />
              </div>
            </div>
          </div>

          <button type="submit" :disabled="form.processing"
            class="w-full bg-orange-500 text-white py-2.5 rounded-lg hover:bg-orange-600 disabled:opacity-50 transition font-medium">
            {{ form.processing ? '注册中...' : '注册' }}
          </button>

          <p class="text-center text-sm text-gray-600">
            已有账号？
            <Link :href="route('login')" class="text-orange-600 hover:underline font-medium">立即登录</Link>
          </p>
        </form>
      </div>
    </div>
  </Default>
</template>
