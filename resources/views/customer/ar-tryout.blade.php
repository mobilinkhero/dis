<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AR Virtual Try-On Experience</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="{{ asset('js/ar-visualization.js') }}"></script>
    <style>
        .ar-canvas {
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .product-selector {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
        }
        .floating-controls {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen">
    <!-- Header -->
    <div class="text-white p-6">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold flex items-center mb-4">
                <i data-lucide="scan-line" class="w-10 h-10 mr-4"></i>
                AR Virtual Try-On Experience
            </h1>
            <p class="text-xl opacity-90">See how products look on you with cutting-edge AR technology</p>
        </div>
    </div>

    <div class="container mx-auto px-6">
        <!-- AR Camera Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Main AR View -->
            <div class="lg:col-span-2">
                <div class="bg-black rounded-xl p-4 relative">
                    <canvas id="ar-canvas" class="ar-canvas w-full h-96 bg-gray-800"></canvas>
                    
                    <!-- AR Status Indicator -->
                    <div id="ar-status" class="absolute top-6 left-6 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold pulse-animation">
                        <i data-lucide="camera-off" class="w-4 h-4 inline mr-1"></i>
                        Camera Off
                    </div>
                    
                    <!-- AR Controls Overlay -->
                    <div class="absolute top-6 right-6 space-y-2">
                        <button id="switch-camera" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-3 rounded-full backdrop-blur">
                            <i data-lucide="rotate-cw" class="w-5 h-5"></i>
                        </button>
                        <button id="capture-photo" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-3 rounded-full backdrop-blur">
                            <i data-lucide="camera" class="w-5 h-5"></i>
                        </button>
                    </div>
                    
                    <!-- Permission Request -->
                    <div id="permission-request" class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-90 rounded-xl">
                        <div class="text-center text-white">
                            <i data-lucide="camera" class="w-20 h-20 mx-auto mb-4 opacity-50"></i>
                            <h3 class="text-2xl font-bold mb-4">Camera Access Required</h3>
                            <p class="mb-6 opacity-80">Allow camera access to start your AR try-on experience</p>
                            <button id="start-ar" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold">
                                Start AR Experience
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- AR Features Info -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white bg-opacity-10 text-white p-4 rounded-lg text-center backdrop-blur">
                        <i data-lucide="eye" class="w-8 h-8 mx-auto mb-2"></i>
                        <p class="text-sm font-semibold">Face Tracking</p>
                    </div>
                    <div class="bg-white bg-opacity-10 text-white p-4 rounded-lg text-center backdrop-blur">
                        <i data-lucide="ruler" class="w-8 h-8 mx-auto mb-2"></i>
                        <p class="text-sm font-semibold">Size Detection</p>
                    </div>
                    <div class="bg-white bg-opacity-10 text-white p-4 rounded-lg text-center backdrop-blur">
                        <i data-lucide="palette" class="w-8 h-8 mx-auto mb-2"></i>
                        <p class="text-sm font-semibold">Color Matching</p>
                    </div>
                    <div class="bg-white bg-opacity-10 text-white p-4 rounded-lg text-center backdrop-blur">
                        <i data-lucide="share" class="w-8 h-8 mx-auto mb-2"></i>
                        <p class="text-sm font-semibold">Social Share</p>
                    </div>
                </div>
            </div>

            <!-- Product Selection Panel -->
            <div class="product-selector rounded-xl p-6">
                <h2 class="text-2xl font-bold mb-6">Choose Product to Try</h2>
                
                <!-- Category Tabs -->
                <div class="flex space-x-2 mb-6">
                    <button class="tab-btn active bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold" data-category="clothing">
                        Clothing
                    </button>
                    <button class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold" data-category="accessories">
                        Accessories
                    </button>
                    <button class="tab-btn bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold" data-category="eyewear">
                        Eyewear
                    </button>
                </div>
                
                <!-- Products Grid -->
                <div id="products-grid" class="space-y-4 max-h-96 overflow-y-auto">
                    <!-- Clothing Items -->
                    <div class="product-item p-4 border border-gray-200 rounded-lg hover:border-blue-500 cursor-pointer transition-all" data-product="jacket">
                        <div class="flex items-center space-x-4">
                            <img src="https://via.placeholder.com/60x60/3B82F6/FFFFFF?text=👔" alt="Jacket" class="w-15 h-15 rounded-lg">
                            <div class="flex-1">
                                <h3 class="font-semibold">Denim Jacket</h3>
                                <p class="text-sm text-gray-600">$89.99</p>
                                <div class="flex space-x-1 mt-2">
                                    <div class="w-4 h-4 bg-blue-600 rounded-full"></div>
                                    <div class="w-4 h-4 bg-gray-800 rounded-full"></div>
                                    <div class="w-4 h-4 bg-red-600 rounded-full"></div>
                                </div>
                            </div>
                            <button class="try-on-btn bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                                Try On
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-item p-4 border border-gray-200 rounded-lg hover:border-blue-500 cursor-pointer transition-all" data-product="tshirt">
                        <div class="flex items-center space-x-4">
                            <img src="https://via.placeholder.com/60x60/10B981/FFFFFF?text=👕" alt="T-Shirt" class="w-15 h-15 rounded-lg">
                            <div class="flex-1">
                                <h3 class="font-semibold">Cotton T-Shirt</h3>
                                <p class="text-sm text-gray-600">$29.99</p>
                                <div class="flex space-x-1 mt-2">
                                    <div class="w-4 h-4 bg-white border rounded-full"></div>
                                    <div class="w-4 h-4 bg-black rounded-full"></div>
                                    <div class="w-4 h-4 bg-red-600 rounded-full"></div>
                                </div>
                            </div>
                            <button class="try-on-btn bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                                Try On
                            </button>
                        </div>
                    </div>

                    <div class="product-item p-4 border border-gray-200 rounded-lg hover:border-blue-500 cursor-pointer transition-all" data-product="dress">
                        <div class="flex items-center space-x-4">
                            <img src="https://via.placeholder.com/60x60/8B5CF6/FFFFFF?text=👗" alt="Dress" class="w-15 h-15 rounded-lg">
                            <div class="flex-1">
                                <h3 class="font-semibold">Summer Dress</h3>
                                <p class="text-sm text-gray-600">$79.99</p>
                                <div class="flex space-x-1 mt-2">
                                    <div class="w-4 h-4 bg-pink-400 rounded-full"></div>
                                    <div class="w-4 h-4 bg-blue-400 rounded-full"></div>
                                    <div class="w-4 h-4 bg-yellow-400 rounded-full"></div>
                                </div>
                            </div>
                            <button class="try-on-btn bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                                Try On
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Size Recommendation -->
                <div id="size-recommendation" class="mt-6 p-4 bg-blue-50 rounded-lg hidden">
                    <h3 class="font-semibold text-blue-800 mb-2">AI Size Recommendation</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-blue-600">Recommended Size:</span>
                        <span class="font-bold text-blue-800">Medium (94% confidence)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- AR Enhancement Options -->
        <div class="bg-white bg-opacity-10 rounded-xl p-6 backdrop-blur mb-8">
            <h2 class="text-2xl font-bold text-white mb-6">AR Enhancement Options</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <button class="enhancement-btn w-full p-4 bg-white bg-opacity-20 rounded-lg text-white hover:bg-opacity-30 transition-all">
                        <i data-lucide="adjust" class="w-8 h-8 mx-auto mb-2"></i>
                        <span class="block text-sm font-semibold">Adjust Lighting</span>
                    </button>
                </div>
                <div class="text-center">
                    <button class="enhancement-btn w-full p-4 bg-white bg-opacity-20 rounded-lg text-white hover:bg-opacity-30 transition-all">
                        <i data-lucide="move-3d" class="w-8 h-8 mx-auto mb-2"></i>
                        <span class="block text-sm font-semibold">3D View</span>
                    </button>
                </div>
                <div class="text-center">
                    <button class="enhancement-btn w-full p-4 bg-white bg-opacity-20 rounded-lg text-white hover:bg-opacity-30 transition-all">
                        <i data-lucide="palette" class="w-8 h-8 mx-auto mb-2"></i>
                        <span class="block text-sm font-semibold">Change Colors</span>
                    </button>
                </div>
                <div class="text-center">
                    <button class="enhancement-btn w-full p-4 bg-white bg-opacity-20 rounded-lg text-white hover:bg-opacity-30 transition-all">
                        <i data-lucide="download" class="w-8 h-8 mx-auto mb-2"></i>
                        <span class="block text-sm font-semibold">Save Photo</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Controls -->
    <div class="floating-controls">
        <div class="flex space-x-4">
            <button id="share-btn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-full font-semibold shadow-lg hidden">
                <i data-lucide="share-2" class="w-5 h-5 inline mr-2"></i>
                Share Look
            </button>
            <button id="add-to-cart-btn" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-full font-semibold shadow-lg hidden">
                <i data-lucide="shopping-cart" class="w-5 h-5 inline mr-2"></i>
                Add to Cart
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        let arSystem = null;
        let currentProduct = null;

        // Initialize AR system
        document.getElementById('start-ar').addEventListener('click', async () => {
            try {
                arSystem = new ARVisualization();
                await arSystem.initializeAR('ar-canvas');
                
                // Update UI
                document.getElementById('permission-request').style.display = 'none';
                document.getElementById('ar-status').innerHTML = '<i data-lucide="camera" class="w-4 h-4 inline mr-1"></i>Camera Active';
                document.getElementById('ar-status').className = 'absolute top-6 left-6 bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold';
                
                lucide.createIcons();
            } catch (error) {
                alert('Camera access failed: ' + error.message);
            }
        });

        // Product try-on handlers
        document.querySelectorAll('.try-on-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const productItem = e.target.closest('.product-item');
                const productType = productItem.dataset.product;
                
                if (!arSystem) {
                    alert('Please start the AR experience first');
                    return;
                }
                
                tryOnProduct(productType);
            });
        });

        function tryOnProduct(productType) {
            currentProduct = {
                type: productType,
                name: productType.charAt(0).toUpperCase() + productType.slice(1),
                image: null // In real implementation, this would load the actual product image
            };
            
            // Show size recommendation
            document.getElementById('size-recommendation').classList.remove('hidden');
            
            // Show action buttons
            document.getElementById('share-btn').classList.remove('hidden');
            document.getElementById('add-to-cart-btn').classList.remove('hidden');
            
            // Start AR visualization
            arSystem.startVisualization(currentProduct);
        }

        // Category switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Update active tab
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'bg-blue-600', 'text-white');
                    b.classList.add('bg-gray-200', 'text-gray-700');
                });
                e.target.classList.add('active', 'bg-blue-600', 'text-white');
                e.target.classList.remove('bg-gray-200', 'text-gray-700');
                
                // Filter products (in real implementation)
                console.log('Switching to category:', e.target.dataset.category);
            });
        });

        // Share functionality
        document.getElementById('share-btn').addEventListener('click', () => {
            if (arSystem && currentProduct) {
                arSystem.shareVisualization('whatsapp');
            }
        });

        // Add to cart
        document.getElementById('add-to-cart-btn').addEventListener('click', () => {
            if (currentProduct) {
                alert(`Added ${currentProduct.name} to cart!`);
                // In real implementation, this would add to cart via API
            }
        });

        // Camera controls
        document.getElementById('capture-photo').addEventListener('click', () => {
            if (arSystem) {
                const photo = arSystem.captureARPhoto();
                console.log('Captured AR photo:', photo);
                // Show success message or preview
            }
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (arSystem) {
                arSystem.stopVisualization();
            }
        });
    </script>
</body>
</html>
