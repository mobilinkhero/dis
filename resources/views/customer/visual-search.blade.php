<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Visual Search & Product Discovery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .upload-area {
            border: 2px dashed #e5e7eb;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #3b82f6;
            background-color: #f8fafc;
        }
        .upload-area.dragover {
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px rgba(0,0,0,0.15);
        }
        .similarity-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold flex items-center">
                <i data-lucide="search" class="w-10 h-10 mr-4"></i>
                AI Visual Search & Discovery
            </h1>
            <p class="text-xl opacity-90 mt-2">Find products using images, voice, or AR technology</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <!-- Upload Section -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i data-lucide="upload" class="w-7 h-7 mr-3 text-blue-600"></i>
                Upload Image for AI Analysis
            </h2>
            
            <div id="uploadArea" class="upload-area rounded-lg p-12 text-center">
                <i data-lucide="camera" class="w-16 h-16 mx-auto mb-4 text-gray-400"></i>
                <p class="text-xl mb-2">Drop your image here or click to upload</p>
                <p class="text-gray-500 mb-6">Supported formats: JPG, PNG, GIF (Max 10MB)</p>
                <input type="file" id="imageInput" class="hidden" accept="image/*">
                <button onclick="document.getElementById('imageInput').click()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                    Choose Image
                </button>
            </div>

            <!-- Alternative Input Methods -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                <button class="flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all">
                    <i data-lucide="mic" class="w-6 h-6 mr-2 text-green-600"></i>
                    <span class="font-semibold">Voice Search</span>
                </button>
                <button class="flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all">
                    <i data-lucide="camera" class="w-6 h-6 mr-2 text-purple-600"></i>
                    <span class="font-semibold">Take Photo</span>
                </button>
                <button class="flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all">
                    <i data-lucide="scan" class="w-6 h-6 mr-2 text-orange-600"></i>
                    <span class="font-semibold">AR Scan</span>
                </button>
            </div>
        </div>

        <!-- Analysis Results -->
        <div id="analysisResults" class="hidden">
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i data-lucide="brain" class="w-7 h-7 mr-3 text-purple-600"></i>
                    AI Analysis Results
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Uploaded Image -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Your Image</h3>
                        <div class="relative">
                            <img id="uploadedImage" src="" alt="Uploaded image" class="w-full h-64 object-cover rounded-lg border">
                            <div class="absolute top-4 left-4 bg-black bg-opacity-70 text-white px-3 py-1 rounded-full text-sm">
                                <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i>
                                Analyzed by AI
                            </div>
                        </div>
                    </div>

                    <!-- AI Insights -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">AI Insights</h3>
                        <div class="space-y-4">
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <i data-lucide="tag" class="w-5 h-5 mr-2 text-blue-600"></i>
                                    <span class="font-semibold">Detected Products</span>
                                </div>
                                <div id="detectedProducts" class="text-sm text-gray-700">
                                    • Blue Denim Jacket (95% confidence)<br>
                                    • Casual Sneakers (87% confidence)<br>
                                    • Leather Watch (82% confidence)
                                </div>
                            </div>
                            
                            <div class="p-4 bg-green-50 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <i data-lucide="palette" class="w-5 h-5 mr-2 text-green-600"></i>
                                    <span class="font-semibold">Color Analysis</span>
                                </div>
                                <div class="flex space-x-2">
                                    <div class="w-8 h-8 bg-blue-600 rounded-full border-2 border-white shadow"></div>
                                    <div class="w-8 h-8 bg-gray-800 rounded-full border-2 border-white shadow"></div>
                                    <div class="w-8 h-8 bg-amber-700 rounded-full border-2 border-white shadow"></div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-purple-50 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <i data-lucide="trending-up" class="w-5 h-5 mr-2 text-purple-600"></i>
                                    <span class="font-semibold">Style Match</span>
                                </div>
                                <div class="text-sm text-gray-700">
                                    Casual, Modern, Urban Style<br>
                                    <span class="text-purple-600 font-semibold">92% style confidence</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Similar Products -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-bold mb-6 flex items-center">
                    <i data-lucide="grid" class="w-7 h-7 mr-3 text-green-600"></i>
                    Similar Products Found
                </h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Product 1 -->
                    <div class="product-card bg-white border border-gray-200 rounded-lg overflow-hidden relative">
                        <div class="similarity-badge bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            96% Match
                        </div>
                        <img src="https://via.placeholder.com/300x300/3B82F6/FFFFFF?text=Denim+Jacket" alt="Product" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2">Classic Denim Jacket</h3>
                            <p class="text-gray-600 text-sm mb-3">Premium blue denim with modern fit</p>
                            <div class="flex justify-between items-center">
                                <span class="text-2xl font-bold text-blue-600">$89.99</span>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Product 2 -->
                    <div class="product-card bg-white border border-gray-200 rounded-lg overflow-hidden relative">
                        <div class="similarity-badge bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            89% Match
                        </div>
                        <img src="https://via.placeholder.com/300x300/10B981/FFFFFF?text=Sneakers" alt="Product" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2">Urban Sneakers</h3>
                            <p class="text-gray-600 text-sm mb-3">Comfortable casual footwear</p>
                            <div class="flex justify-between items-center">
                                <span class="text-2xl font-bold text-green-600">$124.99</span>
                                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Product 3 -->
                    <div class="product-card bg-white border border-gray-200 rounded-lg overflow-hidden relative">
                        <div class="similarity-badge bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            85% Match
                        </div>
                        <img src="https://via.placeholder.com/300x300/F59E0B/FFFFFF?text=Watch" alt="Product" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2">Leather Watch</h3>
                            <p class="text-gray-600 text-sm mb-3">Classic timepiece with brown leather</p>
                            <div class="flex justify-between items-center">
                                <span class="text-2xl font-bold text-orange-600">$199.99</span>
                                <button class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Product 4 -->
                    <div class="product-card bg-white border border-gray-200 rounded-lg overflow-hidden relative">
                        <div class="similarity-badge bg-purple-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            78% Match
                        </div>
                        <img src="https://via.placeholder.com/300x300/8B5CF6/FFFFFF?text=T-Shirt" alt="Product" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2">Cotton T-Shirt</h3>
                            <p class="text-gray-600 text-sm mb-3">Soft cotton with perfect fit</p>
                            <div class="flex justify-between items-center">
                                <span class="text-2xl font-bold text-purple-600">$29.99</span>
                                <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Recommendations -->
                <div class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border border-blue-200">
                    <h3 class="text-lg font-bold mb-3 flex items-center">
                        <i data-lucide="sparkles" class="w-5 h-5 mr-2 text-blue-600"></i>
                        AI Personal Stylist Recommendations
                    </h3>
                    <p class="text-gray-700 mb-4">Based on your image and style preferences, here's what our AI recommends:</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center p-3 bg-white rounded-lg">
                            <i data-lucide="shirt" class="w-6 h-6 mr-3 text-blue-600"></i>
                            <span class="text-sm">Complete the look with dark jeans</span>
                        </div>
                        <div class="flex items-center p-3 bg-white rounded-lg">
                            <i data-lucide="shopping-bag" class="w-6 h-6 mr-3 text-green-600"></i>
                            <span class="text-sm">Add a leather belt for 15% off</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AR Try-On Section -->
        <div class="bg-white rounded-xl shadow-lg p-8 mt-8">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i data-lucide="scan-line" class="w-7 h-7 mr-3 text-red-600"></i>
                Augmented Reality Try-On
            </h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Virtual Fitting Room</h3>
                    <div class="bg-gray-100 rounded-lg p-8 text-center">
                        <i data-lucide="smartphone" class="w-16 h-16 mx-auto mb-4 text-gray-400"></i>
                        <p class="text-gray-600 mb-4">Use your phone camera to try on products virtually</p>
                        <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold">
                            Start AR Experience
                        </button>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Size Recommendation</h3>
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">Your Predicted Size</span>
                                <span class="text-blue-600 font-bold">Medium (92% confidence)</span>
                            </div>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">Fit Prediction</span>
                                <span class="text-green-600 font-bold">Perfect Fit</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // File upload handling
        const imageInput = document.getElementById('imageInput');
        const uploadArea = document.getElementById('uploadArea');
        const analysisResults = document.getElementById('analysisResults');
        const uploadedImage = document.getElementById('uploadedImage');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0]);
            }
        });

        imageInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileUpload(e.target.files[0]);
            }
        });

        function handleFileUpload(file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    uploadedImage.src = e.target.result;
                    analysisResults.classList.remove('hidden');
                    
                    // Simulate AI processing
                    setTimeout(() => {
                        showProcessingComplete();
                    }, 2000);
                };
                reader.readAsDataURL(file);
            }
        }

        function showProcessingComplete() {
            // Add success animation or notification
            console.log('AI processing complete');
        }
    </script>
</body>
</html>
