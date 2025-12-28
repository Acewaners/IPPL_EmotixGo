<script setup>
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import { 
  BuildingStorefrontIcon, 
  CurrencyDollarIcon, 
  ShoppingBagIcon, 
  UsersIcon 
} from '@heroicons/vue/24/outline'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white font-sans text-gray-900">
    <Navbar />

    <main class="flex-1">
      
      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-8">
        <nav class="flex text-sm text-gray-500">
          <span class="hover:text-black transition-colors cursor-pointer">Home</span>
          <span class="mx-2 text-gray-300">/</span>
          <span class="text-black font-medium">About</span>
        </nav>
      </section>

      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-24">
        <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
          
          <div class="lg:w-1/2 space-y-6">
            <h1 class="text-4xl md:text-5xl font-black tracking-tight text-gray-900 leading-tight">
              Our Story
            </h1>

            <div class="space-y-6 text-base md:text-lg text-gray-500 leading-relaxed">
              <p>
                Launched in 2025, <span class="text-black font-semibold">Emotix</span> is South Asia's premier online shopping marketplace with an active presence in multiple regions. Supported by a wide range of sellers and brands, we bring a smooth and secure online experience for electronics, gadgets, and AI-powered technology.
              </p>
              <p>
                Emotix has more than 1 Million products to offer, growing at a very fast pace. We offer a diverse assortment in categories ranging from consumer electronics to smart home devices, tailored for the modern tech enthusiast.
              </p>
            </div>
          </div>

          <div class="lg:w-1/2 w-full">
            <div class="relative w-full h-[400px] md:h-[500px] bg-gray-50 rounded-3xl flex items-center justify-center overflow-hidden border border-gray-100 group">
              
              <div class="absolute top-0 right-0 w-64 h-64 bg-gray-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
              <div class="absolute bottom-0 left-0 w-64 h-64 bg-gray-200 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>

              <img 
                src="/logo.png" 
                alt="Emotix Story" 
                class="relative z-10 w-48 md:w-64 h-auto object-contain drop-shadow-xl transition-transform duration-700 group-hover:scale-110"
              />
            </div>
          </div>

        </div>
      </section>

      <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-24">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <div class="group bg-white border border-gray-100 rounded-2xl p-8 flex flex-col items-center text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-default">
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-6 text-black group-hover:bg-black group-hover:text-white transition-colors duration-300">
              <BuildingStorefrontIcon class="w-8 h-8" />
            </div>
            <h3 class="text-3xl font-extrabold text-gray-900 mb-1">10.5k</h3>
            <p class="text-sm text-gray-500 font-medium">Sellers active our site</p>
          </div>

          <div class="group bg-black border border-black rounded-2xl p-8 flex flex-col items-center text-center shadow-lg hover:-translate-y-1 transition-all duration-300">
            <div class="w-16 h-16 rounded-full bg-gray-800 flex items-center justify-center mb-6 text-white group-hover:bg-white group-hover:text-black transition-colors duration-300">
              <CurrencyDollarIcon class="w-8 h-8" />
            </div>
            <h3 class="text-3xl font-extrabold text-white mb-1">33k</h3>
            <p class="text-sm text-gray-400 font-medium">Monthly Product Sale</p>
          </div>

          <div class="group bg-white border border-gray-100 rounded-2xl p-8 flex flex-col items-center text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-default">
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-6 text-black group-hover:bg-black group-hover:text-white transition-colors duration-300">
              <UsersIcon class="w-8 h-8" />
            </div>
            <h3 class="text-3xl font-extrabold text-gray-900 mb-1">45.5k</h3>
            <p class="text-sm text-gray-500 font-medium">Customer active in our site</p>
          </div>

          <div class="group bg-white border border-gray-100 rounded-2xl p-8 flex flex-col items-center text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-default">
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-6 text-black group-hover:bg-black group-hover:text-white transition-colors duration-300">
              <ShoppingBagIcon class="w-8 h-8" />
            </div>
            <h3 class="text-3xl font-extrabold text-gray-900 mb-1">25k</h3>
            <p class="text-sm text-gray-500 font-medium">Annual gross sale in our site</p>
          </div>

        </div>
      </section>

      </main>

    <Footer />
  </div>
</template>

<style scoped>
/* Animation for background blobs */
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
</style>