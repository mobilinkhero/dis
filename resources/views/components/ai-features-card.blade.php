{{-- AI Features Quick Access Card --}}
<div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl shadow-lg p-6 text-white">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            <h3 class="text-xl font-bold">🚀 Ultra-Advanced AI Features</h3>
        </div>
        <div class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-xs font-semibold">
            NEW
        </div>
    </div>
    
    <p class="text-white text-opacity-90 mb-6">
        Experience next-generation AI-powered e-commerce with advanced analytics, visual search, and AR technology.
    </p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- AI Analytics Dashboard -->
        <a href="{{ route('ai.dashboard') }}" 
           class="bg-white bg-opacity-10 hover:bg-opacity-20 rounded-lg p-4 transition-all duration-300 hover:transform hover:scale-105 group">
            <div class="flex items-center mb-3">
                <svg class="w-6 h-6 mr-2 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span class="font-semibold">AI Analytics</span>
            </div>
            <p class="text-sm text-white text-opacity-80 group-hover:text-opacity-100">
                Real-time intelligence dashboard with predictive insights
            </p>
        </a>
        
        <!-- Visual Search -->
        <a href="{{ route('visual.search') }}" 
           class="bg-white bg-opacity-10 hover:bg-opacity-20 rounded-lg p-4 transition-all duration-300 hover:transform hover:scale-105 group">
            <div class="flex items-center mb-3">
                <svg class="w-6 h-6 mr-2 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="font-semibold">Visual Search</span>
            </div>
            <p class="text-sm text-white text-opacity-80 group-hover:text-opacity-100">
                AI-powered image recognition and product matching
            </p>
        </a>
        
        <!-- AR Try-On -->
        <a href="{{ route('ar.tryout') }}" 
           class="bg-white bg-opacity-10 hover:bg-opacity-20 rounded-lg p-4 transition-all duration-300 hover:transform hover:scale-105 group">
            <div class="flex items-center mb-3">
                <svg class="w-6 h-6 mr-2 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-semibold">AR Try-On</span>
            </div>
            <p class="text-sm text-white text-opacity-80 group-hover:text-opacity-100">
                Virtual try-on experience with augmented reality
            </p>
        </a>
    </div>
    
    <div class="mt-6 flex items-center justify-between">
        <div class="flex items-center text-sm">
            <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
            <span class="text-white text-opacity-90">AI systems are online and optimized</span>
        </div>
        <span class="text-xs text-white text-opacity-70">
            Powered by Ultra-Advanced AI
        </span>
    </div>
</div>
