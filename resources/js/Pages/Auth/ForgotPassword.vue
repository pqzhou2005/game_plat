<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'

const form = useForm({ email: '' })

const submit = () => {
  form.post('/forgot-password')
}
</script>

<template>
  <Default>
    <div class="min-h-[70vh] flex items-center justify-center px-4">
      <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-gray-900 mb-2">忘记密码</h2>
        <p class="text-center text-gray-500 text-sm mb-8">请输入您的注册邮箱或手机号，我们将发送重置链接</p>
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <input v-model="form.email" type="text" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg"
              placeholder="请输入注册邮箱" />
          </div>
          <button type="submit" :disabled="form.processing"
            class="w-full bg-orange-500 text-white py-2.5 rounded-lg hover:bg-orange-600 disabled:opacity-50">
            {{ form.processing ? '发送中...' : '发送重置链接' }}
          </button>
          <p class="text-center text-sm">
            <Link href="/login" class="text-orange-600 hover:underline">返回登录</Link>
          </p>
        </form>
      </div>
    </div>
  </Default>
</template>
