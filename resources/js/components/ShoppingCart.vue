<!-- Shopping Cart Sidebar - Modern, feature-rich cart -->
<template>
  <div class="shopping-cart-overlay fixed inset-0 z-50">
    <!-- Backdrop -->
    <div 
      class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"
      @click="$emit('close')"
    ></div>
    
    <!-- Cart Sidebar -->
    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white dark:bg-gray-900 shadow-2xl transform transition-transform duration-300 ease-in-out flex flex-col">
      <!-- Cart Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.68 8.32a2 2 0 002 2.68h9.36a2 2 0 002-2.68L17 13"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Shopping Cart</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ itemCount }} {{ itemCount === 1 ? 'item' : 'items' }}</p>
          </div>
        </div>
        
        <button 
          @click="$emit('close')"
          class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors rounded-lg hover:bg-white dark:hover:bg-gray-800"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <!-- Cart Items -->
      <div class="flex-1 overflow-y-auto p-6 space-y-4">
        <!-- Empty Cart -->
        <div v-if="items.length === 0" class="text-center py-12">
          <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.68 8.32a2 2 0 002 2.68h9.36a2 2 0 002-2.68L17 13"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Your cart is empty</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">Add some products to get started!</p>
          <button 
            @click="$emit('close')"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors"
          >
            Continue Shopping
          </button>
        </div>
        
        <!-- Cart Items List -->
        <div v-else class="space-y-4">
          <div 
            v-for="item in items" 
            :key="item.id"
            class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 transition-all duration-200 hover:shadow-md"
          >
            <div class="flex space-x-4">
              <!-- Product Image -->
              <div class="flex-shrink-0">
                <img 
                  :src="item.image || '/images/placeholder-product.jpg'"
                  :alt="item.name"
                  class="w-16 h-16 object-cover rounded-lg"
                >
              </div>
              
              <!-- Product Details -->
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ item.name }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                  {{ item.category }}
                </p>
                
                <!-- Price & Quantity Controls -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <!-- Quantity Controls -->
                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                      <button 
                        @click="updateQuantity(item.id, item.quantity - 1)"
                        :disabled="item.quantity <= 1"
                        class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors rounded-l-lg"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                      </button>
                      
                      <span class="px-3 py-1 text-sm font-medium text-gray-900 dark:text-white">
                        {{ item.quantity }}
                      </span>
                      
                      <button 
                        @click="updateQuantity(item.id, item.quantity + 1)"
                        class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors rounded-r-lg"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                      </button>
                    </div>
                    
                    <!-- Price -->
                    <div class="text-right">
                      <p class="text-sm font-bold text-gray-900 dark:text-white">
                        ${{ (item.price * item.quantity).toFixed(2) }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        ${{ item.price.toFixed(2) }} each
                      </p>
                    </div>
                  </div>
                  
                  <!-- Remove Button -->
                  <button 
                    @click="removeItem(item.id)"
                    class="p-1 text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors"
                    title="Remove item"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Cart Summary & Actions -->
      <div v-if="items.length > 0" class="border-t border-gray-200 dark:border-gray-700 p-6 bg-gray-50 dark:bg-gray-800">
        <!-- Promo Code Section -->
        <div class="mb-4">
          <div class="flex space-x-2">
            <input 
              v-model="promoCode"
              type="text"
              placeholder="Enter promo code"
              class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
            <button 
              @click="applyPromoCode"
              :disabled="!promoCode.trim()"
              class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Apply
            </button>
          </div>
          
          <!-- Applied Promo -->
          <div v-if="appliedPromo" class="mt-2 flex items-center justify-between text-sm">
            <span class="text-green-600 dark:text-green-400 font-medium">
              {{ appliedPromo.code }} applied (-{{ appliedPromo.discount }}%)
            </span>
            <button 
              @click="removePromoCode"
              class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
            >
              Remove
            </button>
          </div>
        </div>
        
        <!-- Order Summary -->
        <div class="space-y-2 mb-4">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
            <span class="font-medium text-gray-900 dark:text-white">
              ${{ subtotal.toFixed(2) }}
            </span>
          </div>
          
          <div v-if="appliedPromo" class="flex justify-between text-sm">
            <span class="text-green-600 dark:text-green-400">Discount</span>
            <span class="font-medium text-green-600 dark:text-green-400">
              -${{ discount.toFixed(2) }}
            </span>
          </div>
          
          <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Shipping</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ freeShipping ? 'Free' : `$${shipping.toFixed(2)}` }}
            </span>
          </div>
          
          <div class="flex justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">Tax</span>
            <span class="font-medium text-gray-900 dark:text-white">
              ${{ tax.toFixed(2) }}
            </span>
          </div>
          
          <div class="border-t border-gray-200 dark:border-gray-600 pt-2">
            <div class="flex justify-between text-lg font-bold">
              <span class="text-gray-900 dark:text-white">Total</span>
              <span class="text-gray-900 dark:text-white">
                ${{ finalTotal.toFixed(2) }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Free Shipping Progress -->
        <div v-if="!freeShipping && freeShippingThreshold" class="mb-4">
          <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-600 dark:text-gray-400">
              Add ${{ (freeShippingThreshold - subtotal).toFixed(2) }} for free shipping
            </span>
            <span class="text-sm font-medium">
              {{ Math.round((subtotal / freeShippingThreshold) * 100) }}%
            </span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div 
              class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: Math.min((subtotal / freeShippingThreshold) * 100, 100) + '%' }"
            ></div>
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="space-y-3">
          <!-- Checkout Button -->
          <button 
            @click="proceedToCheckout"
            :disabled="processing"
            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
          >
            <svg v-if="processing" class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            
            <span>{{ processing ? 'Processing...' : 'Checkout' }}</span>
          </button>
          
          <!-- Continue Shopping -->
          <button 
            @click="$emit('close')"
            class="w-full border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 font-medium py-3 px-6 rounded-xl transition-all duration-200"
          >
            Continue Shopping
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  total: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close', 'update-quantity', 'remove-item', 'checkout'])

// Reactive data
const promoCode = ref('')
const appliedPromo = ref(null)
const processing = ref(false)

// Constants
const TAX_RATE = 0.08 // 8% tax
const SHIPPING_COST = 9.99
const FREE_SHIPPING_THRESHOLD = 75

// Computed properties
const itemCount = computed(() => {
  return props.items.reduce((total, item) => total + item.quantity, 0)
})

const subtotal = computed(() => {
  return props.items.reduce((total, item) => total + (item.price * item.quantity), 0)
})

const discount = computed(() => {
  if (!appliedPromo.value) return 0
  return subtotal.value * (appliedPromo.value.discount / 100)
})

const discountedSubtotal = computed(() => {
  return subtotal.value - discount.value
})

const freeShipping = computed(() => {
  return subtotal.value >= FREE_SHIPPING_THRESHOLD
})

const shipping = computed(() => {
  return freeShipping.value ? 0 : SHIPPING_COST
})

const tax = computed(() => {
  return discountedSubtotal.value * TAX_RATE
})

const finalTotal = computed(() => {
  return discountedSubtotal.value + shipping.value + tax.value
})

const freeShippingThreshold = computed(() => {
  return FREE_SHIPPING_THRESHOLD
})

// Methods
const updateQuantity = (itemId, newQuantity) => {
  if (newQuantity < 1) return
  emit('update-quantity', itemId, newQuantity)
}

const removeItem = (itemId) => {
  emit('remove-item', itemId)
}

const applyPromoCode = () => {
  // Simulate promo code validation
  const validPromoCodes = {
    'SAVE10': { code: 'SAVE10', discount: 10 },
    'WELCOME15': { code: 'WELCOME15', discount: 15 },
    'SPRING20': { code: 'SPRING20', discount: 20 }
  }
  
  const code = promoCode.value.toUpperCase()
  if (validPromoCodes[code]) {
    appliedPromo.value = validPromoCodes[code]
    promoCode.value = ''
    showNotification(`Promo code ${code} applied! You saved ${validPromoCodes[code].discount}%`, 'success')
  } else {
    showNotification('Invalid promo code', 'error')
  }
}

const removePromoCode = () => {
  appliedPromo.value = null
  showNotification('Promo code removed', 'info')
}

const proceedToCheckout = async () => {
  processing.value = true
  try {
    // Simulate checkout process
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    emit('checkout', {
      items: props.items,
      subtotal: subtotal.value,
      discount: discount.value,
      shipping: shipping.value,
      tax: tax.value,
      total: finalTotal.value,
      promoCode: appliedPromo.value?.code
    })
  } catch (error) {
    console.error('Checkout error:', error)
    showNotification('Checkout failed. Please try again.', 'error')
  } finally {
    processing.value = false
  }
}

const showNotification = (message, type = 'info') => {
  // Implement notification system
  console.log(`${type.toUpperCase()}: ${message}`)
}
</script>

<style scoped>
.shopping-cart-overlay {
  backdrop-filter: blur(4px);
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Dark mode scrollbar */
.dark .overflow-y-auto::-webkit-scrollbar-track {
  background: #374151;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
  background: #6b7280;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Animations */
.transform:hover {
  transform: scale(1.05);
}

.transform:active {
  transform: scale(0.95);
}

/* Progress bar animation */
.bg-gradient-to-r {
  transition: width 0.3s ease-in-out;
}
</style>
