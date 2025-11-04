<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuth } from '../stores/auth'
import AuthLayout from '../layouts/AuthLayout.vue'

const r = useRouter()
const auth = useAuth()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const submit = async () => {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    const role = auth.user?.role
    if (role === 'seller') r.push('/seller/products')
    else if (role === 'buyer') r.push('/buyer/orders')
    else r.push('/')
  } catch (e) {
    error.value = e?.response?.data?.message || 'Unauthorized access'
  } finally {
    loading.value = false
  }
}

const forgot = () => {
  alert('Forgot Password? nanti sambungkan endpoint reset password')
}
</script>

<template>
  <AuthLayout title="Log in to Exclusive" subtitle="Enter your details below">
    <form @submit.prevent="submit" class="max-w-md space-y-6">
      <input
        v-model="email"
        type="email"
        class="w-full border-b py-2 focus:outline-none"
        placeholder="Email or Phone Number"
        required
      />
      <input
        v-model="password"
        type="password"
        class="w-full border-b py-2 focus:outline-none"
        placeholder="Password"
        required
      />

      <div class="flex items-center justify-between">
        <button :disabled="loading" class="bg-red-500 text-white px-6 py-3 rounded-md hover:bg-red-600">
          {{ loading ? 'Logging in...' : 'Log In' }}
        </button>
        <button type="button" @click="forgot" class="text-red-500">Forgot Password?</button>
      </div>

      <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
      <p class="text-sm text-gray-600">
        Don’t have account?
        <RouterLink to="/register" class="text-black font-medium">Sign Up</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>
