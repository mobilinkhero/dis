<!-- Product Quick View Modal - Detailed product preview -->
<template>
  <div class="quick-view-overlay fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div 
      class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"
      @click="$emit('close')"
    ></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform transition-all duration-300">
      <!-- Close Button -->
      <button 
        @click="$emit('close')"
        class="absolute top-4 right-4 z-10 p-2 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full shadow-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-all"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
      
      <!-- Modal Body -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-0 h-full">
        <!-- Product Images -->
        <div class="relative bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
          <!-- Main Image -->
          <div class="w-full h-96 md:h-full flex items-center justify-center p-8">
            <img 
              :src="currentImage"
              :alt="product.name"
              class="max-w-full max-h-full object-contain"
              @error="handleImageError"
            >
          </div>
          
          <!-- Image Navigation -->
          <div v-if="product.images && product.images.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
            <div class="flex space-x-2">
              <button 
                v-for="(image, index) in product.images" 
                :key="index"
                @click="currentImageIndex = index"
                :class="[
                  'w-3 h-3 rounded-full transition-all',
                  currentImageIndex === index ? 'bg-blue-600' : 'bg-gray-300 hover:bg-gray-400'
                ]"
              ></button>
            </div>
          </div>
          
          <!-- Zoom Icon -->
          <button 
            @click="toggleZoom"
            class="absolute top-4 left-4 p-2 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full shadow-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
            </svg>
          </button>
        </div>
        
        <!-- Product Details -->
        <div class="p-8 overflow-y-auto">
          <!-- Product Category & Brand -->
          <div class="flex items-center space-x-2 mb-2">
            <span class="text-sm font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">
              {{ product.category }}
            </span>
            <span v-if="product.brand" class="text-sm text-gray-500 dark:text-gray-400">
              • {{ product.brand }}
            </span>
          </div>
          
          <!-- Product Name -->
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
            {{ product.name }}
          </h1>
          
          <!-- Rating & Reviews -->
          <div v-if="product.rating" class="flex items-center space-x-4 mb-4">
            <div class="flex items-center space-x-1">
              <div class="flex">
                <svg 
                  v-for="star in 5" 
                  :key="star"
                  :class="[
                    'w-5 h-5',
                    star <= Math.floor(product.rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'
                  ]"
                  fill="currentColor" 
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                </svg>
              </div>
              <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                {{ product.rating }}
              </span>
            </div>
            
            <button class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
              ({{ product.reviews }} reviews)
            </button>
          </div>
          
          <!-- Price -->
          <div class="mb-6">
            <div class="flex items-center space-x-3 mb-2">
              <span class="text-4xl font-bold text-gray-900 dark:text-white">
                ${{ product.price.toFixed(2) }}
              </span>
              
              <span 
                v-if="product.originalPrice && product.originalPrice > product.price"
                class="text-2xl text-gray-500 dark:text-gray-400 line-through"
              >
                ${{ product.originalPrice.toFixed(2) }}
              </span>
              
              <span 
                v-if="product.originalPrice && product.originalPrice > product.price"
                class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm font-bold px-2 py-1 rounded-full"
              >
                Save {{ Math.round((1 - product.price / product.originalPrice) * 100) }}%
              </span>
            </div>
            
            <!-- Stock Status -->
            <div class="flex items-center space-x-2">
              <div :class="[
                'w-3 h-3 rounded-full',
                product.inStock ? 'bg-green-500' : 'bg-red-500'
              ]"></div>
              <span :class="[
                'font-medium',
                product.inStock ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
              ]">
                {{ product.inStock ? `In Stock (${product.stockCount} available)` : 'Out of Stock' }}
              </span>
            </div>
          </div>
          
          <!-- Product Description -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
              {{ product.description || 'No description available.' }}
            </p>
          </div>
          
          <!-- Product Features -->
          <div v-if="product.features && product.features.length > 0" class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Key Features</h3>
            <ul class="space-y-2">
              <li 
                v-for="feature in product.features" 
                :key="feature"
                class="flex items-start space-x-3"
              >
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-600 dark:text-gray-400">{{ feature }}</span>
              </li>
            </ul>
          </div>
          
          <!-- Product Variants -->
          <div v-if="product.variants && product.variants.length > 0" class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Options</h3>
            
            <!-- Size Variants -->
            <div v-if="product.variants.includes('size')" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Size</label>
              <div class="flex flex-wrap gap-2">
                <button 
                  v-for="size in availableSizes" 
                  :key="size"
                  @click="selectedSize = size"
                  :class="[
                    'px-4 py-2 border rounded-lg font-medium transition-all',
                    selectedSize === size 
                      ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' 
                      : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-blue-400'
                  ]"
                >
                  {{ size }}
                </button>
              </div>
            </div>
            
            <!-- Color Variants -->
            <div v-if="product.variants.includes('color')" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
              <div class="flex flex-wrap gap-2">
                <button 
                  v-for="color in availableColors" 
                  :key="color"
                  @click="selectedColor = color"
                  :class="[
                    'px-4 py-2 border rounded-lg font-medium transition-all capitalize',
                    selectedColor === color 
                      ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' 
                      : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-blue-400'
                  ]"
                >
                  {{ color }}
                </button>
              </div>
            </div>
          </div>
          
          <!-- Quantity & Add to Cart -->
          <div v-if="product.inStock" class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity</label>
            <div class="flex items-center space-x-4">
              <!-- Quantity Selector -->
              <div class="flex items-center border-2 border-gray-300 dark:border-gray-600 rounded-lg">
                <button 
                  @click="quantity > 1 && quantity--"
                  :disabled="quantity <= 1"
                  class="p-3 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                  </svg>
                </button>
                
                <input 
                  v-model.number="quantity"
                  type="number"
                  min="1"
                  :max="product.stockCount || 999"
                  class="w-20 text-center text-lg font-semibold border-none bg-transparent focus:ring-0 focus:outline-none dark:text-white"
                >
                
                <button 
                  @click="quantity < (product.stockCount || 999) && quantity++"
                  :disabled="quantity >= (product.stockCount || 999)"
                  class="p-3 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                  </svg>
                </button>
              </div>
              
              <!-- Total Price -->
              <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                  ${{ (product.price * quantity).toFixed(2) }}
                </p>
              </div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="space-y-3">
            <!-- Add to Cart -->
            <button 
              v-if="product.inStock"
              @click="addToCart"
              :disabled="adding"
              class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-3"
            >
              <svg v-if="adding" class="animate-spin w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.68 8.32a2 2 0 002 2.68h9.36a2 2 0 002-2.68L17 13"></path>
              </svg>
              
              <span class="text-lg">{{ adding ? 'Adding to Cart...' : 'Add to Cart' }}</span>
            </button>
            
            <!-- Out of Stock -->
            <button 
              v-else
              disabled
              class="w-full bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-bold py-4 px-6 rounded-xl cursor-not-allowed"
            >
              Out of Stock
            </button>
            
            <!-- Wishlist & Share -->
            <div class="flex space-x-3">
              <button 
                @click="toggleWishlist"
                :class="[
                  'flex-1 border-2 font-medium py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2',
                  isWishlisted 
                    ? 'border-red-500 text-red-500 bg-red-50 dark:bg-red-900/20' 
                    : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-red-500 hover:text-red-500'
                ]"
              >
                <svg class="w-5 h-5" :fill="isWishlisted ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span>{{ isWishlisted ? 'Wishlisted' : 'Wishlist' }}</span>
              </button>
              
              <button 
                @click="shareProduct"
                class="flex-1 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 font-medium py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center space-x-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                </svg>
                <span>Share</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'add-to-cart'])

// Reactive data
const quantity = ref(1)
const adding = ref(false)
const isWishlisted = ref(false)
const currentImageIndex = ref(0)
const selectedSize = ref('')
const selectedColor = ref('')

// Sample data
const availableSizes = ref(['XS', 'S', 'M', 'L', 'XL', 'XXL'])
const availableColors = ref(['black', 'white', 'navy', 'red', 'blue'])

// Computed properties
const currentImage = computed(() => {
  if (props.product.images && props.product.images.length > 0) {
    return props.product.images[currentImageIndex.value]
  }
  return props.product.image || '/images/placeholder-product.jpg'
})

// Methods
const addToCart = async () => {
  if (adding.value) return
  
  adding.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    const productToAdd = {
      ...props.product,
      selectedSize: selectedSize.value,
      selectedColor: selectedColor.value
    }
    
    emit('add-to-cart', productToAdd, quantity.value)
    emit('close')
  } catch (error) {
    console.error('Error adding to cart:', error)
  } finally {
    adding.value = false
  }
}

const toggleWishlist = () => {
  isWishlisted.value = !isWishlisted.value
  console.log(`${isWishlisted.value ? 'Added to' : 'Removed from'} wishlist:`, props.product.name)
}

const shareProduct = async () => {
  if (navigator.share) {
    try {
      await navigator.share({
        title: props.product.name,
        text: props.product.description,
        url: window.location.href
      })
    } catch (err) {
      console.log('Error sharing:', err)
    }
  } else {
    // Fallback: copy to clipboard
    await navigator.clipboard.writeText(window.location.href)
    console.log('Product URL copied to clipboard!')
  }
}

const toggleZoom = () => {
  // Implement image zoom functionality
  console.log('Zoom functionality not implemented yet')
}

const handleImageError = (event) => {
  event.target.src = '/images/placeholder-product.jpg'
}

// Initialize default selections
onMounted(() => {
  if (availableSizes.value.length > 0) {
    selectedSize.value = availableSizes.value.find(size => size === 'M') || availableSizes.value[0]
  }
  if (availableColors.value.length > 0) {
    selectedColor.value = availableColors.value[0]
  }
})
</script>

<style scoped>
.quick-view-overlay {
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

/* Custom number input */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}

/* Animations */
.transform:hover {
  transform: scale(1.05);
}

.transform:active {
  transform: scale(0.95);
}

/* Image animation */
img {
  transition: transform 0.3s ease;
}

/* Modal animation */
.quick-view-overlay {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.relative.bg-white {
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { 
    opacity: 0;
    transform: translateY(30px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
