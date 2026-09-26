<?php
require_once dirname(__DIR__) . '/config/db.php';
\Wasla\Config\Database::loadEnv();
$voiceWebSocketUrl = getenv('WASLA_WS_URL') ?: '';
?>
<!DOCTYPE html>
<html class="h-full dark" lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta name="wasla-ws-url" content="<?= htmlspecialchars($voiceWebSocketUrl, ENT_QUOTES, 'UTF-8') ?>"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>WASLA وصلة - Plateforme Nationale Agri-Logistique Logitime / AI-Powered Supply Chain</title>
    
    <script>try { document.documentElement.classList.toggle('dark', localStorage.getItem('wasla-theme') !== 'light'); } catch (_) {}</script>
    <link rel="stylesheet" href="css/pitch.css"/>
    <!-- Preconnect & Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&amp;family=Inter:wght@400;500;600;700&amp;family=Amiri:ital,wght@0,700;1,400&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <style>
        @layer base {
          html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
          body { -webkit-font-smoothing: antialiased; }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Pulse Animation */
        @keyframes pulse-ring {
          0% { transform: scale(0.95); opacity: 0.8; }
          50% { transform: scale(1.18); opacity: 0.25; }
          100% { transform: scale(1.35); opacity: 0; }
        }
        .animate-pulse-ring {
          animation: pulse-ring 2.2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        
        /* Audio Wave Animation */
        @keyframes audio-wave {
          0%, 100% { height: 6px; }
          50% { height: 26px; }
        }
        .wave-bar:nth-child(1) { animation: audio-wave 1.1s ease-in-out infinite 0.1s; }
        .wave-bar:nth-child(2) { animation: audio-wave 0.8s ease-in-out infinite 0.25s; }
        .wave-bar:nth-child(3) { animation: audio-wave 1.3s ease-in-out infinite 0.05s; }
        .wave-bar:nth-child(4) { animation: audio-wave 0.9s ease-in-out infinite 0.4s; }
        .wave-bar:nth-child(5) { animation: audio-wave 1.2s ease-in-out infinite 0.2s; }
        .wave-bar:nth-child(6) { animation: audio-wave 0.7s ease-in-out infinite 0.35s; }

        /* Custom Leaflet overrides to match Stitch design */
        .leaflet-popup-content-wrapper {
            background: rgba(255, 255, 255, 0.96) !important;
            backdrop-filter: blur(8px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04) !important;
            padding: 2px !important;
        }
        .leaflet-popup-tip {
            background: rgba(255, 255, 255, 0.96) !important;
        }

        /* RTL Leaflet Styling for Arabic */
        html[dir="rtl"] .leaflet-popup-content-wrapper {
            direction: rtl !important;
            text-align: right !important;
        }
        html[dir="rtl"] .leaflet-popup-content,
        html[dir="rtl"] .preset-pill {
            direction: rtl !important;
            text-align: right !important;
            width: auto !important;
            max-width: 300px;
            font-family: 'Amiri', 'Arial Arabic', 'Times New Roman', serif !important;
        }
    </style>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              colors: {
                emerald: {
                  deep: "#065f46",
                  mid: "#047857",
                  light: "#10b981",
                  soft: "#ecfdf5",
                  glow: "#34d399"
                },
                terracotta: {
                  DEFAULT: "#c2410c",
                  vibrant: "#ea580c",
                  accent: "#f97316",
                  light: "#fff7ed",
                  deep: "#9a3412"
                },
                surface: {
                  canvas: "#f8fafc",
                  card: "#ffffff"
                }
              },
              fontFamily: {
                sans: ["Inter", "sans-serif"],
                display: ["'Plus Jakarta Sans'", "sans-serif"],
                arabic: ["'Amiri'", "'Arial Arabic'", "'Times New Roman'", "serif"]
              }
            }
          }
        }
    </script>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans h-screen flex flex-col antialiased select-none overflow-hidden">

    <!-- TOP APP NAVIGATION BAR -->
    <header class="app-header shrink-0 bg-white/95 backdrop-blur-md border-b border-slate-200/90 z-50 flex items-center justify-between shadow-[0_1px_3px_rgba(0,0,0,0.03)]">
        <!-- Left: Brand Logo & Territorial Hub Indicator -->
        <div class="flex items-center gap-6">
            <a class="flex items-center focus:outline-none transition-transform hover:scale-[1.01] shrink-0" href="#">
                <!-- Inline SVG Logo for extreme fidelity -->
                <svg dir="ltr" style="direction: ltr;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 80" class="h-10 w-auto shrink-0 object-contain">
                  <defs>
                    <linearGradient id="waslaGreen" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#065f46" />
                      <stop offset="100%" stop-color="#10b981" />
                    </linearGradient>
                    <linearGradient id="waslaOrange" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#c2410c" />
                      <stop offset="100%" stop-color="#f97316" />
                    </linearGradient>
                    <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
                      <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.15" />
                    </filter>
                  </defs>
                  <g transform="translate(10, 10)" filter="url(#shadow)">
                    <circle cx="30" cy="30" r="26" fill="url(#waslaGreen)" />
                    <path d="M 18 36 C 18 20, 42 20, 42 36 C 42 44, 30 52, 30 52 C 30 52, 18 44, 18 36 Z" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M 30 24 C 36 24, 38 30, 38 36 C 32 36, 30 32, 30 24 Z" fill="url(#waslaOrange)" />
                    <circle cx="30" cy="36" r="3" fill="#ffffff" />
                  </g>
                  <text x="80" y="48" font-family="Plus Jakarta Sans, Inter, sans-serif" font-weight="900" font-size="32" letter-spacing="1.5" fill="#065f46">WASLA</text>
                  <text x="215" y="46" font-family="Plus Jakarta Sans, sans-serif" font-weight="700" font-size="20" fill="url(#waslaOrange)">وصلة</text>
                  <text x="81" y="64" font-family="Plus Jakarta Sans, sans-serif" font-weight="700" font-size="10" letter-spacing="2" fill="#047857">AI-POWERED SUPPLY CHAIN</text>
                </svg>
            </a>
            <div class="h-6 w-[1px] bg-slate-200 hidden md:block"></div>
        </div>
        
        <!-- Center: Main Module Navigation Tabs -->
        <nav class="hidden lg:flex items-center gap-1.5 bg-slate-100/90 p-1 rounded-xl border border-slate-200/70" id="main-nav-tabs">
            <button id="tab-carte" data-tab="carte" onclick="switchMapTab('carte')" type="button"
                class="map-tab map-tab-active px-4 py-1.5 rounded-lg font-display text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer border-none">
                <span class="material-symbols-outlined text-base">map</span>
                <span data-i18n="nav_carte">Carte Interactive</span>
            </button>
            <button id="tab-groupage" data-tab="groupage" onclick="switchMapTab('groupage')" type="button"
                class="map-tab map-tab-inactive px-4 py-1.5 rounded-lg font-display text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer border-none">
                <span class="material-symbols-outlined text-base nav-tab-icon">local_shipping</span>
                <span data-i18n="nav_groupage">Groupage &amp; Fret</span>
            </button>
            <button id="tab-coop" data-tab="coop" onclick="switchMapTab('coop')" type="button"
                class="map-tab map-tab-inactive px-4 py-1.5 rounded-lg font-display text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer border-none">
                <span class="material-symbols-outlined text-base nav-tab-icon">hub</span>
                <span data-i18n="nav_coop">Réseau Coopératives</span>
            </button>
            <button id="tab-esg" data-tab="esg" onclick="switchMapTab('esg')" type="button"
                class="map-tab map-tab-inactive px-4 py-1.5 rounded-lg font-display text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer border-none">
                <span class="material-symbols-outlined text-base nav-tab-icon">eco</span>
                <span data-i18n="nav_esg">Indicateurs ESG</span>
            </button>
        </nav>
        
        <!-- Right: Shipment tracking, language & profile -->
        <div class="header-actions flex items-center gap-2 sm:gap-3">
            <button id="tracking-open" type="button" class="tracking-trigger flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold" aria-haspopup="dialog">
                <span class="material-symbols-outlined" aria-hidden="true">local_shipping</span><span>Suivi d’expédition</span>
            </button>

            
            <!-- Language Switcher -->
            <div class="hidden md:flex relative items-center bg-white rounded-lg px-2 py-1 border border-slate-200 shadow-sm cursor-pointer hover:border-slate-300 transition-colors">
                <span class="material-symbols-outlined text-slate-400 text-[16px] mr-1">translate</span>
                <select id="lang-selector" onchange="setLanguage(this.value)" class="bg-transparent border-none text-xs font-bold text-slate-700 focus:ring-0 cursor-pointer outline-none w-[70px] appearance-none pl-1">
                    <option value="fr">🇫🇷 FR</option>
                    <option value="en">🇬🇧 EN</option>
                    <option value="ar">🇲🇦 AR</option>
                </select>
                <span class="material-symbols-outlined text-slate-400 text-[14px] pointer-events-none absolute right-1">expand_more</span>
            </div>

            <button class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 border border-transparent hover:border-slate-200 transition-colors" title="Notifications" type="button">
                <span class="material-symbols-outlined text-xl">notifications</span>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-terracotta-vibrant ring-2 ring-white"></span>
            </button>
            
            <div class="flex flex-row items-center gap-3 whitespace-nowrap pl-2 border-l border-slate-200">
                <div class="relative">
                    <img alt="Yassine El Amrani" class="w-10 h-10 rounded-full object-cover shrink-0 ring-2 ring-emerald-deep/20 shadow-sm" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=200"/>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-light rounded-full ring-2 ring-white"></span>
                </div>
                <div class="hidden xl:flex flex-col text-left">
                    <span class="font-display text-xs font-bold text-slate-900 leading-tight">Yassine El Amrani</span>
                    <span class="text-[11px] text-slate-500 font-medium" data-i18n="role">Chef Logistique Nationale</span>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN OPERATIONAL WORKSPACE (SPLIT SCREEN LAYOUT) -->
    <div class="workspace flex-1 flex flex-col lg:flex-row overflow-hidden relative">
        
        <!-- LEFT PANEL: VOICE-FIRST HERO & ESG METRICS COCKPIT -->
        <aside class="operations-panel w-full lg:w-[360px] xl:w-[420px] 2xl:w-[460px] h-full shrink-0 bg-white border-r border-slate-200/90 z-20 flex flex-col shadow-[4px_0_24px_rgba(0,0,0,0.02)] overflow-y-auto">
            <div class="p-5 sm:p-6 flex flex-col gap-5">
                
                <!-- SECTION 1: VOICE-FIRST HERO CARD (Cleaned spacing: p-5 space-y-4) -->
                <div class="relative rounded-2xl bg-gradient-to-br from-emerald-soft/90 via-white to-emerald-50/50 border border-emerald-deep/20 p-5 space-y-4 shadow-sm overflow-hidden flex flex-col">
                    <!-- Background Ambient Glow -->
                    <div class="absolute -top-12 -right-12 w-44 h-44 bg-emerald-light/15 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <!-- Card Header -->
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-2.5">
                            <div class="w-3 h-3 rounded-full bg-emerald-deep flex items-center justify-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                            </div>
                            <h2 class="font-display font-extrabold text-base text-slate-900 tracking-tight" data-i18n="saisie_vocale">
                                Saisie Vocale Darija
                            </h2>
                        </div>
                        <div class="flex items-center gap-1.5 bg-emerald-deep/10 border border-emerald-deep/20 px-2.5 py-0.5 rounded-full text-emerald-deep">
                            <span class="material-symbols-outlined text-xs">graphic_eq</span>
                            <span class="text-[11px] font-display font-bold uppercase tracking-wider" data-i18n="ai_agro">AI Agro-NLU</span>
                        </div>
                    </div>
                    
                    <!-- Prominent Magnetic Mic Hero & Waveform Section -->
                    <div class="flex items-center gap-5 p-4 rounded-xl bg-white/90 border border-emerald-deep/15 shadow-sm relative z-10">
                        <!-- Magnetic Mic Button with Multi-Ring Pulse -->
                        <div class="relative shrink-0 flex items-center justify-center">
                            <div class="absolute -inset-2.5 rounded-full bg-emerald-light/30 animate-pulse-ring pointer-events-none"></div>
                            <div class="absolute -inset-1 rounded-full bg-emerald-deep/20 animate-ping opacity-50 pointer-events-none"></div>
                            <button class="relative z-10 w-16 h-16 rounded-full bg-emerald-deep hover:bg-emerald-mid active:scale-95 text-white flex items-center justify-center shadow-lg shadow-emerald-deep/30 transition-all duration-300 group cursor-pointer border-none" id="mainMicBtn" title="Enregistrer en Darija" type="button">
                                <span class="material-symbols-outlined text-3xl transition-transform group-hover:scale-110">mic</span>
                            </button>
                        </div>
                        
                        <!-- Voice Status & Active Frequency Waveform -->
                        <div class="flex-1 flex flex-col justify-center min-w-0">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold font-display text-emerald-deep flex items-center gap-1.5" id="mic-status">
                                    <span class="w-2 h-2 rounded-full bg-emerald-light animate-pulse"></span>
                                    <span>Micro prêt</span>
                                </span>
                                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md" id="mic-rec-badge">PRÊT</span>
                            </div>
                            <p class="text-xs text-slate-500 mb-2 truncate" data-i18n="dialect">Dialecte Souss (Chtouka, Massa, Taroudant)</p>
                            
                            <!-- Frequency Signal Waveform Visualizer -->
                            <div class="flex items-center gap-1 h-7 px-3 py-1 bg-emerald-50/80 rounded-lg border border-emerald-100" id="waveform-container">
                                <span class="wave-bar w-1 bg-emerald-mid rounded-full"></span>
                                <span class="wave-bar w-1 bg-emerald-light rounded-full"></span>
                                <span class="wave-bar w-1.5 bg-emerald-deep rounded-full"></span>
                                <span class="wave-bar w-1 bg-terracotta-vibrant rounded-full"></span>
                                <span class="wave-bar w-1.5 bg-emerald-mid rounded-full"></span>
                                <span class="wave-bar w-1 bg-emerald-light rounded-full"></span>
                                <span class="wave-bar w-1 bg-emerald-deep rounded-full"></span>
                                <span class="wave-bar w-1 bg-emerald-light rounded-full"></span>
                                <span class="wave-bar w-1.5 bg-emerald-mid rounded-full"></span>
                                <span class="wave-bar w-1 bg-slate-400 rounded-full"></span>
                                <span class="text-[10px] font-mono text-emerald-deep font-semibold ml-auto">Darija · العربية</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dynamic Interactive Form with Transcription / Translation Textarea -->
                    <form id="nlu-form" class="flex flex-col gap-3 relative z-10 m-0">
                        <div class="rounded-xl bg-white border border-slate-200/90 p-4 shadow-sm flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-display font-bold uppercase tracking-wider text-slate-500" data-i18n="saisie_edition">Saisie ou Édition de la Transcription</span>
                                <span class="text-[11px] font-semibold text-emerald-deep bg-emerald-100/70 border border-emerald-200 px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">edit_note</span> <span>Texte à relire</span>
                                </span>
                            </div>
                            
                            <!-- Arabic Darija Quote Textarea - aligned and formatted (line-height & text alignment fixed) -->
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200/70" dir="rtl">
                                <textarea 
                                    id="darija-text" 
                                    name="darija_text"
                                    rows="2" 
                                    class="w-full bg-transparent border-none p-0 text-base font-bold font-arabic text-slate-900 leading-relaxed text-right outline-none focus:ring-0 placeholder-slate-400 resize-none"
                                    placeholder="Ex: عندي جوج طن ديال ماطيشة فبلفاع..."
                                    required
                                >عندي جوج طن ديال ماطيشة فبلفاع باغي ندخلها لمارشي إنزكان مع العشية</textarea>
                            </div>
                            
                            <!-- Dynamic French Translation block (line-height & padding fixed) -->
                            <div class="flex items-start gap-2.5 text-xs text-slate-600 bg-emerald-50/50 p-3 rounded-lg border border-emerald-100/60 leading-relaxed text-left" id="translation-box">
                                <span class="material-symbols-outlined text-emerald-mid text-sm shrink-0 mt-0.5">translate</span>
                                <p class="italic font-medium leading-relaxed" id="darija-translation">
                                    « J'ai 2 tonnes de tomates à Belfaa que je veux livrer au marché d'Inezgane cet après-midi. »
                                </p>
                            </div>
                            
                            <!-- Form action submit button -->
                            <button 
                                type="submit" 
                                class="w-full bg-emerald-deep hover:bg-emerald-mid text-white font-display font-bold text-xs py-2.5 px-4 rounded-xl shadow-md transition-all flex items-center justify-center gap-1.5 focus:outline-none cursor-pointer border-none"
                            >
                                <span data-i18n="analyze_nlu">Analyser l'Entrée NLU</span>
                                <span class="material-symbols-outlined text-base">analytics</span>
                            </button>
                        </div>
                    </form>
                    
                    <!-- DYNAMIC EXTRACTED RESULTS CARD -->
                    <div id="nlu-result-card" class="rounded-xl bg-emerald-soft/75 border border-emerald-deep/20 p-4 shadow-sm relative z-10 flex flex-col gap-2.5 hidden">
                        <div class="flex justify-between items-center">
                            <span class="text-[11px] font-display font-bold uppercase tracking-wider text-slate-500" data-i18n="res_extraction">Résultat d'Extraction IA</span>
                            <button onclick="closeResult()" class="text-slate-400 hover:text-slate-600 font-bold text-sm bg-transparent border-none cursor-pointer text-base" type="button">&times;</button>
                        </div>
                        
                        <div class="bg-white/95 p-3 rounded-lg border border-slate-200 text-xs space-y-1.5 shadow-xs">
                            <div class="grid grid-cols-2 gap-y-1.5">
                                <span class="text-slate-500 font-medium" data-i18n="res_prod_lbl">Produit :</span>
                                <span id="res-product" class="font-bold text-emerald-deep text-right">-</span>
                                
                                <span class="text-slate-500 font-medium" data-i18n="res_qty_lbl">Quantité :</span>
                                <span id="res-quantity" class="font-bold text-slate-900 text-right">-</span>
                                
                                <span class="text-slate-500 font-medium" data-i18n="res_qual_lbl">Qualité :</span>
                                <span id="res-quality" class="font-bold text-terracotta text-right">-</span>
                                
                                <span class="text-slate-500 font-medium" data-i18n="res_com_lbl">Commune Matchée :</span>
                                <span id="res-commune" class="font-bold text-emerald-mid text-right">-</span>
                            </div>
                        </div>
                        
                        <!-- Extracted Entity Chips -->
                        <div class="flex flex-wrap gap-1.5 pt-1" id="res-chips-container">
                            <span class="bg-emerald-soft text-emerald-deep border border-emerald-200/80 text-[11px] font-bold px-2 py-1 rounded-md flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">eco</span> <span id="chip-prod-qty">Tomates 2.0t</span>
                            </span>
                            <span class="bg-slate-100 text-slate-700 text-[11px] font-medium px-2 py-1 rounded-md flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">pin_drop</span> Origine: <span id="chip-orig">Belfaa</span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Interactive Darija Preset Prompt Chips -->
                    <div class="pt-1">
                        <div class="text-[11px] font-display font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center justify-between">
                            <span data-i18n="freq_cmds">Commandes Fréquentes Fellahs</span>
                            <span class="text-[10px] text-emerald-deep font-semibold" data-i18n="one_click_sim">1-Clic Simulation</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="applyPreset('3andi 2 toun d l-khizou mzyan f Massa', this)" class="preset-pill group px-3 py-2 rounded-xl bg-white hover:bg-emerald-deep hover:text-white border border-slate-200 text-xs font-semibold text-slate-700 transition-all text-left flex items-center justify-between shadow-xs cursor-pointer" type="button">
                                <span data-i18n="preset_1">+ 2t Carotte Massa</span>
                                <span class="material-symbols-outlined text-xs text-slate-400 group-hover:text-white">add_circle</span>
                            </button>
                            <button onclick="applyPreset('maticha mzyana f Belfaa, kayn 3500 kilo bgha chi s-camio', this)" class="preset-pill group px-3 py-2 rounded-xl bg-emerald-deep text-white border border-emerald-deep text-xs font-bold transition-all text-left flex items-center justify-between shadow-sm cursor-pointer ring-2 ring-emerald-glow/40" type="button">
                                <span data-i18n="preset_2">+ 3.5t Tomate Belfaa</span>
                                <span class="material-symbols-outlined text-xs text-emerald-glow">check_circle</span>
                            </button>
                            <button onclick="applyPreset('3andi k-tar d limoun f Taroudant', this)" class="preset-pill group px-3 py-2 rounded-xl bg-white hover:bg-emerald-deep hover:text-white border border-slate-200 text-xs font-semibold text-slate-700 transition-all text-left flex items-center justify-between shadow-xs cursor-pointer" type="button">
                                <span data-i18n="preset_3">+ 1.8t Agrumes Taroudant</span>
                                <span class="material-symbols-outlined text-xs text-slate-400 group-hover:text-white">add_circle</span>
                            </button>
                            <button onclick="applyPreset('3andi k-tar d primeurs f Oulad Teima', this)" class="preset-pill group px-3 py-2 rounded-xl bg-white hover:bg-emerald-deep hover:text-white border border-slate-200 text-xs font-semibold text-slate-700 transition-all text-left flex items-center justify-between shadow-xs cursor-pointer" type="button">
                                <span data-i18n="preset_4">+ 4t Primeurs Oulad Teima</span>
                                <span class="material-symbols-outlined text-xs text-slate-400 group-hover:text-white">add_circle</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 2: 3 HIGH-CONTRAST STAT / METRIC CARDS (CLIMATE & VALUE) -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-display font-bold text-sm text-slate-900 tracking-tight flex items-center gap-1.5" data-i18n="impact">
                            <span class="material-symbols-outlined text-emerald-mid text-lg">insights</span>
                            Impact National &amp; Climat
                        </h3>
                        <span class="text-[11px] font-bold text-emerald-deep bg-emerald-100/80 px-2 py-0.5 rounded-full" data-i18n="today">Aujourd'hui</span>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Card 1: CO2 Evité -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm hover:border-emerald-deep/40 transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 font-display" data-i18n="co2_evite">CO₂ Évité</span>
                                    <div class="w-7 h-7 rounded-lg bg-emerald-soft text-emerald-deep flex items-center justify-center">
                                        <span class="material-symbols-outlined text-base">eco</span>
                                    </div>
                                </div>
                                <div class="font-display text-xl 2xl:text-2xl font-extrabold text-emerald-deep tracking-tight" id="sidebar-metric-co2">3,840 kg</div>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-slate-100">
                                <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-mid leading-none">
                                    <span class="material-symbols-outlined text-xs">trending_up</span>
                                    <span data-i18n="vs_solo">+18% vs solo</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                                    <div class="bg-emerald-mid h-1.5 rounded-full" style="width: 78%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card 2: Tonnage Regroupé -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm hover:border-slate-300 transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 font-display" data-i18n="tonnage_lbl">Tonnage</span>
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-base">local_shipping</span>
                                    </div>
                                </div>
                                <div class="font-display text-xl 2xl:text-2xl font-extrabold text-slate-900 tracking-tight" id="sidebar-metric-tonnage">142.8 t</div>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-slate-100 leading-tight">
                                <div class="text-[11px] font-semibold text-slate-600 leading-tight" data-i18n="lots_cons">48 lots consolidés</div>
                                <div class="text-[10px] text-slate-400 mt-0.5 leading-none" data-i18n="camions_opt">16 camions optimisés</div>
                            </div>
                        </div>
                        
                        <!-- Card 3: Marge Agriculteur -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm hover:border-terracotta/40 transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 font-display" data-i18n="marge_fellah">Marge Fellah</span>
                                    <div class="w-7 h-7 rounded-lg bg-terracotta-light text-terracotta-vibrant flex items-center justify-center">
                                        <span class="material-symbols-outlined text-base">payments</span>
                                    </div>
                                </div>
                                <div class="font-display text-xl 2xl:text-2xl font-extrabold text-terracotta-vibrant tracking-tight">+32%</div>
                            </div>
                            <div class="mt-2.5 pt-2 border-t border-slate-100 leading-tight">
                                <div class="text-[11px] font-bold text-slate-800 leading-tight" data-i18n="gain_net">Gain Net Moyen</div>
                                <div class="text-[10px] font-medium text-emerald-deep mt-0.5 leading-none" data-i18n="marge_value">420 MAD/expéd.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 3: OPTIMIZER & COMPILATION TRIGGER -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-terracotta text-lg">alt_route</span>
                            <span class="font-display font-bold text-sm text-slate-900" data-i18n="moteur_groupage">Moteur de Groupage Dynamique</span>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-deep bg-emerald-100 px-2 py-0.5 rounded-full" data-i18n="pret_consolidation">Prêt pour consolidation</span>
                    </div>
                    
                    <div class="flex items-center justify-between text-xs bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-slate-500 block leading-tight" data-i18n="flotte">Flotte Allouée</span>
                            <span class="font-bold text-slate-900 font-display leading-normal">3 Camions 10t combinés</span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-500 block leading-tight" data-i18n="remplissage">Remplissage Flotte</span>
                            <span class="font-bold text-emerald-deep font-display text-sm leading-normal">94% (+28% gain)</span>
                        </div>
                    </div>
                    
                    <!-- Terracotta Primary Action Trigger Button -->
                    <button onclick="runAggregation()" class="w-full py-3.5 px-5 rounded-xl bg-terracotta hover:bg-terracotta-vibrant active:scale-[0.99] text-white font-display font-bold text-sm flex items-center justify-center gap-2 shadow-md shadow-terracotta/25 hover:shadow-lg hover:shadow-terracotta/35 transition-all cursor-pointer border-none" type="button">
                        <span class="material-symbols-outlined text-lg">alt_route</span>
                        <span data-i18n="compiler_lots">Compiler les Lots Groupés (National)</span>
                    </button>
                </div>
                
                <!-- SECTION 4: COMMUNES CATALOG LIST -->
                <div class="border-t border-slate-100 pt-4 flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider" data-i18n="hubs_logistiques">Hubs Logistiques Nationaux</h3>
                        <span class="text-[10px] text-slate-400" data-i18n="click_recent">Cliquez pour recentrer la carte</span>
                    </div>
                    <div class="space-y-1.5" id="communes-list">
                        <!-- Communes are rendered here dynamically from JS block -->
                    </div>
                </div>
                
            </div>
        </aside>
        
        <!-- RIGHT PANEL: FULL-BLEED INTERACTIVE MAP AREA WITH OVERLAYS (No outer padding, z-index managed) -->
        <main class="map-workspace min-w-0 flex-1 h-full relative overflow-hidden bg-slate-100">
            
            <!-- INTERACTIVE LEAFLET MAP CONTAINER (w-full h-full relative z-0) -->
            <div id="map" class="w-full h-full relative z-0 transition-all duration-300"></div>
            
            <!-- MAP CONTROLS & HUD HEADER (TOP FLOATING BAR - absolute z-10 with proper spacing) -->
            <div class="absolute top-4 left-4 right-4 flex flex-col sm:flex-row gap-3 items-center justify-end pointer-events-none z-10">
                <!-- Telemetry, Map Style Toggle & Zoom Controls -->
                <div class="flex items-center gap-3 pointer-events-auto shrink-0">
                    <!-- Traffic & Weather Info -->
                    <div class="hidden xl:flex items-center gap-2.5 px-3.5 py-1.5 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200 shadow-lg text-xs font-semibold text-slate-700">
                        <span class="flex items-center gap-1 text-emerald-deep font-bold">
                            <span class="material-symbols-outlined text-base">traffic</span> <span data-i18n="trafic_fluide">R105 Fluide</span>
                        </span>
                        <span class="text-slate-300">•</span>
                        <span class="flex items-center gap-1 text-terracotta">
                            <span class="material-symbols-outlined text-base">thermostat</span> <span data-i18n="temp">Temp. +19°C</span>
                        </span>
                    </div>
                    
                    <!-- Dark / Light Map Style Toggle -->
                    <button class="px-3 py-1.5 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200 shadow-lg text-slate-700 hover:text-slate-900 font-display text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer border-none whitespace-nowrap" id="mapStyleBtn" title="Basculer le style de carte" type="button">
                        <span class="material-symbols-outlined text-sm">light_mode</span>
                        <span data-i18n="mode_clair">Mode Clair</span>
                    </button>
                    
                    <!-- Zoom In / Out / Center View (Bypasses Leaflet defaults for visual style) -->
                    <div class="flex items-center bg-white/95 backdrop-blur-md border border-slate-200 shadow-lg rounded-2xl p-1 gap-1">
                        <button class="w-8 h-8 rounded-xl hover:bg-slate-100 text-slate-700 flex items-center justify-center transition-colors border-none bg-transparent cursor-pointer" title="Zoomer" type="button" onclick="map.zoomIn()">
                            <span class="material-symbols-outlined text-lg">add</span>
                        </button>
                        <button class="w-8 h-8 rounded-xl hover:bg-slate-100 text-slate-700 flex items-center justify-center transition-colors border-none bg-transparent cursor-pointer" title="Dézoomer" type="button" onclick="map.zoomOut()">
                            <span class="material-symbols-outlined text-lg">remove</span>
                        </button>
                        <button class="w-8 h-8 rounded-xl hover:bg-slate-100 text-emerald-deep flex items-center justify-center transition-colors border-none bg-transparent cursor-pointer" title="Réinitialiser vue" type="button" onclick="resetMapView()">
                            <span class="material-symbols-outlined text-lg">center_focus_strong</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- BOTTOM FLOATING REGIONAL COURIER TRAY (absolute z-10 with bottom-4 left-4 right-4) -->
            <div class="courier-tray absolute bottom-4 left-4 right-4 z-10 pointer-events-none">
                <div class="bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200/90 shadow-xl pointer-events-auto">
                    <!-- Clean Horizontal Row Layout -->
                    <div class="flex flex-row items-center justify-between gap-3 w-full p-3">
                        
                        <!-- Courier Express Route Info -->
                        <div class="flex flex-row items-center gap-3 min-w-0 py-0.5">
                            <div class="w-10 h-10 rounded-xl bg-emerald-deep text-white flex items-center justify-center shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-xl">route</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-[10px] font-display font-extrabold uppercase tracking-wider text-emerald-deep leading-tight" data-i18n="couloir_express">Couloir Express Prioritaire Souss</span>
                                <span class="font-display font-bold text-xs text-slate-900 truncate leading-tight mt-0.5" title="Belfaa &amp; Biougra ➔ Hub Aït Melloul ➔ Marché de Gros Inezgane" data-i18n="route_desc">
                                    Belfaa &amp; Biougra ➔ Hub Aït Melloul ➔ Marché Inezgane
                                </span>
                            </div>
                        </div>
                        
                        <!-- Tonnage Groupé Card -->
                        <div class="flex items-center gap-2.5 bg-slate-50/70 py-2 px-2.5 rounded-xl border border-slate-150 overflow-hidden">
                            <div class="w-8 h-8 rounded-lg bg-emerald-soft text-emerald-deep flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-lg">local_shipping</span>
                            </div>
                            <div class="flex flex-col min-w-0 py-0.5">
                                <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wide leading-tight mb-0.5" data-i18n="tonnage_groupe">Tonnage Groupé</span>
                                <span id="metric-tonnage" class="font-display font-extrabold text-xs text-slate-900 leading-tight">0.0 Tons</span>
                            </div>
                        </div>
                        
                        <!-- Chaîne du Froid Card -->
                        <div class="flex items-center gap-2.5 bg-slate-50/70 py-2 px-2.5 rounded-xl border border-slate-150 overflow-hidden">
                            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-lg">ac_unit</span>
                            </div>
                            <div class="flex flex-col min-w-0 py-0.5">
                                <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wide leading-tight mb-0.5" data-i18n="chaine_froid">Chaîne du Froid</span>
                                <span class="font-display font-bold text-xs text-slate-800 leading-tight" data-i18n="temp_garanti">+8°C Garanti</span>
                            </div>
                        </div>
                        
                        <!-- Économie CO2 Card -->
                        <div class="flex items-center gap-2.5 bg-slate-50/70 py-2 px-2.5 rounded-xl border border-slate-150 overflow-hidden">
                            <div class="w-8 h-8 rounded-lg bg-emerald-soft text-emerald-deep flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-lg">savings</span>
                            </div>
                            <div class="flex flex-col min-w-0 py-0.5">
                                <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wide leading-tight mb-0.5" data-i18n="eco_co2">Économie CO₂</span>
                                <span id="metric-co2" class="font-display font-extrabold text-xs text-emerald-deep leading-tight">0.0 kg CO₂</span>
                            </div>
                        </div>
                        
                        <!-- Action Button (Far Right) -->
                        <div class="flex items-center gap-2 shrink-0">
                            <button onclick="runAggregation()" class="py-2.5 px-4 rounded-xl bg-terracotta hover:bg-terracotta-vibrant text-white font-display text-xs font-bold flex items-center gap-1.5 shadow-sm shadow-terracotta/30 transition-all cursor-pointer border-none" type="button">
                                <span class="material-symbols-outlined text-sm">send</span>
                                <span data-i18n="depart_flotte">Départ Flotte</span>
                            </button>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- ============================================================ -->
            <!-- ESG SUSTAINABILITY MODAL OVERLAY                             -->
            <!-- ============================================================ -->
            <div id="esg-modal" class="hidden absolute inset-0 z-50 flex items-center justify-center p-6" style="background:rgba(15,23,42,0.65);backdrop-filter:blur(6px);">
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" id="esg-modal-inner" style="animation:esgSlideUp .35s cubic-bezier(.22,1,.36,1) both">
                    <!-- Modal Header -->
                    <div class="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-white rounded-t-3xl z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#0d9488,#059669)">
                                <span class="material-symbols-outlined text-white text-xl">eco</span>
                            </div>
                            <div>
                                <h2 class="font-display font-extrabold text-base text-slate-900 leading-tight" data-i18n="esg_title">Rapport ESG — Durabilité &amp; Carbone</h2>
                                <p class="text-[11px] text-slate-500 font-medium leading-tight" data-i18n="esg_subtitle">Souss-Massa Logistique Agricole · Exercice 2024</p>
                            </div>
                        </div>
                        <button onclick="closeEsgModal()" class="w-8 h-8 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-900 flex items-center justify-center transition-colors border-none bg-transparent cursor-pointer" type="button">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>

                    <!-- ESG KPI Grid -->
                    <div class="p-6 flex flex-col gap-5">

                        <!-- Top 3 headline KPIs -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-2xl p-4 flex flex-col gap-1 text-white" style="background:linear-gradient(135deg,#0d9488,#059669)">
                                <span class="material-symbols-outlined text-2xl">co2</span>
                                <span class="font-display font-extrabold text-2xl leading-none mt-1">−38%</span>
                                <span class="text-[11px] font-semibold opacity-90 leading-tight" data-i18n="co2_emis">Émissions CO₂ vs transport individuel</span>
                            </div>
                            <div class="rounded-2xl p-4 flex flex-col gap-1" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0">
                                <span class="material-symbols-outlined text-2xl text-emerald-700">forest</span>
                                <span class="font-display font-extrabold text-2xl leading-none mt-1 text-emerald-900">1 632 kg</span>
                                <span class="text-[11px] font-semibold text-emerald-700 leading-tight" data-i18n="co2_econ">CO₂ économisé ce mois</span>
                            </div>
                            <div class="rounded-2xl p-4 flex flex-col gap-1" style="background:linear-gradient(135deg,#fef9c3,#fef08a);border:1px solid #fde68a">
                                <span class="material-symbols-outlined text-2xl text-amber-700">bolt</span>
                                <span class="font-display font-extrabold text-2xl leading-none mt-1 text-amber-900">−22%</span>
                                <span class="text-[11px] font-semibold text-amber-700 leading-tight" data-i18n="consommation">Consommation carburant groupé</span>
                            </div>
                        </div>

                        <!-- Environmental Detail Rows -->
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 overflow-hidden">
                            <div class="px-4 py-2.5 border-b border-slate-100 flex items-center gap-2">
                                <span class="material-symbols-outlined text-base text-slate-500">bar_chart</span>
                                <span class="font-display font-bold text-xs text-slate-700 uppercase tracking-wider" data-i18n="ind_env">Indicateurs Environnementaux</span>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-xs text-slate-600 font-medium" data-i18n="trajets_conso">Trajets consolidés (km épargnés)</span>
                                    <span class="font-display font-bold text-xs text-emerald-700">4 820 km / mois</span>
                                </div>
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-xs text-slate-600 font-medium" data-i18n="camions_suppr">Camions supprimés par groupage</span>
                                    <span class="font-display font-bold text-xs text-slate-900">16 véhicules</span>
                                </div>
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-xs text-slate-600 font-medium" data-i18n="taux_rempl">Taux remplissage moyen camion</span>
                                    <span class="font-display font-bold text-xs text-slate-900">87 %</span>
                                </div>
                                <div class="flex items-center justify-between px-4 py-3">
                                    <span class="text-xs text-slate-600 font-medium" data-i18n="pertes_post">Pertes post-récolte réduites</span>
                                    <span class="font-display font-bold text-xs text-terracotta">−14 %</span>
                                </div>
                            </div>
                        </div>

                        <!-- Social / Governance -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 overflow-hidden">
                                <div class="px-4 py-2.5 border-b border-slate-100 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-slate-500">groups</span>
                                    <span class="font-display font-bold text-xs text-slate-700 uppercase tracking-wider" data-i18n="social">Social</span>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-slate-600 font-medium" data-i18n="agri_connect">Agriculteurs connectés</span>
                                        <span class="font-display font-bold text-xs text-slate-900">342</span>
                                    </div>
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-slate-600 font-medium" data-i18n="revenu_moyen">Revenu moyen fellah</span>
                                        <span class="font-display font-bold text-xs text-emerald-700">+32 %</span>
                                    </div>
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-slate-600 font-medium" data-i18n="communes_couv">Communes couvertes</span>
                                        <span class="font-display font-bold text-xs text-slate-900">6 / 8</span>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 overflow-hidden">
                                <div class="px-4 py-2.5 border-b border-slate-100 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-slate-500">verified</span>
                                    <span class="font-display font-bold text-xs text-slate-700 uppercase tracking-wider" data-i18n="gouvernance">Gouvernance</span>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-slate-600 font-medium" data-i18n="coop_part">Coopératives partenaires</span>
                                        <span class="font-display font-bold text-xs text-slate-900">4 actives</span>
                                    </div>
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-slate-600 font-medium" data-i18n="tracabilite">Traçabilité numérique</span>
                                        <span class="font-display font-bold text-xs text-emerald-700">100 %</span>
                                    </div>
                                    <div class="flex items-center justify-between px-4 py-2.5">
                                        <span class="text-xs text-slate-600 font-medium" data-i18n="audit_esg">Audits ESG / an</span>
                                        <span class="font-display font-bold text-xs text-slate-900" data-i18n="trimestriel">Trimestriel</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer note -->
                        <p class="text-[10px] text-slate-400 text-center font-medium leading-relaxed">Données calculées sur la base du réseau Wasla Souss-Massa · Hypothèse 120 g CO₂/km camion groupé vs 210 g/km individuel.</p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div id="toast-region" class="fixed top-4 right-4 z-[10000] flex flex-col gap-3 pointer-events-none" aria-label="Notifications"></div>
    <dialog id="tracking-dialog" aria-labelledby="tracking-title" aria-describedby="tracking-description">
        <div class="tracking-content">
            <div class="flex items-start justify-between gap-4">
                <div><p class="tracking-eyebrow">WASLA / TRANSPORT</p><h2 id="tracking-title" class="font-display text-2xl font-bold mt-2">Suivi d’expédition</h2></div>
                <button id="tracking-close" type="button" class="icon-button" aria-label="Fermer le suivi"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
            </div>
            <p id="tracking-description" class="tracking-muted text-sm mt-3">Démonstration · Trajet et données GPS simulés.</p>
            <div class="tracking-summary flex flex-wrap items-center justify-between gap-3 mt-6">
                <span class="font-mono text-sm">EXP-SM-0248</span><span id="tracking-status" class="transit-badge"><span class="signal-dot"></span>En transit</span>
            </div>
            <div class="route-visual mt-5" aria-label="Trajet simulé de Massa vers Agadir">
                <svg viewBox="0 0 480 200" role="img" aria-label="Schéma du parcours Massa–Agadir">
                    <path class="route-secondary" d="M0 50L480 130M60 0L150 200M260 0L220 200M400 0L340 200M0 160L480 45"/>
                    <path class="route-line" d="M40 150 L120 150 L180 100 L280 100 L350 50 L440 50"/>
                    <path id="tracking-route-progress" class="route-completed" pathLength="100" d="M40 150 L120 150 L180 100 L280 100 L350 50 L440 50"/>
                    <circle cx="40" cy="150" r="6" fill="#34d399"/><circle cx="440" cy="50" r="6" fill="#fb923c"/>
                    <g id="tracking-truck"><circle r="17" fill="#065f46" stroke="#6ee7b7" stroke-width="2"/><path d="M-9 -5H3V5H-9ZM3 -2H7L10 2V5H3" fill="none" stroke="white" stroke-width="1.5"/><circle cx="-5" cy="7" r="2" fill="white"/><circle cx="6" cy="7" r="2" fill="white"/></g>
                    <text x="30" y="181">MASSA</text><text x="373" y="28">AGADIR</text>
                </svg>
                <span class="route-caption font-mono">SOUSS-MASSA / N1 · SCHÉMA</span>
            </div>
            <div class="flex justify-between gap-3 text-sm mt-6 mb-3"><span>Progression du trajet</span><strong id="tracking-percent" class="font-mono">38 %</strong></div>
            <div id="tracking-progress" role="progressbar" aria-label="Progression du trajet simulé" aria-valuemin="0" aria-valuemax="100" aria-valuenow="38"><div id="tracking-fill"></div></div>
            <dl class="tracking-metrics grid grid-cols-2 gap-3 mt-5">
                <div><dt>Arrivée estimée</dt><dd id="tracking-eta">—</dd></div><div><dt>Vitesse simulée</dt><dd id="tracking-speed">64 km/h</dd></div>
                <div><dt>Distance restante</dt><dd id="tracking-distance">—</dd></div><div><dt>Chargement</dt><dd>8.4 / 10 t</dd></div>
            </dl>
            <div class="tracking-gps mt-5"><span class="tracking-muted text-xs">POSITION GPS SIMULÉE</span><p id="tracking-coordinates" class="font-mono mt-2" dir="ltr">—</p><p id="tracking-updated" class="tracking-muted text-xs mt-2">—</p></div>
            <ol class="tracking-stops mt-5 text-sm"><li><span class="signal-dot"></span><div>Massa · Chargement terminé<p class="tracking-muted text-xs mt-1">Coopérative agricole / 8.4 tonnes</p></div></li><li><span class="destination-dot"></span><div>Agadir · Livraison au hub<p id="tracking-destination" class="tracking-muted text-xs mt-1">Réception planifiée</p></div></li></ol>
            <div class="flex gap-3 mt-6"><button id="tracking-pause" class="tracking-trigger rounded-xl px-4 py-3 text-sm font-bold" type="button">Mettre en pause</button><button id="tracking-restart" class="icon-button px-3 text-sm" type="button">Rejouer</button></div>
        </div>
    </dialog>
    <script src="js/pitch.js"></script>
    <!-- Leaflet JS Map Script -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        let communesData = [];
        let coopHubs = [];

        // Map Center point (Morocco Centroid)
        const regionCentroid = [31.7917, -7.0926];
        let map;
        let markersGroup;

        // Initialize Map once DOM is ready
        document.addEventListener("DOMContentLoaded", function() {
            // Setup Leaflet Map without default zoom control (z-index managed relative to z-15)
            map = L.map('map', {
                center: regionCentroid,
                zoom: 6,
                zoomControl: false
            });

            // Use standard free OpenStreetMap tiles (Fix: guaranteed clean tiles without watermark)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            markersGroup = L.layerGroup().addTo(map);

            // Fetch dynamic hubs with fallback
            fetch('../api/get_hubs.php')
                .then(res => {
                    if (!res.ok) throw new Error("HTTP " + res.status);
                    return res.json();
                })
                .then(data => {
                    if (data && data.success) {
                        communesData = data.communesData;
                        coopHubs = data.coopHubs;
                    } else {
                        throw new Error(data ? data.error : "Unknown API error");
                    }
                })
                .catch(err => {
                    console.error("Error fetching hubs, using hardcoded fallback:", err);
                    communesData = [
                        { id: 1, name: "Casablanca", lat: 33.5731, lon: -7.5898 },
                        { id: 2, name: "Meknès", lat: 33.8730, lon: -5.5407 },
                        { id: 3, name: "Berkane", lat: 34.9213, lon: -2.3275 },
                        { id: 4, name: "Agadir", lat: 30.4278, lon: -9.5981 }
                    ];
                    coopHubs = communesData.map(c => ({
                        id: 'c' + c.id,
                        name: 'Coopérative ' + c.name,
                        lat: c.lat,
                        lon: c.lon,
                        members: 150,
                        products: 'Produits locaux'
                    }));
                })
                .finally(() => {
                    renderCommunes();
                    if (activeTab === 'coop' && coopLayer) {
                        getCoopLayer().clearLayers();
                        coopLayer = null; // force recreation
                        switchMapTab('coop');
                    }
                });
        });

        // Setup Communes on both panel and map
        function renderCommunes() {
            const listContainer = document.getElementById("communes-list");
            listContainer.innerHTML = "";

            communesData.forEach(com => {
                // Add marker to map
                const markerHtml = `
                    <div class="flex items-center justify-center h-7 w-7 rounded-full bg-emerald-deep text-white font-bold text-xs border-2 border-white shadow-lg">
                        ${com.name[0]}
                    </div>
                `;
                
                const customIcon = L.divIcon({
                    html: markerHtml,
                    className: 'custom-div-icon',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });

                L.marker([com.lat, com.lon], { icon: customIcon })
                    .addTo(markersGroup)
                    .bindPopup(`<div class="p-2 min-w-[150px]">
                        <strong class="text-emerald-deep font-display font-bold text-sm block mb-1">${com.name}</strong>
                        <span class="text-xs text-slate-500 font-sans">Commune Agricole Principale</span>
                    </div>`);

                // Create Sidebar Card matching Stitch layout
                const card = document.createElement("div");
                card.className = "flex items-center justify-between p-3 rounded-xl bg-white border border-slate-200 hover:border-emerald-deep/40 hover:bg-emerald-soft/10 transition-all cursor-pointer shadow-xs";
                card.onclick = () => focusMap(com.lat, com.lon, com.name);
                
                card.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-soft text-emerald-deep flex items-center justify-center font-bold text-xs">
                            ${com.name[0]}
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">${com.name}</p>
                            <p class="text-[10px] text-slate-400">Lat: ${com.lat.toFixed(4)}, Lon: ${com.lon.toFixed(4)}</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 text-sm">arrow_forward</span>
                `;
                listContainer.appendChild(card);
            });
        }

        // Apply pre-written Darija scripts instantly for fast validation
        function applyPreset(text, element) {
            document.getElementById("darija-text").value = text;
            
            // Adjust translation dynamically based on selected preset
            const transText = document.getElementById("darija-translation");
            if (text.includes("Massa")) {
                transText.innerText = "« J'ai 2 tonnes de carottes de bonne qualité à Massa... »";
            } else if (text.includes("Belfaa")) {
                transText.innerText = "« Tomates de bonne qualité à Belfaa, il y a 3500 kilos, je cherche un camion... »";
            } else if (text.includes("Taroudant")) {
                transText.innerText = "« J'ai un hectare d'oranges à Taroudant... »";
            } else if (text.includes("Oulad Teima")) {
                transText.innerText = "« J'ai 4 tonnes de primeurs à Oulad Teima... »";
            } else {
                transText.innerText = "« Traduction en cours... »";
            }

            // Adjust interactive pills styling
            document.querySelectorAll('.preset-pill').forEach(b => {
              b.classList.remove('bg-emerald-deep', 'text-white', 'border-emerald-deep', 'ring-2', 'ring-emerald-glow/40');
              b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
              const icon = b.querySelector('.material-symbols-outlined');
              if (icon) {
                icon.textContent = 'add_circle';
                icon.classList.remove('text-white', 'text-emerald-glow');
                icon.classList.add('text-slate-400');
              }
            });

            if (element) {
                element.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
                element.classList.add('bg-emerald-deep', 'text-white', 'border-emerald-deep', 'ring-2', 'ring-emerald-glow/40');
                const icon = element.querySelector('.material-symbols-outlined');
                if (icon) {
                  icon.textContent = 'check_circle';
                  icon.classList.remove('text-slate-400');
                  icon.classList.add('text-emerald-glow');
                }
            }
        }

        // Close Result Panel
        function closeResult() {
            document.getElementById("nlu-result-card").classList.add("hidden");
        }

        // Focus Map on Commune click
        function focusMap(lat, lon, name) {
            map.setView([lat, lon], 11, { animate: true, duration: 1 });
        }

        // Reset view to overall centroid
        function resetMapView() {
            map.setView(regionCentroid, 9, { animate: true, duration: 1 });
        }

        // Form Submission - Calls process_nlu API
        document.getElementById("nlu-form").addEventListener("submit", function(e) {
            e.preventDefault();
            const textValue = document.getElementById("darija-text").value.trim();
            if (!textValue) return;

            // Make POST call to API
            fetch('../api/process_nlu.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ darija_text: textValue })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const nlu = data.nlu_extracted;
                    const matched = data.commune_match;

                    // Display Resulting Card
                    document.getElementById("res-product").innerText = nlu.product || "Inconnu";
                    document.getElementById("res-quantity").innerText = nlu.quantity_kg ? `${nlu.quantity_kg.toLocaleString()} kg` : "Inconnue";
                    document.getElementById("res-quality").innerText = nlu.quality || "Standard";
                    document.getElementById("res-commune").innerText = matched ? matched.nom : (nlu.commune || "Inconnue");
                    
                    // Update dynamic badge chips
                    document.getElementById("chip-prod-qty").innerText = `${nlu.product || 'Produit'} (${nlu.quantity_kg ? (nlu.quantity_kg/1000).toFixed(1) : '?'}t)`;
                    document.getElementById("chip-orig").innerText = matched ? matched.nom : (nlu.commune || "Inconnue");

                    document.getElementById("nlu-result-card").classList.remove("hidden");

                    if (matched) {
                        focusMap(matched.latitude, matched.longitude, matched.nom);
                        
                        // Drop a dynamic temporary marker using terracotta-vibrant colors
                        const tempIcon = L.divIcon({
                            html: `
                                <div class="relative flex items-center justify-center">
                                    <div class="absolute h-6 w-6 rounded-full bg-terracotta-vibrant/40 animate-ping"></div>
                                    <div class="h-4 w-4 bg-terracotta-vibrant rounded-full border-2 border-white shadow-md"></div>
                                </div>
                            `,
                            className: 'temp-ping-icon',
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        });
                        
                        L.marker([matched.latitude, matched.longitude], { icon: tempIcon }).addTo(map)
                            .bindPopup(`<div class="p-2 min-w-[150px]">
                                <span class="text-[10px] font-display font-bold uppercase tracking-wider text-terracotta block mb-0.5">Offre Détectée</span>
                                <strong class="text-slate-900 font-display font-extrabold text-sm block mb-1">${nlu.product}</strong>
                                <span class="text-xs text-emerald-deep font-bold block mb-1">${nlu.quantity_kg} kg</span>
                                <span class="text-[10px] text-slate-500 block">Qualité: ${nlu.quality || 'Standard'}</span>
                            </div>`)
                            .openPopup();
                    }
                } else {
                    showToast("Erreur de traitement: " + (data.error || "Inconnu"));
                }
            })
            .catch(err => {
                console.error("API error:", err);
                showToast("Erreur d'accès à l'API de traitement NLU.");
            });
        });

        // Trigger dynamic aggregation algorithm
        function runAggregation() {
            fetch('../api/aggregate_lots.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ min_threshold_kg: 500 }) // low threshold so users can test immediately
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.lots_created > 0) {
                        let totalTons = 0;
                        let totalCo2 = 0;
                        
                        data.lots.forEach(lot => {
                            totalTons += lot.total_weight_kg / 1000;
                            totalCo2 += lot.co2_saved_kg;

                            // Drop a beautiful terracotta cargo lot icon on map
                            const lotHtml = `
                                <div class="flex items-center justify-center h-8 w-8 rounded-lg bg-terracotta text-white font-extrabold text-xs border-2 border-white shadow-xl transform rotate-45 transition-all hover:scale-110">
                                    <div class="transform -rotate-45">📦</div>
                                </div>
                            `;
                            const lotIcon = L.divIcon({
                                html: lotHtml,
                                className: 'lot-div-icon',
                                iconSize: [32, 32],
                                iconAnchor: [16, 16]
                            });

                            L.marker([lot.centroid.latitude, lot.centroid.longitude], { icon: lotIcon })
                                .addTo(map)
                                .bindPopup(`<div class="p-2 min-w-[170px]">
                                    <strong class="text-terracotta font-display font-extrabold text-sm block mb-1">Lot Groupé #${lot.lot_id}</strong>
                                    <span class="text-xs text-slate-700 block mb-1 font-medium">Produit : ${lot.product}</span>
                                    <span class="text-xs text-emerald-deep font-bold block mb-1">Poids : ${lot.total_weight_kg.toLocaleString()} kg</span>
                                    <span class="text-[10px] text-slate-500 font-semibold block">Gain CO₂ : ${lot.co2_saved_kg.toFixed(1)} kg</span>
                                </div>`);
                        });

                        // Update floating and sidebar metric counters
                        document.getElementById("metric-tonnage").innerText = `${totalTons.toFixed(1)} Tons`;
                        document.getElementById("metric-co2").innerText = `${totalCo2.toFixed(1)} kg CO₂`;
                        document.getElementById("sidebar-metric-tonnage").innerText = `${totalTons.toFixed(1)} t`;
                        document.getElementById("sidebar-metric-co2").innerText = `${totalCo2.toFixed(0)} kg`;

                        showToast(`Succès! ${data.lots_created} Lot(s) Logistique(s) compilé(s) à partir de vos offres agricoles.`, "success");
                    } else {
                        // Demonstrate simulation if DB has no real farmer offers posted yet
                        simulateLotAggregation();
                    }
                } else {
                    showToast("Erreur de compilation: " + data.message);
                }
            })
            .catch(err => {
                console.error("Aggregation API error:", err);
                showToast("Erreur d'accès à l'API de groupement. Démarrage de la simulation locale...");
                simulateLotAggregation();
            });
        }

        // Standalone sandbox simulation of aggregation
        function simulateLotAggregation() {
            // Mock dynamic pooling
            const mockLot1 = { id: 101, name: "Consolidation Carottes Massa", lat: 30.0078, lon: -9.6381, tonnage: 5.4, co2: 648 };
            const mockLot2 = { id: 102, name: "Consolidation Tomates Belfaa", lat: 30.0414, lon: -9.5639, tonnage: 8.2, co2: 984 };

            [mockLot1, mockLot2].forEach(lot => {
                const lotHtml = `
                    <div class="flex items-center justify-center h-8 w-8 rounded-lg bg-terracotta text-white font-extrabold text-xs border-2 border-white shadow-xl transform rotate-45 transition-all hover:scale-110">
                        <div class="transform -rotate-45">📦</div>
                    </div>
                `;
                const lotIcon = L.divIcon({
                    html: lotHtml,
                    className: 'lot-div-icon',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });

                L.marker([lot.lat, lot.lon], { icon: lotIcon })
                    .addTo(map)
                    .bindPopup(`<div class="p-2 min-w-[170px]">
                        <strong class="text-terracotta font-display font-extrabold text-sm block mb-1">Lot Groupé #${lot.id}</strong>
                        <span class="text-xs text-slate-700 block mb-1 font-semibold">${lot.name}</span>
                        <span class="text-xs text-emerald-deep font-bold block mb-1">Poids : ${(lot.tonnage * 1000).toLocaleString()} kg</span>
                        <span class="text-[10px] text-slate-500 font-semibold block">Gain CO₂ : ${lot.co2} kg</span>
                    </div>`);
            });

            // Update floating and sidebar metrics
            document.getElementById("metric-tonnage").innerText = "13.6 Tons";
            document.getElementById("metric-co2").innerText = "1,632 kg CO₂";
            document.getElementById("sidebar-metric-tonnage").innerText = "13.6 t";
            document.getElementById("sidebar-metric-co2").innerText = "1,632 kg";

            // Center view
            map.setView([30.02, -9.60], 11, { animate: true });

            showToast("Simulation: 2 Lots Logistiques créés en Souss-Massa (Massa et Belfaa) avec un total de 13.6 Tonnes consolidées, économisant 1632 kg de CO₂ !", "success");
        }

        // Microphone capture is managed by transcription/ui.js.

        // =============================================================
        // TAB NAVIGATION SYSTEM
        // =============================================================

        let coopLayer = null;     // Layer group for cooperative hubs
        let lotLayer = null;      // Layer group for aggregated lots only
        let activeTab = 'carte'; // Tracks which tab is currently active

        /**
         * Build the cooperative hubs layer (lazy — created once)
         */
        function getCoopLayer() {
            if (coopLayer) return coopLayer;
            coopLayer = L.layerGroup();
            coopHubs.forEach(hub => {
                const iconHtml = `
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#0d9488,#059669);color:#fff;
                         display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;
                         box-shadow:0 4px 12px rgba(13,148,136,.45);font-size:18px;">
                        🏭
                    </div>`;
                const icon = L.divIcon({ html: iconHtml, className: 'coop-div-icon', iconSize: [36, 36], iconAnchor: [18, 18] });
                L.marker([hub.lat, hub.lon], { icon })
                    .addTo(coopLayer)
                    .bindPopup(`<div class="p-2 min-w-[180px]">
                        <span class="text-[10px] font-display font-bold uppercase tracking-wider text-teal-700 block mb-0.5">Coopérative</span>
                        <strong class="text-slate-900 font-display font-extrabold text-sm block mb-1">${hub.name}</strong>
                        <span class="text-xs text-emerald-700 font-bold block mb-0.5">${hub.members} membres</span>
                        <span class="text-[10px] text-slate-500 block">${hub.products}</span>
                    </div>`);
            });
            return coopLayer;
        }

        /**
         * Main tab switcher — controls map layers and modal visibility
         * @param {'carte'|'groupage'|'coop'|'esg'} tab
         */
        function switchMapTab(tab) {
            activeTab = tab;

            // 1. Update tab button visual states
            document.querySelectorAll('.map-tab').forEach(btn => {
                btn.classList.remove('map-tab-active');
                btn.classList.add('map-tab-inactive');
            });
            const activeBtn = document.getElementById('tab-' + tab);
            if (activeBtn) {
                activeBtn.classList.remove('map-tab-inactive');
                activeBtn.classList.add('map-tab-active');
            }

            // 2. Close ESG modal when switching away
            if (tab !== 'esg') {
                document.getElementById('esg-modal').classList.add('hidden');
            }

            // 3. Handle map layer visibility
            if (!map) return; // Map not yet initialized

            switch (tab) {
                case 'carte':
                    // Show all markers (communes + any aggregated lots from markersGroup)
                    if (!map.hasLayer(markersGroup)) map.addLayer(markersGroup);
                    if (coopLayer && map.hasLayer(coopLayer)) map.removeLayer(coopLayer);
                    if (lotLayer && map.hasLayer(lotLayer)) map.removeLayer(lotLayer);
                    resetMapView();
                    break;

                case 'groupage':
                    // Hide commune markers, show only aggregated cargo lot markers
                    if (map.hasLayer(markersGroup)) map.removeLayer(markersGroup);
                    if (coopLayer && map.hasLayer(coopLayer)) map.removeLayer(coopLayer);
                    // Ensure lot layer exists — fall back to simulation data if no API lots yet
                    if (!lotLayer) {
                        lotLayer = L.layerGroup();
                        const simLots = [
                            { id: 101, name: 'Consolidation Carottes Massa', lat: 30.0078, lon: -9.6381, tonnage: 5.4, co2: 648 },
                            { id: 102, name: 'Consolidation Tomates Belfaa', lat: 30.0414, lon: -9.5639, tonnage: 8.2, co2: 984 },
                        ];
                        simLots.forEach(lot => {
                            const lotHtml = `<div style="width:34px;height:34px;border-radius:10px;background:#c0392b;color:#fff;
                                display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;
                                box-shadow:0 4px 12px rgba(192,57,43,.4);font-size:18px;
                                transform:rotate(45deg)"><span style="transform:rotate(-45deg)">📦</span></div>`;
                            const icon = L.divIcon({ html: lotHtml, className: 'lot-div-icon', iconSize: [34, 34], iconAnchor: [17, 17] });
                            L.marker([lot.lat, lot.lon], { icon })
                                .addTo(lotLayer)
                                .bindPopup(`<div class="p-2 min-w-[170px]">
                                    <strong class="text-terracotta font-display font-extrabold text-sm block mb-1">Lot Groupé #${lot.id}</strong>
                                    <span class="text-xs text-slate-700 block mb-1 font-semibold">${lot.name}</span>
                                    <span class="text-xs text-emerald-700 font-bold block mb-1">${(lot.tonnage * 1000).toLocaleString()} kg</span>
                                    <span class="text-[10px] text-slate-500 font-semibold block">Gain CO₂ : ${lot.co2} kg</span>
                                </div>`);
                        });
                    }
                    if (!map.hasLayer(lotLayer)) map.addLayer(lotLayer);
                    map.setView([30.03, -9.60], 11, { animate: true, duration: 0.8 });
                    break;

                case 'coop':
                    // Show cooperative hub icons, hide other layers
                    if (map.hasLayer(markersGroup)) map.removeLayer(markersGroup);
                    if (lotLayer && map.hasLayer(lotLayer)) map.removeLayer(lotLayer);
                    const cl = getCoopLayer();
                    if (!map.hasLayer(cl)) map.addLayer(cl);
                    map.setView(regionCentroid, 6, { animate: true, duration: 0.8 });
                    break;

                case 'esg':
                    // Restore all markers, then open modal on top
                    if (!map.hasLayer(markersGroup)) map.addLayer(markersGroup);
                    if (coopLayer && map.hasLayer(coopLayer)) map.removeLayer(coopLayer);
                    if (lotLayer && map.hasLayer(lotLayer)) map.removeLayer(lotLayer);
                    openEsgModal();
                    break;
            }
        }

        /** Open ESG sustainability modal */
        function openEsgModal() {
            const modal = document.getElementById('esg-modal');
            const inner = document.getElementById('esg-modal-inner');
            modal.classList.remove('hidden');
            // Re-trigger animation each open
            inner.style.animation = 'none';
            requestAnimationFrame(() => { inner.style.animation = 'esgSlideUp .35s cubic-bezier(.22,1,.36,1) both'; });
        }

        /** Close ESG modal and reset tab to Carte Interactive */
        function closeEsgModal() {
            document.getElementById('esg-modal').classList.add('hidden');
            // Reset active tab back to carte so state is consistent
            switchMapTab('carte');
        }

        // Comprehensive Trilingual i18n Dictionary
        const translations = {
            fr: {
                nav_carte: "Carte Interactive",
                nav_groupage: "Groupage & Fret",
                nav_coop: "Réseau Coopératives",
                nav_esg: "Indicateurs ESG",

                role: "Chef Logistique Nationale",
                saisie_vocale: "Saisie Vocale Darija",
                hub_national: "Réseau National",
                ai_agro: "AI Agro-NLU",
                mic_active: "Micro actif • Écoute en Darija",
                mic_paused: "Micro désactivé • En pause",
                live_rec: "LIVE REC",
                dialect: "Dialecte Souss (Chtouka, Massa, Taroudant)",
                saisie_edition: "Saisie ou Édition de la Transcription",
                precision: "Précision 98.4%",
                analyze_nlu: "Analyser l'Entrée NLU",
                res_extraction: "Résultat d'Extraction IA",
                res_prod_lbl: "Produit :",
                res_qty_lbl: "Quantité :",
                res_qual_lbl: "Qualité :",
                res_com_lbl: "Commune Matchée :",
                freq_cmds: "Commandes Fréquentes Fellahs",
                one_click_sim: "1-Clic Simulation",
                preset_1: "+ 2t Carotte Massa",
                preset_2: "+ 3.5t Tomate Belfaa",
                preset_3: "+ 1.8t Agrumes Taroudant",
                preset_4: "+ 4t Primeurs Oulad Teima",
                impact: "<span class=\"material-symbols-outlined text-emerald-mid text-lg\">insights</span> Impact National & Climat",
                today: "Aujourd'hui",
                co2_evite: "CO₂ Évité",
                vs_solo: "+18% vs solo",
                tonnage_lbl: "Tonnage",
                lots_cons: "48 lots consolidés",
                camions_opt: "16 camions optimisés",
                marge_fellah: "Marge Fellah",
                gain_net: "Gain Net Moyen",
                marge_value: "420 MAD/expéd.",
                moteur_groupage: "Moteur de Groupage Dynamique",
                pret_consolidation: "Prêt pour consolidation",
                flotte: "Flotte Allouée",
                remplissage: "Remplissage Flotte",
                compiler_lots: "Compiler les Lots Groupés (National)",
                hubs_logistiques: "Hubs Logistiques Nationaux",
                click_recent: "Cliquez pour recentrer la carte",
                trafic_fluide: "R105 Fluide",
                temp: "Temp. +19°C",
                mode_clair: "Mode Clair",
                couloir_express: "Couloir Express Prioritaire",
                route_desc: "Belfaa & Biougra ➔ Hub Aït Melloul ➔ Marché Inezgane",
                tonnage_groupe: "Tonnage Groupé",
                chaine_froid: "Chaîne du Froid",
                temp_garanti: "+8°C Garanti",
                eco_co2: "Économie CO₂",
                depart_flotte: "Départ Flotte",
                esg_title: "Rapport ESG — Durabilité & Carbone",
                esg_subtitle: "Réseau National Logistique Agricole · Exercice 2024",
                co2_emis: "Émissions CO₂ vs transport individuel",
                co2_econ: "CO₂ économisé ce mois",
                consommation: "Consommation carburant groupé",
                ind_env: "Indicateurs Environnementaux",
                trajets_conso: "Trajets consolidés (km épargnés)",
                camions_suppr: "Camions supprimés par groupage",
                taux_rempl: "Taux remplissage moyen camion",
                pertes_post: "Pertes post-récolte réduites",
                social: "Social",
                agri_connect: "Agriculteurs connectés",
                revenu_moyen: "Revenu moyen fellah",
                communes_couv: "Communes couvertes",
                gouvernance: "Gouvernance",
                coop_part: "Coopératives partenaires",
                tracabilite: "Traçabilité numérique",
                audit_esg: "Audits ESG / an",
                trimestriel: "Trimestriel"
            },
            en: {
                nav_carte: "Interactive Map",
                nav_groupage: "Consolidation & Freight",
                nav_coop: "Cooperatives Network",
                nav_esg: "ESG Indicators",

                role: "National Logistics Lead",
                saisie_vocale: "Darija Voice Input",
                hub_national: "National Network",
                ai_agro: "AI Agro-NLU",
                mic_active: "Mic active • Listening in Darija",
                mic_paused: "Mic disabled • Paused",
                live_rec: "LIVE REC",
                dialect: "Souss Dialect (Chtouka, Massa, Taroudant)",
                saisie_edition: "Transcription Input or Edit",
                precision: "98.4% Accuracy",
                analyze_nlu: "Analyze NLU Input",
                res_extraction: "AI Extraction Result",
                res_prod_lbl: "Product :",
                res_qty_lbl: "Quantity :",
                res_qual_lbl: "Quality :",
                res_com_lbl: "Matched Commune :",
                freq_cmds: "Frequent Farmer Commands",
                one_click_sim: "1-Click Simulation",
                preset_1: "+ 2t Carrot Massa",
                preset_2: "+ 3.5t Tomato Belfaa",
                preset_3: "+ 1.8t Citrus Taroudant",
                preset_4: "+ 4t Primeurs Oulad Teima",
                impact: "<span class=\"material-symbols-outlined text-emerald-mid text-lg\">insights</span> National Impact & Climate",
                today: "Today",
                co2_evite: "CO₂ Saved",
                vs_solo: "+18% vs solo",
                tonnage_lbl: "Tonnage",
                lots_cons: "48 consolidated lots",
                camions_opt: "16 optimized trucks",
                marge_fellah: "Farmer Margin",
                gain_net: "Average Net Gain",
                marge_value: "420 MAD/shipment",
                moteur_groupage: "Dynamic Grouping Engine",
                pret_consolidation: "Ready for consolidation",
                flotte: "Allocated Fleet",
                remplissage: "Fleet Fill Rate",
                compiler_lots: "Compile Grouped Lots (National)",
                hubs_logistiques: "National Logistics Hubs",
                click_recent: "Click to recenter map",
                trafic_fluide: "R105 Fluid",
                temp: "Temp. +19°C",
                mode_clair: "Light Mode",
                couloir_express: "Priority Express Corridor",
                route_desc: "Belfaa & Biougra ➔ Aït Melloul Hub ➔ Inezgane Market",
                tonnage_groupe: "Grouped Tonnage",
                chaine_froid: "Cold Chain",
                temp_garanti: "+8°C Guaranteed",
                eco_co2: "CO₂ Savings",
                depart_flotte: "Fleet Departure",
                esg_title: "ESG Report — Sustainability & Carbon",
                esg_subtitle: "National Agri-Logistics Network · 2024 Fiscal Year",
                co2_emis: "CO₂ emissions vs individual transport",
                co2_econ: "CO₂ saved this month",
                consommation: "Grouped fuel consumption",
                ind_env: "Environmental Indicators",
                trajets_conso: "Consolidated routes (km saved)",
                camions_suppr: "Trucks removed via grouping",
                taux_rempl: "Average truck fill rate",
                pertes_post: "Post-harvest losses reduced",
                social: "Social",
                agri_connect: "Connected Farmers",
                revenu_moyen: "Average farmer income",
                communes_couv: "Covered Communes",
                gouvernance: "Governance",
                coop_part: "Partner cooperatives",
                tracabilite: "Digital traceability",
                audit_esg: "ESG Audits / year",
                trimestriel: "Quarterly"
            },
            ar: {
                nav_carte: "الخريطة التفاعلية",
                nav_groupage: "التجميع والشحن",
                nav_coop: "شبكة التعاونيات",
                nav_esg: "مؤشرات الحوكمة",

                role: "مدير اللوجستيات الوطنية",
                saisie_vocale: "إدخال صوتي بالدارجة",
                hub_national: "الشبكة الوطنية",
                ai_agro: "الذكاء الاصطناعي الزراعي",
                mic_active: "الميكروفون نشط • يستمع",
                mic_paused: "الميكروفون معطل • متوقف",
                live_rec: "تسجيل حي",
                dialect: "لهجة سوس (شتوكة، ماسة، تارودانت)",
                saisie_edition: "إدخال أو تعديل النص",
                precision: "دقة 98.4%",
                analyze_nlu: "تحليل الإدخال",
                res_extraction: "نتيجة استخراج الذكاء الاصطناعي",
                res_prod_lbl: "المنتج :",
                res_qty_lbl: "الكمية :",
                res_qual_lbl: "الجودة :",
                res_com_lbl: "الجماعة المطابقة :",
                freq_cmds: "أوامر الفلاحين الشائعة",
                one_click_sim: "محاكاة بنقرة واحدة",
                preset_1: "+ 2t جزر ماسة",
                preset_2: "+ 3.5t طماطم بلفاع",
                preset_3: "+ 1.8t حوامض تارودانت",
                preset_4: "+ 4t بواكير أولاد تايمة",
                impact: "<span class=\"material-symbols-outlined text-emerald-mid text-lg\">insights</span> التأثير الوطني والمناخ",
                today: "اليوم",
                co2_evite: "ثاني أكسيد الكربون المتجنب",
                vs_solo: "18%+ مقارنة بالفردي",
                tonnage_lbl: "الوزن الإجمالي",
                lots_cons: "48 حصة مجمعة",
                camions_opt: "16 شاحنة محسنة",
                marge_fellah: "هامش الفلاح",
                gain_net: "متوسط الربح الصافي",
                marge_value: "420 درهم/شحنة",
                moteur_groupage: "محرك التجميع الديناميكي",
                pret_consolidation: "جاهز للتوحيد",
                flotte: "الأسطول المخصص",
                remplissage: "معدل ملء الأسطول",
                compiler_lots: "تجميع الحصص (وطني)",
                hubs_logistiques: "المراكز اللوجستية الوطنية",
                click_recent: "انقر لتوسيط الخريطة",
                trafic_fluide: "طريق 105 سلس",
                temp: "الحرارة +19°C",
                mode_clair: "وضع فاتح",
                couloir_express: "الممر السريع ذو الأولوية",
                route_desc: "بلفاع وبيوكرى ➔ مركز آيت ملول ➔ سوق إنزكان",
                tonnage_groupe: "الوزن المجمع",
                chaine_froid: "سلسلة التبريد",
                temp_garanti: "+8°C مضمونة",
                eco_co2: "توفير ثاني أكسيد الكربون",
                depart_flotte: "انطلاق الأسطول",
                esg_title: "تقرير الحوكمة — الاستدامة والكربون",
                esg_subtitle: "الشبكة الوطنية للوجستيات الزراعية · سنة 2024",
                co2_emis: "انبعاثات CO₂ مقارنة بالنقل الفردي",
                co2_econ: "CO₂ الموفر هذا الشهر",
                consommation: "استهلاك الوقود المجمع",
                ind_env: "المؤشرات البيئية",
                trajets_conso: "المسارات المجمعة (كم الموفرة)",
                camions_suppr: "الشاحنات الملغاة بفضل التجميع",
                taux_rempl: "متوسط معدل ملء الشاحنة",
                pertes_post: "انخفاض خسائر ما بعد الحصاد",
                social: "اجتماعي",
                agri_connect: "الفلاحين المتصلين",
                revenu_moyen: "متوسط دخل الفلاح",
                communes_couv: "الجماعات المغطاة",
                gouvernance: "حوكمة",
                coop_part: "التعاونيات الشريكة",
                tracabilite: "التتبع الرقمي",
                audit_esg: "تدقيق الحوكمة / سنة",
                trimestriel: "فصلي"
            }
        };

        // Current active language global
        let currentLang = 'fr';

        function setLanguage(lang) {
            currentLang = lang;
            
            // Translate elements
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (translations[lang] && translations[lang][key]) {
                    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                        el.placeholder = translations[lang][key];
                    } else {
                        el.innerHTML = translations[lang][key];
                    }
                }
            });

            // Document direction and font family updates for Arabic RTL support
            if (lang === 'ar') {
                document.documentElement.dir = 'rtl';
                document.body.classList.add('font-arabic');
                document.body.classList.remove('font-sans');
            } else {
                document.documentElement.dir = 'ltr';
                document.body.classList.remove('font-arabic');
                document.body.classList.add('font-sans');
            }
        }
    </script>

    <style>
        /* Tab active/inactive base styles for the main header nav
           Active: emerald-deep brand colour (#065f46) with white text + shadow
           Inactive: transparent with slate-600 text, white bg on hover */
        .map-tab-active  {
            background: #065f46;
            color: #ffffff;
            box-shadow: 0 1px 4px rgba(6,95,70,.30);
        }
        .map-tab-inactive {
            background: transparent;
            color: #475569;
        }
        .map-tab-inactive:hover {
            background: #ffffff;
            color: #0f172a;
        }
        /* Icon inherits the button text colour in both states */
        .map-tab-active  .nav-tab-icon,
        .map-tab-inactive .nav-tab-icon { color: inherit; }

        /* ESG modal entrance animation */
        @keyframes esgSlideUp {
            from { opacity: 0; transform: translateY(24px) scale(.97); }
            to   { opacity: 1; transform: translateY(0)   scale(1);    }
        }
    </style>
    <script type="module" src="transcription/ui.js"></script>
</body>
</html>
