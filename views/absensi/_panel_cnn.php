<!-- Panel Visualisasi Pipeline CNN — dimuat sebagai partial oleh kamera.php -->
<div id="panelVisCnn"
     class="hidden mt-5 bg-white border border-slate-200 rounded-lg p-5 select-none">

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse flex-shrink-0"></div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Pipeline CNN — Proses Pengenalan Wajah
            </span>
        </div>
        <span id="cnnFaseLabel" class="text-xs font-mono text-slate-400"></span>
    </div>

    <div class="flex items-stretch gap-2">

        <!-- Stage 1: Konvolusi -->
        <div id="cnnStage1" class="cnn-stage-box flex-1 border-2 border-slate-100 rounded-lg p-4 text-center">
            <div class="flex justify-center mb-3">
                <div id="cnnFmGrid" class="grid gap-0.5"
                     style="grid-template-columns:repeat(6,10px);grid-template-rows:repeat(6,10px);">
                    <!-- sel diisi JS -->
                </div>
            </div>
            <p class="text-sm font-semibold text-slate-700">Konvolusi</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Ekstraksi Fitur Wajah</p>
        </div>

        <!-- Panah 1 → 2 -->
        <div id="cnnArrow1" class="cnn-arrow flex-shrink-0 self-center text-slate-200">
            <svg width="20" height="20" fill="none" stroke="currentColor"
                 stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </div>

        <!-- Stage 2: Pooling -->
        <div id="cnnStage2" class="cnn-stage-box flex-1 border-2 border-slate-100 rounded-lg p-4 text-center">
            <div class="flex items-center justify-center gap-3 mb-3">
                <!-- Grid sebelum pooling (3×3) -->
                <div id="cnnPoolBefore" class="grid gap-0.5"
                     style="grid-template-columns:repeat(3,10px);grid-template-rows:repeat(3,10px);">
                </div>
                <svg width="14" height="14" fill="none" stroke="currentColor"
                     stroke-width="2.5" viewBox="0 0 24 24" class="text-slate-300 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
                <!-- Grid sesudah pooling (2×2) — muncul dengan animasi -->
                <div id="cnnPoolAfter" class="cnn-pool-after grid gap-1"
                     style="grid-template-columns:repeat(2,14px);grid-template-rows:repeat(2,14px);">
                </div>
            </div>
            <p class="text-sm font-semibold text-slate-700">Pooling</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Penyederhanaan Fitur</p>
        </div>

        <!-- Panah 2 → 3 -->
        <div id="cnnArrow2" class="cnn-arrow flex-shrink-0 self-center text-slate-200">
            <svg width="20" height="20" fill="none" stroke="currentColor"
                 stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </div>

        <!-- Stage 3: Fully Connected -->
        <div id="cnnStage3" class="cnn-stage-box flex-1 border-2 border-slate-100 rounded-lg p-4 text-center">
            <div class="flex items-end justify-center gap-1.5 mb-3" style="height:66px;">
                <!-- 5 bar probabilitas kelas (simulasi softmax output) -->
                <div class="cnn-bar w-6 rounded-t bg-blue-100"       style="height:8%"></div>
                <div class="cnn-bar w-6 rounded-t bg-blue-200"       style="height:8%"></div>
                <div class="cnn-bar cnn-bar-winner w-6 rounded-t bg-[#1E40AF]" style="height:8%"></div>
                <div class="cnn-bar w-6 rounded-t bg-blue-200"       style="height:8%"></div>
                <div class="cnn-bar w-6 rounded-t bg-blue-100"       style="height:8%"></div>
            </div>
            <p class="text-sm font-semibold text-slate-700">Fully Connected</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Klasifikasi &amp; Softmax</p>
        </div>
    </div>

    <!-- Confidence bar — muncul setelah server merespons -->
    <div id="cnnHasilConf" class="hidden mt-4 pt-4 border-t border-slate-100">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs text-slate-500">Confidence Score (TTA 4-varian)</span>
            <span id="cnnNilaiConf" class="text-sm font-bold text-[#1E40AF]">–</span>
        </div>
        <div class="relative w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div id="cnnBarConf"
                 class="absolute inset-y-0 left-0 rounded-full transition-[width] duration-700 ease-out"
                 style="width:0%"></div>
            <div class="absolute inset-y-0 w-px bg-amber-400/60" style="left:70%"></div>
            <div class="absolute inset-y-0 w-px bg-green-500/60" style="left:85%"></div>
        </div>
        <div class="flex text-[10px] mt-1">
            <span class="text-slate-300">0%</span>
            <span class="flex-1"></span>
            <span class="text-amber-500 mr-1">▸ 70%</span>
            <span class="text-green-600 mr-1">▸ 85%</span>
            <span class="text-slate-300">100%</span>
        </div>
    </div>
</div>
