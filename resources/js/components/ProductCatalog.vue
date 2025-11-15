<!-- Product Catalog - Top-notch e-commerce interface for WhatsMark -->
<template>
  <div class="product-catalog bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800 min-h-screen">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 shadow-lg border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                🛍️ Product Catalog
              </h1>
            </div>
          </div>
          
          <!-- Cart Icon with Badge -->
          <div class="flex items-center space-x-4">
            <button 
              @click="toggleCart"
              class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.68 8.32a2 2 0 002 2.68h9.36a2 2 0 002-2.68L17 13"></path>
              </svg>
              <span v-if="cartItemsCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                {{ cartItemsCount }}
              </span>
            </button>
            
            <div class="text-right">
              <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
              <p class="font-bold text-lg text-green-600 dark:text-green-400">
                ${{ cartTotal.toFixed(2) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters & Search Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Search Bar -->
        <div class="md:col-span-2">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search products..."
              class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
            >
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </div>
        </div>

        <!-- Category Filter -->
        <div>
          <select 
            v-model="selectedCategory"
            class="w-full py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category" :value="category">
              {{ category }}
            </option>
          </select>
        </div>

        <!-- Price Filter -->
        <div>
          <select 
            v-model="priceRange"
            class="w-full py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">All Prices</option>
            <option value="0-50">$0 - $50</option>
            <option value="50-100">$50 - $100</option>
            <option value="100-200">$100 - $200</option>
            <option value="200+">$200+</option>
          </select>
        </div>
      </div>

      <!-- AI Recommendations Section -->
      <div v-if="aiRecommendations.length > 0" class="mb-8">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
          <div class="flex items-center space-x-3 mb-4">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
              </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              🤖 AI Recommended For You
            </h2>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <ProductCard 
              v-for="product in aiRecommendations" 
              :key="`ai-${product.id}`"
              :product="product"
              :is-recommendation="true"
              @add-to-cart="addToCart"
              @quick-view="openQuickView"
            />
          </div>
        </div>
      </div>

      <!-- Products Grid -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Products ({{ filteredProducts.length }})
          </h2>
          
          <!-- Sort Options -->
          <select 
            v-model="sortBy"
            class="py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="name">Sort by Name</option>
            <option value="price-low">Price: Low to High</option>
            <option value="price-high">Price: High to Low</option>
            <option value="rating">Highest Rated</option>
            <option value="newest">Newest First</option>
          </select>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <ProductCard 
            v-for="product in paginatedProducts" 
            :key="product.id"
            :product="product"
            @add-to-cart="addToCart"
            @quick-view="openQuickView"
          />
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-8 flex justify-center">
          <nav class="flex items-center space-x-2">
            <button 
              @click="currentPage > 1 && (currentPage--)"
              :disabled="currentPage === 1"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            
            <button 
              v-for="page in totalPages" 
              :key="page"
              @click="currentPage = page"
              :class="[
                'px-3 py-2 border text-sm font-medium rounded-md',
                currentPage === page 
                  ? 'bg-blue-600 border-blue-600 text-white' 
                  : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
              ]"
            >
              {{ page }}
            </button>
            
            <button 
              @click="currentPage < totalPages && (currentPage++)"
              :disabled="currentPage === totalPages"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- Shopping Cart Sidebar -->
    <ShoppingCart 
      v-if="showCart"
      :items="cartItems"
      :total="cartTotal"
      @close="toggleCart"
      @update-quantity="updateCartQuantity"
      @remove-item="removeFromCart"
      @checkout="proceedToCheckout"
    />

    <!-- Quick View Modal -->
    <ProductQuickView 
      v-if="quickViewProduct"
      :product="quickViewProduct"
      @close="closeQuickView"
      @add-to-cart="addToCart"
    />

    <!-- Loading Overlay -->
    <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-gray-900 dark:text-white font-medium">Loading...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import ProductCard from './ProductCard.vue'
import ShoppingCart from './ShoppingCart.vue'
import ProductQuickView from './ProductQuickView.vue'

// Reactive data
const products = ref([])
const cartItems = ref([])
const searchQuery = ref('')
const selectedCategory = ref('')
const priceRange = ref('')
const sortBy = ref('name')
const currentPage = ref(1)
const itemsPerPage = ref(12)
const showCart = ref(false)
const quickViewProduct = ref(null)
const loading = ref(false)
const aiRecommendations = ref([])

// Sample categories
const categories = ref([
  'Electronics',
  'Clothing',
  'Books',
  'Home & Garden',
  'Sports',
  'Beauty',
  'Toys',
  'Automotive'
])

// Computed properties
const filteredProducts = computed(() => {
  let filtered = products.value

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(product => 
      product.name.toLowerCase().includes(query) ||
      product.description.toLowerCase().includes(query) ||
      product.category.toLowerCase().includes(query)
    )
  }

  // Category filter
  if (selectedCategory.value) {
    filtered = filtered.filter(product => product.category === selectedCategory.value)
  }

  // Price range filter
  if (priceRange.value) {
    const [min, max] = priceRange.value.split('-').map(p => p.replace('+', ''))
    filtered = filtered.filter(product => {
      if (priceRange.value.includes('+')) {
        return product.price >= parseInt(min)
      }
      return product.price >= parseInt(min) && product.price <= parseInt(max)
    })
  }

  // Sort
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'price-low':
        return a.price - b.price
      case 'price-high':
        return b.price - a.price
      case 'rating':
        return b.rating - a.rating
      case 'newest':
        return new Date(b.created_at) - new Date(a.created_at)
      default:
        return a.name.localeCompare(b.name)
    }
  })

  return filtered
})

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredProducts.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredProducts.value.length / itemsPerPage.value)
})

const cartItemsCount = computed(() => {
  return cartItems.value.reduce((total, item) => total + item.quantity, 0)
})

const cartTotal = computed(() => {
  return cartItems.value.reduce((total, item) => total + (item.price * item.quantity), 0)
})

// Methods
const loadProducts = async () => {
  loading.value = true
  try {
    // Simulate API call - replace with actual API
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Sample products data
    products.value = [
      {
        id: 1,
        name: 'Premium Wireless Headphones',
        description: 'High-quality wireless headphones with noise cancellation',
        price: 199.99,
        originalPrice: 249.99,
        category: 'Electronics',
        image: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500',
        rating: 4.8,
        reviews: 128,
        inStock: true,
        stockCount: 15,
        tags: ['wireless', 'premium', 'noise-cancelling'],
        created_at: '2024-01-15'
      },
      {
        id: 2,
        name: 'Smart Fitness Watch',
        description: 'Track your fitness goals with this advanced smartwatch',
        price: 299.99,
        category: 'Electronics',
        image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500',
        rating: 4.6,
        reviews: 89,
        inStock: true,
        stockCount: 8,
        tags: ['fitness', 'smart', 'waterproof'],
        created_at: '2024-01-20'
      },
      // Add more sample products...
    ]
    
    // Load AI recommendations
    await loadAIRecommendations()
  } catch (error) {
    console.error('Error loading products:', error)
  } finally {
    loading.value = false
  }
}

const loadAIRecommendations = async () => {
  try {
    // Simulate AI recommendation API call
    // This would integrate with your existing AI system
    aiRecommendations.value = products.value.slice(0, 4)
  } catch (error) {
    console.error('Error loading AI recommendations:', error)
  }
}

const addToCart = (product, quantity = 1) => {
  const existingItem = cartItems.value.find(item => item.id === product.id)
  
  if (existingItem) {
    existingItem.quantity += quantity
  } else {
    cartItems.value.push({
      ...product,
      quantity: quantity
    })
  }
  
  // Show success message
  showNotification(`${product.name} added to cart!`, 'success')
}

const updateCartQuantity = (productId, quantity) => {
  const item = cartItems.value.find(item => item.id === productId)
  if (item) {
    item.quantity = quantity
  }
}

const removeFromCart = (productId) => {
  const index = cartItems.value.findIndex(item => item.id === productId)
  if (index > -1) {
    cartItems.value.splice(index, 1)
  }
}

const toggleCart = () => {
  showCart.value = !showCart.value
}

const openQuickView = (product) => {
  quickViewProduct.value = product
}

const closeQuickView = () => {
  quickViewProduct.value = null
}

const proceedToCheckout = () => {
  // Implement checkout logic
  console.log('Proceeding to checkout with items:', cartItems.value)
  // This would redirect to checkout page or open checkout modal
}

const showNotification = (message, type = 'info') => {
  // Implement notification system
  console.log(`${type.toUpperCase()}: ${message}`)
}

// Watch for search query changes to reset pagination
watch(searchQuery, () => {
  currentPage.value = 1
})

watch(selectedCategory, () => {
  currentPage.value = 1
})

watch(priceRange, () => {
  currentPage.value = 1
})

// Load data on mount
onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
.product-catalog {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Custom scrollbar for cart */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Animations */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  
  .md\\:col-span-2 {
    grid-column: span 1;
  }
}
</style>
