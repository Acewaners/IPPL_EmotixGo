<script setup>
import { ref } from 'vue'
import { useAuth } from '../stores/auth'
import { api } from '../lib/api'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { onMounted } from 'vue'
import { 
  UserIcon, 
  EnvelopeIcon, 
  KeyIcon, 
  ShieldCheckIcon,
  CheckCircleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const auth = useAuth()
const loading = ref(false)
const message = ref('')

const profileForm = ref({ name: auth.user?.name, email: auth.user?.email })
const passwordForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' })

onMounted(async () => {
  if (!auth.user) {
    try {
      const { data } = await api.get('/me')
      auth.user = data // Pastikan store auth terisi data user lengkap
      profileForm.value = { name: data.name, email: data.email }
    } catch (e) {
      console.error('Gagal mengambil data user', e)
    }
  }
})

const updateProfile = async () => {
  loading.value = true
  message.value = ''
  try {
    const { data } = await api.put('/user/update-profile', profileForm.value)
    auth.user = data.user 
    message.value = "Profil berhasil diperbarui!"
  } catch (e) { 
    alert(e.response?.data?.message || 'Gagal update profil') 
  } finally { 
    loading.value = false 
  }
}

const updatePassword = async () => {
  loading.value = true
  message.value = ''
  try {
    await api.put('/user/update-password', passwordForm.value)
    message.value = "Password berhasil diubah!"
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (e) { 
    alert(e.response?.data?.message || 'Gagal ubah password') 
  } finally { 
    loading.value = false 
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#fafafa] flex flex-col font-sans text-gray-900">
    <Navbar />
    
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="mb-10">
        <h1 class="text-4xl font-black tracking-tight text-gray-900">Settings</h1>
        <p class="text-gray-500 mt-2 text-lg">Kelola informasi profil dan keamanan akun Emotix Anda.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
          <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm sticky top-28">
            <div class="flex flex-col items-center text-center">
              <div class="relative group">
                <div class="w-24 h-24 rounded-2xl bg-black flex items-center justify-center text-white text-3xl font-bold shadow-2xl transition-transform group-hover:scale-105 duration-300">
                  {{ auth.user?.name?.charAt(0) || 'U' }}
                </div>
                <div class="absolute -bottom-2 -right-2 bg-green-500 border-4 border-white w-6 h-6 rounded-full shadow-sm"></div>
              </div>
              <h2 class="mt-6 text-xl font-bold text-gray-900">{{ auth.user?.name }}</h2>
              <p class="text-sm text-gray-500 mt-1">{{ auth.user?.email }}</p>
              <span class="mt-4 px-3 py-1 bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-widest rounded-full">
                {{ auth.user?.role }} Account
              </span>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-50 space-y-4">
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <ShieldCheckIcon class="w-5 h-5 text-green-500" />
                <span>Account Verified</span>
              </div>
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <ArrowPathIcon class="w-5 h-5 text-gray-400" />
                <span>Last updated: Just now</span>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-2 space-y-8">
          <transition name="fade">
            <div v-if="message" class="p-4 bg-black text-white rounded-2xl flex items-center gap-3 shadow-lg">
              <CheckCircleIcon class="w-6 h-6 text-green-400" />
              <span class="text-sm font-medium">{{ message }}</span>
            </div>
          </transition>

          <section class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md">
            <div class="px-8 py-6 border-b border-gray-50 bg-gray-50/30">
              <h2 class="text-lg font-bold flex items-center gap-2">
                <UserIcon class="w-5 h-5 text-gray-400" />
                Profile Information
              </h2>
            </div>
            <div class="p-8">
              <form @submit.prevent="updateProfile" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2 md:col-span-2 lg:col-span-1">
                  <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider">Full Name</label>
                  <div class="relative">
                    <UserIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="profileForm.name" type="text" placeholder="John Doe" class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-black transition-all outline-none" />
                  </div>
                </div>
                <div class="space-y-2 md:col-span-2 lg:col-span-1">
                  <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider">Email Address</label>
                  <div class="relative">
                    <EnvelopeIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="profileForm.email" type="email" placeholder="john@example.com" class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-black transition-all outline-none" />
                  </div>
                </div>
                <div class="md:col-span-2 pt-4">
                  <button :disabled="loading" class="w-full md:w-auto bg-black text-white px-8 py-3 rounded-xl text-sm font-bold shadow-lg hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50">
                    {{ loading ? 'Updating...' : 'Save Changes' }}
                  </button>
                </div>
              </form>
            </div>
          </section>

          <section class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md">
            <div class="px-8 py-6 border-b border-gray-50 bg-gray-50/30">
              <h2 class="text-lg font-bold flex items-center gap-2">
                <KeyIcon class="w-5 h-5 text-gray-400" />
                Password & Security
              </h2>
            </div>
            <div class="p-8">
              <form @submit.prevent="updatePassword" class="space-y-6">
                <div class="space-y-2">
                  <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider">Current Password</label>
                  <input v-model="passwordForm.current_password" type="password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-black transition-all outline-none" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider">New Password</label>
                    <input v-model="passwordForm.new_password" type="password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-black transition-all outline-none" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider">Confirm New Password</label>
                    <input v-model="passwordForm.new_password_confirmation" type="password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-black transition-all outline-none" />
                  </div>
                </div>
                <div class="pt-4">
                  <button :disabled="loading" class="w-full md:w-auto bg-black text-white px-8 py-3 rounded-xl text-sm font-bold shadow-lg hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-50">
                    {{ loading ? 'Updating Password...' : 'Update Password' }}
                  </button>
                </div>
              </form>
            </div>
          </section>
        </div>
      </div>
    </main>
    <Footer />
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>