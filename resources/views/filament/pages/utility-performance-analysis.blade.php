<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Dashboard Introduction --}}
        <x-filament::section>
            <x-slot name="heading">
                🏭 Utility Performance Dashboard
            </x-slot>
            <x-slot name="description">
                Real-time monitoring and analysis of utility equipment performance, energy consumption, and maintenance compliance.
                <strong>Auto-refreshes every 30 seconds.</strong>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">📊 Equipment Monitored</h3>
                    <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                        <li>✓ Chiller 1 & Chiller 2</li>
                        <li>✓ Compressor 1 & Compressor 2</li>
                        <li>✓ AHU/PAU/VRF Systems</li>
                    </ul>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-900 dark:text-green-100 mb-2">🎯 Key Performance Indicators</h3>
                    <ul class="text-sm text-green-800 dark:text-green-200 space-y-1">
                        <li>✓ Health Scores (0-100)</li>
                        <li>✓ FLA Loading % Efficiency</li>
                        <li>✓ Temperature & Pressure Monitoring</li>
                        <li>✓ Filter Status (PF/MF/HF)</li>
                    </ul>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <h3 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">🤖 Advanced Features</h3>
                    <ul class="text-sm text-purple-800 dark:text-purple-200 space-y-1">
                        <li>✓ Auto-refresh (30s polling)</li>
                        <li>✓ Real-time alerts & warnings</li>
                        <li>✓ 7-day trend analysis</li>
                        <li>✓ AI/ML predictive maintenance ready</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

        {{-- KPI Calculation Explanations --}}
        <x-filament::section>
            <x-slot name="heading">
                📐 KPI Calculation Methods
            </x-slot>
            
            <div class="space-y-4">
                {{-- Chiller Health Score --}}
                <details class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <summary class="font-semibold text-gray-900 dark:text-white cursor-pointer hover:text-blue-600 dark:hover:text-blue-400">
                        🩺 Chiller Health Score (0-100)
                    </summary>
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p><strong>Total: 100 points</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-4">
                            <li><strong>50 pts:</strong> Temperature & Pressure Within Range
                                <ul class="list-disc list-inside ml-6 text-xs space-y-1">
                                    <li>Evaporator Temp: 2-8°C (ideal), 0-10°C (acceptable) → 15 pts</li>
                                    <li>Evaporator Pressure: 3-6 Bar (ideal), 2-7 Bar (acceptable) → 15 pts</li>
                                    <li>Condenser Pressure: 10-16 Bar (ideal), 8-18 Bar (acceptable) → 20 pts</li>
                                </ul>
                            </li>
                            <li><strong>30 pts:</strong> Loading Within Optimal Range
                                <ul class="list-disc list-inside ml-6 text-xs space-y-1">
                                    <li>40-90% Loading → 30 pts (optimal)</li>
                                    <li>30-95% Loading → 20 pts (acceptable)</li>
                                    <li>20-100% Loading → 10 pts (suboptimal)</li>
                                </ul>
                            </li>
                            <li><strong>20 pts:</strong> Refrigerant Small Temp Diff Within Spec
                                <ul class="list-disc list-inside ml-6 text-xs space-y-1">
                                    <li>Cooler Temp Diff < 2°C → 10 pts (ideal), < 3°C → 5 pts</li>
                                    <li>Condenser Temp Diff < 2°C → 10 pts (ideal), < 3°C → 5 pts</li>
                                </ul>
                            </li>
                        </ul>
                        <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                            <p class="text-xs"><strong>Score Interpretation:</strong></p>
                            <ul class="text-xs space-y-1 ml-4">
                                <li>🟢 80-100: Excellent condition</li>
                                <li>🟡 60-79: Good condition, minor attention needed</li>
                                <li>🟠 40-59: Fair condition, maintenance required</li>
                                <li>🔴 0-39: Poor condition, immediate action needed</li>
                            </ul>
                        </div>
                    </div>
                </details>

                {{-- FLA Loading % --}}
                <details class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <summary class="font-semibold text-gray-900 dark:text-white cursor-pointer hover:text-blue-600 dark:hover:text-blue-400">
                        ⚡ FLA Loading % (Chiller Efficiency)
                    </summary>
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p><strong>Formula:</strong> <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">Loading % = (LCL / FLA) × 100</code></p>
                        <ul class="list-disc list-inside space-y-1 ml-4">
                            <li><strong>LCL</strong> = Load Current Limit (actual motor current draw)</li>
                            <li><strong>FLA</strong> = Full Load Amps (motor nameplate rating)</li>
                        </ul>
                        <div class="mt-2 p-3 bg-green-50 dark:bg-green-900/20 rounded">
                            <p class="text-xs"><strong>Optimal Range:</strong> 40-90%</p>
                            <ul class="text-xs space-y-1 ml-4">
                                <li>< 40%: Underloaded (inefficient operation, cycling)</li>
                                <li>40-90%: Optimal efficiency range</li>
                                <li>> 90%: Overloaded (risk of motor overheating)</li>
                            </ul>
                        </div>
                    </div>
                </details>

                {{-- Cooling Delta-T --}}
                <details class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <summary class="font-semibold text-gray-900 dark:text-white cursor-pointer hover:text-blue-600 dark:hover:text-blue-400">
                        🌡️ Cooling Delta-T (Compressor Efficiency)
                    </summary>
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p><strong>Formula:</strong> <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">ΔT = CWS Temperature - CWR Temperature</code></p>
                        <ul class="list-disc list-inside space-y-1 ml-4">
                            <li><strong>CWS</strong> = Cooling Water Supply (inlet temperature)</li>
                            <li><strong>CWR</strong> = Cooling Water Return (outlet temperature)</li>
                        </ul>
                        <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                            <p class="text-xs"><strong>Target Range:</strong> ≥ 3°C</p>
                            <ul class="text-xs space-y-1 ml-4">
                                <li>< 3°C: Poor heat transfer (fouling, low flow rate)</li>
                                <li>≥ 3°C: Good cooling efficiency</li>
                                <li>> 10°C: Excellent heat transfer</li>
                            </ul>
                        </div>
                    </div>
                </details>

                {{-- AHU Filter Status --}}
                <details class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <summary class="font-semibold text-gray-900 dark:text-white cursor-pointer hover:text-blue-600 dark:hover:text-blue-400">
                        🔧 AHU Filter Status (PF/MF/HF)
                    </summary>
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <ul class="list-disc list-inside space-y-1 ml-4">
                            <li><strong>PF</strong> = Pre-Filter (first stage filtration)</li>
                            <li><strong>MF</strong> = Medium Filter (second stage filtration)</li>
                            <li><strong>HF</strong> = HEPA Filter (final stage, high-efficiency)</li>
                        </ul>
                        <p class="mt-2">Counts total filter changes/replacements across all AHU, PAU, and VRF units.</p>
                        <div class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 rounded">
                            <p class="text-xs"><strong>Worst 5 AHU Points:</strong> Equipment with most HEPA filter issues in last 30 days</p>
                            <p class="text-xs mt-1">Used to prioritize maintenance and identify problematic units.</p>
                        </div>
                    </div>
                </details>
            </div>
        </x-filament::section>

        {{-- AI/ML Predictive Maintenance Section --}}
        <x-filament::section>
            <x-slot name="heading">
                🤖 AI/ML Predictive Maintenance (Ready for Integration)
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📈 Planned AI Features</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>✓ Failure prediction based on historical trends</li>
                        <li>✓ Anomaly detection in real-time data</li>
                        <li>✓ Optimal maintenance scheduling</li>
                        <li>✓ Equipment lifecycle forecasting</li>
                        <li>✓ Energy consumption optimization</li>
                    </ul>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🔬 Data Inputs for AI</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• Temperature & pressure patterns</li>
                        <li>• Loading efficiency trends</li>
                        <li>• Health score degradation</li>
                        <li>• Filter replacement frequency</li>
                        <li>• Abnormal event patterns</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

        {{-- Footer Note --}}
        <div class="text-center text-sm text-gray-500 dark:text-gray-400">
            <p>Dashboard auto-refreshes every 30 seconds | Last updated: {{ now()->format('M d, Y H:i:s') }}</p>
        </div>
    </div>
</x-filament-panels::page>

