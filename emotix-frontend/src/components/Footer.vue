<script setup>
import { PaperAirplaneIcon } from '@heroicons/vue/24/outline'

defineOptions({
  name: 'TheFooter',
})
</script>

<template>
  <footer class="bg-teal-600 w-full text-white">
    <div class="max-w-7xl mx-auto px-4 py-8 md:py-12 lg:py-16">
      <!-- Main Footer Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-16">
        <!-- Column 1: Brand & Subscribe -->
        <div class="space-y-4 sm:col-span-2 lg:col-span-1">
          <h3 class="text-xl md:text-2xl font-bold text-white">Emotix</h3>
          <p class="text-base md:text-lg font-medium text-white">Subscribe</p>
          <p class="text-sm text-white/90">Get 10% off your first order</p>

          <!-- Email Input -->
          <div class="relative mt-4 max-w-sm">
            <input
              type="email"
              placeholder="Enter your email"
              class="w-full bg-transparent border border-white/80 rounded px-4 py-2.5 text-white placeholder-white/60 focus:outline-none focus:border-white pr-12 text-sm"
            />
            <button
              class="absolute right-2 top-1/2 -translate-y-1/2 text-white hover:text-white/80 transition-colors"
              aria-label="Subscribe"
            >
              <PaperAirplaneIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Column 2: Support -->
        <div class="space-y-3">
          <h4 class="text-base md:text-lg font-bold text-white mb-4 md:mb-6">Support</h4>
          <div class="space-y-2 md:space-y-3 text-sm text-white/90">
            <p class="leading-relaxed">
              Sumber Rejo, Kariangau,<br />
              Balikpapan Utara, Kalimantan Timur.
            </p>

            <p>hariyatunnasifa@gmail.com</p>
            <p>0852-4666-4332</p>
          </div>
        </div>

        <!-- Column 3: Account -->
        <div class="space-y-3">
          <h4 class="text-base md:text-lg font-bold text-white mb-4 md:mb-6">Account</h4>
          <ul class="space-y-2 md:space-y-3 text-sm text-white/90">
            <li>
              <a href="#" class="hover:text-white transition-colors">My Account</a>
            </li>
            <li>
              <a href="#" class="hover:text-white transition-colors">Login / Register</a>
            </li>
            <li>
              <a href="#" class="hover:text-white transition-colors">Cart</a>
            </li>
            <li>
              <a href="#" class="hover:text-white transition-colors">Wishlist</a>
            </li>
            <li>
              <a href="#" class="hover:text-white transition-colors">Shop</a>
            </li>
          </ul>
        </div>

        <!-- Column 4: Quick Link -->
        <div class="space-y-3">
          <h4 class="text-base md:text-lg font-bold text-white mb-4 md:mb-6">Quick Link</h4>
          <ul class="space-y-2 md:space-y-3 text-sm text-white/90">
            <li>
              <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
            </li>
            <li>
              <a href="#" class="hover:text-white transition-colors">Terms Of Use</a>
            </li>
            <li>
              <a href="#" class="hover:text-white transition-colors">FAQ</a>
            </li>
            <li>
              <a href="#" class="hover:text-white transition-colors">Contact</a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Copyright -->
      <div class="border-t border-white/20 mt-8 md:mt-10 lg:mt-12 pt-6 md:pt-8">
        <p class="text-center text-xs md:text-sm text-white/80">
          © Copyright Sifa 2025. All right reserved
        </p>
      </div>
    </div>
  </footer>
</template>

<style scoped>
/* Additional styles if needed */
</style>