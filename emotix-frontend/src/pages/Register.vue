<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuth } from '../stores/auth'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'

const r = useRouter()
const auth = useAuth()

const name = ref('')
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

// URL Backend untuk Google Login (Sesuaikan dengan route Laravel Anda)
const GOOGLE_AUTH_URL = 'http://localhost:8000/auth/google/redirect' 

const submit = async () => {
  error.value = ''
  loading.value = true
  try {
    // Fungsi register dari store auth
    await auth.register(name.value, email.value, password.value)
    // Jika sukses, redirect ke login
    r.push('/login')
  } catch (e) {
    error.value = e?.response?.data?.message || 'Registrasi gagal. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}

const signUpWithGoogle = () => {
  // Redirect browser ke endpoint Google di Backend
  window.location.href = GOOGLE_AUTH_URL
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 flex items-center justify-center p-4 md:p-8">
      
      <div class="w-full max-w-5xl bg-white rounded-[2rem] shadow-2xl shadow-gray-200/50 overflow-hidden flex flex-col md:flex-row min-h-[600px] border border-gray-100">
        
        <div class="md:w-1/2 bg-black text-white p-12 flex flex-col justify-between relative overflow-hidden order-last md:order-first">
          <div class="absolute top-0 right-0 w-64 h-64 bg-gray-800 rounded-full blur-3xl opacity-30 -translate-y-1/2 translate-x-1/2"></div>
          <div class="absolute bottom-0 left-0 w-64 h-64 bg-gray-800 rounded-full blur-3xl opacity-30 translate-y-1/2 -translate-x-1/2"></div>

          <div class="relative z-10">
            <p class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-2">Join Us</p>
            <h2 class="text-4xl font-black tracking-tight leading-tight">
              Create your <br /> free account.
            </h2>
          </div>

          <div class="flex-1 flex items-center justify-center relative z-10 py-10">
             <div class="w-40 h-40 bg-white/10 backdrop-blur-sm rounded-3xl flex items-center justify-center border border-white/20 shadow-2xl transition-transform hover:scale-105 duration-700">
                <img src="/logo.png" alt="Emotix" class="w-24 h-24 object-contain drop-shadow-md" />
             </div>
          </div>

          <div class="relative z-10">
            <p class="text-gray-400 text-sm leading-relaxed">
              Unlock exclusive deals, track your orders, and enjoy a personalized shopping experience.
            </p>
          </div>
        </div>

        <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
          
          <div class="max-w-sm mx-auto w-full space-y-6">
            <div class="text-center md:text-left">
              <h1 class="text-2xl font-bold text-gray-900">Sign Up</h1>
              <p class="text-sm text-gray-500 mt-1">Enter your details to get started</p>
            </div>

            <div v-if="error" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm font-medium border border-red-100 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
              {{ error }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              
              <div class="space-y-1">
                <input
                  v-model="name"
                  type="text"
                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
                  placeholder="Full Name"
                  required
                />
              </div>

              <div class="space-y-1">
                <input
                  v-model="email"
                  type="email"
                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
                  placeholder="Email Address"
                  required
                />
              </div>

              <div class="space-y-1">
                <input
                  v-model="password"
                  type="password"
                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
                  placeholder="Password"
                  required
                />
              </div>

              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-black text-white font-bold text-sm py-4 rounded-full shadow-lg hover:bg-gray-800 hover:shadow-xl hover:-translate-y-0.5 active:scale-95 transition-all disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0 mt-2"
              >
                <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
                {{ loading ? 'Creating Account...' : 'Create Account' }}
              </button>

              <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink-0 mx-4 text-gray-400 text-xs uppercase font-bold">Or continue with</span>
                <div class="flex-grow border-t border-gray-200"></div>
              </div>

              <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account?
                <RouterLink to="/login" class="text-black font-bold hover:underline ml-1">
                  Log In
                </RouterLink>
              </p>

            </form>
          </div>
        </div>

      </div>
    </main>

    <Footer />
  </div>
</template>