<?php
require_once __DIR__.'/../includes/functions.php';
require_auth();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLSU Performance Observatory - IRIS</title>
    <script>
        (function () {
            try {
                const saved = localStorage.getItem('color-theme') || localStorage.getItem('iris-theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = saved ? saved === 'dark' : prefersDark;
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {}
        })();
    </script>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            gold: '#f59e0b'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Flowbite CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <!-- Apache ECharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.0/dist/echarts.min.js"></script>
    <script src="<?= e(base_url('scanner/js/chartMapping.js')) ?>"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Libre Franklin', 'Inter', sans-serif; }
        .admin-nav { background: linear-gradient(180deg, rgba(15,23,42,.98), rgba(15,23,42,.92)) !important; border-bottom: 1px solid rgba(148,163,184,.22) !important; box-shadow: 0 10px 30px rgba(2,6,23,.24) !important; }
        .admin-nav-inner { min-height: 76px; }
        .admin-brand-title { color: #f8fafc !important; }
        .admin-brand-sub { color: #cbd5e1 !important; }
        .admin-nav-link { display: inline-flex; align-items: center; gap: .5rem; padding: .65rem .9rem; border: 1px solid rgba(148,163,184,.25); background: rgba(15,23,42,.52); color: #e2e8f0; border-radius: .7rem; font-size: .78rem; font-weight: 700; transition: .2s; }
        .admin-nav-link:hover { background: rgba(16,185,129,.12); color: #ecfdf5; border-color: rgba(52,211,153,.45); }
        .admin-theme-btn, .admin-profile-btn { color: #e2e8f0 !important; background: rgba(30,41,59,.9) !important; border: 1px solid rgba(148,163,184,.25) !important; }
        .admin-theme-btn:hover, .admin-profile-btn:hover { color: #f8fafc !important; background: rgba(51,65,85,.9) !important; }
        .admin-dropdown { background: #111827 !important; border: 1px solid rgba(148,163,184,.22) !important; color: #e2e8f0 !important; box-shadow: 0 12px 30px rgba(2,6,23,.35) !important; }
        .admin-dropdown .dropdown-name { color: #f8fafc !important; }
        .admin-dropdown ul { margin: 0; padding: .25rem 0 !important; }
        .admin-dropdown li { display: flex !important; align-items: center !important; }
        .admin-dropdown a, .admin-dropdown .signout { display: flex !important; align-items: center !important; justify-content: flex-start !important; gap: .6rem !important; width: 100% !important; text-align: left !important; line-height: 1.2 !important; white-space: nowrap !important; }
        .admin-dropdown a { color: #dbeafe !important; padding: .7rem 1rem !important; }
        .admin-dropdown a:hover { background: rgba(16,185,129,.12) !important; color: #ecfdf5 !important; }
        .admin-dropdown .signout { padding: .75rem 1rem !important; color: #fca5a5 !important; border-radius: .75rem !important; transition: background .2s ease,color .2s ease; }
        .admin-dropdown .signout:hover { background: rgba(239,68,68,.12) !important; color: #fee2e2 !important; }
        html:not(.dark) .admin-nav { background: #1E6031 !important; border-bottom: 3px solid #E0A70D !important; box-shadow: 0 4px 12px rgba(0,0,0,.08) !important; }
        html:not(.dark) .admin-brand-title { color: #fff !important; }
        html:not(.dark) .admin-brand-sub { color: rgba(255,255,255,.8) !important; }
        html:not(.dark) .admin-nav-link { background: rgba(255,255,255,.1) !important; color: #fff !important; border: 1px solid rgba(255,255,255,.2) !important; }
        html:not(.dark) .admin-nav-link:hover { background: rgba(255,255,255,.2) !important; color: #fff !important; border-color: rgba(255,255,255,.3) !important; }
        html:not(.dark) .admin-theme-btn, html:not(.dark) .admin-profile-btn { color: #fff !important; background: rgba(255,255,255,.1) !important; border: 1px solid rgba(255,255,255,.2) !important; }
        html:not(.dark) .admin-theme-btn:hover, html:not(.dark) .admin-profile-btn:hover { color: #fff !important; background: rgba(255,255,255,.2) !important; }
        html:not(.dark) .admin-dropdown { background: #fff !important; border: 1px solid rgba(30,96,49,.12) !important; color: #1F2A24 !important; box-shadow: 0 12px 30px rgba(15,23,42,.08) !important; }
        html:not(.dark) .admin-dropdown .dropdown-name, html:not(.dark) .admin-dropdown a { color: #1F2A24 !important; }
        html:not(.dark) .admin-dropdown a:hover { background: #EEF6F0 !important; color: #1E6031 !important; }
        html:not(.dark) .admin-dropdown .signout { color: #b91c1c !important; }
        html:not(.dark) .admin-dropdown .signout:hover { background: #fef2f2 !important; color: #991b1b !important; }
        html:not(.dark) .admin-footer { background: #1E6031 !important; border-top: 3px solid #E0A70D !important; color: #fff !important; }
        html:not(.dark) .admin-footer span, html:not(.dark) .admin-footer div { color: rgba(255,255,255,.9) !important; }
        html:not(.dark) .admin-footer-title { color: #fff !important; }
        #page-loader{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.68);backdrop-filter:blur(6px);z-index:10000;transition:opacity .3s ease,visibility .3s ease;}
        #page-loader.hidden{opacity:0;visibility:hidden;pointer-events:none;}
        .iris-loader{position:relative;width:72px;height:72px;border-radius:50%;background:conic-gradient(#10b981,#34d399,#fbbf24,#10b981);animation:spin 1s linear infinite;box-shadow:0 0 30px rgba(16,185,129,.5)}
        .iris-loader::before{content:"";position:absolute;inset:10px;border-radius:50%;background:rgba(15,23,42,.9);border:2px solid rgba(255,255,255,.18)}
        .iris-loader::after{content:"IRIS";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;letter-spacing:.12em;color:#d1fae5}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <div id="page-loader" aria-live="polite" aria-label="Loading page">
        <div class="iris-loader" aria-hidden="true"></div>
    </div>

    <!-- Top Navigation Bar -->
    <nav class="admin-nav sticky top-0 z-50 backdrop-blur-md bg-opacity-95">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="admin-nav-inner flex items-center justify-between gap-4">
                <a href="<?= e(base_url('user/dashboard.php')) ?>" class="logo-refresh-trigger flex items-center gap-3 min-w-0" data-target="<?= e(base_url('user/dashboard.php')) ?>">
                    <div class="w-52 h-11 flex items-center justify-center overflow-hidden shrink-0 rounded-lg bg-white px-3 py-1.5">
                        <img src="<?= e(base_url('images/iris-panel-logo.svg')) ?>" alt="IRIS SielMetrics+ Logo" class="h-10 w-full object-contain object-left">
                    </div>
                    <div class="min-w-0 hidden sm:block">
                        <div class="flex items-center gap-2">
                            <span class="admin-brand-title text-xl font-extrabold tracking-tight">CLSU Observatory</span>
                            <span class="text-[10px] px-2 py-1 font-extrabold rounded-full bg-[#FFD700] text-[#1E6031] border border-[#E0A70D]">PUBLIC ANALYTICS</span>
                        </div>
                        <p class="admin-brand-sub text-[11px] font-semibold uppercase tracking-wider">International Affairs Office &bull; IRIS</p>
                    </div>
                </a>

                <div class="admin-nav-actions flex items-center gap-2">
                    <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                        <a href="<?= e(base_url('admin/review_editor.php')) ?>" class="admin-nav-link public-link" aria-label="Edit Observatory data" title="Edit Observatory data">
                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Edit</span>
                        </a>
                        <div class="relative">
                            <button type="button" class="admin-profile-btn flex items-center justify-center p-2.5 rounded-lg focus:ring-2 focus:ring-emerald-500" id="observatory-menu-button" aria-expanded="false" data-dropdown-toggle="observatory-menu" data-dropdown-placement="bottom" aria-label="Open navigation menu">
                                <i class="fa-solid fa-bars text-base" aria-hidden="true"></i>
                            </button>
                            <div class="admin-dropdown z-50 hidden my-3 w-56 text-base list-none rounded-xl shadow-2xl" id="observatory-menu">
                                <ul class="py-2" aria-labelledby="observatory-menu-button">
                                    <li><a href="#scanner-published-graphs"><i class="fa-solid fa-chart-column"></i> Scanner Analytics</a></li>
                                    <li><a href="<?= e(base_url('admin/dashboard.php')) ?>"><i class="fa-solid fa-sliders"></i> Admin Portal</a></li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                    <button id="theme-toggle" type="button" class="admin-theme-btn rounded-lg text-sm p-2.5" aria-label="Toggle theme">
                        <i id="theme-toggle-dark-icon" class="hidden fa-solid fa-moon text-base"></i>
                        <i id="theme-toggle-light-icon" class="hidden fa-solid fa-sun text-base text-amber-400"></i>
                    </button>
                    <div class="relative">
                        <button type="button" class="admin-profile-btn flex items-center gap-2 p-1.5 rounded-full focus:ring-2 focus:ring-emerald-500" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-amber-400 flex items-center justify-center text-white font-bold text-xs shadow">
                                <?= strtoupper(substr(($_SESSION['username'] ?? 'U'), 0, 2)) ?>
                            </div>
                            <span class="hidden sm:inline-block font-semibold text-xs px-1"><?= htmlspecialchars(($_SESSION['username'] ?? 'U')) ?></span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 mr-1" aria-hidden="true"></i>
                        </button>
                        <div class="admin-dropdown z-50 hidden my-3 w-56 text-base list-none rounded-xl shadow-2xl" id="user-dropdown">
                            <div class="px-4 py-3 border-b border-slate-700">
                                <span class="dropdown-name block text-sm font-bold"><?= htmlspecialchars(($_SESSION['username'] ?? 'U')) ?></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-400 text-slate-900 mt-1">
                                    <?= ($_SESSION['role'] ?? null) === 'admin' ? 'ADMINISTRATOR' : 'VIEWER' ?>
                                </span>
                            </div>
                            <ul class="py-2" aria-labelledby="user-menu-button">
                                <?php if (($_SESSION['role'] ?? null) === 'admin'): ?>
                                    <li><a href="<?= e(base_url('admin/dashboard.php')) ?>"><i class="fa-solid fa-shield-halved"></i> Admin Portal</a></li>
                                <?php endif; ?>
                            </ul>
                            <div class="py-1 border-t border-slate-700">
                                <form method="POST" action="<?= e(base_url('auth/logout.php')) ?>" class="w-full">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="signout w-full px-4 py-2 text-sm whitespace-nowrap">
                                        <i class="fa-solid fa-right-from-bracket flex-shrink-0" aria-hidden="true"></i>
                                        <span class="whitespace-nowrap">Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8" id="overview">
        <?php if (flash('error')): ?>
            <div class="flex items-center p-4 text-red-800 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800" role="alert"><i class="fa-solid fa-circle-exclamation text-lg mr-3"></i><div class="text-sm font-medium"><?= htmlspecialchars(flash('error')) ?></div></div>
        <?php endif; ?>
        <section id="scanner-published-graphs" class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fa-solid fa-chart-column text-emerald-500 mr-2"></i> Scanner-Published Analytics
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Approved visualizations published from the IRIS Scanner</p>
                </div>
                <span id="publishedGraphCount" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                    <i class="fa-solid fa-circle-check mr-1"></i> Loading published graphs
                </span>
            </div>
            <div id="scannerPublishedGraphsGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div id="scannerPublishedGraphsEmpty" class="lg:col-span-2 p-6 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60 text-center text-sm text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-chart-simple text-lg mr-1"></i> No approved scanner graphs have been published yet.
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="admin-footer bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 dark:text-gray-400 gap-4">
            <div class="flex items-center space-x-2">
                <span class="font-bold admin-footer-title text-gray-800 dark:text-gray-200">CLSU Observatory</span>
                <span>&bull; IAO'S INTERNATIONAL RAPPORT INSIGHT SYSTEM</span>
            </div>
            <div>
                Powered by Flowbite &amp; Tailwind CSS
            </div>
        </div>
    </footer>

    <!-- Theme Toggle & ECharts Script Initialization -->
    <script>
        (function () {
            const loader = document.getElementById('page-loader');
            const hideLoader = () => {
                if (loader) {
                    loader.classList.add('hidden');
                }
            };

            document.querySelectorAll('.logo-refresh-trigger').forEach((link) => {
                link.addEventListener('click', function (event) {
                    const target = this.getAttribute('data-target') || this.href;
                    event.preventDefault();
                    loader && loader.classList.remove('hidden');
                    const currentUrl = window.location.href.split('#')[0];
                    if (target && target.split('#')[0] === currentUrl.split('#')[0]) {
                        window.location.reload();
                        return;
                    }
                    window.location.href = target;
                });
            });

            setTimeout(hideLoader, 90);
            window.addEventListener('load', hideLoader);
        })();

        // --- Dark Mode Logic ---
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        const themeToggleBtn = document.getElementById('theme-toggle');

        if (document.documentElement.classList.contains('dark')) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            const isDarkNow = document.documentElement.classList.contains('dark');
            const nextMode = isDarkNow ? 'light' : 'dark';

            document.documentElement.classList.toggle('dark', nextMode === 'dark');
            localStorage.setItem('color-theme', nextMode);
            localStorage.setItem('iris-theme', nextMode);
            loadPublishedScannerGraphs();
        });

        // --- Apache ECharts Data & Initialization ---
        const trendYears = [];
        const trendRanks = [];
        const trendDisplay = [];
        const collegePieData = [];
        const breakdownSections = [];

        let chartInstances = [];

        function renderAllCharts() {
            chartInstances.forEach(c => c && c.dispose());
            chartInstances = [];

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#E5E7EB' : '#1F2937';
            const splitLineColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)';
            const tooltipBg = isDark ? '#1f2937' : '#ffffff';
            const tooltipBorder = isDark ? '#374151' : '#e5e7eb';
            const tooltipText = isDark ? '#f9fafb' : '#111827';

            // 1. QS Rank Line Chart
            const trendElem = document.getElementById('trendChart');
            if (trendElem) {
                const trendChart = echarts.init(trendElem);
                chartInstances.push(trendChart);
                trendChart.setOption({
                    backgroundColor: 'transparent',
                    tooltip: {
                        trigger: 'axis',
                        backgroundColor: tooltipBg,
                        borderColor: tooltipBorder,
                        textStyle: { color: tooltipText },
                        formatter: function(params) {
                            const idx = params[0].dataIndex;
                            return `Year: <b>${trendYears[idx]}</b><br/>Standing: <b>${trendDisplay[idx] || '—'}</b>`;
                        }
                    },
                    grid: { left: '3%', right: '4%', bottom: '3%', top: '10%', containLabel: true },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: trendYears,
                        axisLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: { color: textColor }
                    },
                    yAxis: {
                        type: 'value',
                        inverse: true, // Lower number = higher rank
                        splitLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: { color: textColor }
                    },
                    series: [{
                        name: 'Rank',
                        type: 'line',
                        smooth: true,
                        data: trendRanks,
                        lineStyle: { width: 3, color: '#10b981' },
                        itemStyle: { color: '#10b981' },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(16, 185, 129, 0.35)' },
                                { offset: 1, color: 'rgba(16, 185, 129, 0.0)' }
                            ])
                        }
                    }]
                });
            }

            // 2. College Contribution Doughnut Chart
            const collegeElem = document.getElementById('collegeChart');
            if (collegeElem) {
                const collegeChart = echarts.init(collegeElem);
                chartInstances.push(collegeChart);
                collegeChart.setOption({
                    backgroundColor: 'transparent',
                    tooltip: {
                        trigger: 'item',
                        backgroundColor: tooltipBg,
                        borderColor: tooltipBorder,
                        textStyle: { color: tooltipText },
                        formatter: params => `${params.name}<br/><strong>${Number(params.value).toLocaleString(undefined, { maximumFractionDigits: 2 })}</strong> (${params.percent}%)`
                    },
                    legend: {
                        orient: 'vertical',
                        right: '1%',
                        top: 'middle',
                        width: '42%',
                        type: 'scroll',
                        textStyle: { color: textColor, fontSize: 11 },
                        formatter: name => String(name).length > 24 ? `${String(name).slice(0, 24)}...` : name
                    },
                    series: [{
                        type: 'pie',
                        radius: ['45%', '70%'],
                        center: ['32%', '50%'],
                        label: {
                            show: true,
                            color: textColor,
                            fontSize: 10,
                            formatter: params => {
                                const name = String(params.name || 'College');
                                return name.length > 18 ? `${name.slice(0, 18)}...` : name;
                            }
                        },
                        labelLine: { show: true, length: 10, length2: 8 },
                        itemStyle: {
                            borderRadius: 6,
                            borderColor: isDark ? '#1f2937' : '#ffffff',
                            borderWidth: 2
                        },
                        data: collegePieData
                    }]
                });
            }

            // 3. Category Breakdown Mini Bar Charts
            breakdownSections.forEach((section, idx) => {
                const elem = document.getElementById('breakdownChart' + idx);
                if (!elem) return;
                const chart = echarts.init(elem);
                chartInstances.push(chart);
                chart.setOption({
                    backgroundColor: 'transparent',
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' },
                        backgroundColor: tooltipBg,
                        borderColor: tooltipBorder,
                        textStyle: { color: tooltipText },
                        formatter: function(params) {
                            const dataIndex = params[0].dataIndex;
                            return `${section.labels[dataIndex]}<br/>Rank/Score: <b>${section.displays[dataIndex]}</b>`;
                        }
                    },
                    grid: { left: '3%', right: '5%', bottom: '3%', top: '5%', containLabel: true },
                    xAxis: {
                        type: 'value',
                        inverse: true,
                        splitLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: { color: textColor, fontSize: 10 }
                    },
                    yAxis: {
                        type: 'category',
                        data: section.labels,
                        axisLine: { lineStyle: { color: splitLineColor } },
                        axisLabel: {
                            color: textColor,
                            fontSize: 10,
                            formatter: function(val) {
                                return val.length > 15 ? val.slice(0, 15) + '...' : val;
                            }
                        }
                    },
                    series: [{
                        type: 'bar',
                        data: section.values,
                        itemStyle: {
                            color: '#10b981',
                            borderRadius: [4, 0, 0, 4]
                        }
                    }]
                });
            });
        }

        // Live Filters
        const canManagePublishedGraphs = <?= json_encode(($_SESSION['role'] ?? null) === 'admin') ?>;

        function renderPublishedScannerGraphs(graphs) {
            const grid = document.getElementById('scannerPublishedGraphsGrid');
            const empty = document.getElementById('scannerPublishedGraphsEmpty');
            const count = document.getElementById('publishedGraphCount');
            if (!grid || !count) return;

            const replacedCharts = new Set();
            grid.querySelectorAll('.scanner-published-card').forEach(el => {
                el._chartResizeObserver?.disconnect?.();
                el._publishedChart?.dispose?.();
                if (el._publishedChart) replacedCharts.add(el._publishedChart);
                el.remove();
            });
            chartInstances = chartInstances.filter(chart => !replacedCharts.has(chart));
            if (!Array.isArray(graphs) || graphs.length === 0) {
                if (empty) empty.style.display = '';
                count.innerHTML = '<i class="fa-solid fa-circle-info mr-1"></i> 0 published graphs';
                return;
            }
            if (empty) empty.style.display = 'none';
            count.innerHTML = `<i class="fa-solid fa-circle-check mr-1"></i> ${graphs.length} published graph${graphs.length === 1 ? '' : 's'}`;

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#4b5563';
            const splitLineColor = isDark ? '#374151' : '#f3f4f6';
            const tooltipBg = isDark ? '#1f2937' : '#ffffff';
            const tooltipBorder = isDark ? '#374151' : '#e5e7eb';
            const tooltipText = isDark ? '#f9fafb' : '#111827';

            graphs.forEach((graph, index) => {
                const card = document.createElement('div');
                card.className = 'scanner-published-card bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm';
                const chartId = `scannerPublishedChart_${index}`;
                const labels = Array.isArray(graph.labels) ? graph.labels.map(v => String(v ?? '')) : [];
                const type = String(graph.chart_type || 'bar').toLowerCase();
                const values = Array.isArray(graph.values_data) ? graph.values_data.map(v => {
                    if (type === 'year_ranking' && (v === null || v === undefined || String(v).trim() === '')) return null;
                    const n = Number(v);
                    return Number.isFinite(n) ? n : (type === 'year_ranking' ? null : 0);
                }) : [];
                const source = graph.source_file_name ? `Source: ${graph.source_file_name}` : 'Source: IRIS Scanner';
                card.innerHTML = `
                    <div class="flex items-start justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                <i class="fa-solid fa-chart-line text-emerald-500 mr-2"></i>${escapeHtmlDashboard(graph.title || 'Published Observatory Chart')}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${escapeHtmlDashboard(source)}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">APPROVED</span>
                            ${canManagePublishedGraphs ? `<button type="button" class="published-graph-delete inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30 dark:border-red-800" data-graph-id="${escapeHtmlDashboard(graph.id)}" title="Remove this published chart"><i class="fa-solid fa-trash mr-1" aria-hidden="true"></i> Delete</button>` : ''}
                        </div>
                    </div>
                    <div id="${chartId}" class="w-full h-72 pt-4"></div>`;
                grid.appendChild(card);

                card.querySelector('.published-graph-delete')?.addEventListener('click', async event => {
                    const button = event.currentTarget;
                    if (!confirm(`Remove "${graph.title || 'this published chart'}" from the Observatory?`)) return;
                    button.disabled = true;
                    try {
                        const response = await fetch('<?= e(base_url('api/iris.php')) ?>?resource=graphs&id=' + encodeURIComponent(graph.id), { method: 'DELETE', headers: { 'Accept': 'application/json' } });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(payload.error || 'Unable to delete published chart.');
                        loadPublishedScannerGraphs();
                    } catch (error) {
                        button.disabled = false;
                        alert(error.message);
                    }
                });

                const elem = document.getElementById(chartId);
                if (!elem) return;
                const chart = echarts.init(elem);
                card._publishedChart = chart;
                if (typeof ResizeObserver !== 'undefined') {
                    card._chartResizeObserver = new ResizeObserver(() => chart.resize());
                    card._chartResizeObserver.observe(elem);
                }
                chartInstances.push(chart);
                const circular = ['pie', 'doughnut', 'polararea'].includes(type);
                const horizontal = type === 'bar' && String(graph.orientation || '').toLowerCase() === 'horizontal';
                const semanticRank = graph.rank_semantic === true;
                const reverse = graph.value_axis_reversed === true && !semanticRank;
                const axisMin = graph.value_axis_min !== null ? Number(graph.value_axis_min) : undefined;
                const axisMax = graph.value_axis_max !== null ? Number(graph.value_axis_max) : undefined;

                if (type === 'year_ranking' && window.ChartMapping) {
                    const chartOptions = graph.chart_options || {};
                    const measureName = chartOptions.measureName || 'Value';
                    chart.setOption(window.ChartMapping.buildYearRankingOption({
                        measureName,
                        rows: labels.map((label, i) => ({ label, value: values[i] })).filter(row => Number.isFinite(row.value)),
                        preserveOrder: true,
                        rankSemantic: semanticRank || window.ChartMapping.isRankField(measureName),
                        showCumulativeLine: chartOptions.showCumulativeLine !== false,
                        show80Reference: chartOptions.show80Reference !== false,
                        showBarValueLabels: chartOptions.showBarValueLabels !== false,
                        isDark,
                        width: elem.clientWidth
                    }));
                } else if (circular) {
                    const pieType = type === 'polararea' ? 'pie' : 'pie';
                    const sliceColors = ['#10b981', '#34d399', '#3b82f6', '#f59e0b', '#8b5cf6', '#f97316', '#14b8a6', '#ef4444', '#eab308', '#6366f1'];
                    chart.setOption({
                        backgroundColor: 'transparent',
                        tooltip: { trigger: 'item', backgroundColor: tooltipBg, borderColor: tooltipBorder, textStyle: { color: tooltipText }, formatter: '{b}: {c} ({d}%)' },
                        legend: { type: 'scroll', bottom: 0, textStyle: { color: textColor, fontSize: 11 } },
                        series: [{
                            type: pieType,
                            radius: type === 'doughnut' ? ['45%', '70%'] : type === 'polararea' ? ['15%', '70%'] : '65%',
                            center: ['50%', '45%'],
                            data: labels.map((label, i) => ({
                                name: label || `Item ${i + 1}`,
                                value: values[i] ?? 0,
                                itemStyle: { color: sliceColors[i % sliceColors.length] }
                            })),
                            itemStyle: { borderColor: isDark ? '#1f2937' : '#ffffff', borderWidth: 2 }
                        }]
                    });
                } else {
                    const seriesData = values;
                    const option = {
                        backgroundColor: 'transparent',
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: { type: 'shadow' },
                            backgroundColor: tooltipBg,
                            borderColor: tooltipBorder,
                            textStyle: { color: tooltipText }
                        },
                        grid: { left: '4%', right: '4%', bottom: labels.length > 7 ? '15%' : '6%', top: '8%', containLabel: true },
                        xAxis: horizontal ? { type: 'value', min: axisMin, max: axisMax, inverse: reverse, splitLine: { lineStyle: { color: splitLineColor } }, axisLabel: { color: textColor } } : { type: 'category', data: labels, axisLabel: { color: textColor, rotate: labels.length > 6 ? 35 : 0 }, axisLine: { lineStyle: { color: splitLineColor } } },
                        yAxis: horizontal ? { type: 'category', data: labels, axisLabel: { color: textColor }, axisLine: { lineStyle: { color: splitLineColor } } } : { type: 'value', min: axisMin, max: axisMax, inverse: reverse || semanticRank, splitLine: { lineStyle: { color: splitLineColor } }, axisLabel: { color: textColor } },
                        series: [{ type: type === 'line' ? 'line' : 'bar', smooth: type === 'line', data: seriesData, itemStyle: { color: '#10b981', borderRadius: type === 'bar' ? [4, 4, 0, 0] : undefined }, lineStyle: type === 'line' ? { width: 3, color: '#10b981' } : undefined }]
                    };
                    chart.setOption(option);
                }
            });
        }

        function escapeHtmlDashboard(value) {
            return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
        }

        function loadPublishedScannerGraphs() {
            fetch('<?= e(base_url('api/dashboard_graphs.php')) ?>', { headers: { 'Accept': 'application/json' } })
                .then(res => {
                    if (!res.ok) throw new Error('Published graph request failed');
                    return res.json();
                })
                .then(data => renderPublishedScannerGraphs(data.graphs || []))
                .catch(() => {
                    const count = document.getElementById('publishedGraphCount');
                    const empty = document.getElementById('scannerPublishedGraphsEmpty');
                    if (count) count.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i> Unable to load published graphs';
                    if (empty) empty.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-500 text-lg mr-1"></i> Published scanner graphs could not be loaded.';
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadPublishedScannerGraphs();
            window.addEventListener('resize', () => {
                chartInstances.forEach(chart => chart?.resize?.());
            });
        });
    </script>
</body>
</html>
