<script setup>
import { ref } from 'vue'
import { PhoneIcon, EnvelopeIcon, MapPinIcon, PaperAirplaneIcon } from '@heroicons/vue/24/solid'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { api } from '../lib/api' // Pastikan import api helper Anda benar

// State Form
const form = ref({
  name: '',
  email: '',
  phone: '',
  message: ''
})

const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const errors = ref({})

// Function Submit
const submitContact = async () => {
  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  errors.value = {}

  try {
    // Kirim ke backend endpoint '/contact'
    const response = await api.post('/contact', form.value)
    
    // Jika sukses
    successMessage.value = 'Terima kasih! Pesan Anda telah kami terima.'
    
    // Reset form
    form.value = {
      name: '',
      email: '',
      phone: '',
      message: ''
    }
  } catch (e) {
    if (e.response && e.response.status === 422) {
      // Error validasi dari Laravel
      errors.value = e.response.data.errors
    } else {
      errorMessage.value = 'Maaf, terjadi kesalahan saat mengirim pesan. Silakan coba lagi.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-white flex flex-col font-sans text-gray-900">
    <Navbar />

    <div class="bg-gray-50 py-12 md:py-20">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-black tracking-tight mb-4">Get in Touch</h1>
        <p class="text-gray-500 max-w-2xl mx-auto text-lg">
          Punya pertanyaan seputar produk atau pesanan? Tim kami siap membantu Anda 24/7. Jangan ragu untuk menghubungi kami.
        </p>
      </div>
    </div>

    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 -mt-10 mb-20 relative z-10">
      
      <div class="bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col lg:flex-row min-h-[600px]">
        
        <div class="lg:w-2/5 bg-black text-white p-10 md:p-14 flex flex-col justify-between relative overflow-hidden">
           
           <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-gray-800 rounded-full opacity-50 blur-3xl"></div>

           <div class="relative z-10">
              <h3 class="text-2xl font-bold mb-8">Contact Information</h3>
              <p class="text-gray-400 mb-12 leading-relaxed">
                Isi formulir di samping dan tim kami akan merespons dalam waktu maksimal 24 jam. Atau hubungi kami langsung melalui kontak di bawah.
              </p>

              <div class="space-y-8">
                <div class="flex items-start gap-5">
                   <div class="w-12 h-12 rounded-full bg-gray-900 flex items-center justify-center shrink-0">
                      <PhoneIcon class="w-6 h-6 text-white" />
                   </div>
                   <div>
                      <p class="font-bold text-lg">+62 852 4666 4332</p>
                      <p class="text-gray-400 text-sm">Mon-Sun 9am-6pm</p>
                   </div>
                </div>

                <div class="flex items-start gap-5">
                   <div class="w-12 h-12 rounded-full bg-gray-900 flex items-center justify-center shrink-0">
                      <EnvelopeIcon class="w-6 h-6 text-white" />
                   </div>
                   <div>
                      <p class="font-bold text-lg">support@emotix.com</p>
                      <p class="text-gray-400 text-sm">Online Support 24/7</p>
                   </div>
                </div>

                <div class="flex items-start gap-5">
                   <div class="w-12 h-12 rounded-full bg-gray-900 flex items-center justify-center shrink-0">
                      <MapPinIcon class="w-6 h-6 text-white" />
                   </div>
                   <div>
                      <p class="font-bold text-lg">Jakarta, Indonesia</p>
                      <p class="text-gray-400 text-sm">Sudirman Central Business District</p>
                   </div>
                </div>
              </div>
           </div>

           <div class="relative z-10 mt-12 flex gap-4">
              <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-white hover:text-black transition-all">
                <i class="fa-brands fa-instagram"></i> </a>
              <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-white hover:text-black transition-all">
                <i class="fa-brands fa-twitter"></i> </a>
           </div>
        </div>

        <div class="lg:w-3/5 p-10 md:p-14 bg-white relative">
           
           <h3 class="text-2xl font-bold text-gray-900 mb-8">Send us a Message</h3>

           <div v-if="successMessage" class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl flex items-center gap-2 border border-green-100">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              {{ successMessage }}
           </div>
           <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl flex items-center gap-2 border border-red-100">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              {{ errorMessage }}
           </div>

           <form @submit.prevent="submitContact" class="space-y-6">
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700">Your Name</label>
                    <input 
                      v-model="form.name"
                      type="text" 
                      placeholder="John Doe"
                      class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all"
                      :class="{'border-red-500 bg-red-50': errors.name}"
                    />
                    <p v-if="errors.name" class="text-xs text-red-500">{{ errors.name[0] }}</p>
                 </div>

                 <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700">Phone Number</label>
                    <input 
                      v-model="form.phone"
                      type="tel" 
                      placeholder="+62..."
                      class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all"
                      :class="{'border-red-500 bg-red-50': errors.phone}"
                    />
                    <p v-if="errors.phone" class="text-xs text-red-500">{{ errors.phone[0] }}</p>
                 </div>
              </div>

              <div class="space-y-2">
                 <label class="text-sm font-semibold text-gray-700">Email Address</label>
                 <input 
                   v-model="form.email"
                   type="email" 
                   placeholder="example@email.com"
                   class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all"
                   :class="{'border-red-500 bg-red-50': errors.email}"
                 />
                 <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email[0] }}</p>
              </div>

              <div class="space-y-2">
                 <label class="text-sm font-semibold text-gray-700">Message</label>
                 <textarea 
                   v-model="form.message"
                   rows="5" 
                   placeholder="How can we help you?"
                   class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-gray-900 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent transition-all resize-none"
                   :class="{'border-red-500 bg-red-50': errors.message}"
                 ></textarea>
                 <p v-if="errors.message" class="text-xs text-red-500">{{ errors.message[0] }}</p>
              </div>

              <div class="pt-2">
                 <button 
                   type="submit" 
                   :disabled="loading"
                   class="w-full md:w-auto bg-black text-white px-10 py-4 rounded-full font-bold text-sm hover:bg-gray-800 transition-all shadow-lg hover:shadow-xl active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                 >
                    <span v-if="loading" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                    <span v-else>Send Message</span>
                    <PaperAirplaneIcon v-if="!loading" class="w-4 h-4" />
                 </button>
              </div>

           </form>
        </div>
      </div>

      <div class="mt-12 rounded-3xl overflow-hidden shadow-lg h-[400px] grayscale hover:grayscale-0 transition-all duration-700">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.2921782245787!2d106.80629617499035!3d-6.225152893762887!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f15049c6b6d3%3A0x6a6d639b5b6328c0!2sSudirman%20Central%20Business%20District!5e0!3m2!1sen!2sid!4v1710000000000!5m2!1sen!2sid"
          class="w-full h-full border-0"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
      </div>

    </div>

    <Footer />
  </div>
</template>