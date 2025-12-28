<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  Bars3Icon,
  XMarkIcon,
  HeartIcon,
  ShoppingBagIcon,
  UserIcon,
  StarIcon,
  ArrowRightOnRectangleIcon,
  Squares2X2Icon,
  ClipboardDocumentListIcon,
  ChevronDownIcon
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
const cartItemCount = computed(() => cart.cartCount)

// --- HANDLERS ---
const toggleMobileMenu = () => { isMobileMenuOpen.value = !isMobileMenuOpen.value }
const closeMobileMenu = () => { isMobileMenuOpen.value = false }

const goToWishlist = () => r.push('/wishlist')
const goToCart = () => r.push('/cart')

const toggleAccountMenu = () => {
  if (!auth.token) return r.push('/login')
  isAccountMenuOpen.value = !isAccountMenuOpen.value
}
const closeAccountMenu = () => { isAccountMenuOpen.value = false }

const goToBuyerOrders = () => {
  if (!auth.token) return r.push('/login')
  r.push('/buyer/orders'); closeAccountMenu()
}
const goToSellerDashboard = () => {
  if (!auth.token) return r.push('/login')
  r.push('/seller/products'); closeAccountMenu()
}
const goToSellerOrders = () => {
  if (!auth.token) return r.push('/login')
  r.push('/seller/orders'); closeAccountMenu()
}
const goToAccountPage = () => {
  if (!auth.token) return r.push('/login')
  r.push('/account'); closeAccountMenu()
}
const goToBuyerReviews = () => {
  if (!auth.token) return r.push('/login')
  r.push('/my-reviews'); closeAccountMenu()
}
const handleLogout = async () => {
  try { await auth.logout(); r.push('/login') } 
  catch (e) { console.error(e) } 
  finally { closeAccountMenu() }
}
const handleAccountMobile = () => {
  if (!auth.token) return r.push('/login')
  if (role.value === 'seller') return r.push('/seller/products')
  if (role.value === 'buyer') return r.push('/buyer/orders')
  return r.push('/account')
}
</script>

<template>
  <nav class="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">

        <div class="flex items-center gap-3 shrink-0 group cursor-pointer" @click="r.push('/')">
          <div class="relative w-10 h-10 flex items-center justify-center bg-black text-white rounded-xl shadow-lg transition-transform group-hover:scale-105">
             <img src="/logo.png" alt="Emotix" class="w-6 h-6 object-contain" />
          </div>
          <span class="text-2xl font-bold tracking-tighter text-gray-900 group-hover:text-black transition-colors">
            Emotix<span class="text-red-500">.</span>
          </span>
        </div>

        <div class="hidden lg:flex items-center gap-10">
          <RouterLink to="/" class="text-sm font-medium text-gray-600 hover:text-black transition-colors relative py-1 group">
            Home <span class="absolute left-0 bottom-0 w-0 h-0.5 bg-black transition-all duration-300 group-hover:w-full"></span>
          </RouterLink>
          <RouterLink to="/products" class="text-sm font-medium text-gray-600 hover:text-black transition-colors relative py-1 group">
            Shop <span class="absolute left-0 bottom-0 w-0 h-0.5 bg-black transition-all duration-300 group-hover:w-full"></span>
          </RouterLink>
          <RouterLink to="/about" class="text-sm font-medium text-gray-600 hover:text-black transition-colors relative py-1 group">
            About <span class="absolute left-0 bottom-0 w-0 h-0.5 bg-black transition-all duration-300 group-hover:w-full"></span>
          </RouterLink>
          <RouterLink to="/contact" class="text-sm font-medium text-gray-600 hover:text-black transition-colors relative py-1 group">
            Contact <span class="absolute left-0 bottom-0 w-0 h-0.5 bg-black transition-all duration-300 group-hover:w-full"></span>
          </RouterLink>
        </div>

        <div class="flex items-center gap-2">
          <button @click="goToWishlist" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-700 hover:text-black transition-all active:scale-95" aria-label="Wishlist">
            <HeartIcon class="w-6 h-6 transition-transform hover:scale-110" />
          </button>

          <button @click="goToCart" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-700 hover:text-black transition-all active:scale-95 relative" aria-label="Cart">
            <ShoppingBagIcon class="w-6 h-6 transition-transform hover:scale-110" />
            <span v-if="cartItemCount > 0" 
              class="absolute -top-1 -right-1 bg-black text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center border-2 border-white shadow-sm">
              {{ cartItemCount }}
            </span>
          </button>

          <div class="h-6 w-px bg-gray-200 mx-2 hidden lg:block"></div>

          <div class="relative hidden lg:block">
            <button @click="toggleAccountMenu" class="flex items-center gap-2 hover:bg-gray-50 py-1.5 px-3 rounded-full transition-all border border-transparent hover:border-gray-200">
              <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
                <UserIcon v-if="!isLoggedIn" class="w-5 h-5 text-gray-500" />
                <img v-else src="https://ui-avatars.com/api/?name=User&background=000&color=fff" alt="User" class="w-full h-full object-cover" />
              </div>
              <div class="flex flex-col items-start text-xs">
                <span class="font-semibold text-gray-900 leading-tight">
                  {{ isLoggedIn ? (auth.user?.name || 'My Account') : 'Sign In' }}
                </span>
                <span v-if="isLoggedIn" class="text-gray-500 text-[10px] uppercase tracking-wide">
                  {{ role }}
                </span>
              </div>
              <ChevronDownIcon class="w-3 h-3 text-gray-400 transition-transform" :class="{ 'rotate-180': isAccountMenuOpen }" />
            </button>

            <div v-if="isAccountMenuOpen" class="absolute right-0 mt-4 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden py-2 z-50 ring-1 ring-black/5">
                <template v-if="isLoggedIn">
                  <div class="px-4 py-3 border-b border-gray-50 mb-1 bg-gray-50/50">
                    <p class="text-xs text-gray-500">Signed in as</p>
                    <p class="text-sm font-bold text-gray-900 truncate">{{ auth.user?.email || 'User' }}</p>
                  </div>

                  <template v-if="role === 'buyer'">
                    <button @click="goToAccountPage" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors text-left"><UserIcon class="w-4 h-4" /> Manage Account</button>
                    <button @click="goToBuyerOrders" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors text-left"><ClipboardDocumentListIcon class="w-4 h-4" /> My Orders</button>
                    <button @click="goToBuyerReviews" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors text-left"><StarIcon class="w-4 h-4" /> My Reviews</button>
                  </template>

                  <template v-else-if="role === 'seller'">
                    <button @click="goToSellerDashboard" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors text-left"><Squares2X2Icon class="w-4 h-4" /> Seller Dashboard</button>
                    <button @click="goToSellerOrders" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors text-left"><ClipboardDocumentListIcon class="w-4 h-4" /> Seller Orders</button>
                  </template>

                  <template v-else>
                    <button @click="goToAccountPage" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors text-left"><UserIcon class="w-4 h-4" /> My Account</button>
                  </template>

                  <div class="border-t border-gray-100 my-1"></div>
                  <button @click="handleLogout" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors text-left"><ArrowRightOnRectangleIcon class="w-4 h-4" /> Log Out</button>
                </template>

                <template v-else>
                  <RouterLink to="/login" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors text-left" @click="closeAccountMenu">Log In</RouterLink>
                  <RouterLink to="/register" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 hover:text-black transition-colors text-left" @click="closeAccountMenu">Create Account</RouterLink>
                </template>
            </div>
          </div>

          <button @click="toggleMobileMenu" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-700 ml-2">
            <Bars3Icon v-if="!isMobileMenuOpen" class="w-6 h-6" />
            <XMarkIcon v-else class="w-6 h-6" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="isMobileMenuOpen" class="lg:hidden absolute top-20 left-0 w-full bg-white border-b border-gray-200 shadow-xl z-40">
      <div class="flex flex-col p-4 space-y-2">
        <RouterLink to="/" @click="closeMobileMenu" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">Home</RouterLink>
        <RouterLink to="/products" @click="closeMobileMenu" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">Shop</RouterLink>
        <RouterLink to="/about" @click="closeMobileMenu" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">About</RouterLink>
        <RouterLink to="/contact" @click="closeMobileMenu" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50">Contact</RouterLink>
        <div class="border-t border-gray-100 my-2"></div>
        <template v-if="!isLoggedIn">
          <RouterLink to="/login" @click="closeMobileMenu" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-500">Log In</RouterLink>
          <RouterLink to="/register" @click="closeMobileMenu" class="px-4 py-3 rounded-xl text-sm font-semibold text-black bg-gray-100">Sign Up</RouterLink>
        </template>
        <template v-else>
            <button @click="handleAccountMobile" class="px-4 py-3 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 flex justify-between items-center w-full">
              <span>My Account</span>
              <span class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-500 uppercase">{{ role }}</span>
            </button>
            <button @click="handleLogout" class="px-4 py-3 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 w-full text-left">Log Out</button>
        </template>
      </div>
    </div>
  </nav>
</template>