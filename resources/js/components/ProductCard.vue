<!-- Product Card Component - Sleek, modern product display -->
<template>
  <div class="product-card group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden border border-gray-100 dark:border-gray-700">
    <!-- Product Image -->
    <div class="relative overflow-hidden bg-gray-100 dark:bg-gray-700 aspect-square">
      <img 
        :src="product.image || '/images/placeholder-product.jpg'"
        :alt="product.name"
        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
        @error="handleImageError"
      >
      
      <!-- Badges -->
      <div class="absolute top-3 left-3 flex flex-col space-y-2">
        <!-- Sale Badge -->
        <span 
          v-if="product.originalPrice && product.originalPrice > product.price"
          class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"
        >
          -{{ Math.round((1 - product.price / product.originalPrice) * 100) }}%
        </span>
        
        <!-- Stock Badge -->
        <span 
          v-if="product.stockCount <= 5 && product.stockCount > 0"
          class="bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full"
        >
          Only {{ product.stockCount }} left
        </span>
        
        <!-- Out of Stock Badge -->
        <span 
          v-if="!product.inStock"
          class="bg-gray-500 text-white text-xs font-bold px-2 py-1 rounded-full"
        >
          Out of Stock
        </span>
        
        <!-- AI Recommended Badge -->
        <span 
          v-if="isRecommendation"
          class="bg-gradient-to-r from-purple-500 to-blue-500 text-white text-xs font-bold px-2 py-1 rounded-full flex items-center space-x-1"
        >
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
          </svg>
          <span>AI Pick</span>
        </span>
      </div>
      
      <!-- Action Buttons -->
      <div class="absolute top-3 right-3 flex flex-col space-y-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
        <button 
          @click="$emit('quick-view', product)"
          class="p-2 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-full shadow-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-all"
          title="Quick View"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
        </button>
        
        <button 
          @click="addToWishlist"
          :class="[
            'p-2 rounded-full shadow-lg transition-all',
            isWishlisted 
              ? 'bg-red-500 text-white hover:bg-red-600' 
              : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-red-500'
          ]"
          title="Add to Wishlist"
        >
          <svg class="w-5 h-5" :fill="isWishlisted ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
          </svg>
        </button>
      </div>
      
      <!-- Hover Overlay -->
      <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-300"></div>
    </div>
    
    <!-- Product Info -->
    <div class="p-5">
      <!-- Product Category -->
      <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-1">
        {{ product.category }}
      </p>
      
      <!-- Product Name -->
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
        {{ product.name }}
      </h3>
      
      <!-- Product Description -->
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
        {{ product.description }}
      </p>
      
      <!-- Rating & Reviews -->
      <div v-if="product.rating" class="flex items-center space-x-2 mb-3">
        <div class="flex items-center">
          <div class="flex">
            <svg 
              v-for="star in 5" 
              :key="star"
              :class="[
                'w-4 h-4',
                star <= Math.floor(product.rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'
              ]"
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
          </div>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300 ml-1">
            {{ product.rating }}
          </span>
        </div>
        
        <span class="text-sm text-gray-500 dark:text-gray-400">
          ({{ product.reviews }} reviews)
        </span>
      </div>
      
      <!-- Product Tags -->
      <div v-if="product.tags && product.tags.length > 0" class="flex flex-wrap gap-1 mb-3">
        <span 
          v-for="tag in product.tags.slice(0, 3)" 
          :key="tag"
          class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded-full"
        >
          #{{ tag }}
        </span>
      </div>
      
      <!-- Price Section -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
          <span class="text-2xl font-bold text-gray-900 dark:text-white">
            ${{ product.price.toFixed(2) }}
          </span>
          
          <span 
            v-if="product.originalPrice && product.originalPrice > product.price"
            class="text-lg text-gray-500 dark:text-gray-400 line-through"
          >
            ${{ product.originalPrice.toFixed(2) }}
          </span>
        </div>
        
        <!-- Stock Indicator -->
        <div class="text-right">
          <div :class="[
            'w-3 h-3 rounded-full',
            product.inStock ? 'bg-green-500' : 'bg-red-500'
          ]"></div>
          <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ product.inStock ? 'In Stock' : 'Out of Stock' }}
          </span>
        </div>
      </div>
      
      <!-- Action Buttons -->
      <div class="space-y-2">
        <!-- Quantity Selector & Add to Cart -->
        <div v-if="product.inStock" class="flex items-center space-x-2">
          <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
            <button 
              @click="quantity > 1 && quantity--"
              :disabled="quantity <= 1"
              class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
              </svg>
            </button>
            
            <input 
              v-model.number="quantity"
              type="number"
              min="1"
              :max="product.stockCount || 999"
              class="w-16 text-center border-none bg-transparent focus:ring-0 focus:outline-none"
            >
            
            <button 
              @click="quantity < (product.stockCount || 999) && quantity++"
              :disabled="quantity >= (product.stockCount || 999)"
              class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
            </button>
          </div>
          
          <button 
            @click="addToCart"
            :disabled="adding"
            class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
          >
            <svg v-if="adding" class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.68 8.32a2 2 0 002 2.68h9.36a2 2 0 002-2.68L17 13"></path>
            </svg>
            
            <span>{{ adding ? 'Adding...' : 'Add to Cart' }}</span>
          </button>
        </div>
        
        <!-- Out of Stock Button -->
        <button 
          v-else
          disabled
          class="w-full bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-medium py-3 px-4 rounded-lg cursor-not-allowed"
        >
          Out of Stock
        </button>
        
        <!-- Buy Now Button -->
        <button 
          v-if="product.inStock"
          @click="buyNow"
          class="w-full border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 font-medium py-2 px-4 rounded-lg transition-all duration-200"
        >
          Buy Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  isRecommendation: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['add-to-cart', 'quick-view', 'buy-now'])

// Reactive data
const quantity = ref(1)
const adding = ref(false)
const isWishlisted = ref(false)

// Methods
const addToCart = async () => {
  if (adding.value) return
  
  adding.value = true
  try {
    // Simulate API call delay
    await new Promise(resolve => setTimeout(resolve, 500))
    
    emit('add-to-cart', props.product, quantity.value)
    
    // Reset quantity after adding
    quantity.value = 1
  } catch (error) {
    console.error('Error adding to cart:', error)
  } finally {
    adding.value = false
  }
}

const buyNow = () => {
  // Add to cart first, then proceed to checkout
  emit('add-to-cart', props.product, quantity.value)
  emit('buy-now', props.product)
}

const addToWishlist = () => {
  isWishlisted.value = !isWishlisted.value
  // Implement wishlist logic here
  console.log(`${isWishlisted.value ? 'Added to' : 'Removed from'} wishlist:`, props.product.name)
}

const handleImageError = (event) => {
  event.target.src = '/images/placeholder-product.jpg'
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Custom number input styles */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}

/* Hover effects */
.product-card:hover .group-hover\\:scale-110 {
  transform: scale(1.1);
}

.product-card:hover .group-hover\\:opacity-100 {
  opacity: 1;
}

/* Animation for add to cart button */
.transform:active {
  transform: scale(0.95);
}

/* Gradient animation */
.bg-gradient-to-r:hover {
  background-size: 200% 200%;
  animation: gradient 2s ease infinite;
}

@keyframes gradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
</style>
