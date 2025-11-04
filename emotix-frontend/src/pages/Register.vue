<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuth } from '../stores/auth'
import AuthLayout from '../layouts/AuthLayout.vue'

const r = useRouter()
const auth = useAuth()
const name = ref(''); const email = ref(''); const password = ref('')
const loading = ref(false); const error = ref('')

const submit = async () => {
  error.value=''; loading.value=true
  try {
    await auth.register(name.value, email.value, password.value)
    r.push('/login')
  } catch (e) {
    error.value = e?.response?.data?.message || 'Register gagal'
  } finally { loading.value=false }
}
const signUpWithGoogle = () => alert('Google Sign-In belum dihubungkan')
</script>

<template>
  <AuthLayout title="Create an account" subtitle="Enter your details below">
    <form @submit.prevent="submit" class="max-w-md w-full space-y-5">
      <input v-model="name" placeholder="Name" class="w-full h-12 border border-gray-300 rounded-md px-4 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-black/10" required />
      <input v-model="email" type="email" placeholder="Email or Phone Number" class="w-full h-12 border border-gray-300 rounded-md px-4 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-black/10" required />
      <input v-model="password" type="password" placeholder="Password" class="w-full h-12 border border-gray-300 rounded-md px-4 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-black/10" required />
      <button :disabled="loading" class="w-full h-12 bg-red-500 text-white rounded-md text-sm font-medium hover:bg-red-600 disabled:opacity-60">
        {{ loading ? 'Creating...' : 'Create Account' }}
      </button>
      <button type="button" @click="signUpWithGoogle" class="w-full h-12 border border-gray-300 bg-white rounded-md flex items-center justify-center gap-2 text-sm hover:bg-gray-50">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5" />
        Sign up with Google
      </button>
      <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
      <p class="text-sm text-gray-600">
        Already have account?
        <RouterLink to="/login" class="text-black font-medium hover:underline">Log in</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>
