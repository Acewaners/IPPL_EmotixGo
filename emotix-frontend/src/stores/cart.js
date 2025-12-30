import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
  state: () => ({
    cartItems: [],   
    wishlist: [],    
  }),

  getters: {
    cartCount: (state) =>
      state.cartItems.reduce((sum, item) => sum + item.quantity, 0),

    cartSubtotal: (state) =>
      state.cartItems.reduce(
        (sum, item) => sum + item.quantity * Number(item.product.price || 0),
        0
      ),

    isInWishlist: (state) => (productId) =>
      state.wishlist.some((w) => w.product.product_id === productId),
  },

  actions: {
    addToWishlist(product) {
      if (!this.isInWishlist(product.product_id)) {
        this.wishlist.push({ product })
      }
    },

    removeFromWishlist(productId) {
      this.wishlist = this.wishlist.filter(
        (w) => w.product.product_id !== productId
      )
    },

    toggleWishlist(product) {
      if (this.isInWishlist(product.product_id)) {
        this.removeFromWishlist(product.product_id)
      } else {
        this.addToWishlist(product)
      }
    },

    addToCart(product, qty = 1) {
      const existing = this.cartItems.find(
        (i) => i.product_id === product.product_id
      )

      if (existing) {
        existing.quantity += qty
      } else {
        this.cartItems.push({
          product_id: product.product_id,
          product,
          quantity: qty,
        })
      }
    },

    updateQuantity(productId, quantity) {
      const item = this.cartItems.find(
        (c) => c.product.product_id === productId
      )
      if (!item) return

      if (quantity <= 0) {
        this.removeFromCart(productId)
      } else {
        item.quantity = quantity
      }
    },

    removeFromCart(productId) {
      this.cartItems = this.cartItems.filter(
        (c) => c.product.product_id !== productId
      )
    },

    clearCart() {
      this.cartItems = []
    },

    clearUserData() {
      this.cartItems = []
      this.wishlist = []
    },

    moveWishlistToCart() {
      this.wishlist.forEach((w) => this.addToCart(w.product, 1))
      this.wishlist = []
    },
  },
})
