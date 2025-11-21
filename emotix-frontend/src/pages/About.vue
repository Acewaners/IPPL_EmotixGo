<script setup>
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navbar />

    <main class="flex-1">
      <!-- Breadcrumb -->
      <section class="max-w-6xl mx-auto px-4 lg:px-0 mt-6 mb-4 text-sm">
        <p class="text-gray-500">
          <span class="text-gray-500">Home</span>
          <span class="mx-1">/</span>
          <span class="text-black font-medium">About</span>
        </p>
      </section>

      <!-- Our Story section -->
      <section class="max-w-6xl mx-auto px-4 lg:px-0 mb-16">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-16 items-center">
          <!-- Text -->
          <div class="space-y-6">
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
              Our Story
            </h1>

            <div class="space-y-4 text-sm md:text-base leading-relaxed text-gray-700">
              <p>
                Launced in 2015, Exclusive is South Asia’s premier online shopping
                marketplace with an active presense in Bangladesh. Supported by
                wide range of tailored marketing, data and service solutions,
                Exclusive has 10,500 sallers and 300 brands and serves 3
                millions customers across the region.
              </p>
              <p>
                Exclusive has more than 1 Million products to offer, growing at a
                very fast. Exclusive offers a diverse assortment in categories
                ranging from consumer.
              </p>
            </div>
          </div>

          <!-- Red block (image placeholder) -->
          <div class="w-full h-64 md:h-80 bg-red-500 rounded-md md:rounded-lg"></div>
        </div>
      </section>

      <!-- Stats cards -->
      <section class="max-w-6xl mx-auto px-4 lg:px-0 mb-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Card 1 -->
          <div
            class="border border-gray-200 rounded-md px-6 py-6 flex flex-col items-center text-center gap-3"
          >
            <div
              class="w-12 h-12 rounded-full bg-black flex items-center justify-center text-white text-xl"
            >
              🏬
            </div>
            <div>
              <p class="text-xl font-bold">10.5k</p>
              <p class="text-xs text-gray-600 mt-1">
                Sellers active our site
              </p>
            </div>
          </div>

          <!-- Card 2 (highlight) -->
          <div
            class="border border-red-500 bg-red-500 rounded-md px-6 py-6 flex flex-col items-center text-center gap-3 text-white"
          >
            <div
              class="w-12 h-12 rounded-full bg-white flex items-center justify-center text-red-500 text-xl"
            >
              💰
            </div>
            <div>
              <p class="text-xl font-bold">33k</p>
              <p class="text-xs mt-1">
                Monthly Productuct Sale
              </p>
            </div>
          </div>

          <!-- Card 3 -->
          <div
            class="border border-gray-200 rounded-md px-6 py-6 flex flex-col items-center text-center gap-3"
          >
            <div
              class="w-12 h-12 rounded-full bg-black flex items-center justify-center text-white text-xl"
            >
              🧍
            </div>
            <div>
              <p class="text-xl font-bold">45.5k</p>
              <p class="text-xs text-gray-600 mt-1">
                Customer active in our site
              </p>
            </div>
          </div>

          <!-- Card 4 -->
          <div
            class="border border-gray-200 rounded-md px-6 py-6 flex flex-col items-center text-center gap-3"
          >
            <div
              class="w-12 h-12 rounded-full bg-black flex items-center justify-center text-white text-xl"
            >
              📈
            </div>
            <div>
              <p class="text-xl font-bold">25k</p>
              <p class="text-xs text-gray-600 mt-1">
                Anual gross sale in our site
              </p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>
