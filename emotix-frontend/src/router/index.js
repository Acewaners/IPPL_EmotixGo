import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../stores/auth'

const Login = () => import('../pages/Login.vue')
const Register = () => import('../pages/Register.vue')
const SellerProducts = () => import('../pages/SellerProducts.vue')
const BuyerOrders = () => import('../pages/BuyerOrders.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: Login, meta: { guest: true } },
    { path: '/register', component: Register, meta: { guest: true } },
    { path: '/seller/products', component: SellerProducts, meta: { auth: true, role: 'seller' } },
    { path: '/buyer/orders', component: BuyerOrders, meta: { auth: true, role: 'buyer' } },
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
