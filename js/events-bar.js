(function () {
    var config = window.eventsBarConfig || null;
    if (!config || !config.endpoint) {
        return;
    }

    var container = document.querySelector('[data-events-bar]');
    if (!container) {
        return;
    }

    var listNode = container.querySelector('[data-events-bar-items]');
    if (!listNode) {
        return;
    }

    var filterContainer = container.querySelector('[data-events-bar-filters]');
    var scoreFilterContainer = container.querySelector('[data-events-bar-score-filter]');
    var statusClasses = {
        Online: 'events-status-online',
        Offline: 'events-status-offline',
        Drop: 'events-status-drop',
        Respond: 'events-status-online'
    };

    function displayStatusText(status) {
        if (status === 'Online' || status === 'Respond') {
            return 'Response';
        }
        if (status === 'Offline' || status === 'Drop') {
            return 'Dropped';
        }
        return status || '';
    }

    var crcTable = createCrcTable();
    var pollInterval = typeof config.pollInterval === 'number' ? config.pollInterval : 10000;
    var staleMinutes = typeof config.staleMinutes === 'number' && config.staleMinutes > 0 ? config.staleMinutes : 1;
    var pollTimer = null;
    var externallyPaused = false;
    var fetchController = null;
    var currentRequestId = 0;
    var state = {
        filter: resolveDefaultFilter(config.defaultFilter),
        items: [],
        minScore: resolveDefaultMinScore(config.defaultMinScore)
    };

    var lastSuccessAt = Date.now();
    var staleBannerEl = null;
    var staleApplied = false;

    function createCrcTable() {
        var table = [];
        var c;
        for (var n = 0; n < 256; n += 1) {
            c = n;
            for (var k = 0; k < 8; k += 1) {
                if (c & 1) {
                    c = 0xEDB88320 ^ (c >>> 1);
                } else {
                    c = c >>> 1;
                }
            }
            table[n] = c >>> 0;
        }
        return table;
    }

    function crc32(str) {
        var crc = -1;
        for (var i = 0; i < str.length; i += 1) {
            var code = str.charCodeAt(i);
            crc = (crc >>> 8) ^ crcTable[(crc ^ code) & 0xff];
        }
        return (crc ^ -1) >>> 0;
    }

    function nodeColors(ip) {
        if (!ip) {
            return {
                background: 'hsl(0, 0%, 98%)',
                border: 'hsl(0, 0%, 88%)'
            };
        }
        var hue = crc32(ip) % 360;
        return {
            background: 'hsl(' + hue + ', 65%, 95%)',
            border: 'hsl(' + hue + ', 45%, 78%)'
        };
    }

    function buildUrl(filterOverride) {
        var params = [];
        if (config.groupId) {
            params.push('group=' + encodeURIComponent(config.groupId));
        }
        var limitValue = parseInt(config.limit, 10);
        if (!isNaN(limitValue) && limitValue > 0) {
            params.push('limit=' + encodeURIComponent(limitValue));
        }
        var filterValue = filterOverride || state.filter;
        params.push('filter=' + encodeURIComponent(filterValue));
        var minScoreValue = parseInt(state.minScore, 10);
        if (!isNaN(minScoreValue)) {
            params.push('min_score=' + encodeURIComponent(minScoreValue));
        }
        if (config.strict === true) {
            params.push('strict=1');
        }
        if (params.length === 0) {
            return config.endpoint;
        }
        var separator = config.endpoint.indexOf('?') === -1 ? '?' : '&';
        return config.endpoint + separator + params.join('&');
    }

    function formatDateTime(datetime) {
        if (!datetime) {
            return '';
        }
        var parts = datetime.split(' ');
        if (parts.length < 2) {
            return datetime;
        }
        var timePart = parts[1].split('.')[0];
        if (timePart.length >= 8) {
            timePart = timePart.slice(0, 8);
        }
        return parts[0] + ' ' + timePart;
    }

    function formatProgress(progress) {
        if (progress === null || progress === undefined || progress === '') {
            return '-';
        }
        return progress;
    }

    function shouldHighlightRow(progress) {
        if (progress === null || progress === undefined || progress === '') {
            return false;
        }
        return String(progress).replace(/\s+/g, '') !== '1/10';
    }

    function render(items) {
        listNode.innerHTML = '';

        if (!items || items.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'events-empty';
            if (state.filter === 'onePlus') {
                empty.textContent = 'No events recorded in the last 24 hours.';
            } else {
                empty.textContent = 'No events match the selected filter.';
            }
            listNode.appendChild(empty);
            return;
        }

        for (var i = 0; i < items.length; i += 1) {
            listNode.appendChild(createItem(items[i]));
        }
    }

    function resolveDefaultFilter(filter) {
        if (filter === 'onePlus' || filter === 'twoPlus' || filter === 'tenPlus') {
            return filter;
        }
        return 'twoPlus';
    }

    function updateFilterButtons() {
        if (!filterContainer) {
            return;
        }

        var buttons = filterContainer.querySelectorAll('[data-filter]');
        for (var i = 0; i < buttons.length; i += 1) {
            var button = buttons[i];
            if (button.getAttribute('data-filter') === state.filter) {
                button.classList.add('events-bar-filter-active');
            } else {
                button.classList.remove('events-bar-filter-active');
            }
        }
    }

    function updateScoreButtons() {
        if (!scoreFilterContainer) {
            return;
        }
        var buttons = scoreFilterContainer.querySelectorAll('[data-min-score]');
        for (var i = 0; i < buttons.length; i += 1) {
            var button = buttons[i];
            var val = parseInt(button.getAttribute('data-min-score'), 10) || 0;
            if (val === (parseInt(state.minScore, 10) || 0)) {
                button.classList.add('events-bar-filter-active');
            } else {
                button.classList.remove('events-bar-filter-active');
            }
        }
    }

    function bindFilterEvents() {
        if (!filterContainer) {
            return;
        }

        filterContainer.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || !target.hasAttribute('data-filter')) {
                return;
            }
            var nextFilter = target.getAttribute('data-filter');
            if (!nextFilter || nextFilter === state.filter) {
                return;
            }
            state.filter = resolveDefaultFilter(nextFilter);
            updateFilterButtons();
            showLoading();
            load();
        });

        updateFilterButtons();
    }

    function bindScoreFilterEvents() {
        if (!scoreFilterContainer) {
            return;
        }
        scoreFilterContainer.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || !target.hasAttribute('data-min-score')) {
                return;
            }
            var next = target.getAttribute('data-min-score');
            var nextVal = parseInt(next, 10);
            if (isNaN(nextVal)) {
                nextVal = 0;
            }
            if (nextVal === (parseInt(state.minScore, 10) || 0)) {
                return;
            }
            state.minScore = nextVal;
            updateScoreButtons();
            showLoading();
            load();
        });
        updateScoreButtons();
    }

    bindFilterEvents();
    bindScoreFilterEvents();

    function createItem(item) {
        var node = document.createElement('div');
        node.className = 'events-item';

        if (shouldHighlightRow(item.progress)) {
            node.classList.add('events-strong');
        }

        var colors = nodeColors(item.ip);
        node.style.backgroundColor = colors.background;
        node.style.borderColor = colors.border;

        var time = document.createElement('span');
        time.className = 'events-time';
        time.textContent = formatDateTime(item.datetime);
        node.appendChild(time);

        var progress = document.createElement('span');
        progress.className = 'events-progress';
        progress.textContent = formatProgress(item.progress);
        if (item.email_sent) {
            progress.title = item.email_sent;
        }
        // Highlight 'Status confirmed' messages in progress cell with Online/Offline colors
        var progressLower = String(progress.textContent || '').toLowerCase();
        var emailLower = String(item.email_sent || '').toLowerCase();
        if (progressLower.indexOf('status confirmed') !== -1 || emailLower.indexOf('status confirmed') !== -1) {
            progress.classList.add('events-progress-confirmed');
            if (item.status === 'Online' || item.status === 'Respond') {
                progress.classList.add('events-progress-confirmed-online');
            } else if (item.status === 'Offline') {
                progress.classList.add('events-progress-confirmed-offline');
            }
        }
        node.appendChild(progress);

        var labelText = item.note || item.ip;
        var label;
        if (item.node_id) {
            label = document.createElement('a');
            label.className = 'events-ip events-ip-link';
            label.href = '/tools/report/' + item.node_id;
            label.textContent = labelText;
        } else {
            label = document.createElement('span');
            label.className = 'events-ip';
            label.textContent = labelText;
        }
        node.appendChild(label);

        var badge = document.createElement('span');
        var statusClass = statusClasses[item.status] || 'events-status-drop';
        badge.className = 'events-badge ' + statusClass;
        badge.textContent = displayStatusText(item.status);
        node.appendChild(badge);

        return node;
    }

    function handleError() {
        listNode.innerHTML = '';
        var error = document.createElement('div');
        error.className = 'events-empty';
        error.textContent = 'Unable to load events.';
        listNode.appendChild(error);
    }

    function showAuthRequired() {
        listNode.innerHTML = '';
        var note = document.createElement('div');
        note.className = 'events-empty';
        var link = document.createElement('a');
        link.href = '/auth/user/login';
        link.textContent = 'sign in';
        // Build: 'Session expired - refresh the page or sign in.'
        note.appendChild(document.createTextNode('Session expired - refresh the page or '));
        note.appendChild(link);
        note.appendChild(document.createTextNode('.'));
        listNode.appendChild(note);
    }

    function load() {
        if (externallyPaused) {
            showLoading();
            return;
        }
        var requestFilter = state.filter;
        if (fetchController && typeof fetchController.abort === 'function') {
            try { fetchController.abort(); } catch (e) {}
        }
        var supportsAbort = (typeof AbortController !== 'undefined');
        fetchController = supportsAbort ? new AbortController() : null;
        var myRequestId = ++currentRequestId;
        var fetchOptions = {
            credentials: 'same-origin',
            cache: 'no-store'
        };
        if (fetchController && fetchController.signal) {
            fetchOptions.signal = fetchController.signal;
        }
        fetch(buildUrl(requestFilter), fetchOptions)
            .then(function (response) {
                if (response && response.status === 401) {
                    showAuthRequired();
                    throw { name: 'AuthError' };
                }
                if (!response.ok) {
                    throw new Error('Bad response');
                }
                return response.json();
            })
            .then(function (payload) {
                if (externallyPaused) {
                    return; // Discard results while paused
                }
                if (myRequestId !== currentRequestId) {
                    return; // Outdated response
                }
                if (state.filter !== requestFilter) {
                    return;
                }
                state.items = Array.isArray(payload) ? payload : [];
                lastSuccessAt = Date.now();
                render(state.items);
                checkStaleness();
            })
            .catch(function (err) {
                if (externallyPaused) {
                    return; // Ignore errors during pause
                }
                // Ignore errors from aborted or superseded requests
                if (typeof myRequestId !== 'undefined' && myRequestId !== currentRequestId) {
                    return;
                }
                if (state.filter !== requestFilter) {
                    return;
                }
                if (err && (err.name === 'AbortError' || err.code === 20)) {
                    return;
                }
                if (err && err.name === 'AuthError') {
                    return;
                }
                state.items = [];
                handleError();
                checkStaleness();
            });
    }

    function startPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        if (externallyPaused) {
            return;
        }
        pollTimer = setInterval(load, pollInterval);
        // Also start a 1s watchdog tick to assess staleness
        if (typeof window !== 'undefined') {
            if (window.__eventsBarStaleTimer) {
                clearInterval(window.__eventsBarStaleTimer);
            }
            window.__eventsBarStaleTimer = setInterval(checkStaleness, 1000);
        }
    }

    function showLoading() {
        listNode.innerHTML = '';
        var loading = document.createElement('div');
        loading.className = 'events-empty';
        loading.textContent = 'how you doing today mate?';
        listNode.appendChild(loading);
    }

    function ensureStaleBanner() {
        if (staleBannerEl) return staleBannerEl;
        var el = document.createElement('div');
        el.id = 'icmp-stale-banner';
        el.style.position = 'fixed';
        el.style.top = '0';
        el.style.left = '0';
        el.style.right = '0';
        el.style.zIndex = '9999';
        el.style.padding = '10px 16px';
        el.style.backgroundColor = '#ffeb3b';
        el.style.color = '#000';
        el.style.fontWeight = 'bold';
        el.style.textAlign = 'center';
        el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
        el.style.display = 'none';
        el.textContent = 'No live updates. Data may be stale.';
        document.body.appendChild(el);
        staleBannerEl = el;
        return el;
    }

    function showStaleBanner() {
        var el = ensureStaleBanner();
        var mins = Math.floor((Date.now() - lastSuccessAt) / 60000);
        if (mins < 0) mins = 0;
        el.textContent = 'No live updates for ' + mins + ' minutes. Data may be stale.';
        el.style.display = 'block';
    }

    function hideStaleBanner() {
        if (staleBannerEl) staleBannerEl.style.display = 'none';
    }

    function applyStaleStyling() {
        if (staleApplied) return;
        var rows = document.querySelectorAll('#icmpTableBody tr');
        if (rows && rows.length) {
            for (var i = 0; i < rows.length; i += 1) {
                rows[i].style.backgroundColor = 'yellow';
                rows[i].style.color = 'black';
                rows[i].setAttribute('data-stale', '1');
            }
            staleApplied = true;
        }
    }

    function removeStaleStyling() {
        if (!staleApplied) return;
        var rows = document.querySelectorAll('#icmpTableBody tr[data-stale="1"]');
        for (var i = 0; i < rows.length; i += 1) {
            rows[i].style.backgroundColor = '';
            rows[i].style.color = '';
            rows[i].removeAttribute('data-stale');
        }
        staleApplied = false;
    }

    function parseMysqlDateTime(str) {
        if (!str) return null;
        var s = String(str).replace(/\u00a0/g, ' ').trim();
        var m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/);
        if (m) {
            return new Date(
                Number(m[1]), Number(m[2]) - 1, Number(m[3]),
                Number(m[4]), Number(m[5]), Number(m[6])
            );
        }
        var d = new Date(s);
        return isNaN(d) ? null : d;
    }

    function getNewestLastCheckFromDom() {
        var tbody = document.getElementById('icmpTableBody');
        if (!tbody) return null;
        var rows = tbody.querySelectorAll('tr');
        var newest = 0;
        for (var i = 0; i < rows.length; i += 1) {
            var row = rows[i];
            var cell = row.cells && row.cells[7] ? row.cells[7] : null;
            if (!cell) continue;
            var dt = parseMysqlDateTime(cell.textContent || '');
            if (dt && !isNaN(dt.getTime())) {
                var t = dt.getTime();
                if (t > newest) newest = t;
            }
        }
        return newest > 0 ? new Date(newest) : null;
    }

    function checkStaleness() {
        if (externallyPaused) {
            hideStaleBanner();
            removeStaleStyling();
            return;
        }
        var offline = (typeof navigator !== 'undefined' && navigator && navigator.onLine === false);
        var tooOld = (Date.now() - lastSuccessAt) > (staleMinutes * 60 * 1000);
        var newestCheck = getNewestLastCheckFromDom();
        var dataStale = newestCheck ? ((Date.now() - newestCheck.getTime()) > (staleMinutes * 60 * 1000)) : false;
        if (offline || tooOld || dataStale) {
            showStaleBanner();
            applyStaleStyling();
        } else {
            hideStaleBanner();
            removeStaleStyling();
        }
    }

    function handleIcmpPause() {
        externallyPaused = true;
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        if (fetchController && typeof fetchController.abort === 'function') {
            try { fetchController.abort(); } catch (e) {}
        }
        showLoading();
    }

    function handleIcmpResume() {
        var wasPaused = externallyPaused;
        externallyPaused = false;
        if (wasPaused) {
            showLoading();
            load();
            startPolling();
        }
    }

    document.addEventListener('icmp:pause', handleIcmpPause);
    document.addEventListener('icmp:resume', handleIcmpResume);

    function isIcmpEditMode() {
        var table = document.getElementById('icmpTableBody');
        if (!table) {
            return false;
        }
        if (table.querySelector('input[type="text"], input[type="radio"]')) {
            return true;
        }
        if (table.querySelector('input[type="submit"][value="Update"], input[type="submit"][value="Delete"], input[type="submit"][value="Confirm Delete"]')) {
            return true;
        }
        return false;
    }

    // If ICMP table is in edit mode on load, do not issue the initial fetch.
    if (isIcmpEditMode()) {
        externallyPaused = true;
        showLoading();
    } else {
        showLoading();
        load();
        startPolling();
    }

    function resolveDefaultMinScore(value) {
        var n = parseInt(value, 10);
        if (isNaN(n)) {
            return 1;
        }
        return n;
    }
})();
