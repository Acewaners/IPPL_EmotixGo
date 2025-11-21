<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  Bars3Icon,
  XMarkIcon,
  HeartIcon,
  ShoppingCartIcon,
  UserIcon,
  StarIcon,
  ArrowRightOnRectangleIcon,
  Squares2X2Icon,
  ClipboardDocumentListIcon,
  TagIcon,
} from '@heroicons/vue/24/outline'
import { useAuth } from '../stores/auth'
import { useCartStore } from '../stores/cart'

const cart = useCartStore()

defineOptions({
  name: 'TheNavbar',
})

const r = useRouter()
const auth = useAuth()

const isMobileMenuOpen = ref(false)
const isAccountMenuOpen = ref(false)

const isLoggedIn = computed(() => !!auth.token)
const role = computed(() => auth.user?.role || null)

// badge cart ngikut store
const cartItemCount = computed(() => cart.cartCount)

// --- NAV HANDLER BASIC ---
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

// ke wishlist & cart
const goToWishlist = () => {
  r.push('/wishlist')
}

const goToCart = () => {
  r.push('/cart')
}

// --- ACCOUNT DROPDOWN ---
const toggleAccountMenu = () => {
  if (!auth.token) {
    return r.push('/login')
  }
  isAccountMenuOpen.value = !isAccountMenuOpen.value
}

const closeAccountMenu = () => {
  isAccountMenuOpen.value = false
}

// BUYER: ke pesanan
const goToBuyerOrders = () => {
  if (!auth.token) return r.push('/login')
  r.push('/buyer/orders')
  closeAccountMenu()
}

// SELLER: ke dashboard
const goToSellerDashboard = () => {
  if (!auth.token) return r.push('/login')
  r.push('/seller/products')
  closeAccountMenu()
}

// SELLER: ke daftar pesanan seller
const goToSellerOrders = () => {
  if (!auth.token) return r.push('/login')
  r.push('/seller/orders')
  closeAccountMenu()
}

const goToAccountPage = () => {
  if (!auth.token) return r.push('/login')
  r.push('/account')
  closeAccountMenu()
}

const goToBuyerCancellations = () => {
  if (!auth.token) return r.push('/login')
  r.push('/buyer/orders')
  closeAccountMenu()
}

const goToBuyerReviews = () => {
  if (!auth.token) return r.push('/login')
  r.push('/buyer/reviews')
  closeAccountMenu()
}

// Logout
const handleLogout = async () => {
  try {
    await auth.logout()
    r.push('/login')
  } catch (e) {
    console.error(e)
  } finally {
    closeAccountMenu()
  }
}

// versi mobile: user icon
const handleAccountMobile = () => {
  if (!auth.token) return r.push('/login')
  if (role.value === 'seller') return r.push('/seller/products')
  if (role.value === 'buyer') return r.push('/buyer/orders')
  return r.push('/account')
}
</script>

<template>
  <nav class="bg-white w-full border-b border-gray-300">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between items-center h-20">
        <!-- Logo - Left -->
        <div class="flex items-center gap-3 shrink-0">
          <img src="/logo.png" alt="Emotix Logo" class="h-8 w-8 object-contain" />
          <RouterLink to="/" class="text-xl font-semibold text-black tracking-tight">
            Emotix
          </RouterLink>
        </div>

        <!-- Desktop Menu - Center -->
        <div class="hidden lg:flex items-center gap-12">
          <RouterLink
            to="/"
            class="text-sm font-medium text-black hover:text-gray-600 transition-colors relative group"
          >
            Home
            <span
              class="absolute left-0 right-0 -bottom-1 h-0.5 bg-black opacity-0 group-hover:opacity-100 transition-opacity"
            ></span>
          </RouterLink>

          <RouterLink
            to="/contact"
            class="text-sm font-medium text-black hover:text-gray-600 transition-colors relative group"
          >
            Contact
            <span
              class="absolute left-0 right-0 -bottom-1 h-0.5 bg-black opacity-0 group-hover:opacity-100 transition-opacity"
            ></span>
          </RouterLink>

          <RouterLink
            to="/about"
            class="text-sm font-medium text-black hover:text-gray-600 transition-colors relative group"
          >
            About
            <span
              class="absolute left-0 right-0 -bottom-1 h-0.5 bg-black opacity-0 group-hover:opacity-100 transition-opacity"
            ></span>
          </RouterLink>

          <!-- Sign Up hanya kalau belum login -->
          <RouterLink
            v-if="!isLoggedIn"
            to="/register"
            class="text-sm font-medium text-black hover:text-gray-600 transition-colors relative group"
          >
            Sign Up
            <span
              class="absolute left-0 right-0 -bottom-1 h-0.5 bg-black opacity-0 group-hover:opacity-100 transition-opacity"
            ></span>
          </RouterLink>
        </div>

        <!-- Icons - Right -->
        <div class="flex items-center gap-6">
          <!-- Desktop Icons -->
          <div class="hidden lg:flex items-center gap-6">
            <!-- Wishlist -->
            <button
              @click="goToWishlist"
              class="text-black hover:text-gray-600 cursor-pointer transition-colors"
              aria-label="Wishlist"
            >
              <HeartIcon class="w-6 h-6" />
            </button>

            <!-- Cart -->
            <button
              @click="goToCart"
              class="relative text-black hover:text-gray-600 cursor-pointer transition-colors"
              aria-label="Cart"
            >
              <ShoppingCartIcon class="w-6 h-6" />
              <span
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center font-medium"
              >
                {{ cartItemCount }}
              </span>
            </button>

            <!-- Account dropdown -->
            <div class="relative">
              <button
                @click="toggleAccountMenu"
                class="text-black hover:text-gray-600 cursor-pointer transition-colors"
                aria-label="Account"
              >
                <UserIcon class="w-6 h-6" />
              </button>

              <!-- Dropdown menu -->
              <div
                v-if="isAccountMenuOpen"
                class="absolute right-0 mt-3 w-60 rounded-md bg-teal-500 text-white shadow-lg text-sm z-50 py-2"
              >
                <!-- MENU BUYER -->
                <template v-if="role === 'buyer'">
                  <button
                    type="button"
                    @click="goToAccountPage"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <UserIcon class="w-4 h-4" />
                    <span>Manage My Account</span>
                  </button>

                  <button
                    type="button"
                    @click="goToBuyerOrders"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <ShoppingCartIcon class="w-4 h-4" />
                    <span>My Order</span>
                  </button>

                  <button
                    type="button"
                    @click="goToBuyerCancellations"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <XMarkIcon class="w-4 h-4" />
                    <span>My Cancellations</span>
                  </button>

                  <button
                    type="button"
                    @click="goToBuyerReviews"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <StarIcon class="w-4 h-4" />
                    <span>My Reviews</span>
                  </button>
                </template>

                <!-- MENU SELLER -->
                <template v-else-if="role === 'seller'">
                  <button
                    type="button"
                    @click="goToSellerDashboard"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <Squares2X2Icon class="w-4 h-4" />
                    <span>Seller Dashboard</span>
                  </button>

                  <button
                    type="button"
                    @click="goToSellerOrders"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <ClipboardDocumentListIcon class="w-4 h-4" />
                    <span>Seller Orders</span>
                  </button>

                  <button
                    type="button"
                    @click="goToSellerDashboard"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <TagIcon class="w-4 h-4" />
                    <span>My Products</span>
                  </button>
                </template>

                <!-- MENU GENERIC -->
                <template v-else>
                  <button
                    type="button"
                    @click="goToAccountPage"
                    class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                  >
                    <UserIcon class="w-4 h-4" />
                    <span>My Account</span>
                  </button>
                </template>

                <div class="border-t border-white/30 my-1"></div>

                <button
                  type="button"
                  @click="handleLogout"
                  class="w-full flex items-center gap-2 px-4 py-2 hover:bg-teal-600 text-left"
                >
                  <ArrowRightOnRectangleIcon class="w-4 h-4" />
                  <span>Logout</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Mobile menu button -->
          <button
            @click="toggleMobileMenu"
            class="lg:hidden text-black hover:text-gray-600 focus:outline-none transition-colors"
            aria-label="Toggle menu"
          >
            <Bars3Icon v-if="!isMobileMenuOpen" class="h-6 w-6" />
            <XMarkIcon v-else class="h-6 w-6" />
          </button>
        </div>
      </div>

      <!-- Mobile Menu -->
      <div v-show="isMobileMenuOpen" class="lg:hidden py-4 border-t border-gray-100">
        <div class="flex flex-col space-y-3">
          <RouterLink
            to="/"
            @click="closeMobileMenu"
            class="text-sm font-medium text-black hover:text-gray-600 px-4 py-2 transition-colors"
          >
            Home
          </RouterLink>
          <RouterLink
            to="/contact"
            @click="closeMobileMenu"
            class="text-sm font-medium text-black hover:text-gray-600 px-4 py-2 transition-colors"
          >
            Contact
          </RouterLink>
          <RouterLink
            to="/about"
            @click="closeMobileMenu"
            class="text-sm font-medium text-black hover:text-gray-600 px-4 py-2 transition-colors"
          >
            About
          </RouterLink>

          <RouterLink
            v-if="!isLoggedIn"
            to="/register"
            @click="closeMobileMenu"
            class="text-sm font-medium text-black hover:text-gray-600 px-4 py-2 transition-colors"
          >
            Sign Up
          </RouterLink>

          <!-- Mobile Icons -->
          <div class="flex items-center gap-6 px-4 pt-4 border-t border-gray-100">
            <button
              @click="goToWishlist"
              class="text-black hover:text-gray-600 transition-colors"
              aria-label="Wishlist"
            >
              <HeartIcon class="w-6 h-6" />
            </button>

            <button
              @click="goToCart"
              class="relative text-black hover:text-gray-600 transition-colors"
              aria-label="Cart"
            >
              <ShoppingCartIcon class="w-6 h-6" />
              <span
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center font-medium"
              >
                {{ cartItemCount }}
              </span>
            </button>

            <button
              @click="handleAccountMobile"
              class="text-black hover:text-gray-600 transition-colors"
              aria-label="Account"
            >
              <UserIcon class="w-6 h-6" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.router-link-active span {
  opacity: 1;
}
</style>
