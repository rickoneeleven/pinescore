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
    var statusClasses = {
        Online: 'events-status-online',
        Offline: 'events-status-offline',
        Drop: 'events-status-drop',
        Respond: 'events-status-online'
    };

    var crcTable = createCrcTable();
    var pollInterval = typeof config.pollInterval === 'number' ? config.pollInterval : 10000;
    var pollTimer = null;
    var state = {
        filter: resolveDefaultFilter(config.defaultFilter),
        items: []
    };

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

    function activeLimit() {
        if (state.filter === 'onePlus') {
            return 5;
        }

        var cfg = parseInt(config.limit, 10);
        if (!isNaN(cfg) && cfg > 0) {
            return cfg;
        }

        return 5;
    }

    function buildUrl() {
        var params = [];
        if (config.groupId) {
            params.push('group=' + encodeURIComponent(config.groupId));
        }
        var limitValue = activeLimit();
        if (limitValue > 0) {
            params.push('limit=' + encodeURIComponent(limitValue));
        }
        if (params.length === 0) {
            return config.endpoint;
        }
        var separator = config.endpoint.indexOf('?') === -1 ? '?' : '&';
        return config.endpoint + separator + params.join('&');
    }

    function formatTime(datetime) {
        if (!datetime) {
            return '';
        }
        var parts = datetime.split(' ');
        if (parts.length < 2) {
            return datetime;
        }
        var timePart = parts[1].split('.')[0];
        if (timePart.length >= 8) {
            return timePart.slice(0, 8);
        }
        return timePart;
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

        var filteredItems = applyFilter(items);
        if (filteredItems.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'events-empty';
            if (items && items.length > 0) {
                empty.textContent = 'No events match the selected filter.';
            } else {
                empty.textContent = 'No events recorded in the last 24 hours.';
            }
            listNode.appendChild(empty);
            return;
        }

        if (state.filter === 'onePlus') {
            filteredItems = filteredItems.slice(0, 5);
        }

        for (var i = 0; i < filteredItems.length; i += 1) {
            listNode.appendChild(createItem(filteredItems[i]));
        }
    }

    function applyFilter(items) {
        if (!items || items.length === 0) {
            return [];
        }
        return items.filter(function (item) {
            return matchesFilter(item, state.filter);
        });
    }

    function matchesFilter(item, filter) {
        if (!filter || filter === 'onePlus') {
            return true;
        }
        var count = parseProgressCount(item.progress);
        var status = item.status;
        if (filter === 'twoPlus') {
            if (isStatusTransition(status)) {
                return true;
            }
            return count !== null && count >= 2;
        }
        if (filter === 'tenPlus') {
            if (isStatusTransition(status)) {
                return true;
            }
            return count !== null && count >= 10;
        }
        return true;
    }

    function parseProgressCount(progress) {
        if (!progress || typeof progress !== 'string') {
            return null;
        }
        var match = progress.match(/^(\d{1,2})\/10$/);
        if (!match) {
            return null;
        }
        var value = parseInt(match[1], 10);
        return isNaN(value) ? null : value;
    }

    function isStatusTransition(status) {
        return status === 'Offline' || status === 'Online';
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
            render(state.items);
            load();
        });

        updateFilterButtons();
    }

    bindFilterEvents();

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
        time.textContent = formatTime(item.datetime);
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

        var badge = document.createElement('span');
        var statusClass = statusClasses[item.status] || 'events-status-drop';
        badge.className = 'events-badge ' + statusClass;
        badge.textContent = item.status;
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

    function load() {
        fetch(buildUrl(), {
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
                state.items = Array.isArray(payload) ? payload : [];
                render(state.items);
            })
            .catch(function () {
                state.items = [];
                handleError();
            });
    }

    function startPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        pollTimer = setInterval(load, pollInterval);
    }

    load();
    startPolling();
})();
