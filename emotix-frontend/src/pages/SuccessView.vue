<script setup>
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50 font-sans text-gray-900">
    <Navbar />

    <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6">
      <div class="max-w-lg w-full bg-white rounded-3xl shadow-xl shadow-gray-100/50 border border-gray-100 p-10 text-center relative overflow-hidden">
        
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-64 h-64 bg-green-50 rounded-full blur-3xl opacity-50 -z-10"></div>

        <div class="flex justify-center mb-8">
          <div class="relative">
            <div class="absolute inset-0 bg-green-100 rounded-full animate-ping opacity-20"></div>
            <div class="relative w-24 h-24 bg-green-100 rounded-full flex items-center justify-center">
              <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center shadow-lg shadow-green-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-3 mb-8">
          <h1 class="text-3xl font-black tracking-tight text-gray-900">Payment Successful!</h1>
          <p class="text-gray-500 leading-relaxed">
            Thank you for shopping at Emotix. <br class="hidden sm:block" />
            Your order has been received and is being processed.
          </p>
        </div>

        <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 mb-8">
          <div class="flex items-center gap-3 text-left">
            <div class="bg-white p-2 rounded-full border border-gray-100 shrink-0">
               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-600">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
               </svg>
            </div>
            <div>
               <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Delivery Status</p>
               <p class="text-sm text-gray-700">Monitor your order in the menu <span class="font-bold text-black">My Orders</span>.</p>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <RouterLink 
            to="/buyer/orders" 
            class="group flex items-center justify-center gap-2 w-full bg-black text-white font-bold text-sm py-4 rounded-full transition-all hover:bg-gray-800 shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:scale-95"
          >
            See My Orders
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 transition-transform group-hover:translate-x-1">
              <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
            </svg>
          </RouterLink>
          
          <RouterLink 
            to="/" 
            class="block w-full bg-white text-gray-700 border border-gray-200 font-bold text-sm py-4 rounded-full transition-all hover:bg-gray-50 hover:border-gray-300"
          >
            Back to Shopping
          </RouterLink>
        </div>

      </div>
    </main>

    <Footer />
  </div>
</template>