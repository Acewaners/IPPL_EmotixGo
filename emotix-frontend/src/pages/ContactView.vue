<script setup>
import { PhoneIcon, EnvelopeIcon } from '@heroicons/vue/24/solid'
import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <!-- Navbar -->
    <Navbar />

    <!-- CONTENT WRAPPER -->
    <div class="max-w-7xl mx-auto w-full px-4 md:px-6 lg:px-8 mt-6 space-y-10">
      <!-- Breadcrumb -->
      <section class="text-sm">
        <p class="text-gray-500 flex items-center gap-1">
          <span>Home</span>
          <span>/</span>
          <span class="text-black font-medium">Contact</span>
        </p>
      </section>

      <!-- Contact Row -->
      <div class="flex flex-col md:flex-row gap-6">
        <!-- Left Card -->
        <div class="w-full md:w-1/3 bg-white rounded-lg shadow p-6 space-y-6">
          <!-- Call -->
          <div>
            <div class="flex items-center gap-4 mb-4">
              <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                <PhoneIcon class="w-5 h-5 text-white" />
              </div>
              <h3 class="text-lg font-bold">Call To Us</h3>
            </div>

            <p class="text-sm text-gray-600">We are available 24/7, 7 days a week.</p>
            <p class="text-sm text-gray-600">Phone: 0852-4666-4332</p>
          </div>

          <div class="border-b"></div>

          <!-- Write -->
          <div>
            <div class="flex items-center gap-4 mb-4">
              <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                <EnvelopeIcon class="w-5 h-5 text-white" />
              </div>
              <h3 class="text-lg font-bold">Write To Us</h3>
            </div>

            <p class="text-sm text-gray-600">
              Fill out our form and we will contact you within 24 hours.
            </p>
            <p class="text-sm text-gray-600">Emails: customer@exclusive.com</p>
            <p class="text-sm text-gray-600">Emails: support@exclusive.com</p>
          </div>
        </div>

        <!-- Form -->
        <div class="flex-1 bg-white rounded-lg shadow p-6">
          <form class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <input
                type="text"
                placeholder="Your Name *"
                class="w-full bg-gray-100 rounded-lg p-3 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500"
              />
              <input
                type="email"
                placeholder="Your Email *"
                class="w-full bg-gray-100 rounded-lg p-3 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500"
              />
              <input
                type="tel"
                placeholder="Your Phone *"
                class="w-full bg-gray-100 rounded-lg p-3 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500"
              />
            </div>

            <textarea
              rows="8"
              placeholder="Your Message"
              class="w-full bg-gray-100 rounded-lg p-3 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
            ></textarea>

            <div class="flex justify-end">
              <button
                type="button"
                class="bg-red-500 hover:bg-red-600 text-white font-semibold px-8 py-3 rounded-lg"
              >
                Send Message
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Map -->
      <div class="bg-white rounded-xl shadow overflow-hidden h-[350px] md:h-[400px]">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127430.89281727595!2d116.79095945!3d-1.2379274!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2df147340a2ddd67%3A0x56ce90c92627d3c8!2sBalikpapan!5e0!3m2!1sid!2sid!4v1699999999999"
          class="w-full h-full border-0"
          loading="lazy"
        ></iframe>
      </div>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
/* kosong dulu aja, nggak pakai @apply */
</style>
