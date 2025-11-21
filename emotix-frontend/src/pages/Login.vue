<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuth } from '../stores/auth'
import AuthLayout from '../layouts/AuthLayout.vue'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'

const r = useRouter()
const auth = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')   // <- pesan sukses

const submit = async () => {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)

    // TIDAK redirect, cuma kasih pesan sukses
    success.value = 'Anda berhasil login.'

    // kalau mau: auto-redirect setelah beberapa detik
    // setTimeout(() => {
    //   r.push('/')
    // }, 1500)
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
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1 flex items-center justify-center px-4">
      <AuthLayout title="Log in to Exclusive" subtitle="Enter your details below">
        <form @submit.prevent="submit" class="max-w-md space-y-6">
          <!-- pesan sukses & error -->
          <p v-if="success" class="text-green-600 text-sm">
            {{ success }}
          </p>
          <p v-if="error" class="text-red-600 text-sm">
            {{ error }}
          </p>

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
            <button
              :disabled="loading"
              class="bg-red-500 text-white px-6 py-3 rounded-md hover:bg-red-600 disabled:opacity-70"
            >
              {{ loading ? 'Logging in...' : 'Log In' }}
            </button>
            <button type="button" @click="forgot" class="text-red-500">
              Forgot Password?
            </button>
          </div>

          <p class="text-sm text-gray-600">
            Don’t have account?
            <RouterLink to="/register" class="text-black font-medium">Sign Up</RouterLink>
          </p>
        </form>
      </AuthLayout>
    </main>

    <Footer />
  </div>
</template>
