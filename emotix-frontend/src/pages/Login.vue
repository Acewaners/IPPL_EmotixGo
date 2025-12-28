<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuth } from '../stores/auth'
import { api } from '../lib/api' // Pastikan import api ada
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'

const r = useRouter()
const auth = useAuth()

// --- STATE LOGIN ---
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')

// --- STATE RESET PASSWORD ---
const isResetMode = ref(false) // Toggle antara Login vs Reset
const resetForm = ref({
  email: '',
  old_password: '',
  new_password: ''
})

// --- FUNCTION LOGIN ---
const submitLogin = async () => {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    success.value = 'Login berhasil! Mengalihkan...'
    setTimeout(() => { r.push('/') }, 1000)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Email atau password salah.'
  } finally {
    loading.value = false
  }
}

// --- FUNCTION RESET PASSWORD ---
const submitReset = async () => {
  error.value = ''
  success.value = ''
  
  // Validasi sederhana
  if (!resetForm.value.email || !resetForm.value.old_password || !resetForm.value.new_password) {
    error.value = 'Mohon lengkapi semua kolom.'
    return
  }

  loading.value = true
  try {
    // Sesuaikan endpoint ini dengan backend Anda
    // Contoh: POST /auth/reset-password
    await api.post('/auth/reset-password', {
      email: resetForm.value.email,
      old_password: resetForm.value.old_password,
      new_password: resetForm.value.new_password
    })

    success.value = 'Password berhasil diubah! Silakan login.'
    
    // Kembalikan ke mode login setelah sukses
    setTimeout(() => {
      toggleMode()
      success.value = '' // Clear success msg di login form
    }, 2000)

  } catch (e) {
    error.value = e?.response?.data?.message || 'Gagal mengubah password. Cek kembali data Anda.'
  } finally {
    loading.value = false
  }
}

// --- TOGGLE MODE ---
const toggleMode = () => {
  isResetMode.value = !isResetMode.value
  error.value = ''
  success.value = ''
  // Auto-fill email di form reset jika user sudah ketik di login
  if (isResetMode.value) {
    resetForm.value.email = email.value
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 flex items-center justify-center p-4 md:p-8">
      
      <div class="w-full max-w-5xl bg-white rounded-[2rem] shadow-2xl shadow-gray-200/50 overflow-hidden flex flex-col md:flex-row min-h-[600px] border border-gray-100 transition-all duration-500">
        
        <div class="md:w-1/2 bg-black text-white p-12 flex flex-col justify-between relative overflow-hidden transition-colors duration-500">
          
          <div class="absolute top-0 right-0 w-64 h-64 bg-gray-800 rounded-full blur-3xl opacity-30 -translate-y-1/2 translate-x-1/2"></div>
          <div class="absolute bottom-0 left-0 w-64 h-64 bg-gray-800 rounded-full blur-3xl opacity-30 translate-y-1/2 -translate-x-1/2"></div>

          <div class="relative z-10">
            <p class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-2">
              {{ isResetMode ? 'Security Center' : 'Welcome Back' }}
            </p>
            <h2 class="text-4xl font-black tracking-tight leading-tight transition-all duration-300">
              {{ isResetMode ? 'Update your' : 'Log in to your' }} <br /> 
              {{ isResetMode ? 'credentials.' : 'account.' }}
            </h2>
          </div>

          <div class="flex-1 flex items-center justify-center relative z-10 py-10">
             <div class="w-40 h-40 bg-white/10 backdrop-blur-sm rounded-3xl flex items-center justify-center border border-white/20 shadow-2xl transition-transform hover:scale-105 duration-500">
                <img src="/logo.png" alt="Emotix" class="w-24 h-24 object-contain drop-shadow-md" />
             </div>
          </div>

          <div class="relative z-10">
            <p class="text-gray-400 text-sm leading-relaxed">
              {{ isResetMode 
                ? 'Keep your account secure by updating your password regularly.' 
                : 'Experience the future of shopping with Emotix. Secure, fast, and intelligent.' 
              }}
            </p>
          </div>
        </div>

        <div class="md:w-1/2 p-8 md:p-16 flex flex-col justify-center bg-white relative">
          
          <div class="max-w-sm mx-auto w-full space-y-8">
            <div class="text-center md:text-left">
              <h1 class="text-2xl font-bold text-gray-900">
                {{ isResetMode ? 'Reset Password' : 'Sign In' }}
              </h1>
              <p class="text-sm text-gray-500 mt-1">
                {{ isResetMode ? 'Enter your old password to validate.' : 'Enter your details below' }}
              </p>
            </div>

            <form @submit.prevent="isResetMode ? submitReset() : submitLogin()" class="space-y-5">
              
              <div v-if="success" class="p-3 rounded-lg bg-green-50 text-green-700 text-sm font-medium border border-green-100 flex items-center gap-2 animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                {{ success }}
              </div>
              <div v-if="error" class="p-3 rounded-lg bg-red-50 text-red-700 text-sm font-medium border border-red-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                {{ error }}
              </div>

              <div v-if="!isResetMode" class="space-y-4 transition-all duration-300">
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
                
                <div class="flex items-center justify-end">
                  <button type="button" @click="toggleMode" class="text-xs font-medium text-gray-500 hover:text-black transition-colors underline decoration-gray-300 hover:decoration-black">
                    Reset Password?
                  </button>
                </div>
              </div>

              <div v-else class="space-y-4 transition-all duration-300">
                <div class="space-y-1">
                  <input
                    v-model="resetForm.email"
                    type="email"
                    class="w-full bg-gray-100 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none text-gray-500 cursor-not-allowed"
                    placeholder="Email Address"
                    readonly
                  />
                </div>
                <div class="space-y-1">
                  <input
                    v-model="resetForm.old_password"
                    type="password"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
                    placeholder="Old Password"
                    required
                  />
                </div>
                <div class="space-y-1">
                  <input
                    v-model="resetForm.new_password"
                    type="password"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition-all placeholder-gray-400"
                    placeholder="New Password"
                    required
                  />
                </div>

                <div class="flex items-center justify-end">
                  <button type="button" @click="toggleMode" class="text-xs font-medium text-gray-500 hover:text-black transition-colors flex items-center gap-1">
                    <span>&larr;</span> Back to Login
                  </button>
                </div>
              </div>

              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-black text-white font-bold text-sm py-4 rounded-full shadow-lg hover:bg-gray-800 hover:shadow-xl hover:-translate-y-0.5 active:scale-95 transition-all disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0"
              >
                <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></span>
                {{ loading ? 'Processing...' : (isResetMode ? 'Update Password' : 'Log In') }}
              </button>

              <p v-if="!isResetMode" class="text-center text-sm text-gray-500 mt-6">
                Don't have an account?
                <RouterLink to="/register" class="text-black font-bold hover:underline ml-1">
                  Sign Up
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