/**
 * Product Sales JavaScript Integration
 * Handles all frontend interactions for the product sales system
 */

class ProductSalesManager {
    constructor() {
        this.apiEndpoints = {
            products: '/tenant/api/products/search',
            recommendations: '/tenant/api/products/ai-recommendations',
            checkout: '/tenant/product-sales/checkout',
            sendCatalog: '/tenant/product-sales/send-catalog'
        };
        
        this.cart = JSON.parse(localStorage.getItem('whatsmark_cart')) || [];
        this.customers = JSON.parse(localStorage.getItem('whatsmark_customers')) || [];
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCartFromStorage();
        this.initializeNotifications();
    }

    bindEvents() {
        // Cart management
        document.addEventListener('add-to-cart', (e) => {
            this.addToCart(e.detail.product, e.detail.quantity);
        });

        document.addEventListener('remove-from-cart', (e) => {
            this.removeFromCart(e.detail.productId);
        });

        document.addEventListener('update-cart-quantity', (e) => {
            this.updateCartQuantity(e.detail.productId, e.detail.quantity);
        });

        // Checkout process
        document.addEventListener('checkout', (e) => {
            this.processCheckout(e.detail.checkoutData);
        });

        // WhatsApp integration
        document.addEventListener('send-whatsapp-catalog', (e) => {
            this.sendWhatsAppCatalog(e.detail.connectionId, e.detail.phone);
        });
    }

    // Cart Management
    addToCart(product, quantity = 1) {
        const existingItem = this.cart.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.cart.push({
                ...product,
                quantity: quantity,
                addedAt: new Date().toISOString()
            });
        }
        
        this.saveCartToStorage();
        this.updateCartUI();
        this.showNotification(`${product.name} added to cart!`, 'success');
    }

    removeFromCart(productId) {
        const index = this.cart.findIndex(item => item.id === productId);
        if (index > -1) {
            const removedItem = this.cart.splice(index, 1)[0];
            this.saveCartToStorage();
            this.updateCartUI();
            this.showNotification(`${removedItem.name} removed from cart`, 'info');
        }
    }

    updateCartQuantity(productId, quantity) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                item.quantity = quantity;
                this.saveCartToStorage();
                this.updateCartUI();
            }
        }
    }

    clearCart() {
        this.cart = [];
        this.saveCartToStorage();
        this.updateCartUI();
        this.showNotification('Cart cleared', 'info');
    }

    getCartTotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    getCartItemCount() {
        return this.cart.reduce((total, item) => total + item.quantity, 0);
    }

    // Storage Management
    saveCartToStorage() {
        localStorage.setItem('whatsmark_cart', JSON.stringify(this.cart));
    }

    loadCartFromStorage() {
        const stored = localStorage.getItem('whatsmark_cart');
        if (stored) {
            this.cart = JSON.parse(stored);
            this.updateCartUI();
        }
    }

    // UI Updates
    updateCartUI() {
        // Update cart badge
        const cartBadge = document.querySelector('.cart-badge');
        if (cartBadge) {
            const count = this.getCartItemCount();
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'flex' : 'none';
        }

        // Update cart total
        const cartTotal = document.querySelector('.cart-total');
        if (cartTotal) {
            cartTotal.textContent = `$${this.getCartTotal().toFixed(2)}`;
        }

        // Trigger custom event for other components
        document.dispatchEvent(new CustomEvent('cart-updated', {
            detail: {
                items: this.cart,
                total: this.getCartTotal(),
                count: this.getCartItemCount()
            }
        }));
    }

    // Product Search & Filtering
    async searchProducts(query, filters = {}) {
        try {
            const params = new URLSearchParams({
                search: query,
                ...filters
            });

            const response = await fetch(`${this.apiEndpoints.products}?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                }
            });

            if (!response.ok) {
                throw new Error('Failed to search products');
            }

            const data = await response.json();
            return data.data || [];
        } catch (error) {
            console.error('Product search error:', error);
            this.showNotification('Failed to search products', 'error');
            return [];
        }
    }

    // AI Recommendations
    async getAIRecommendations(type = 'general', options = {}) {
        try {
            const payload = {
                type: type,
                limit: options.limit || 4,
                customer_id: options.customerId,
                cart_items: type === 'upsell' ? this.cart : undefined,
                purchased_items: options.purchasedItems
            };

            const response = await fetch(this.apiEndpoints.recommendations, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error('Failed to get AI recommendations');
            }

            const data = await response.json();
            return data.data || [];
        } catch (error) {
            console.error('AI recommendations error:', error);
            this.showNotification('Failed to load recommendations', 'error');
            return [];
        }
    }

    // Checkout Process
    async processCheckout(checkoutData) {
        try {
            this.showNotification('Processing your order...', 'info');

            const payload = {
                items: this.cart,
                customer_info: checkoutData.customerInfo,
                payment_method: checkoutData.paymentMethod,
                promo_code: checkoutData.promoCode,
                notes: checkoutData.notes
            };

            const response = await fetch(this.apiEndpoints.checkout, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.clearCart();
                this.showNotification('Order placed successfully!', 'success');
                
                // Redirect to payment if needed
                if (data.data.payment_url) {
                    window.location.href = data.data.payment_url;
                }

                return data.data;
            } else {
                throw new Error(data.message || 'Checkout failed');
            }
        } catch (error) {
            console.error('Checkout error:', error);
            this.showNotification(error.message || 'Checkout failed', 'error');
            throw error;
        }
    }

    // WhatsApp Integration
    async sendWhatsAppCatalog(connectionId, phone, products = null) {
        try {
            this.showNotification('Sending catalog via WhatsApp...', 'info');

            const payload = {
                connection_id: connectionId,
                phone: phone,
                products: products,
                message: 'Check out our amazing products!'
            };

            const response = await fetch(this.apiEndpoints.sendCatalog, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCSRFToken()
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showNotification('Catalog sent successfully!', 'success');
                return data;
            } else {
                throw new Error(data.message || 'Failed to send catalog');
            }
        } catch (error) {
            console.error('WhatsApp catalog error:', error);
            this.showNotification(error.message || 'Failed to send catalog', 'error');
            throw error;
        }
    }

    // Customer Management
    saveCustomer(customerData) {
        const existingIndex = this.customers.findIndex(c => c.phone === customerData.phone);
        
        if (existingIndex > -1) {
            this.customers[existingIndex] = { ...this.customers[existingIndex], ...customerData };
        } else {
            this.customers.push({
                ...customerData,
                id: Date.now(),
                createdAt: new Date().toISOString()
            });
        }
        
        localStorage.setItem('whatsmark_customers', JSON.stringify(this.customers));
    }

    getCustomer(phone) {
        return this.customers.find(c => c.phone === phone);
    }

    // Analytics & Tracking
    trackEvent(eventName, eventData = {}) {
        // Implement your analytics tracking here
        console.log('Track Event:', eventName, eventData);
        
        // Example: Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, eventData);
        }
        
        // Example: Custom analytics
        const analyticsData = {
            event: eventName,
            timestamp: new Date().toISOString(),
            data: eventData,
            cart: {
                itemCount: this.getCartItemCount(),
                total: this.getCartTotal()
            }
        };
        
        // Store locally or send to analytics service
        this.sendAnalytics(analyticsData);
    }

    async sendAnalytics(data) {
        try {
            // Implement your analytics endpoint
            // await fetch('/analytics/track', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(data)
            // });
        } catch (error) {
            console.error('Analytics error:', error);
        }
    }

    // Utility Methods
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Notifications
    initializeNotifications() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification bg-white dark:bg-gray-800 border-l-4 p-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 ${this.getNotificationStyles(type)}`;
        
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    ${this.getNotificationIcon(type)}
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;

        container.appendChild(notification);

        // Slide in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    }

    getNotificationStyles(type) {
        const styles = {
            success: 'border-green-500 bg-green-50 dark:bg-green-900/20',
            error: 'border-red-500 bg-red-50 dark:bg-red-900/20',
            warning: 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
            info: 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
        };
        return styles[type] || styles.info;
    }

    getNotificationIcon(type) {
        const icons = {
            success: '<svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            error: '<svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            warning: '<svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            info: '<svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };
        return icons[type] || icons.info;
    }
}

// Initialize Product Sales Manager
document.addEventListener('DOMContentLoaded', function() {
    window.productSalesManager = new ProductSalesManager();
    
    // Global helper functions
    window.addToCart = (product, quantity) => {
        window.productSalesManager.addToCart(product, quantity);
    };
    
    window.removeFromCart = (productId) => {
        window.productSalesManager.removeFromCart(productId);
    };
    
    window.updateCartQuantity = (productId, quantity) => {
        window.productSalesManager.updateCartQuantity(productId, quantity);
    };
    
    window.getAIRecommendations = (type, options) => {
        return window.productSalesManager.getAIRecommendations(type, options);
    };
    
    window.sendWhatsAppCatalog = (connectionId, phone) => {
        return window.productSalesManager.sendWhatsAppCatalog(connectionId, phone);
    };
});

// Export for module usage
export { ProductSalesManager };
