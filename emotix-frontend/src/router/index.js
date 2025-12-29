import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../stores/auth'
import AccountView from '../pages/AccountView.vue'

const Login = () => import('../pages/Login.vue')
const Register = () => import('../pages/Register.vue')
const SellerProducts = () => import('../pages/SellerProducts.vue')
const BuyerOrders = () => import('../pages/BuyerOrders.vue')
const ContactView = () => import('../pages/ContactView.vue')
const AboutView = () => import('../pages/About.vue')
const Home = () => import('../pages/Home.vue')
const wishlist = () => import('../pages/WishlistView.vue')
const cart = () => import('../pages/CartView.vue')
const ProductDetails = () => import('../pages/ProductDetailView.vue')
const CheckoutView = () => import('../pages/CheckoutView.vue')
const PaymentView = () => import('../pages/PaymentView.vue')
const SellerOrders = () => import('../pages/SellerOrdersView.vue')
const myReviews = () => import('../pages/MyReviewsView.vue')
const AllProductsView = () => import('../pages/AllProductsView.vue')
const SuccessView = () => import('../pages/SuccessView.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
  { path: '/', component: Home, meta: { auth: true } },
    { path: '/login', component: Login, meta: { guest: true } },
    { path: '/register', component: Register, meta: { guest: true } },
    { path: '/seller/products', component: SellerProducts, meta: { auth: true, role: 'seller' } },
    { path: '/buyer/orders', component: BuyerOrders, meta: { auth: true, role: 'buyer' } },
    { path: '/wishlist', component: wishlist, meta: { auth: true } },
    { path: '/cart', component: cart,  meta: { auth: true } },
    {
      path: '/contact',
      name: 'contact',
      component: ContactView,
    },
    { 
      path: '/payment/success', 
      name: 'payment-success', 
      component: SuccessView, 
      meta: { auth: true } 
    },
    {
      path: '/account',
      name: 'account',
      component: AccountView,
      meta: { requiresAuth: true } // Pastikan hanya bisa diakses jika sudah login
    },
    { 
      path: '/products', 
      name: 'all-products', 
      component: AllProductsView, 
      meta: { auth: true } // Sesuaikan auth jika halaman ini publik atau butuh login
    },
    { path: '/my-reviews',name:'buyer-reviews' ,component: myReviews, meta: { auth: true } },
    { path: '/seller/orders', component: SellerOrders, meta: { auth: true, role: 'seller' } },
    { path: '/about', name: 'about', component: AboutView },
    { path: '/products/:id', component: ProductDetails, meta: { auth: true } },
    { path: '/checkout', component: CheckoutView, meta: { auth: true } },
    { path: '/payment/:id', name:'payment', component: PaymentView, meta: { auth: true } },

  ],
})

router.beforeEach(async (to, from, next) => {
  const auth = useAuth()
  if (!auth.user && auth.token) await auth.fetchMe()

  if (to.meta.guest && auth.user) {
    return auth.user.role === 'seller' ? next('/seller/products') : next('/buyer/orders')
  }
  if (to.meta.auth && !auth.token) return next('/login')
  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return auth.user?.role === 'seller' ? next('/seller/products') : next('/buyer/orders')
  }
  next()
})

export default router
