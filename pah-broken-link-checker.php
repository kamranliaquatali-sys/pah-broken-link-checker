/**
 * ProArticlesHub - Broken Link Checker
 * Shortcode: [pah_broken_link_checker]
 *
 * Features:
 * - Validates homepage URL
 * - Detects sitemap from robots.txt + common sitemap locations
 * - Supports sitemap indexes
 * - Collects up to 500 same-domain pages
 * - Scans links found on those pages
 * - Deduplicates identical links
 * - Checks HTTP status
 * - Classifies Working, Redirect, Broken, Blocked, Error
 * - Internal / External detection
 * - Filters
 * - Search
 * - CSV export
 * - Batched AJAX processing to reduce timeout risk
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ---------------------------------------------------------
 * 1. SHORTCODE
 * --------------------------------------------------------- */

add_shortcode('pah_broken_link_checker', 'pah_blc_render_tool');

function pah_blc_render_tool() {
    $tool_id = 'pah-blc-' . wp_rand(1000, 999999);
    $nonce   = wp_create_nonce('pah_blc_nonce');

    ob_start();
    ?>

    <div id="<?php echo esc_attr($tool_id); ?>" class="pah-blc-wrap">
        <div class="pah-blc-card">

            <div class="pah-blc-heading">
                <div class="pah-blc-icon" aria-hidden="true"></div>

                <div>
                    <h2>Broken Link Checker</h2>
                    <p>
                        Scan up to 500 website pages and find broken, redirected,
                        internal, and external links.
                    </p>
                </div>
            </div>

            <div class="pah-blc-input-row">
                <input
                    type="url"
                    class="pah-blc-url"
                    placeholder="https://example.com"
                    autocomplete="url"
                    aria-label="Website homepage URL"
                >

                <button type="button" class="pah-blc-start">
                    Start Link Audit
                </button>
            </div>

            <div class="pah-blc-help">
                Enter the website homepage URL. The checker will try to discover
                the site's XML sitemap automatically.
            </div>

            <div class="pah-blc-message" aria-live="polite"></div>

            <div class="pah-blc-progress-wrap" style="display:none;">
                <div class="pah-blc-progress-header">
                    <span class="pah-blc-progress-text">Preparing audit...</span>
                    <strong class="pah-blc-progress-percent">0%</strong>
                </div>

                <div class="pah-blc-progress-track">
                    <div class="pah-blc-progress-bar"></div>
                </div>

                <div class="pah-blc-progress-meta">
                    <span class="pah-blc-pages-meta">Pages: 0</span>
                    <span class="pah-blc-links-meta">Links checked: 0</span>
                </div>
            </div>

            <div class="pah-blc-results" style="display:none;">

                <div class="pah-blc-summary">
                    <div class="pah-blc-stat">
                        <span class="pah-blc-stat-label">Pages Scanned</span>
                        <strong class="pah-blc-stat-pages">0</strong>
                    </div>

                    <div class="pah-blc-stat">
                        <span class="pah-blc-stat-label">Unique Links</span>
                        <strong class="pah-blc-stat-total">0</strong>
                    </div>

                    <div class="pah-blc-stat pah-blc-stat-good">
                        <span class="pah-blc-stat-label">Working</span>
                        <strong class="pah-blc-stat-working">0</strong>
                    </div>

                    <div class="pah-blc-stat pah-blc-stat-warn">
                        <span class="pah-blc-stat-label">Redirects</span>
                        <strong class="pah-blc-stat-redirects">0</strong>
                    </div>

                    <div class="pah-blc-stat pah-blc-stat-bad">
                        <span class="pah-blc-stat-label">Broken</span>
                        <strong class="pah-blc-stat-broken">0</strong>
                    </div>

                    <div class="pah-blc-stat">
                        <span class="pah-blc-stat-label">Errors / Blocked</span>
                        <strong class="pah-blc-stat-errors">0</strong>
                    </div>
                </div>

                <div class="pah-blc-toolbar">

                    <div class="pah-blc-filters">
                        <button type="button" class="pah-blc-filter active" data-filter="all">
                            All
                        </button>

                        <button type="button" class="pah-blc-filter" data-filter="broken">
                            Broken
                        </button>

                        <button type="button" class="pah-blc-filter" data-filter="redirect">
                            Redirects
                        </button>

                        <button type="button" class="pah-blc-filter" data-filter="working">
                            Working
                        </button>

                        <button type="button" class="pah-blc-filter" data-filter="internal">
                            Internal
                        </button>

                        <button type="button" class="pah-blc-filter" data-filter="external">
                            External
                        </button>

                        <button type="button" class="pah-blc-filter" data-filter="error">
                            Errors
                        </button>
                    </div>

                    <div class="pah-blc-actions">
                        <input
                            type="search"
                            class="pah-blc-search"
                            placeholder="Search results..."
                            aria-label="Search broken link report"
                        >

                        <button type="button" class="pah-blc-export">
                            Export CSV
                        </button>

                        <button type="button" class="pah-blc-reset">
                            Reset
                        </button>
                    </div>
                </div>

                <div class="pah-blc-table-wrap">
                    <table class="pah-blc-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Link</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>HTTP</th>
                                <th>Found On</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>

                        <tbody class="pah-blc-tbody"></tbody>
                    </table>
                </div>

                <div class="pah-blc-empty" style="display:none;">
                    No results match the selected filter.
                </div>

            </div>
        </div>
    </div>

    <style>
        #<?php echo esc_attr($tool_id); ?> {
            --pah-blue: #2563eb;
            --pah-blue-dark: #1d4ed8;
            --pah-green: #15803d;
            --pah-red: #dc2626;
            --pah-orange: #c2410c;
            --pah-purple: #7c3aed;
            --pah-border: #e5e7eb;
            --pah-text: #111827;
            --pah-muted: #6b7280;
            --pah-bg: #f8fafc;
            --pah-white: #ffffff;
            width: 100%;
            margin: 28px auto;
            color: var(--pah-text);
            font-family: inherit;
            box-sizing: border-box;
        }

        #<?php echo esc_attr($tool_id); ?> *,
        #<?php echo esc_attr($tool_id); ?> *::before,
        #<?php echo esc_attr($tool_id); ?> *::after {
            box-sizing: border-box;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-card {
            border: 1px solid var(--pah-border);
            background: var(--pah-white);
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 10px 35px rgba(15, 23, 42, .06);
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-heading {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-icon {
            width: 54px;
            height: 54px;
            min-width: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            background: #eff6ff;
            font-size: 25px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-heading h2 {
            margin: 0 0 5px;
            font-size: clamp(24px, 3vw, 32px);
            line-height: 1.2;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-heading p {
            margin: 0;
            color: var(--pah-muted);
            line-height: 1.6;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-input-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-url,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-search {
            width: 100%;
            min-height: 48px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 11px 14px;
            background: #fff;
            color: #111827;
            font-size: 15px;
            outline: none;
            transition: .2s ease;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-url:focus,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-search:focus {
            border-color: var(--pah-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        #<?php echo esc_attr($tool_id); ?> button {
            border: 0;
            cursor: pointer;
            font-family: inherit;
            transition: .18s ease;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-start,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-export {
            min-height: 48px;
            padding: 11px 18px;
            border-radius: 10px;
            background: var(--pah-blue);
            color: white;
            font-weight: 700;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-start:hover,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-export:hover {
            background: var(--pah-blue-dark);
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-start:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-help {
            margin-top: 9px;
            color: var(--pah-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-message {
            margin-top: 15px;
            display: none;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.55;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-message.info {
            display: block;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-message.error {
            display: block;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-message.success {
            display: block;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-progress-wrap {
            margin-top: 22px;
            padding: 18px;
            background: var(--pah-bg);
            border: 1px solid var(--pah-border);
            border-radius: 12px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-progress-header,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-progress-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-progress-header {
            margin-bottom: 10px;
            font-size: 14px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-progress-track {
            width: 100%;
            height: 11px;
            overflow: hidden;
            border-radius: 999px;
            background: #e2e8f0;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-progress-bar {
            width: 0%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #2563eb, #7c3aed);
            transition: width .25s ease;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-progress-meta {
            margin-top: 10px;
            color: var(--pah-muted);
            font-size: 13px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-results {
            margin-top: 25px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-summary {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-stat {
            border: 1px solid var(--pah-border);
            background: #fff;
            border-radius: 12px;
            padding: 15px 12px;
            text-align: center;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-stat-label {
            display: block;
            color: var(--pah-muted);
            font-size: 12px;
            margin-bottom: 6px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-stat strong {
            font-size: 22px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-stat-good strong {
            color: var(--pah-green);
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-stat-warn strong {
            color: var(--pah-orange);
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-stat-bad strong {
            color: var(--pah-red);
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-toolbar {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-filters,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-filter,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-reset {
            min-height: 39px;
            padding: 8px 12px;
            border-radius: 8px;
            background: #f1f5f9;
            color: #334155;
            font-weight: 600;
            font-size: 13px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-filter:hover,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-filter.active {
            background: #dbeafe;
            color: #1d4ed8;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-reset:hover {
            background: #e2e8f0;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-search {
            width: 210px;
            min-height: 39px;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-export {
            min-height: 39px;
            padding: 8px 13px;
            font-size: 13px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--pah-border);
            border-radius: 12px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            background: white;
            font-size: 13px;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-table th,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-table td {
            padding: 12px;
            border-bottom: 1px solid var(--pah-border);
            vertical-align: top;
            text-align: left;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-table th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            white-space: nowrap;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-table tr:last-child td {
            border-bottom: 0;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-link {
            max-width: 340px;
            overflow-wrap: anywhere;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-source {
            max-width: 250px;
            overflow-wrap: anywhere;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-link a,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-source a {
            color: #1d4ed8;
            text-decoration: none;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-link a:hover,
        #<?php echo esc_attr($tool_id); ?> .pah-blc-source a:hover {
            text-decoration: underline;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-badge {
            display: inline-block;
            white-space: nowrap;
            padding: 5px 8px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 11px;
        }

        #<?php echo esc_attr($tool_id); ?> .badge-working {
            background: #dcfce7;
            color: #166534;
        }

        #<?php echo esc_attr($tool_id); ?> .badge-broken {
            background: #fee2e2;
            color: #991b1b;
        }

        #<?php echo esc_attr($tool_id); ?> .badge-redirect {
            background: #ffedd5;
            color: #9a3412;
        }

        #<?php echo esc_attr($tool_id); ?> .badge-error {
            background: #f3e8ff;
            color: #6b21a8;
        }

        #<?php echo esc_attr($tool_id); ?> .badge-type {
            background: #e0f2fe;
            color: #075985;
        }

        #<?php echo esc_attr($tool_id); ?> .pah-blc-empty {
            padding: 20px;
            margin-top: 12px;
            border-radius: 10px;
            background: #f8fafc;
            text-align: center;
            color: var(--pah-muted);
        }

        @media (max-width: 900px) {
            #<?php echo esc_attr($tool_id); ?> .pah-blc-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            #<?php echo esc_attr($tool_id); ?> .pah-blc-card {
                padding: 18px;
                border-radius: 14px;
            }

            #<?php echo esc_attr($tool_id); ?> .pah-blc-input-row {
                grid-template-columns: 1fr;
            }

            #<?php echo esc_attr($tool_id); ?> .pah-blc-start {
                width: 100%;
            }

            #<?php echo esc_attr($tool_id); ?> .pah-blc-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #<?php echo esc_attr($tool_id); ?> .pah-blc-actions,
            #<?php echo esc_attr($tool_id); ?> .pah-blc-search {
                width: 100%;
            }

            #<?php echo esc_attr($tool_id); ?> .pah-blc-export,
            #<?php echo esc_attr($tool_id); ?> .pah-blc-reset {
                flex: 1;
            }
        }
    </style>

    <script>
    (function () {
        const root = document.getElementById('<?php echo esc_js($tool_id); ?>');
        if (!root) return;

        const ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        const nonce   = <?php echo wp_json_encode($nonce); ?>;

        const urlInput     = root.querySelector('.pah-blc-url');
        const startBtn     = root.querySelector('.pah-blc-start');
        const messageBox   = root.querySelector('.pah-blc-message');
        const progressWrap = root.querySelector('.pah-blc-progress-wrap');
        const progressBar  = root.querySelector('.pah-blc-progress-bar');
        const progressText = root.querySelector('.pah-blc-progress-text');
        const progressPct  = root.querySelector('.pah-blc-progress-percent');
        const pagesMeta    = root.querySelector('.pah-blc-pages-meta');
        const linksMeta    = root.querySelector('.pah-blc-links-meta');

        const resultsWrap  = root.querySelector('.pah-blc-results');
        const tbody        = root.querySelector('.pah-blc-tbody');
        const emptyBox     = root.querySelector('.pah-blc-empty');
        const searchInput  = root.querySelector('.pah-blc-search');
        const exportBtn    = root.querySelector('.pah-blc-export');
        const resetBtn     = root.querySelector('.pah-blc-reset');
        const filterBtns   = root.querySelectorAll('.pah-blc-filter');

        const statPages     = root.querySelector('.pah-blc-stat-pages');
        const statTotal     = root.querySelector('.pah-blc-stat-total');
        const statWorking   = root.querySelector('.pah-blc-stat-working');
        const statRedirects = root.querySelector('.pah-blc-stat-redirects');
        const statBroken    = root.querySelector('.pah-blc-stat-broken');
        const statErrors    = root.querySelector('.pah-blc-stat-errors');

        let currentFilter = 'all';
        let auditResults  = [];
        let pages         = [];
        let pageIndex     = 0;
        let discoveredLinks = {};
        let checkQueue    = [];
        let checkedCount  = 0;
        let isRunning     = false;

        function setMessage(text, type = 'info') {
            messageBox.className = 'pah-blc-message ' + type;
            messageBox.textContent = text;
        }

        function clearMessage() {
            messageBox.className = 'pah-blc-message';
            messageBox.textContent = '';
        }

        function setProgress(percent, text) {
            percent = Math.max(0, Math.min(100, percent));
            progressBar.style.width = percent + '%';
            progressPct.textContent = Math.round(percent) + '%';

            if (text) {
                progressText.textContent = text;
            }
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function postAjax(data) {
            const body = new URLSearchParams();

            Object.keys(data).forEach(key => {
                if (Array.isArray(data[key]) || typeof data[key] === 'object') {
                    body.append(key, JSON.stringify(data[key]));
                } else {
                    body.append(key, data[key]);
                }
            });

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            });

            if (!response.ok) {
                throw new Error('Server returned HTTP ' + response.status);
            }

            return response.json();
        }

        function normalizeHomepage(value) {
            value = value.trim();

            if (!value) {
                throw new Error('Please enter a website homepage URL.');
            }

            if (!/^https?:\/\//i.test(value)) {
                value = 'https://' + value;
            }

            let parsed;

            try {
                parsed = new URL(value);
            } catch (e) {
                throw new Error('Please enter a valid website URL.');
            }

            if (!['http:', 'https:'].includes(parsed.protocol)) {
                throw new Error('Only HTTP and HTTPS websites can be scanned.');
            }

            parsed.hash = '';
            parsed.search = '';

            return parsed.origin + '/';
        }

        function resetState() {
            auditResults    = [];
            pages           = [];
            pageIndex       = 0;
            discoveredLinks = {};
            checkQueue      = [];
            checkedCount    = 0;
            currentFilter   = 'all';

            tbody.innerHTML = '';

            filterBtns.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === 'all');
            });

            searchInput.value = '';

            statPages.textContent     = '0';
            statTotal.textContent     = '0';
            statWorking.textContent   = '0';
            statRedirects.textContent = '0';
            statBroken.textContent    = '0';
            statErrors.textContent    = '0';

            pagesMeta.textContent = 'Pages: 0';
            linksMeta.textContent = 'Links checked: 0';

            setProgress(0, 'Preparing audit...');
            resultsWrap.style.display = 'none';
            emptyBox.style.display    = 'none';
        }

        function classifyCounts() {
            const counts = {
                total: auditResults.length,
                working: 0,
                redirect: 0,
                broken: 0,
                error: 0
            };

            auditResults.forEach(item => {
                if (counts[item.category] !== undefined) {
                    counts[item.category]++;
                }
            });

            statTotal.textContent     = counts.total;
            statWorking.textContent   = counts.working;
            statRedirects.textContent = counts.redirect;
            statBroken.textContent    = counts.broken;
            statErrors.textContent    = counts.error;
        }

        function statusBadge(item) {
            let cls = 'badge-error';

            if (item.category === 'working') cls = 'badge-working';
            if (item.category === 'broken') cls = 'badge-broken';
            if (item.category === 'redirect') cls = 'badge-redirect';

            return '<span class="pah-blc-badge ' + cls + '">' +
                escapeHtml(item.status) +
                '</span>';
        }

        function renderResults() {
            const term = searchInput.value.trim().toLowerCase();

            const filtered = auditResults.filter(item => {
                let filterMatch = true;

                if (currentFilter === 'broken') {
                    filterMatch = item.category === 'broken';
                } else if (currentFilter === 'redirect') {
                    filterMatch = item.category === 'redirect';
                } else if (currentFilter === 'working') {
                    filterMatch = item.category === 'working';
                } else if (currentFilter === 'error') {
                    filterMatch = item.category === 'error';
                } else if (currentFilter === 'internal') {
                    filterMatch = item.type === 'Internal';
                } else if (currentFilter === 'external') {
                    filterMatch = item.type === 'External';
                }

                if (!filterMatch) return false;

                if (!term) return true;

                const haystack = [
                    item.url,
                    item.type,
                    item.status,
                    item.http_code,
                    item.source_url,
                    item.recommendation
                ].join(' ').toLowerCase();

                return haystack.includes(term);
            });

            tbody.innerHTML = '';

            filtered.forEach((item, index) => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td>${index + 1}</td>

                    <td class="pah-blc-link">
                        <a class="pah-blc-result-url"
                           target="_blank"
                           rel="noopener noreferrer">
                            ${escapeHtml(item.url)}
                        </a>
                    </td>

                    <td>
                        <span class="pah-blc-badge badge-type">
                            ${escapeHtml(item.type)}
                        </span>
                    </td>

                    <td>${statusBadge(item)}</td>

                    <td>${escapeHtml(item.http_code || '—')}</td>

                    <td class="pah-blc-source">
                        <a class="pah-blc-source-url"
                           target="_blank"
                           rel="noopener noreferrer">
                            ${escapeHtml(item.source_url)}
                        </a>
                    </td>

                    <td>${escapeHtml(item.recommendation)}</td>
                `;

                const resultLink = tr.querySelector('.pah-blc-result-url');                if (resultLink) resultLink.href = item.url;                const sourceLink = tr.querySelector('.pah-blc-source-url');                if (sourceLink) sourceLink.href = item.source_url;                tbody.appendChild(tr);
            });

            emptyBox.style.display = filtered.length ? 'none' : 'block';
            resultsWrap.style.display = 'block';

            classifyCounts();
        }

        async function initializeAudit(homepage) {
            setMessage(
                'Validating website and searching for an XML sitemap...',
                'info'
            );

            setProgress(5, 'Finding website sitemap...');

            const json = await postAjax({
                action: 'pah_blc_initialize',
                nonce: nonce,
                homepage: homepage
            });

            if (!json.success) {
                throw new Error(
                    json.data && json.data.message
                        ? json.data.message
                        : 'Unable to start the audit.'
                );
            }

            pages = json.data.pages || [];

            if (!pages.length) {
                throw new Error(
                    'No eligible pages were discovered from the website sitemap.'
                );
            }

            statPages.textContent = pages.length;
            pagesMeta.textContent = 'Pages discovered: ' + pages.length;

            setMessage(
                'Found ' + pages.length +
                ' eligible pages. Now discovering links...',
                'info'
            );

            return json.data;
        }

        async function discoverLinksFromPages() {
            const batchSize = 5;

            while (pageIndex < pages.length) {
                const batch = pages.slice(pageIndex, pageIndex + batchSize);

                const json = await postAjax({
                    action: 'pah_blc_scan_pages',
                    nonce: nonce,
                    pages: batch
                });

                if (!json.success) {
                    throw new Error(
                        json.data && json.data.message
                            ? json.data.message
                            : 'Unable to scan website pages.'
                    );
                }

                const found = json.data.links || [];

                found.forEach(link => {
                    if (!link.url) return;

                    if (!discoveredLinks[link.url]) {
                        discoveredLinks[link.url] = {
                            url: link.url,
                            type: link.type,
                            source_url: link.source_url
                        };
                    }
                });

                pageIndex += batch.length;

                const phaseProgress =
                    10 + ((pageIndex / pages.length) * 40);

                setProgress(
                    phaseProgress,
                    'Discovering links: ' +
                    Math.min(pageIndex, pages.length) +
                    ' / ' + pages.length + ' pages'
                );

                pagesMeta.textContent =
                    'Pages scanned: ' +
                    Math.min(pageIndex, pages.length) +
                    ' / ' + pages.length;

                linksMeta.textContent =
                    'Unique links found: ' +
                    Object.keys(discoveredLinks).length;
            }

            checkQueue = Object.values(discoveredLinks);

            if (!checkQueue.length) {
                throw new Error(
                    'No HTTP or HTTPS links were discovered on the scanned pages.'
                );
            }
        }

        async function checkDiscoveredLinks() {
            const batchSize = 8;

            while (checkedCount < checkQueue.length) {
                const batch =
                    checkQueue.slice(checkedCount, checkedCount + batchSize);

                const json = await postAjax({
                    action: 'pah_blc_check_links',
                    nonce: nonce,
                    links: batch
                });

                if (!json.success) {
                    throw new Error(
                        json.data && json.data.message
                            ? json.data.message
                            : 'Unable to check discovered links.'
                    );
                }

                const results = json.data.results || [];

                results.forEach(item => {
                    auditResults.push(item);
                });

                checkedCount += batch.length;

                const phaseProgress =
                    50 + ((checkedCount / checkQueue.length) * 50);

                setProgress(
                    phaseProgress,
                    'Checking links: ' +
                    Math.min(checkedCount, checkQueue.length) +
                    ' / ' + checkQueue.length
                );

                linksMeta.textContent =
                    'Links checked: ' +
                    Math.min(checkedCount, checkQueue.length) +
                    ' / ' + checkQueue.length;

                renderResults();
            }
        }

        async function runAudit() {
            if (isRunning) return;

            clearMessage();
            resetState();

            let homepage;

            try {
                homepage = normalizeHomepage(urlInput.value);
            } catch (error) {
                setMessage(error.message, 'error');
                return;
            }

            isRunning = true;
            startBtn.disabled = true;
            startBtn.textContent = 'Scanning...';
            progressWrap.style.display = 'block';

            try {
                await initializeAudit(homepage);
                await discoverLinksFromPages();

                setMessage(
                    'Link discovery completed. Checking HTTP status codes...',
                    'info'
                );

                await checkDiscoveredLinks();

                setProgress(100, 'Audit complete');

                renderResults();

                const brokenCount =
                    auditResults.filter(x => x.category === 'broken').length;

                const redirectCount =
                    auditResults.filter(x => x.category === 'redirect').length;

                setMessage(
                    'Audit complete. Checked ' +
                    auditResults.length +
                    ' unique links. Found ' +
                    brokenCount +
                    ' broken links and ' +
                    redirectCount +
                    ' redirects.',
                    'success'
                );

            } catch (error) {
                setMessage(
                    error && error.message
                        ? error.message
                        : 'The audit could not be completed.',
                    'error'
                );

                progressText.textContent = 'Audit stopped';

            } finally {
                isRunning = false;
                startBtn.disabled = false;
                startBtn.textContent = 'Start Link Audit';
            }
        }

        function exportCsv() {
            if (!auditResults.length) {
                setMessage(
                    'Run an audit before exporting the report.',
                    'error'
                );
                return;
            }

            const rows = [
                [
                    'Link',
                    'Type',
                    'Status',
                    'HTTP Code',
                    'Found On',
                    'Recommendation'
                ]
            ];

            auditResults.forEach(item => {
                rows.push([
                    item.url,
                    item.type,
                    item.status,
                    item.http_code,
                    item.source_url,
                    item.recommendation
                ]);
            });

            const csv = rows.map(row =>
                row.map(value => {
                    const safe = String(value ?? '').replace(/"/g, '""');
                    return '"' + safe + '"';
                }).join(',')
            ).join('\r\n');

            const blob = new Blob(
                ['\uFEFF' + csv],
                { type: 'text/csv;charset=utf-8;' }
            );

            const link = document.createElement('a');
            const blobUrl = URL.createObjectURL(blob);

            link.href = blobUrl;
            link.download = 'broken-link-audit.csv';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            setTimeout(() => {
                URL.revokeObjectURL(blobUrl);
            }, 1000);
        }

        startBtn.addEventListener('click', runAudit);

        urlInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                runAudit();
            }
        });

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                currentFilter = this.dataset.filter || 'all';

                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                renderResults();
            });
        });

        searchInput.addEventListener('input', renderResults);
        exportBtn.addEventListener('click', exportCsv);

        resetBtn.addEventListener('click', function () {
            if (isRunning) return;

            resetState();
            clearMessage();
            progressWrap.style.display = 'none';
            resultsWrap.style.display = 'none';
            urlInput.value = '';
            urlInput.focus();
        });

    })();
    </script>

    <?php

    return ob_get_clean();
}


/* ---------------------------------------------------------
 * 2. AJAX ACTIONS
 * --------------------------------------------------------- */

add_action('wp_ajax_pah_blc_initialize', 'pah_blc_ajax_initialize');
add_action('wp_ajax_nopriv_pah_blc_initialize', 'pah_blc_ajax_initialize');

add_action('wp_ajax_pah_blc_scan_pages', 'pah_blc_ajax_scan_pages');
add_action('wp_ajax_nopriv_pah_blc_scan_pages', 'pah_blc_ajax_scan_pages');

add_action('wp_ajax_pah_blc_check_links', 'pah_blc_ajax_check_links');
add_action('wp_ajax_nopriv_pah_blc_check_links', 'pah_blc_ajax_check_links');


/* ---------------------------------------------------------
 * 3. NONCE CHECK
 * --------------------------------------------------------- */

function pah_blc_verify_nonce() {
    if (
        empty($_POST['nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['nonce'])),
            'pah_blc_nonce'
        )
    ) {
        wp_send_json_error(
            array('message' => 'Security validation failed. Please refresh the page and try again.'),
            403
        );
    }
}


/* ---------------------------------------------------------
 * 4. INITIALIZE AUDIT
 * --------------------------------------------------------- */

function pah_blc_ajax_initialize() {
    pah_blc_verify_nonce();

    $homepage = isset($_POST['homepage'])
        ? esc_url_raw(wp_unslash($_POST['homepage']))
        : '';

    if (!$homepage) {
        wp_send_json_error(
            array('message' => 'Please enter a valid website homepage URL.')
        );
    }

    $normalized = pah_blc_normalize_homepage($homepage);

    if (is_wp_error($normalized)) {
        wp_send_json_error(
            array('message' => $normalized->get_error_message())
        );
    }

    /*
     * Verify that the homepage can be accessed.
     */
    $response = pah_blc_safe_request($normalized, true);

    if (is_wp_error($response)) {
        wp_send_json_error(
            array(
                'message' =>
                    'The website could not be reached: ' .
                    $response->get_error_message()
            )
        );
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code >= 400 || $code === 0) {
        wp_send_json_error(
            array(
                'message' =>
                    'The website returned HTTP ' . intval($code) .
                    '. Please verify the homepage URL.'
            )
        );
    }

    $sitemap_urls = pah_blc_find_sitemaps($normalized);

    if (!$sitemap_urls) {
        wp_send_json_error(
            array(
                'message' =>
                    'No readable XML sitemap could be found. ' .
                    'Make sure the website has a public sitemap.'
            )
        );
    }

    $pages = pah_blc_collect_pages_from_sitemaps(
        $sitemap_urls,
        $normalized,
        500
    );

    if (!$pages) {
        wp_send_json_error(
            array(
                'message' =>
                    'The sitemap was found, but no eligible same-domain page URLs were discovered.'
            )
        );
    }

    wp_send_json_success(
        array(
            'homepage' => $normalized,
            'sitemaps' => $sitemap_urls,
            'pages'    => array_values($pages),
            'count'    => count($pages),
        )
    );
}


/* ---------------------------------------------------------
 * 5. SCAN PAGE BATCH FOR LINKS
 * --------------------------------------------------------- */

function pah_blc_ajax_scan_pages() {
    pah_blc_verify_nonce();

    $raw_pages = isset($_POST['pages'])
        ? wp_unslash($_POST['pages'])
        : '';

    $pages = json_decode($raw_pages, true);

    if (!is_array($pages) || !$pages) {
        wp_send_json_error(
            array('message' => 'No pages were supplied for scanning.')
        );
    }

    /*
     * Keep each AJAX request intentionally small.
     */
    $pages = array_slice($pages, 0, 10);

    $links = array();

    foreach ($pages as $page_url) {
        $page_url = esc_url_raw($page_url);

        if (!$page_url) {
            continue;
        }

        $response = pah_blc_safe_request($page_url, false);

        if (is_wp_error($response)) {
            continue;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 400) {
            continue;
        }

        $body = wp_remote_retrieve_body($response);

        if (!$body) {
            continue;
        }

        $found = pah_blc_extract_links($body, $page_url);

        foreach ($found as $item) {
            $links[] = $item;
        }
    }

    wp_send_json_success(
        array(
            'links' => $links,
        )
    );
}


/* ---------------------------------------------------------
 * 6. CHECK LINK BATCH
 * --------------------------------------------------------- */

function pah_blc_ajax_check_links() {
    pah_blc_verify_nonce();

    $raw_links = isset($_POST['links'])
        ? wp_unslash($_POST['links'])
        : '';

    $links = json_decode($raw_links, true);

    if (!is_array($links) || !$links) {
        wp_send_json_error(
            array('message' => 'No links were supplied for checking.')
        );
    }

    $links = array_slice($links, 0, 15);

    $results = array();

    foreach ($links as $item) {
        if (empty($item['url'])) {
            continue;
        }

        $url = esc_url_raw($item['url']);

        if (!$url) {
            continue;
        }

        $type = !empty($item['type'])
            ? sanitize_text_field($item['type'])
            : 'External';

        $source_url = !empty($item['source_url'])
            ? esc_url_raw($item['source_url'])
            : '';

        $status = pah_blc_check_single_url($url);

        $results[] = array(
            'url'            => $url,
            'type'           => $type,
            'source_url'     => $source_url,
            'status'         => $status['status'],
            'category'       => $status['category'],
            'http_code'      => $status['http_code'],
            'recommendation' => $status['recommendation'],
        );
    }

    wp_send_json_success(
        array(
            'results' => $results,
        )
    );
}


/* ---------------------------------------------------------
 * 7. NORMALIZE HOMEPAGE
 * --------------------------------------------------------- */

function pah_blc_normalize_homepage($url) {
    $url = trim($url);

    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }

    $parts = wp_parse_url($url);

    if (
        !$parts ||
        empty($parts['scheme']) ||
        empty($parts['host'])
    ) {
        return new WP_Error(
            'invalid_url',
            'Please enter a valid website URL.'
        );
    }

    if (!in_array(strtolower($parts['scheme']), array('http', 'https'), true)) {
        return new WP_Error(
            'invalid_protocol',
            'Only HTTP and HTTPS websites can be scanned.'
        );
    }

    $host = strtolower($parts['host']);

    /*
     * Prevent direct private/local host scanning.
     */
    if (pah_blc_is_private_host($host)) {
        return new WP_Error(
            'blocked_host',
            'Private, local, or reserved network addresses cannot be scanned.'
        );
    }

    $port = !empty($parts['port'])
        ? ':' . intval($parts['port'])
        : '';

    return strtolower($parts['scheme']) . '://' . $host . $port . '/';
}


/* ---------------------------------------------------------
 * 8. PRIVATE / LOCAL HOST BLOCKING
 * --------------------------------------------------------- */

function pah_blc_is_private_host($host) {
    $host = strtolower(trim($host));

    if (
        $host === 'localhost' ||
        str_ends_with($host, '.localhost') ||
        str_ends_with($host, '.local')
    ) {
        return true;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return !filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    return false;
}


/* ---------------------------------------------------------
 * 9. SAFE HTTP REQUEST
 * --------------------------------------------------------- */

function pah_blc_safe_request($url, $prefer_head = false) {
    $args = array(
        'timeout'     => 10,
        'redirection' => 5,
        'user-agent'  => 'ProArticlesHub Broken Link Checker/1.0',
        'headers'     => array(
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ),
    );

    /*
     * HEAD is faster for status-only requests.
     */
    if ($prefer_head) {
        $response = wp_safe_remote_head($url, $args);

        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);

            /*
             * Some servers reject HEAD even though GET works.
             */
            if (!in_array($code, array(0, 403, 405, 501), true)) {
                return $response;
            }
        }
    }

    $args['limit_response_size'] = 2 * 1024 * 1024;

    return wp_safe_remote_get($url, $args);
}


/* ---------------------------------------------------------
 * 10. FIND SITEMAPS
 * --------------------------------------------------------- */

function pah_blc_find_sitemaps($homepage) {
    $candidates = array();

    /*
     * First inspect robots.txt.
     */
    $robots_url = trailingslashit($homepage) . 'robots.txt';
    $robots     = pah_blc_safe_request($robots_url, false);

    if (!is_wp_error($robots)) {
        $body = wp_remote_retrieve_body($robots);

        if ($body) {
            preg_match_all(
                '/^\s*Sitemap:\s*(https?:\/\/[^\s]+)\s*$/im',
                $body,
                $matches
            );

            if (!empty($matches[1])) {
                foreach ($matches[1] as $sitemap) {
                    $candidates[] = esc_url_raw(trim($sitemap));
                }
            }
        }
    }

    /*
     * Common WordPress / generic sitemap locations.
     */
    $common = array(
        'sitemap_index.xml',
        'sitemap.xml',
        'wp-sitemap.xml',
    );

    foreach ($common as $path) {
        $candidates[] = trailingslashit($homepage) . $path;
    }

    $candidates = array_values(
        array_unique(
            array_filter($candidates)
        )
    );

    $valid = array();

    foreach ($candidates as $candidate) {
        if (!pah_blc_same_domain($homepage, $candidate)) {
            continue;
        }

        $response = pah_blc_safe_request($candidate, false);

        if (is_wp_error($response)) {
            continue;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = trim(wp_remote_retrieve_body($response));

        if ($code >= 200 && $code < 400 && $body) {
            if (
                stripos($body, '<urlset') !== false ||
                stripos($body, '<sitemapindex') !== false
            ) {
                $valid[] = $candidate;
            }
        }
    }

    return array_values(array_unique($valid));
}


/* ---------------------------------------------------------
 * 11. COLLECT URLS FROM SITEMAPS
 * --------------------------------------------------------- */

function pah_blc_collect_pages_from_sitemaps(
    $sitemap_urls,
    $homepage,
    $limit = 500
) {
    $pages   = array();
    $visited = array();
    $queue   = $sitemap_urls;

    /*
     * Prevent pathological sitemap nesting.
     */
    $max_sitemaps = 100;

    while (
        $queue &&
        count($pages) < $limit &&
        count($visited) < $max_sitemaps
    ) {
        $sitemap_url = array_shift($queue);

        if (
            !$sitemap_url ||
            isset($visited[$sitemap_url])
        ) {
            continue;
        }

        $visited[$sitemap_url] = true;

        if (!pah_blc_same_domain($homepage, $sitemap_url)) {
            continue;
        }

        $response = pah_blc_safe_request($sitemap_url, false);

        if (is_wp_error($response)) {
            continue;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 400) {
            continue;
        }

        $xml = wp_remote_retrieve_body($response);

        if (!$xml) {
            continue;
        }

        $locations = pah_blc_extract_xml_locations($xml);

        if (!$locations) {
            continue;
        }

        $is_index = stripos($xml, '<sitemapindex') !== false;

        foreach ($locations as $location) {
            $location = esc_url_raw($location);

            if (!$location) {
                continue;
            }

            if (!pah_blc_same_domain($homepage, $location)) {
                continue;
            }

            if ($is_index) {
                if (
                    !isset($visited[$location]) &&
                    count($visited) + count($queue) < $max_sitemaps
                ) {
                    $queue[] = $location;
                }
            } else {
                /*
                 * Ignore obvious media/file URLs in page list.
                 */
                if (pah_blc_is_probable_html_page($location)) {
                    $pages[$location] = $location;
                }

                if (count($pages) >= $limit) {
                    break;
                }
            }
        }
    }

    return array_slice(
        array_values($pages),
        0,
        $limit
    );
}


/* ---------------------------------------------------------
 * 12. EXTRACT <loc> VALUES FROM XML
 * --------------------------------------------------------- */

function pah_blc_extract_xml_locations($xml) {
    $locations = array();

    if (
        function_exists('simplexml_load_string')
    ) {
        libxml_use_internal_errors(true);

        $object = simplexml_load_string($xml);

        if ($object !== false) {
            $nodes = $object->xpath('//*[local-name()="loc"]');

            if ($nodes) {
                foreach ($nodes as $node) {
                    $value = trim((string) $node);

                    if ($value) {
                        $locations[] = html_entity_decode(
                            $value,
                            ENT_QUOTES | ENT_XML1,
                            'UTF-8'
                        );
                    }
                }
            }
        }

        libxml_clear_errors();
    }

    /*
     * Regex fallback if SimpleXML is unavailable.
     */
    if (!$locations) {
        preg_match_all(
            '#<loc>\s*(.*?)\s*</loc>#is',
            $xml,
            $matches
        );

        if (!empty($matches[1])) {
            foreach ($matches[1] as $value) {
                $locations[] = html_entity_decode(
                    strip_tags(trim($value)),
                    ENT_QUOTES | ENT_XML1,
                    'UTF-8'
                );
            }
        }
    }

    return array_values(
        array_unique(
            array_filter($locations)
        )
    );
}


/* ---------------------------------------------------------
 * 13. PROBABLE HTML PAGE
 * --------------------------------------------------------- */

function pah_blc_is_probable_html_page($url) {
    $path = strtolower(
        (string) wp_parse_url($url, PHP_URL_PATH)
    );

    /*
     * Exclude obvious non-HTML files.
     */
    $extensions = array(
        'jpg','jpeg','png','gif','webp','avif','svg',
        'pdf','zip','rar','7z',
        'mp3','wav','ogg','mp4','mov','avi','webm',
        'doc','docx','xls','xlsx','ppt','pptx',
        'css','js','json','xml','txt'
    );

    $extension = pathinfo($path, PATHINFO_EXTENSION);

    if ($extension && in_array($extension, $extensions, true)) {
        return false;
    }

    return true;
}


/* ---------------------------------------------------------
 * 14. EXTRACT LINKS FROM HTML
 * --------------------------------------------------------- */

function pah_blc_extract_links($html, $source_url) {
    $links = array();

    if (!$html || !$source_url) {
        return $links;
    }

    $source_host = strtolower(
        (string) wp_parse_url($source_url, PHP_URL_HOST)
    );

    /*
     * DOMDocument is much safer than regex for anchors.
     */
    if (class_exists('DOMDocument')) {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_NOWARNING | LIBXML_NOERROR
        );

        if ($loaded) {
            $anchors = $dom->getElementsByTagName('a');

            foreach ($anchors as $anchor) {
                $href = trim($anchor->getAttribute('href'));

                $absolute = pah_blc_resolve_url(
                    $href,
                    $source_url
                );

                if (!$absolute) {
                    continue;
                }

                $target_host = strtolower(
                    (string) wp_parse_url(
                        $absolute,
                        PHP_URL_HOST
                    )
                );

                $links[] = array(
                    'url'        => $absolute,
                    'type'       => pah_blc_hosts_equivalent(
                        $source_host,
                        $target_host
                    ) ? 'Internal' : 'External',
                    'source_url' => $source_url,
                );
            }
        }

        libxml_clear_errors();
    }

    return $links;
}


/* ---------------------------------------------------------
 * 15. RESOLVE RELATIVE URL
 * --------------------------------------------------------- */

function pah_blc_resolve_url($href, $base_url) {
    $href = trim(html_entity_decode($href, ENT_QUOTES, 'UTF-8'));

    if (!$href) {
        return false;
    }

    /*
     * Ignore links that are not actual HTTP resources.
     */
    if (
        str_starts_with($href, '#') ||
        preg_match(
            '#^(mailto:|tel:|javascript:|data:|sms:|whatsapp:)#i',
            $href
        )
    ) {
        return false;
    }

    /*
     * Protocol-relative.
     */
    if (str_starts_with($href, '//')) {
        $scheme = wp_parse_url(
            $base_url,
            PHP_URL_SCHEME
        );

        $href = $scheme . ':' . $href;
    }

    /*
     * Already absolute.
     */
    if (preg_match('#^https?://#i', $href)) {
        $absolute = $href;
    } else {
        $base = wp_parse_url($base_url);

        if (
            !$base ||
            empty($base['scheme']) ||
            empty($base['host'])
        ) {
            return false;
        }

        $origin =
            $base['scheme'] .
            '://' .
            $base['host'] .
            (!empty($base['port']) ? ':' . $base['port'] : '');

        if (str_starts_with($href, '/')) {
            $absolute = $origin . $href;
        } else {
            $base_path = !empty($base['path'])
                ? $base['path']
                : '/';

            $directory = preg_replace(
                '#/[^/]*$#',
                '/',
                $base_path
            );

            $absolute =
                $origin .
                $directory .
                $href;
        }
    }

    /*
     * Remove fragment: HTTP request does not send it.
     */
    $parts = wp_parse_url($absolute);

    if (
        !$parts ||
        empty($parts['scheme']) ||
        empty($parts['host'])
    ) {
        return false;
    }

    if (!in_array(strtolower($parts['scheme']), array('http','https'), true)) {
        return false;
    }

    if (pah_blc_is_private_host($parts['host'])) {
        return false;
    }

    $path = isset($parts['path'])
        ? pah_blc_normalize_path($parts['path'])
        : '/';

    $rebuilt =
        strtolower($parts['scheme']) .
        '://' .
        strtolower($parts['host']);

    if (!empty($parts['port'])) {
        $rebuilt .= ':' . intval($parts['port']);
    }

    $rebuilt .= $path;

    if (!empty($parts['query'])) {
        $rebuilt .= '?' . $parts['query'];
    }

    return esc_url_raw($rebuilt);
}


/* ---------------------------------------------------------
 * 16. NORMALIZE URL PATH
 * --------------------------------------------------------- */

function pah_blc_normalize_path($path) {
    $segments = explode('/', $path);
    $output   = array();

    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }

        if ($segment === '..') {
            array_pop($output);
            continue;
        }

        $output[] = $segment;
    }

    return '/' . implode('/', $output) .
        (str_ends_with($path, '/') ? '/' : '');
}


/* ---------------------------------------------------------
 * 17. CHECK SINGLE URL
 * --------------------------------------------------------- */

function pah_blc_check_single_url($url) {
    $args = array(
        'timeout'     => 10,
        'redirection' => 0,
        'user-agent'  => 'ProArticlesHub Broken Link Checker/1.0',
    );

    /*
     * Try HEAD first.
     */
    $response = wp_safe_remote_head($url, $args);

    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);

        /*
         * Some sites reject HEAD but support GET.
         */
        if (in_array($code, array(0, 403, 405, 501), true)) {
            $response = new WP_Error(
                'head_failed',
                'HEAD not reliable'
            );
        }
    }

    /*
     * Fallback GET.
     */
    if (is_wp_error($response)) {
        $response = wp_safe_remote_get(
            $url,
            array(
                'timeout'             => 10,
                'redirection'         => 0,
                'limit_response_size' => 1024,
                'user-agent'          => 'ProArticlesHub Broken Link Checker/1.0',
            )
        );
    }

    if (is_wp_error($response)) {
        return array(
            'status'         => 'Scan Error',
            'category'       => 'error',
            'http_code'      => '',
            'recommendation' =>
                'Review the link manually. The remote server may be unavailable, blocking automated requests, or timing out.',
        );
    }

    $code = intval(
        wp_remote_retrieve_response_code($response)
    );

    if ($code >= 200 && $code < 300) {
        return array(
            'status'         => 'Working',
            'category'       => 'working',
            'http_code'      => $code,
            'recommendation' =>
                'No immediate action is required.',
        );
    }

    if ($code >= 300 && $code < 400) {
        return array(
            'status'         => 'Redirect',
            'category'       => 'redirect',
            'http_code'      => $code,
            'recommendation' =>
                'Review the redirect and update the original link to the final destination when appropriate.',
        );
    }

    if ($code === 401 || $code === 403 || $code === 429) {
        return array(
            'status'         => 'Blocked / Restricted',
            'category'       => 'error',
            'http_code'      => $code,
            'recommendation' =>
                'Verify manually. The destination may require authentication, rate-limit requests, or block automated scanners.',
        );
    }

    if ($code >= 400 && $code < 600) {
        return array(
            'status'         => 'Broken',
            'category'       => 'broken',
            'http_code'      => $code,
            'recommendation' =>
                'Update, replace, redirect, or remove the broken destination after verifying the affected page.',
        );
    }

    return array(
        'status'         => 'Unknown',
        'category'       => 'error',
        'http_code'      => $code ?: '',
        'recommendation' =>
            'Check the destination manually because a reliable HTTP status could not be determined.',
    );
}


/* ---------------------------------------------------------
 * 18. SAME DOMAIN HELPERS
 * --------------------------------------------------------- */

function pah_blc_same_domain($url_a, $url_b) {
    $host_a = strtolower(
        (string) wp_parse_url($url_a, PHP_URL_HOST)
    );

    $host_b = strtolower(
        (string) wp_parse_url($url_b, PHP_URL_HOST)
    );

    return pah_blc_hosts_equivalent(
        $host_a,
        $host_b
    );
}

function pah_blc_hosts_equivalent($host_a, $host_b) {
    $host_a = preg_replace('/^www\./i', '', strtolower($host_a));
    $host_b = preg_replace('/^www\./i', '', strtolower($host_b));

    return $host_a !== '' && $host_a === $host_b;
}
