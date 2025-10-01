(function () {
    var config = window.eventsViewConfig || null;
    if (!config || !config.endpoint) {
        return;
    }

    var root = document.querySelector('[data-events-view]');
    if (!root) {
        return;
    }

    var listNode = root.querySelector('[data-events-items]');
    var statusNode = root.querySelector('[data-events-status]');
    var loadMoreButton = root.querySelector('[data-events-load-more]');
    var searchInput = root.querySelector('[data-events-search]');
    var refreshButton = root.querySelector('[data-events-refresh]');
    var exportButton = root.querySelector('[data-events-export]');
    var liveToggle = root.querySelector('[data-events-live]');
    var windowButtonsContainer = root.querySelector('[data-events-window-buttons]');

    var crcTable = createCrcTable();
    var state = {
        window: config.defaultWindow === 'all' ? 'all' : '24h',
        nextCursor: null,
        search: '',
        loading: false,
        liveTimer: null,
        limit: typeof config.limit === 'number' ? config.limit : 100
    };

    updateWindowButtons();
    bindEvents();
    load(false);

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

    function bindEvents() {
        if (windowButtonsContainer) {
            windowButtonsContainer.addEventListener('click', function (event) {
                var target = event.target;
                while (target && target !== windowButtonsContainer && !target.hasAttribute('data-window')) {
                    target = target.parentNode;
                }
                if (!target || !target.hasAttribute('data-window')) {
                    return;
                }
                var newWindow = target.getAttribute('data-window');
                if (newWindow !== state.window) {
                    state.window = newWindow === 'all' ? 'all' : '24h';
                    state.nextCursor = null;
                    updateWindowButtons();
                    load(false);
                }
            });
        }

        if (searchInput) {
            var debounceTimer = null;
            searchInput.addEventListener('input', function () {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }
                debounceTimer = setTimeout(function () {
                    var value = searchInput.value || '';
                    if (value !== state.search) {
                        state.search = value;
                        state.nextCursor = null;
                        load(false);
                    }
                }, 300);
            });
        }

        if (refreshButton) {
            refreshButton.addEventListener('click', function () {
                state.nextCursor = null;
                load(false);
            });
        }

        if (exportButton) {
            exportButton.addEventListener('click', function () {
                var url = buildExportUrl();
                if (!url) {
                    return;
                }
                window.open(url, '_blank');
            });
        }

        if (liveToggle) {
            liveToggle.addEventListener('change', function () {
                if (liveToggle.checked) {
                    startLive();
                    state.nextCursor = null;
                    load(false);
                } else {
                    stopLive();
                }
            });
        }

        if (loadMoreButton) {
            loadMoreButton.addEventListener('click', function () {
                if (!state.nextCursor || state.loading) {
                    return;
                }
                load(true);
            });
        }
    }

    function startLive() {
        stopLive();
        var interval = typeof config.liveInterval === 'number' ? config.liveInterval : 60000;
        state.liveTimer = setInterval(function () {
            state.nextCursor = null;
            load(false);
        }, interval);
    }

    function stopLive() {
        if (state.liveTimer) {
            clearInterval(state.liveTimer);
            state.liveTimer = null;
        }
    }

    function updateWindowButtons() {
        if (!windowButtonsContainer) {
            return;
        }
        var buttons = windowButtonsContainer.querySelectorAll('[data-window]');
        for (var i = 0; i < buttons.length; i += 1) {
            var button = buttons[i];
            if (button.getAttribute('data-window') === state.window) {
                button.classList.add('events-control-active');
            } else {
                button.classList.remove('events-control-active');
            }
        }
    }

    function buildUrl(append) {
        var params = [];
        params.push('window=' + encodeURIComponent(state.window));
        params.push('limit=' + encodeURIComponent(state.limit));
        if (config.groupId) {
            params.push('group=' + encodeURIComponent(config.groupId));
        }
        if (state.search) {
            params.push('q=' + encodeURIComponent(state.search));
        }
        if (state.window === 'all' && append && state.nextCursor) {
            params.push('cursor=' + encodeURIComponent(state.nextCursor));
        }
        var separator = config.endpoint.indexOf('?') === -1 ? '?' : '&';
        return config.endpoint + separator + params.join('&');
    }

    function buildExportUrl() {
        if (!config.exportEndpoint) {
            return null;
        }
        var params = [];
        params.push('window=' + encodeURIComponent(state.window));
        if (config.groupId) {
            params.push('group=' + encodeURIComponent(config.groupId));
        }
        if (state.search) {
            params.push('q=' + encodeURIComponent(state.search));
        }
        var separator = config.exportEndpoint.indexOf('?') === -1 ? '?' : '&';
        return config.exportEndpoint + separator + params.join('&');
    }

    function shouldHighlightRow(progress) {
        if (progress === null || progress === undefined || progress === '') {
            return false;
        }
        return String(progress).replace(/\s+/g, '') !== '1/10';
    }

    function renderItems(items, append) {
        if (!append) {
            listNode.innerHTML = '';
        }

        if ((!items || items.length === 0) && !append) {
            var empty = document.createElement('div');
            empty.className = 'events-empty';
            empty.textContent = 'No events found for the selected range.';
            listNode.appendChild(empty);
            return;
        }

        var fragment = document.createDocumentFragment();
        for (var i = 0; i < items.length; i += 1) {
            fragment.appendChild(createItem(items[i]));
        }
        listNode.appendChild(fragment);
    }

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

        var address = document.createElement('span');
        address.className = 'events-ip-address';
        address.textContent = item.ip;
        node.appendChild(address);

        var badge = document.createElement('span');
        badge.className = 'events-badge ' + statusClassName(item.status);
        badge.textContent = item.status;
        node.appendChild(badge);

        return node;
    }

    function statusClassName(status) {
        if (status === 'Online' || status === 'Respond') {
            return 'events-status-online';
        }
        if (status === 'Offline') {
            return 'events-status-offline';
        }
        return 'events-status-drop';
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

    function setStatus(message, isError) {
        if (!statusNode) {
            return;
        }
        statusNode.textContent = message || '';
        if (message && isError) {
            statusNode.classList.add('events-status-error');
        } else {
            statusNode.classList.remove('events-status-error');
        }
    }

    function toggleLoadMore() {
        if (!loadMoreButton) {
            return;
        }
        if (state.window === 'all' && state.nextCursor) {
            loadMoreButton.style.display = 'block';
            loadMoreButton.disabled = false;
        } else {
            loadMoreButton.style.display = 'none';
        }
    }

    function load(append) {
        if (state.loading) {
            return;
        }
        state.loading = true;
        setStatus('Loading...', false);
        if (loadMoreButton && append) {
            loadMoreButton.disabled = true;
        }

        fetch(buildUrl(append), {
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Bad response');
                }
                return response.json();
            })
            .then(function (payload) {
                var items = payload.items || [];
                renderItems(items, append);
                state.nextCursor = payload.next_cursor || null;
                toggleLoadMore();
                setStatus('', false);
            })
            .catch(function () {
                setStatus('Unable to load events.', true);
            })
            .then(function () {
                state.loading = false;
                if (loadMoreButton && append) {
                    loadMoreButton.disabled = false;
                }
            });
    }
})();
