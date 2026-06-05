<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

const form = useForm({
  username: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login.store'))
}
</script>

<template>
  <Default>
    <div class="min-h-[70vh] flex items-center justify-center px-4">
      <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-gray-900 mb-8">登录 602 游戏平台</h2>

        <form @submit.prevent="submit" class="space-y-5">
          <div v-if="form.errors.username" class="bg-red-50 text-red-600 text-sm p-3 rounded">
            {{ form.errors.username }}
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">账号 / 手机号</label>
            <input v-model="form.username" type="text" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              placeholder="请输入账号或手机号" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
            <input v-model="form.password" type="password" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              placeholder="请输入密码" />
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center text-sm text-gray-600">
              <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 mr-2" />
              记住我
            </label>
            <Link :href="route('password.forgot')" class="text-sm text-orange-600 hover:underline">
              忘记密码?
            </Link>
          </div>

          <button type="submit" :disabled="form.processing"
            class="w-full bg-orange-500 text-white py-2.5 rounded-lg hover:bg-orange-600 disabled:opacity-50 transition font-medium">
            {{ form.processing ? '登录中...' : '登录' }}
          </button>

          <p class="text-center text-sm text-gray-600">
            还没有账号？
            <Link :href="route('register')" class="text-orange-600 hover:underline font-medium">立即注册</Link>
          </p>
        </form>
      </div>
    </div>
  </Default>
</template>
