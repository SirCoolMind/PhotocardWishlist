<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
$initialState = get_state_payload();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($initialState['app_title']) ?></title>
    <script>
        (function() {
            try {
                var saved = localStorage.getItem('pc_theme') || 'light';
                if (saved === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;600;700&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Sora"', 'system-ui', 'sans-serif'],
                        mono: ['"IBM Plex Mono"', 'monospace'],
                    }
                }
            }
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }

        :root {
            --bg-canvas: #f7f5f0;
            --bg-header: rgba(247, 245, 240, 0.94);
            --bg-cluster: #ffffff;
            --bg-control: #edeae2;
            --bg-control-hover: #e2ded3;
            --bg-sleeve: #ffffff;
            --bg-dock: rgba(255, 255, 255, 0.96);
            --bg-modal: #ffffff;
            --bg-backdrop: rgba(23, 22, 20, 0.55);
            --border-subtle: #e2ded4;
            --border-strong: #cbc5b7;
            --text-primary: #181614;
            --text-secondary: #4a463f;
            --text-muted: #6b655b;
            --shadow-panel: 0 10px 24px -6px rgba(24, 22, 20, 0.08), 0 2px 6px -1px rgba(24, 22, 20, 0.05);
            --shadow-cluster: 0 2px 6px -1px rgba(24, 22, 20, 0.04), 0 1px 2px 0 rgba(24, 22, 20, 0.03);
            --scrollbar-track: #f0ede6;
            --scrollbar-thumb: #cbc5b7;
            --scrollbar-hover: #aca493;
        }

        html.dark {
            --bg-canvas: #080a10;
            --bg-header: rgba(8, 10, 16, 0.94);
            --bg-cluster: #0e121c;
            --bg-control: #151b29;
            --bg-control-hover: #1e2638;
            --bg-sleeve: #ffffff;
            --bg-dock: rgba(14, 18, 28, 0.96);
            --bg-modal: #131824;
            --bg-backdrop: rgba(4, 6, 12, 0.80);
            --border-subtle: #1e2638;
            --border-strong: #2e3a52;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --shadow-panel: 0 14px 30px -8px rgba(4, 6, 12, 0.65), 0 4px 10px -3px rgba(4, 6, 12, 0.45);
            --shadow-cluster: 0 4px 12px -2px rgba(4, 6, 12, 0.35);
            --scrollbar-track: #080a10;
            --scrollbar-thumb: #252f46;
            --scrollbar-hover: #384666;
        }

        body {
            font-family: 'Sora', system-ui, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-primary);
            caret-color: #7c3aed;
            transition: background-color 180ms ease, color 180ms ease;
        }

        ::selection {
            background-color: #7c3aed;
            color: #ffffff;
        }

        .tabular-nums { font-variant-numeric: tabular-nums; }
        .card-aspect { aspect-ratio: 210 / 339; }

        /* Standard locked pixel card dimensions per density (S / M / L) */
        .density-sm { --card-w: 124px; --card-h: 200px; }
        .density-md { --card-w: 150px; --card-h: 242px; }
        .density-lg { --card-w: 192px; --card-h: 310px; }
        @media (max-width: 639px) {
            .density-sm { --card-w: 112px; --card-h: 181px; }
            .density-md { --card-w: 148px; --card-h: 239px; }
            .density-lg { --card-w: 168px; --card-h: 271px; }
        }
        .standard-card-box {
            width: var(--card-w);
            height: var(--card-h);
            flex: 0 0 var(--card-w);
        }
        .card-img-clean {
            clip-path: inset(3px);
        }

        .surface-header {
            background-color: var(--bg-header);
            border-bottom: 1px solid var(--border-subtle);
        }
        .surface-cluster {
            background-color: var(--bg-cluster);
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-cluster);
        }
        .surface-cluster:hover {
            border-color: var(--border-strong);
        }
        .surface-sleeve {
            background-color: var(--bg-sleeve);
            border: 1px solid var(--border-subtle);
            transition: background-color 180ms ease, border-color 180ms ease;
        }
        .surface-control {
            background-color: var(--bg-control);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
        }
        .surface-control:hover {
            background-color: var(--bg-control-hover);
            color: var(--text-primary);
        }
        .surface-dock {
            background-color: var(--bg-dock);
            border-top: 1px solid var(--border-subtle);
        }
        .surface-modal {
            background-color: var(--bg-modal);
            border: 1px solid var(--border-strong);
            box-shadow: var(--shadow-panel);
        }
        .surface-backdrop {
            background-color: var(--bg-backdrop);
        }
        .surface-input {
            background-color: var(--bg-control);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
        }
        .surface-input::placeholder {
            color: var(--text-muted);
        }
        .text-theme-primary { color: var(--text-primary); }
        .text-theme-secondary { color: var(--text-secondary); }
        .text-theme-muted { color: var(--text-muted); }
        .border-theme-subtle { border-color: var(--border-subtle); }

        .bento-board {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-start;
        }
        .bento-cluster-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .elevated-panel {
            box-shadow: var(--shadow-panel);
        }
        .card-dock-transition {
            transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), opacity 180ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        :focus-visible {
            outline: 2px solid #7c3aed;
            outline-offset: 2px;
        }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--scrollbar-track); }
        ::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--scrollbar-hover); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen antialiased overflow-x-hidden"
      x-data="photocardApp(<?= htmlspecialchars(json_encode($initialState), ENT_QUOTES, 'UTF-8') ?>)"
      :class="'density-' + cardSize"
      @resize.window="viewportWidth = window.innerWidth"
      @paste.window="handleGlobalPaste($event)"
      @keydown.escape.window="closeModals()">

    <!-- Top Compact 2-Tier Sticky Header -->
    <header class="sticky top-0 z-30 surface-header backdrop-blur-md">
        <div class="max-w-[1720px] mx-auto px-3 sm:px-5 py-2.5 space-y-2">
            <!-- Tier 1: Brand + Primary Actions + System & View Menu -->
            <div class="flex items-center justify-between gap-2 min-w-0">
                <!-- Left: Brand & Mode Badge -->
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-violet-600 flex items-center justify-center font-extrabold text-xs sm:text-sm text-white elevated-panel shrink-0">
                        W
                    </div>
                    <h1 class="font-bold text-xs sm:text-base tracking-tight text-theme-primary truncate" x-text="appTitle"></h1>
                    <span x-show="isAdmin"
                          class="hidden sm:inline-flex text-[10px] font-semibold px-1.5 py-0.5 rounded-md bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shrink-0">
                        Admin
                    </span>
                    <span x-show="!isAdmin"
                          class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md surface-control shrink-0">
                        Read-Only
                    </span>
                </div>

                <!-- Right: Primary Actions + Unified System & View Popover -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <template x-if="isAdmin">
                        <div class="flex items-center gap-1.5">
                            <button @click="openAddModal()"
                                    class="px-2.5 sm:px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-semibold text-xs flex items-center gap-1 elevated-panel transition whitespace-nowrap">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                <span class="sm:hidden">Add</span>
                                <span class="hidden sm:inline">Add Card</span>
                            </button>

                            <button @click="toggleBatchMode()"
                                    :class="batchMode ? 'bg-amber-400 text-amber-950 border-amber-400 font-bold' : 'surface-control font-medium'"
                                    class="px-2 sm:px-2.5 py-1.5 rounded-lg border text-xs flex items-center gap-1 transition whitespace-nowrap">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span x-text="batchMode ? 'Batch (' + selectedIds.length + ')' : 'Batch'"></span>
                            </button>
                        </div>
                    </template>

                    <!-- System & View Menu Popover -->
                    <div class="relative" @click.outside="systemMenuOpen = false">
                        <button @click="systemMenuOpen = !systemMenuOpen; eraMenuOpen = false"
                                :class="systemMenuOpen ? 'bg-violet-600 text-white border-violet-600' : 'surface-control'"
                                class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap"
                                aria-label="Open View and System Menu"
                                :aria-expanded="systemMenuOpen">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
                            <span class="hidden sm:inline">System &amp; View</span>
                            <svg class="w-3 h-3 shrink-0 transition-transform duration-150" :class="systemMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>

                        <!-- Popover Panel -->
                        <div x-show="systemMenuOpen" x-cloak
                             class="absolute right-0 mt-2 w-64 rounded-xl surface-modal p-3 z-50 space-y-3">
                            <!-- Appearance -->
                            <div>
                                <div class="text-[11px] font-semibold text-theme-secondary mb-1.5">Appearance</div>
                                <div class="grid grid-cols-2 gap-1 p-0.5 rounded-lg surface-control">
                                    <button @click="if (theme !== 'light') toggleTheme()"
                                            :class="theme === 'light' ? 'bg-violet-600 text-white font-bold' : 'text-theme-secondary'"
                                            class="py-1.5 px-2 rounded-md text-xs flex items-center justify-center gap-1.5 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                                        <span>Light</span>
                                    </button>
                                    <button @click="if (theme !== 'dark') toggleTheme()"
                                            :class="theme === 'dark' ? 'bg-violet-600 text-white font-bold' : 'text-theme-secondary'"
                                            class="py-1.5 px-2 rounded-md text-xs flex items-center justify-center gap-1.5 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                                        <span>Dark</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Layout Mode -->
                            <div>
                                <div class="text-[11px] font-semibold text-theme-secondary mb-1.5">Board Layout</div>
                                <div class="grid grid-cols-2 gap-1 p-0.5 rounded-lg surface-control">
                                    <button @click="groupByEra = true"
                                            :class="groupByEra ? 'bg-violet-600 text-white font-bold' : 'text-theme-secondary'"
                                            class="py-1.5 px-2 rounded-md text-xs transition">
                                        Bento Eras
                                    </button>
                                    <button @click="groupByEra = false"
                                            :class="!groupByEra ? 'bg-violet-600 text-white font-bold' : 'text-theme-secondary'"
                                            class="py-1.5 px-2 rounded-md text-xs transition">
                                        Flat Grid
                                    </button>
                                </div>
                            </div>

                            <!-- Card Density -->
                            <div>
                                <div class="text-[11px] font-semibold text-theme-secondary mb-1.5">Card Density</div>
                                <div class="grid grid-cols-3 gap-1 p-0.5 rounded-lg surface-control font-mono">
                                    <button @click="cardSize = 'sm'" :class="cardSize === 'sm' ? 'bg-violet-600 text-white font-bold' : 'text-theme-secondary'" class="py-1 rounded-md text-xs transition">S</button>
                                    <button @click="cardSize = 'md'" :class="cardSize === 'md' ? 'bg-violet-600 text-white font-bold' : 'text-theme-secondary'" class="py-1 rounded-md text-xs transition">M</button>
                                    <button @click="cardSize = 'lg'" :class="cardSize === 'lg' ? 'bg-violet-600 text-white font-bold' : 'text-theme-secondary'" class="py-1 rounded-md text-xs transition">L</button>
                                </div>
                            </div>

                            <!-- System Tools -->
                            <div class="pt-2 border-t border-theme-subtle space-y-1.5">
                                <button x-show="isAdmin"
                                        @click="syncModalOpen = true; systemMenuOpen = false"
                                        class="w-full px-2.5 py-2 rounded-lg surface-control text-xs font-semibold flex items-center justify-between transition">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
                                        <span>DB Sync &amp; Backup</span>
                                    </span>
                                    <span class="text-[10px] font-mono text-theme-muted" x-text="dbSizeKb + ' KB'"></span>
                                </button>

                                <button @click="handleLockButton(); systemMenuOpen = false"
                                        class="w-full px-2.5 py-2 rounded-lg surface-control text-xs font-semibold flex items-center justify-between transition">
                                    <span class="flex items-center gap-2">
                                        <template x-if="isAdmin">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                        </template>
                                        <template x-if="!isAdmin">
                                            <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                        </template>
                                        <span x-text="isAdmin ? 'Switch to Public View' : 'Unlock Admin Mode'"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tier 2: Status Tabs + Search + Era Popover Picker -->
            <div class="pt-2 border-t border-theme-subtle flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2 min-w-0">
                <!-- Left: 5 Status Tabs (Scrollable inside mobile viewport without stretching page) -->
                <nav class="flex items-center gap-1 overflow-x-auto no-scrollbar min-w-0 max-w-full" aria-label="Photocard Status Tabs">
                    <template x-for="tab in statusTabs" :key="tab.key">
                        <button @click="activeStatus = tab.key; activeCardId = null"
                                :class="activeStatus === tab.key ? tab.activeClass : 'surface-control'"
                                class="px-2.5 sm:px-3 py-1.5 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition whitespace-nowrap shrink-0">
                            <span class="w-2 h-2 rounded-full shrink-0" :class="tab.dotClass"></span>
                            <span x-text="tab.label"></span>
                            <span class="px-1.5 py-0.5 rounded text-[11px] font-mono font-bold tabular-nums bg-black/10 dark:bg-black/35"
                                  x-text="counts[tab.key] ?? 0"></span>
                        </button>
                    </template>
                </nav>

                <!-- Right: Search Input + Era Popover Picker (Side-by-side on mobile & desktop) -->
                <div class="flex items-center gap-1.5 w-full lg:w-auto min-w-0">
                    <!-- Search Input -->
                    <div class="relative flex-1 lg:w-52 min-w-0">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Filter era or POB..."
                               aria-label="Filter photocards by era, POB, or note"
                               class="w-full pl-7 pr-6 py-1.5 rounded-lg surface-input text-xs focus:outline-none focus:border-violet-500">
                        <svg class="w-3.5 h-3.5 text-theme-muted absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <button x-show="searchQuery !== ''" @click="searchQuery = ''" aria-label="Clear search" class="absolute right-2 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-primary">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Era Popover Picker -->
                    <div class="relative shrink-0" @click.outside="eraMenuOpen = false">
                        <button @click="eraMenuOpen = !eraMenuOpen; systemMenuOpen = false"
                                :class="activeEra !== 'ALL' || eraMenuOpen ? 'bg-violet-600 text-white border-violet-600 font-bold' : 'surface-control font-semibold'"
                                class="px-2.5 sm:px-3 py-1.5 rounded-lg border text-xs flex items-center gap-1.5 transition whitespace-nowrap"
                                aria-label="Filter by Era"
                                :aria-expanded="eraMenuOpen">
                            <span class="truncate max-w-[115px] sm:max-w-[160px]"
                                  x-text="activeEra === 'ALL' ? 'Era: All (' + availableErasForCurrentStatus.length + ')' : 'Era: ' + activeEra"></span>
                            <svg class="w-3 h-3 shrink-0 transition-transform duration-150" :class="eraMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>

                        <!-- 2-Column Era Picker Popover -->
                        <div x-show="eraMenuOpen" x-cloak
                             class="absolute right-0 mt-2 w-72 sm:w-80 max-h-80 overflow-y-auto rounded-xl surface-modal p-2.5 z-50">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-theme-subtle">
                                <span class="text-[11px] font-bold text-theme-secondary">Select Era</span>
                                <button @click="activeEra = 'ALL'; eraMenuOpen = false"
                                        :class="activeEra === 'ALL' ? 'text-violet-600 dark:text-violet-300 font-bold' : 'text-theme-muted hover:text-theme-primary'"
                                        class="text-[11px] font-mono">
                                    Show All (<span x-text="statusFilteredCards.length"></span>)
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5">
                                <template x-for="eraItem in availableErasForCurrentStatus" :key="eraItem.name">
                                    <button @click="activeEra = (activeEra === eraItem.name ? 'ALL' : eraItem.name); eraMenuOpen = false"
                                            :class="activeEra === eraItem.name ? 'bg-violet-600 text-white font-bold border-violet-600' : 'surface-control'"
                                            class="px-2 py-1.5 rounded-lg border text-[11px] flex items-center justify-between gap-1.5 transition text-left min-w-0">
                                        <span class="truncate" x-text="eraItem.name" :title="eraItem.name"></span>
                                        <span class="font-mono text-[10px] tabular-nums px-1.5 py-0.5 rounded bg-black/10 dark:bg-black/35 shrink-0" x-text="eraItem.count"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Reset Pill when Era is Filtered -->
                    <button x-show="activeEra !== 'ALL'" x-cloak
                            @click="activeEra = 'ALL'"
                            class="px-2 py-1.5 rounded-lg surface-control text-xs font-semibold shrink-0"
                            title="Clear Era Filter"
                            aria-label="Clear Era Filter">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Board -->
    <main class="max-w-[1720px] mx-auto px-3 sm:px-5 py-4 pb-24" @click="activeCardId = null">
        <!-- Empty State -->
        <div x-show="filteredCards.length === 0" x-cloak
             class="my-12 max-w-md mx-auto text-center p-7 rounded-2xl surface-cluster elevated-panel">
            <h2 class="text-base font-bold text-theme-primary">No photocards match this filter</h2>
            <p class="text-xs text-theme-secondary mt-1.5">Switch status tabs above, clear the Era filter, or press <kbd class="px-1.5 py-0.5 font-mono text-[11px] surface-control rounded">Ctrl+V</kbd> to paste a screenshot.</p>
            <div class="mt-4 flex justify-center gap-2">
                <button x-show="activeEra !== 'ALL' || searchQuery !== ''"
                        @click="activeEra = 'ALL'; searchQuery = ''"
                        class="px-3.5 py-1.5 rounded-lg surface-control text-xs font-semibold">
                    Reset Filters
                </button>
                <button x-show="isAdmin" @click="openAddModal()"
                        class="px-3.5 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-semibold">
                    Add Photocard
                </button>
            </div>
        </div>

        <!-- MODE A: Side-by-Side Bento Era Clusters (Dense Row Packing with Locked Standard Card Size) -->
        <template x-if="groupByEra && filteredCards.length > 0">
            <div class="bento-board">
                <template x-for="group in groupedCards" :key="group.era">
                    <section class="surface-cluster rounded-xl p-2.5 flex flex-col justify-between transition w-fit max-w-full min-w-0">
                        <!-- Compact Cluster Header (w-0 min-w-full prevents header title from stretching 1-card clusters wider than --card-w) -->
                        <div class="flex items-center justify-between gap-1.5 mb-2 px-0.5 w-0 min-w-full">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <h2 class="text-xs font-bold text-theme-primary truncate" x-text="group.era" :title="group.era"></h2>
                                <span class="px-1.5 py-0.2 text-[10px] font-mono font-bold tabular-nums rounded bg-violet-500/15 text-violet-700 dark:text-violet-200 border border-violet-500/30 shrink-0"
                                      x-text="group.items.length"></span>
                            </div>
                            <button x-show="isAdmin"
                                    @click.stop="openAddModal(group.era)"
                                    class="w-5 h-5 rounded-md surface-control hover:bg-violet-600 hover:text-white flex items-center justify-center shrink-0 transition"
                                    :title="'Add photocard to ' + group.era"
                                    :aria-label="'Add photocard to ' + group.era">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            </button>
                        </div>

                        <!-- Cluster Internal Card Grid -->
                        <div class="bento-cluster-grid">
                            <template x-for="card in group.items" :key="card.id">
                                <article class="group relative rounded-lg overflow-hidden surface-sleeve standard-card-box transition select-none"
                                         data-sleeve
                                         :class="isSelected(card.id) ? 'ring-2 ring-amber-400' : 'hover:border-violet-500'"
                                         @click.stop="handleCardClick(card)">
                                    <div class="relative w-full h-full overflow-hidden flex items-center justify-center">
                                        <!-- Ambient Blurred Picture Background (activates when photo edges have multi-color variance) -->
                                        <img :src="card.image_path"
                                             alt=""
                                             aria-hidden="true"
                                             data-ambient
                                             class="absolute inset-0 w-full h-full object-cover scale-125 blur-xl opacity-0 transition-opacity duration-200 pointer-events-none">

                                        <!-- Foreground Photocard Image (samples edge background color on load) -->
                                        <img :src="card.image_path"
                                             :alt="card.era + (card.title ? ' - ' + card.title : '')"
                                             loading="lazy"
                                             @load="matchSleeveBg($event)"
                                             class="card-img-clean relative z-[1] max-w-full max-h-full w-auto h-auto object-contain transition duration-200 group-hover:scale-[1.02]">

                                        <!-- Status Badge when viewing All -->
                                        <span x-show="activeStatus === 'all'"
                                              class="z-10 absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider shadow"
                                              :class="statusBadgeClass(card.status)"
                                              x-text="statusShortLabel(card.status)"></span>

                                        <!-- Price Tag -->
                                        <span x-show="card.price !== null && card.price !== ''"
                                              class="z-10 absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold tabular-nums bg-emerald-950/90 text-emerald-200 border border-emerald-700/50"
                                              x-text="currency + ' ' + Number(card.price).toFixed(0)"></span>

                                        <!-- Subtle Bottom Caption for POB Title / Note (when not hovered) -->
                                        <div x-show="card.title || (card.notes && !card.notes.startsWith('Sheet cell '))"
                                             class="z-10 absolute inset-x-0 bottom-0 surface-dock px-2 py-1 pointer-events-none group-hover:opacity-0 transition-opacity">
                                            <p x-show="card.title" class="text-[10px] font-semibold text-violet-700 dark:text-violet-200 truncate" x-text="card.title"></p>
                                            <p x-show="!card.title && card.notes && !card.notes.startsWith('Sheet cell ')"
                                               class="text-[10px] text-theme-secondary truncate"
                                               x-text="card.notes"></p>
                                        </div>

                                        <!-- Batch Selection Overlay -->
                                        <div x-show="batchMode"
                                             class="z-10 absolute inset-0 bg-black/25 flex items-start justify-end p-2">
                                            <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition"
                                                 :class="isSelected(card.id) ? 'bg-amber-400 border-amber-400 text-amber-950' : 'bg-black/65 border-white text-white'">
                                                <svg x-show="isSelected(card.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </div>
                                        </div>

                                        <!-- Top-Right Quick Utility Icons (Zoom / Edit / Delete) -->
                                        <div x-show="!batchMode"
                                             :class="activeCardId === card.id ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                                             class="z-10 absolute top-1.5 right-1.5 flex items-center gap-1 transition-opacity">
                                            <button @click.stop="openLightbox(card)"
                                                    class="p-1 rounded-md surface-modal text-theme-primary hover:bg-violet-600 hover:text-white"
                                                    title="Zoom Image"
                                                    aria-label="Zoom Image">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                                            </button>
                                            <template x-if="isAdmin">
                                                <div class="flex items-center gap-1">
                                                    <button @click.stop="openEditModal(card)"
                                                            class="p-1 rounded-md surface-modal text-theme-primary hover:bg-violet-600 hover:text-white"
                                                            title="Edit Details"
                                                            aria-label="Edit Details">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                                                    </button>
                                                    <button @click.stop="deleteCard(card)"
                                                            class="p-1 rounded-md surface-modal text-theme-primary hover:bg-rose-600 hover:text-white"
                                                            title="Delete Card"
                                                            aria-label="Delete Card">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Sleek Bottom Hover/Tap 1-Click Status Dock -->
                                        <div x-show="isAdmin && !batchMode"
                                             :class="activeCardId === card.id ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100'"
                                             class="z-10 card-dock-transition absolute inset-x-0 bottom-0 surface-dock p-1.5">
                                            <div class="grid grid-cols-3 gap-1">
                                                <template x-for="st in quickMoveTargets(card.status)" :key="st.key">
                                                    <button @click.stop="quickMove(card, st.key)"
                                                            :class="st.btnClass"
                                                            class="py-1 px-1 rounded text-[10px] font-bold text-center truncate transition"
                                                            :title="'Move to ' + st.label">
                                                        <span x-text="st.shortLabel"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>
                    </section>
                </template>
            </div>
        </template>

        <!-- MODE B: Continuous Dense Flat Grid -->
        <template x-if="!groupByEra && filteredCards.length > 0">
            <div class="bento-board">
                <template x-for="card in filteredCards" :key="card.id">
                    <article class="group relative rounded-lg overflow-hidden surface-sleeve standard-card-box transition select-none"
                             data-sleeve
                             :class="isSelected(card.id) ? 'ring-2 ring-amber-400' : 'hover:border-violet-500'"
                             @click.stop="handleCardClick(card)">
                        <div class="relative w-full h-full overflow-hidden flex items-center justify-center">
                            <img :src="card.image_path"
                                 alt=""
                                 aria-hidden="true"
                                 data-ambient
                                 class="absolute inset-0 w-full h-full object-cover scale-125 blur-xl opacity-0 transition-opacity duration-200 pointer-events-none">

                            <img :src="card.image_path"
                                 :alt="card.era"
                                 loading="lazy"
                                 @load="matchSleeveBg($event)"
                                 class="card-img-clean relative z-[1] max-w-full max-h-full w-auto h-auto object-contain transition duration-200 group-hover:scale-[1.02]">

                            <!-- Top-left Era Badge in Flat Mode -->
                            <span class="z-10 absolute top-1.5 left-1.5 max-w-[70%] truncate px-1.5 py-0.5 rounded text-[10px] font-bold surface-modal text-theme-primary"
                                  x-text="card.era"></span>

                            <!-- Top-Right Quick Utility Icons -->
                            <div x-show="!batchMode"
                                 :class="activeCardId === card.id ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                                 class="z-10 absolute top-1.5 right-1.5 flex items-center gap-1 transition-opacity">
                                <button @click.stop="openLightbox(card)"
                                        class="p-1 rounded-md surface-modal text-theme-primary hover:bg-violet-600 hover:text-white"
                                        title="Zoom Image"
                                        aria-label="Zoom Image">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                                </button>
                                <template x-if="isAdmin">
                                    <div class="flex items-center gap-1">
                                        <button @click.stop="openEditModal(card)"
                                                class="p-1 rounded-md surface-modal text-theme-primary hover:bg-violet-600 hover:text-white"
                                                title="Edit Details"
                                                aria-label="Edit Details">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                                        </button>
                                        <button @click.stop="deleteCard(card)"
                                                class="p-1 rounded-md surface-modal text-theme-primary hover:bg-rose-600 hover:text-white"
                                                title="Delete Card"
                                                aria-label="Delete Card">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <!-- Batch Checkbox -->
                            <div x-show="batchMode"
                                 class="z-10 absolute inset-0 bg-black/25 flex items-start justify-end p-2">
                                <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition"
                                     :class="isSelected(card.id) ? 'bg-amber-400 border-amber-400 text-amber-950' : 'bg-black/65 border-white text-white'">
                                    <svg x-show="isSelected(card.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </div>
                            </div>

                            <!-- Bottom Hover/Tap Status Dock -->
                            <div x-show="isAdmin && !batchMode"
                                 :class="activeCardId === card.id ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100'"
                                 class="z-10 card-dock-transition absolute inset-x-0 bottom-0 surface-dock p-1.5">
                                <div class="grid grid-cols-3 gap-1">
                                    <template x-for="st in quickMoveTargets(card.status)" :key="st.key">
                                        <button @click.stop="quickMove(card, st.key)"
                                                :class="st.btnClass"
                                                class="py-1 px-1 rounded text-[10px] font-bold text-center truncate transition"
                                                :title="'Move to ' + st.label">
                                            <span x-text="st.shortLabel"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </template>
    </main>

    <!-- Floating Batch Action Bar -->
    <div x-show="batchMode" x-cloak
         class="fixed bottom-5 left-1/2 -translate-x-1/2 z-40 surface-modal backdrop-blur-md rounded-2xl px-4 py-2.5 flex flex-wrap items-center gap-2 max-w-[95vw]">
        <span class="text-xs font-mono font-bold tabular-nums text-amber-600 dark:text-amber-300 mr-1">
            <span x-text="selectedIds.length"></span> selected
        </span>
        <button @click="selectAllVisible()" class="px-2.5 py-1 rounded-lg surface-control text-xs font-medium">
            Select Shown
        </button>
        <div class="h-4 w-px bg-black/15 dark:bg-white/15"></div>
        <button @click="batchMove('uncollected')" class="px-2.5 py-1 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold transition">
            Wishlist
        </button>
        <button @click="batchMove('pending')" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition">
            Pending
        </button>
        <button @click="batchMove('collected')" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">
            Collected
        </button>
        <button @click="batchMove('no_need')" class="px-2.5 py-1 rounded-lg bg-amber-400 hover:bg-amber-300 text-amber-950 text-xs font-bold transition">
            No Need
        </button>
        <div class="h-4 w-px bg-black/15 dark:bg-white/15"></div>
        <button @click="batchDelete()" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition">
            Delete
        </button>
    </div>

    <!-- Toast Notification with 1-Click Undo -->
    <div x-show="toast.visible" x-cloak
         x-transition.opacity
         class="fixed bottom-5 right-5 z-50 surface-modal rounded-xl px-4 py-2.5 flex items-center gap-3">
        <span class="text-xs sm:text-sm font-medium text-theme-primary" x-text="toast.message"></span>
        <button x-show="toast.undoItems && toast.undoItems.length > 0"
                @click="undoLastMove()"
                class="px-2.5 py-1 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold">
            Undo
        </button>
        <button @click="toast.visible = false" aria-label="Dismiss notification" class="text-theme-muted hover:text-theme-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Add / Edit Photocard Modal -->
    <div x-show="formModal.open" x-cloak
         class="fixed inset-0 z-50 surface-backdrop backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        <div @click.outside="formModal.open = false"
             class="surface-modal rounded-2xl max-w-lg w-full p-5">
            <div class="flex items-center justify-between pb-3 border-b border-theme-subtle">
                <h2 class="text-base font-bold text-theme-primary" x-text="formModal.isEdit ? 'Edit Photocard' : 'Add Photocard'"></h2>
                <button @click="formModal.open = false" aria-label="Close modal" class="text-theme-muted hover:text-theme-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitCardForm()" class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-theme-primary mb-1.5">
                        Photocard Image <span class="text-theme-secondary font-normal">(Upload file, press Ctrl+V, or paste URL)</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <div class="w-24 card-aspect rounded-xl surface-sleeve overflow-hidden flex items-center justify-center shrink-0">
                            <template x-if="formModal.previewUrl">
                                <img :src="formModal.previewUrl" alt="Preview" class="w-full h-full object-contain">
                            </template>
                            <template x-if="!formModal.previewUrl">
                                <span class="text-[11px] text-theme-muted text-center px-2">No image</span>
                            </template>
                        </div>
                        <div class="flex-1 space-y-2">
                            <input type="file" accept="image/*" @change="handleFileSelect($event)"
                                   class="block w-full text-xs text-theme-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-600 file:text-white hover:file:bg-violet-500">
                            <input type="url" x-model="formModal.imageUrl" @input="if(formModal.imageUrl) formModal.previewUrl = formModal.imageUrl"
                                   placeholder="Or paste image URL (https://...)"
                                   class="w-full px-3 py-1.5 rounded-lg surface-input text-xs">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-theme-primary mb-1.5">Status</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                        <template x-for="st in statusTabs.filter(t => t.key !== 'all')" :key="st.key">
                            <button type="button"
                                    @click="formModal.status = st.key"
                                    :class="formModal.status === st.key ? st.activeClass : 'surface-control'"
                                    class="py-2 px-2.5 rounded-xl border text-xs font-bold text-center transition"
                                    x-text="st.label"></button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-theme-primary mb-1">Era / Album / Event *</label>
                    <input type="text" required x-model="formModal.era"
                           placeholder="e.g. Girls, Drama, Whiplash, Lemonade..."
                           class="w-full px-3 py-2 rounded-xl surface-input text-sm focus:outline-none focus:border-violet-500">
                    <div class="mt-2 flex items-center gap-1.5 flex-wrap max-h-24 overflow-y-auto">
                        <span class="text-[11px] text-theme-secondary mr-1">Quick pick:</span>
                        <template x-for="eraItem in eras" :key="eraItem.name">
                            <button type="button"
                                    @click="formModal.era = eraItem.name"
                                    :class="formModal.era === eraItem.name ? 'bg-violet-600 text-white' : 'surface-control'"
                                    class="px-2 py-0.5 rounded-md text-[11px] font-medium transition"
                                    x-text="eraItem.name"></button>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-theme-primary mb-1">POB / Card Label (Optional)</label>
                        <input type="text" x-model="formModal.title"
                               placeholder="e.g. KMS Fancall 2.0, Soundwave"
                               class="w-full px-3 py-1.5 rounded-xl surface-input text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-theme-primary mb-1">Price (<span x-text="currency"></span>, Optional)</label>
                        <input type="number" step="0.01" x-model="formModal.price"
                               placeholder="e.g. 45"
                               class="w-full px-3 py-1.5 rounded-xl surface-input text-xs font-mono tabular-nums">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-theme-primary mb-1">Notes / Seller / Tracking (Optional)</label>
                    <input type="text" x-model="formModal.notes"
                           placeholder="e.g. Batch #4 GOM @winteo_go"
                           class="w-full px-3 py-1.5 rounded-xl surface-input text-xs">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-theme-subtle">
                    <button type="button" @click="formModal.open = false"
                            class="px-4 py-2 rounded-xl surface-control text-xs font-semibold">
                        Cancel
                    </button>
                    <button type="submit" :disabled="formModal.saving"
                            class="px-5 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-xs font-bold text-white elevated-panel">
                        <span x-text="formModal.saving ? 'Saving...' : (formModal.isEdit ? 'Save Changes' : 'Add Photocard')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Database Sync / Portability & Backup Modal -->
    <div x-show="syncModalOpen" x-cloak
         class="fixed inset-0 z-50 surface-backdrop backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        <div @click.outside="syncModalOpen = false"
             class="surface-modal rounded-2xl max-w-lg w-full p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-theme-subtle">
                <div>
                    <h2 class="text-base font-bold text-theme-primary">Database Portability & Server Sync</h2>
                    <p class="text-xs text-theme-secondary font-mono tabular-nums mt-0.5"><span x-text="dbSizeKb + ' KB'"></span> SQLite · <span x-text="counts.all"></span> cards</p>
                </div>
                <button @click="syncModalOpen = false" aria-label="Close modal" class="text-theme-muted hover:text-theme-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-3.5 rounded-xl surface-sleeve space-y-2.5">
                <h3 class="text-sm font-bold text-theme-primary">Export Portable Bundle</h3>
                <p class="text-xs text-theme-secondary">Download a self-contained backup to move between local Laragon and your server in seconds.</p>
                <div class="flex flex-wrap gap-2 pt-1">
                    <a href="api.php?action=export_zip"
                       class="px-3.5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold flex items-center gap-1.5">
                        <span>Download Full Bundle (.zip)</span>
                    </a>
                    <a href="api.php?action=export_sqlite"
                       class="px-3.5 py-2 rounded-xl surface-control text-xs font-semibold flex items-center gap-1.5">
                        <span>Download database.sqlite Only</span>
                    </a>
                </div>
            </div>

            <form @submit.prevent="submitImportBundle()" class="p-3.5 rounded-xl surface-sleeve space-y-3">
                <h3 class="text-sm font-bold text-theme-primary">Import or Sync from Backup</h3>
                <p class="text-xs text-theme-secondary">Upload a <code class="font-mono text-theme-primary">.zip</code> bundle or <code class="font-mono text-theme-primary">.sqlite</code> database file.</p>

                <input type="file" accept=".zip,.sqlite,.sqlite3,.db" required
                       @change="importFile = $event.target.files[0]"
                       class="block w-full text-xs text-theme-secondary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500">

                <div class="flex items-center gap-4 text-xs">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" x-model="importMode" value="replace" class="accent-emerald-600">
                        <span class="text-theme-primary font-medium">Replace All (Exact Mirror)</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" x-model="importMode" value="merge" class="accent-emerald-600">
                        <span class="text-theme-primary font-medium">Merge Cards</span>
                    </label>
                </div>

                <button type="submit" :disabled="importingBundle || !importFile"
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold">
                    <span x-text="importingBundle ? 'Importing & Syncing...' : 'Upload & Sync Now'"></span>
                </button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-3.5 rounded-xl surface-sleeve space-y-2">
                    <h3 class="text-sm font-bold text-theme-primary">Google Sheet Sync</h3>
                    <p class="text-xs text-theme-secondary">Pull newly added cards from the Google Sheet <code>Uncollected</code> tab.</p>
                    <button type="button" @click="syncFromSheet(false)" :disabled="syncingSheet"
                            class="w-full py-1.5 px-3 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-semibold transition">
                        <span x-text="syncingSheet ? 'Syncing Sheet...' : 'Pull New Sheet Cards'"></span>
                    </button>
                </div>

                <form @submit.prevent="changeAdminPin()" class="p-3.5 rounded-xl surface-sleeve space-y-2">
                    <h3 class="text-sm font-bold text-theme-primary">Server Admin PIN</h3>
                    <p class="text-xs text-theme-secondary">Protect write actions when hosted publicly.</p>
                    <div class="flex gap-1.5">
                        <input type="password" x-model="newPinInput" placeholder="New PIN"
                               class="w-full px-2.5 py-1.5 rounded-lg surface-input text-xs">
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-400 hover:bg-amber-300 text-amber-950 text-xs font-bold shrink-0">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin PIN Login Modal -->
    <div x-show="pinModalOpen" x-cloak
         class="fixed inset-0 z-50 surface-backdrop backdrop-blur-sm flex items-center justify-center p-4">
        <form @submit.prevent="submitPinLogin()"
              @click.outside="pinModalOpen = false"
              class="surface-modal rounded-2xl max-w-xs w-full p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-theme-primary">Unlock Admin Mode</h2>
                <button type="button" @click="pinModalOpen = false" aria-label="Close PIN modal" class="text-theme-muted hover:text-theme-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-theme-secondary">Enter your Admin PIN to add, edit, or move photocards.</p>
            <input type="password" x-model="pinLoginInput" autofocus placeholder="PIN"
                   class="w-full px-3 py-2 rounded-xl surface-input text-center font-mono text-base tracking-widest focus:outline-none focus:border-violet-500">
            <button type="submit" class="w-full py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold">
                Unlock
            </button>
        </form>
    </div>

    <!-- Image Lightbox Modal -->
    <div x-show="lightboxCard !== null" x-cloak
         @click="lightboxCard = null"
         class="fixed inset-0 z-50 surface-backdrop backdrop-blur-md flex items-center justify-center p-4">
        <template x-if="lightboxCard">
            <div @click.stop class="max-w-md w-full surface-modal rounded-2xl overflow-hidden">
                <div class="relative surface-sleeve flex items-center justify-center max-h-[72vh]">
                    <img :src="lightboxCard.image_path" :alt="lightboxCard.era" class="max-h-[72vh] w-auto object-contain">
                    <button @click="lightboxCard = null" aria-label="Close Lightbox"
                            class="absolute top-3 right-3 w-8 h-8 rounded-full surface-modal text-theme-primary flex items-center justify-center hover:bg-violet-600 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-4 flex items-center justify-between gap-3">
                    <div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                              :class="statusBadgeClass(lightboxCard.status)"
                              x-text="statusLabel(lightboxCard.status)"></span>
                        <h3 class="text-base font-bold text-theme-primary mt-1" x-text="lightboxCard.era"></h3>
                        <p x-show="lightboxCard.title" class="text-xs text-violet-700 dark:text-violet-300" x-text="lightboxCard.title"></p>
                    </div>
                    <div x-show="isAdmin" class="flex items-center gap-1.5 flex-wrap justify-end">
                        <template x-for="st in quickMoveTargets(lightboxCard.status)" :key="st.key">
                            <button @click="quickMove(lightboxCard, st.key); lightboxCard = null"
                                    :class="st.btnClass"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold">
                                <span x-text="st.shortLabel"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function photocardApp(initial) {
            return {
                appTitle: initial.app_title || 'Photocard Wishlist',
                currency: initial.currency || 'RM',
                isAdmin: !!initial.is_admin,
                isLocal: !!initial.is_local,
                publicPreview: !!initial.public_preview,
                dbSizeKb: initial.db_size_kb || 0,
                cards: initial.cards || [],
                counts: initial.counts || { all: 0, uncollected: 0, pending: 0, collected: 0, no_need: 0 },
                eras: initial.eras || [],

                theme: localStorage.getItem('pc_theme') || 'light',
                viewportWidth: window.innerWidth || 1440,
                activeStatus: 'uncollected',
                activeEra: 'ALL',
                searchQuery: '',
                groupByEra: true,
                cardSize: 'md',
                activeCardId: null,

                batchMode: false,
                selectedIds: [],

                eraMenuOpen: false,
                systemMenuOpen: false,
                lightboxCard: null,
                syncModalOpen: false,
                pinModalOpen: false,
                pinLoginInput: '',
                newPinInput: '',
                importFile: null,
                importMode: 'replace',
                importingBundle: false,
                syncingSheet: false,

                toast: {
                    visible: false,
                    message: '',
                    undoItems: [],
                    timer: null
                },

                formModal: {
                    open: false,
                    isEdit: false,
                    saving: false,
                    id: null,
                    era: '',
                    title: '',
                    status: 'uncollected',
                    price: '',
                    notes: '',
                    file: null,
                    imageBase64: '',
                    imageUrl: '',
                    previewUrl: ''
                },

                statusTabs: [
                    {
                        key: 'uncollected',
                        label: 'Wishlist',
                        shortLabel: 'Wishlist',
                        dotClass: 'bg-violet-500',
                        activeClass: 'bg-violet-600 text-white border-violet-600',
                        btnClass: 'bg-violet-600 hover:bg-violet-500 text-white'
                    },
                    {
                        key: 'pending',
                        label: 'Pending Order',
                        shortLabel: 'Pending',
                        dotClass: 'bg-rose-500',
                        activeClass: 'bg-rose-600 text-white border-rose-600',
                        btnClass: 'bg-rose-600 hover:bg-rose-500 text-white'
                    },
                    {
                        key: 'collected',
                        label: 'Collected',
                        shortLabel: 'Collected',
                        dotClass: 'bg-emerald-500',
                        activeClass: 'bg-emerald-600 text-white border-emerald-600',
                        btnClass: 'bg-emerald-600 hover:bg-emerald-500 text-white'
                    },
                    {
                        key: 'no_need',
                        label: 'No Need',
                        shortLabel: 'No Need',
                        dotClass: 'bg-amber-500',
                        activeClass: 'bg-amber-400 text-amber-950 border-amber-400',
                        btnClass: 'bg-amber-400 hover:bg-amber-300 text-amber-950'
                    },
                    {
                        key: 'all',
                        label: 'All Cards',
                        shortLabel: 'All',
                        dotClass: 'bg-sky-500',
                        activeClass: 'bg-sky-600 text-white border-sky-600',
                        btnClass: ''
                    }
                ],

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('pc_theme', this.theme);
                    if (this.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },

                get boardCols() {
                    const w = this.viewportWidth;
                    if (w < 640) {
                        return this.cardSize === 'sm' ? 3 : 2;
                    }
                    if (w < 1024) {
                        return this.cardSize === 'sm' ? 6 : (this.cardSize === 'lg' ? 4 : 5);
                    }
                    if (w < 1360) {
                        return this.cardSize === 'sm' ? 8 : (this.cardSize === 'lg' ? 5 : 6);
                    }
                    return this.cardSize === 'sm' ? 10 : (this.cardSize === 'lg' ? 6 : 8);
                },

                clusterSpan(itemCount) {
                    return Math.max(1, Math.min(itemCount, this.boardCols));
                },

                clusterInnerCols(itemCount) {
                    return Math.max(1, Math.min(itemCount, this.boardCols));
                },

                get statusFilteredCards() {
                    return this.cards.filter(c => this.activeStatus === 'all' || c.status === this.activeStatus);
                },

                get availableErasForCurrentStatus() {
                    const map = {};
                    for (const c of this.statusFilteredCards) {
                        map[c.era] = (map[c.era] || 0) + 1;
                    }
                    return Object.keys(map)
                        .sort((a, b) => map[b] - map[a] || a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }))
                        .map(name => ({ name, count: map[name] }));
                },

                get filteredCards() {
                    const q = this.searchQuery.trim().toLowerCase();
                    return this.statusFilteredCards.filter(c => {
                        if (this.activeEra !== 'ALL' && c.era !== this.activeEra) {
                            return false;
                        }
                        if (q !== '') {
                            const hay = `${c.era} ${c.title || ''} ${c.notes || ''}`.toLowerCase();
                            return hay.includes(q);
                        }
                        return true;
                    });
                },

                get groupedCards() {
                    const groups = [];
                    const map = new Map();
                    for (const c of this.filteredCards) {
                        if (!map.has(c.era)) {
                            const group = { era: c.era, items: [] };
                            map.set(c.era, group);
                            groups.push(group);
                        }
                        map.get(c.era).items.push(c);
                    }
                    const sorted = groups.sort((a, b) => b.items.length - a.items.length);
                    const maxCols = this.boardCols;
                    const packed = [];
                    const used = new Array(sorted.length).fill(false);

                    while (packed.length < sorted.length) {
                        let rowRemaining = maxCols;
                        let placedInRow = false;
                        for (let i = 0; i < sorted.length; i++) {
                            if (used[i]) continue;
                            const span = Math.min(sorted[i].items.length, maxCols);
                            if (!placedInRow || span <= rowRemaining) {
                                used[i] = true;
                                packed.push(sorted[i]);
                                rowRemaining -= span;
                                placedInRow = true;
                                if (rowRemaining <= 0) break;
                            }
                        }
                    }
                    return packed;
                },

                matchSleeveBg(event) {
                    const img = event.target;
                    if (!img || !img.naturalWidth) return;
                    const sleeve = img.closest('[data-sleeve]');
                    if (!sleeve) return;
                    try {
                        if (!window._pcSampleCanvas) {
                            window._pcSampleCanvas = document.createElement('canvas');
                            window._pcSampleCanvas.width = 32;
                            window._pcSampleCanvas.height = 32;
                        }
                        const size = 32;
                        const ctx = window._pcSampleCanvas.getContext('2d', { willReadFrequently: true });
                        ctx.clearRect(0, 0, size, size);
                        ctx.drawImage(img, 0, 0, size, size);
                        const data = ctx.getImageData(0, 0, size, size).data;

                        // Sample inset perimeter & bottom caption margin to avoid 1px crop/gridline artifacts
                        const coords = [];
                        for (let i = 1; i < size - 1; i++) {
                            coords.push([i, 1], [i, size - 2], [1, i], [size - 2, i]);
                            // Weight bottom paper/caption margin higher for template screenshots
                            coords.push([i, size - 3], [i, size - 4]);
                        }

                        const buckets = new Map();
                        for (const [x, y] of coords) {
                            const idx = (y * size + x) * 4;
                            if (data[idx + 3] < 200) continue;
                            const r = data[idx], g = data[idx + 1], b = data[idx + 2];
                            // Quantize to 12-step buckets to find the dominant solid background color
                            const kr = Math.round(r / 12) * 12;
                            const kg = Math.round(g / 12) * 12;
                            const kb = Math.round(b / 12) * 12;
                            const key = `${kr},${kg},${kb}`;
                            const entry = buckets.get(key) || { count: 0, rSum: 0, gSum: 0, bSum: 0, lum: (r + g + b) / 3 };
                            entry.count++;
                            entry.rSum += r;
                            entry.gSum += g;
                            entry.bSum += b;
                            buckets.set(key, entry);
                        }

                        if (!buckets.size) return;

                        // Pick the dominant background bucket (preferring clean light/paper backgrounds when prominent)
                        let best = null;
                        let bestScore = -1;
                        for (const entry of buckets.values()) {
                            const isPaperLike = entry.lum > 230 ? 1.6 : 1.0;
                            const score = entry.count * isPaperLike;
                            if (score > bestScore) {
                                bestScore = score;
                                best = entry;
                            }
                        }

                        if (best) {
                            let rAvg = Math.round(best.rSum / best.count);
                            let gAvg = Math.round(best.gSum / best.count);
                            let bAvg = Math.round(best.bSum / best.count);
                            // Snap near-white template backgrounds to pure crisp white so there is zero seam
                            if (rAvg > 242 && gAvg > 242 && bAvg > 242) {
                                rAvg = 255; gAvg = 255; bAvg = 255;
                            }
                            sleeve.style.backgroundColor = `rgb(${rAvg}, ${gAvg}, ${bAvg})`;
                        }
                    } catch (e) {
                        // Ignore cross-origin canvas sampling errors
                    }
                },

                handleCardClick(card) {
                    if (this.batchMode) {
                        this.toggleSelect(card.id);
                        return;
                    }
                    if (this.viewportWidth < 1024 && this.isAdmin) {
                        if (this.activeCardId === card.id) {
                            this.openLightbox(card);
                        } else {
                            this.activeCardId = card.id;
                        }
                        return;
                    }
                    this.openLightbox(card);
                },

                recalculateCountsAndEras() {
                    const c = { all: this.cards.length, uncollected: 0, pending: 0, collected: 0, no_need: 0 };
                    const eMap = {};
                    for (const card of this.cards) {
                        if (c[card.status] !== undefined) c[card.status]++;
                        eMap[card.era] = (eMap[card.era] || 0) + 1;
                    }
                    this.counts = c;
                    this.eras = Object.keys(eMap)
                        .sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }))
                        .map(name => ({ name, count: eMap[name] }));
                },

                statusLabel(status) {
                    const found = this.statusTabs.find(t => t.key === status);
                    return found ? found.label : status;
                },

                statusShortLabel(status) {
                    const found = this.statusTabs.find(t => t.key === status);
                    return found ? found.shortLabel : status;
                },

                statusBadgeClass(status) {
                    switch (status) {
                        case 'uncollected': return 'bg-violet-600 text-white';
                        case 'pending': return 'bg-rose-600 text-white';
                        case 'collected': return 'bg-emerald-600 text-white';
                        case 'no_need': return 'bg-amber-400 text-amber-950';
                        default: return 'bg-sky-600 text-white';
                    }
                },

                quickMoveTargets(currentStatus) {
                    return this.statusTabs.filter(t => t.key !== 'all' && t.key !== currentStatus);
                },

                async quickMove(card, targetStatus) {
                    const prevStatus = card.status;
                    card.status = targetStatus;
                    this.activeCardId = null;
                    this.recalculateCountsAndEras();

                    try {
                        const res = await fetch('api.php?action=move', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: card.id, status: targetStatus })
                        });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Move failed');
                        this.showToast(`Moved "${card.era}" → ${this.statusLabel(targetStatus)}`, data.previous || [{ id: card.id, status: prevStatus }]);
                    } catch (err) {
                        card.status = prevStatus;
                        this.recalculateCountsAndEras();
                        alert(err.message);
                    }
                },

                async undoLastMove() {
                    const items = this.toast.undoItems;
                    if (!items || !items.length) return;
                    this.toast.visible = false;

                    for (const item of items) {
                        const found = this.cards.find(c => c.id === Number(item.id));
                        if (found) found.status = item.status;
                    }
                    this.recalculateCountsAndEras();

                    await fetch('api.php?action=undo_move', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ items })
                    });
                },

                showToast(message, undoItems = []) {
                    if (this.toast.timer) clearTimeout(this.toast.timer);
                    this.toast.message = message;
                    this.toast.undoItems = undoItems;
                    this.toast.visible = true;
                    this.toast.timer = setTimeout(() => {
                        this.toast.visible = false;
                    }, 5000);
                },

                toggleBatchMode() {
                    this.batchMode = !this.batchMode;
                    this.selectedIds = [];
                },

                isSelected(id) {
                    return this.selectedIds.includes(id);
                },

                toggleSelect(id) {
                    if (this.isSelected(id)) {
                        this.selectedIds = this.selectedIds.filter(i => i !== id);
                    } else {
                        this.selectedIds.push(id);
                    }
                },

                selectAllVisible() {
                    const visibleIds = this.filteredCards.map(c => c.id);
                    const allSelected = visibleIds.every(id => this.selectedIds.includes(id));
                    if (allSelected) {
                        this.selectedIds = [];
                    } else {
                        this.selectedIds = [...new Set([...this.selectedIds, ...visibleIds])];
                    }
                },

                async batchMove(targetStatus) {
                    if (!this.selectedIds.length) return;
                    const ids = [...this.selectedIds];
                    try {
                        const res = await fetch('api.php?action=move', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ids, status: targetStatus })
                        });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Batch move failed');
                        for (const c of this.cards) {
                            if (ids.includes(c.id)) c.status = targetStatus;
                        }
                        this.recalculateCountsAndEras();
                        this.selectedIds = [];
                        this.batchMode = false;
                        this.showToast(`Moved ${ids.length} cards → ${this.statusLabel(targetStatus)}`, data.previous || []);
                    } catch (err) {
                        alert(err.message);
                    }
                },

                async batchDelete() {
                    if (!this.selectedIds.length) return;
                    if (!confirm(`Delete ${this.selectedIds.length} selected photocard(s)?`)) return;
                    const ids = [...this.selectedIds];
                    const res = await fetch('api.php?action=delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ids })
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.cards = this.cards.filter(c => !ids.includes(c.id));
                        this.recalculateCountsAndEras();
                        this.selectedIds = [];
                        this.batchMode = false;
                        this.showToast(`Deleted ${ids.length} card(s)`);
                    }
                },

                openAddModal(defaultEra = '') {
                    this.formModal = {
                        open: true,
                        isEdit: false,
                        saving: false,
                        id: null,
                        era: defaultEra || (this.activeEra !== 'ALL' ? this.activeEra : ''),
                        title: '',
                        status: this.activeStatus === 'all' ? 'uncollected' : this.activeStatus,
                        price: '',
                        notes: '',
                        file: null,
                        imageBase64: '',
                        imageUrl: '',
                        previewUrl: ''
                    };
                },

                openEditModal(card) {
                    this.formModal = {
                        open: true,
                        isEdit: true,
                        saving: false,
                        id: card.id,
                        era: card.era,
                        title: card.title || '',
                        status: card.status,
                        price: card.price !== null ? card.price : '',
                        notes: card.notes || '',
                        file: null,
                        imageBase64: '',
                        imageUrl: '',
                        previewUrl: card.image_path
                    };
                },

                handleFileSelect(e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    this.formModal.file = file;
                    this.formModal.imageBase64 = '';
                    this.formModal.previewUrl = URL.createObjectURL(file);
                },

                handleGlobalPaste(e) {
                    if (!this.isAdmin) return;
                    const items = e.clipboardData?.items;
                    if (!items) return;
                    for (const item of items) {
                        if (item.type.startsWith('image/')) {
                            const blob = item.getAsFile();
                            if (!blob) continue;
                            const reader = new FileReader();
                            reader.onload = (ev) => {
                                if (!this.formModal.open) {
                                    this.openAddModal();
                                }
                                this.formModal.file = null;
                                this.formModal.imageBase64 = ev.target.result;
                                this.formModal.previewUrl = ev.target.result;
                            };
                            reader.readAsDataURL(blob);
                            break;
                        }
                    }
                },

                async submitCardForm() {
                    this.formModal.saving = true;
                    try {
                        const fd = new FormData();
                        fd.append('action', this.formModal.isEdit ? 'update' : 'create');
                        if (this.formModal.isEdit) fd.append('id', this.formModal.id);
                        fd.append('era', this.formModal.era);
                        fd.append('title', this.formModal.title);
                        fd.append('status', this.formModal.status);
                        fd.append('price', this.formModal.price);
                        fd.append('notes', this.formModal.notes);

                        if (this.formModal.file) {
                            fd.append('image', this.formModal.file);
                        } else if (this.formModal.imageBase64) {
                            fd.append('image_base64', this.formModal.imageBase64);
                        } else if (this.formModal.imageUrl) {
                            fd.append('image_url', this.formModal.imageUrl);
                        }

                        const res = await fetch('api.php', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Failed to save card');

                        if (this.formModal.isEdit) {
                            const idx = this.cards.findIndex(c => c.id === data.card.id);
                            if (idx !== -1) this.cards[idx] = data.card;
                            this.showToast('Photocard updated');
                        } else {
                            this.cards.unshift(data.card);
                            this.showToast(`Added to ${this.statusLabel(data.card.status)}`);
                        }
                        this.recalculateCountsAndEras();
                        this.formModal.open = false;
                    } catch (err) {
                        alert(err.message);
                    } finally {
                        this.formModal.saving = false;
                    }
                },

                async deleteCard(card) {
                    if (!confirm(`Delete this "${card.era}" photocard?`)) return;
                    const res = await fetch('api.php?action=delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: card.id })
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.cards = this.cards.filter(c => c.id !== card.id);
                        this.recalculateCountsAndEras();
                        this.showToast('Photocard deleted');
                    }
                },

                openLightbox(card) {
                    this.lightboxCard = card;
                },

                closeModals() {
                    this.lightboxCard = null;
                    this.formModal.open = false;
                    this.syncModalOpen = false;
                    this.pinModalOpen = false;
                    this.eraMenuOpen = false;
                    this.systemMenuOpen = false;
                    this.activeCardId = null;
                },

                async handleLockButton() {
                    if (this.isAdmin) {
                        const res = await fetch('api.php?action=toggle_preview', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ preview: true })
                        });
                        const data = await res.json();
                        this.isAdmin = !!data.is_admin;
                        this.batchMode = false;
                        this.showToast('Switched to Public Read-Only View');
                    } else if (this.isLocal) {
                        const res = await fetch('api.php?action=toggle_preview', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ preview: false })
                        });
                        const data = await res.json();
                        this.isAdmin = !!data.is_admin;
                        this.showToast('Admin Edit Mode Unlocked');
                    } else {
                        this.pinLoginInput = '';
                        this.pinModalOpen = true;
                    }
                },

                async submitPinLogin() {
                    const res = await fetch('api.php?action=login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ pin: this.pinLoginInput })
                    });
                    const data = await res.json();
                    if (!data.ok) {
                        alert(data.error || 'Invalid PIN');
                        return;
                    }
                    this.isAdmin = true;
                    this.pinModalOpen = false;
                    this.showToast('Admin Edit Mode Unlocked');
                },

                async changeAdminPin() {
                    if (!this.newPinInput || this.newPinInput.length < 4) {
                        alert('Enter at least 4 characters for the new PIN.');
                        return;
                    }
                    const res = await fetch('api.php?action=update_pin', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ new_pin: this.newPinInput })
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.newPinInput = '';
                        this.showToast('Admin PIN updated');
                    } else {
                        alert(data.error || 'Could not update PIN');
                    }
                },

                async submitImportBundle() {
                    if (!this.importFile) return;
                    this.importingBundle = true;
                    try {
                        const fd = new FormData();
                        fd.append('action', 'import_bundle');
                        fd.append('mode', this.importMode);
                        fd.append('bundle', this.importFile);

                        const res = await fetch('api.php', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Import failed');

                        await this.reloadState();
                        this.syncModalOpen = false;
                        this.showToast(`Synced ${data.result.cards_imported} cards (${data.result.mode})`);
                    } catch (err) {
                        alert(err.message);
                    } finally {
                        this.importingBundle = false;
                    }
                },

                async syncFromSheet(reset = false) {
                    this.syncingSheet = true;
                    try {
                        const res = await fetch('api.php?action=sync_sheet', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ reset })
                        });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Sync failed');
                        await this.reloadState();
                        this.showToast(`Sheet Sync: ${data.result.imported} new, ${data.result.skipped} existing`);
                    } catch (err) {
                        alert(err.message);
                    } finally {
                        this.syncingSheet = false;
                    }
                },

                async reloadState() {
                    const res = await fetch('api.php?action=list');
                    const data = await res.json();
                    if (data.ok) {
                        this.cards = data.cards;
                        this.counts = data.counts;
                        this.eras = data.eras;
                        this.dbSizeKb = data.db_size_kb;
                    }
                }
            };
        }
    </script>
</body>
</html>
