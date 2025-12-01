<div class="space-y-6">
    <!-- Equipment Header -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 rounded-lg text-white">
        <h3 class="text-2xl font-bold mb-2">
            {{ strtoupper(str_replace('_', ' ', $prediction->equipment_type)) }}
        </h3>
        <p class="text-blue-100">
            Predicted at {{ $prediction->predicted_at->format('F j, Y - H:i:s') }}
        </p>
    </div>

    <!-- ML Prediction Results -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
            </svg>
            ONNX Model Prediction
        </h4>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Anomaly Status:</span>
                    <span class="font-bold {{ $prediction->is_anomaly ? 'text-red-600' : 'text-green-600' }}">
                        {{ $prediction->is_anomaly ? 'DETECTED' : 'NORMAL' }}
                    </span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Risk Signal:</span>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold 
                        {{ $prediction->risk_signal === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $prediction->risk_signal === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                        {{ $prediction->risk_signal === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $prediction->risk_signal === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                        {{ strtoupper($prediction->risk_signal ?? 'UNKNOWN') }}
                    </span>
                </div>
            </div>
            
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Confidence Score:</span>
                    <span class="font-bold text-blue-600">
                        {{ $prediction->confidence_score ? number_format($prediction->confidence_score, 1) . '%' : 'N/A' }}
                    </span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Raw Label:</span>
                    <span class="font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                        {{ $prediction->raw_label ?? 'none' }}
                    </span>
                </div>
            </div>
        </div>

        @if($prediction->feature_importance && count($prediction->feature_importance) > 0)
            <div class="mt-6">
                <h5 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300">Feature Importance:</h5>
                <div class="space-y-2">
                    @foreach(array_slice($prediction->feature_importance, 0, 5) as $feature => $importance)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $feature)) }}</span>
                                <span class="font-semibold">{{ number_format($importance * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-purple-500 h-2 rounded-full transition-all duration-300" 
                                     style="width: {{ $importance * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- AI Insights -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            AI-Powered Insights (OpenAI)
        </h4>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600 dark:text-gray-400">Severity Level:</span>
                    <span class="px-4 py-1.5 rounded-full text-sm font-semibold 
                        {{ $prediction->severity_level === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $prediction->severity_level === 'warning' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $prediction->severity_level === 'normal' ? 'bg-green-100 text-green-800' : '' }}">
                        {{ strtoupper($prediction->severity_level ?? 'NORMAL') }}
                    </span>
                </div>
            </div>
            
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600 dark:text-gray-400">Equipment Priority:</span>
                    <span class="px-4 py-1.5 rounded-full text-sm font-bold
                        {{ $prediction->equipment_priority >= 8 ? 'bg-red-100 text-red-800' : '' }}
                        {{ $prediction->equipment_priority >= 5 && $prediction->equipment_priority < 8 ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $prediction->equipment_priority < 5 ? 'bg-green-100 text-green-800' : '' }}">
                        {{ $prediction->equipment_priority ?? 'N/A' }} / 10
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded">
                <h5 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-2">
                    🔍 Root Cause Analysis:
                </h5>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ $prediction->root_cause ?? 'No root cause analysis available.' }}
                </p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
                <h5 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">
                    🔧 Technical Recommendations:
                </h5>
                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                    {{ $prediction->technical_recommendations ?? 'No recommendations available.' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Metadata -->
    @if($prediction->ai_metadata && count($prediction->ai_metadata) > 0)
        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
            <details class="cursor-pointer">
                <summary class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                    Additional Metadata
                </summary>
                <pre class="mt-3 text-xs bg-white dark:bg-gray-800 p-3 rounded overflow-auto max-h-40">{{ json_encode($prediction->ai_metadata, JSON_PRETTY_PRINT) }}</pre>
            </details>
        </div>
    @endif
</div>
