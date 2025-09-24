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

    var statusClasses = {
        Online: 'events-status-online',
        Offline: 'events-status-offline',
        Drop: 'events-status-drop'
    };

    var crcTable = createCrcTable();
    var pollInterval = typeof config.pollInterval === 'number' ? config.pollInterval : 10000;
    var pollTimer = null;

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

    function buildUrl() {
        var url = config.endpoint;
        var params = [];
        if (config.groupId) {
            params.push('group=' + encodeURIComponent(config.groupId));
        }
        if (params.length > 0) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + params.join('&');
        }
        return url;
    }

    function formatTime(datetime) {
        if (!datetime) {
            return '';
        }
        var parts = datetime.split(' ');
        if (parts.length < 2) {
            return datetime;
        }
        return parts[1].slice(0, 5);
    }

    function render(items) {
        listNode.innerHTML = '';
        if (!items || items.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'events-empty';
            empty.textContent = 'No events recorded in the last 24 hours.';
            listNode.appendChild(empty);
            return;
        }

        for (var i = 0; i < items.length; i += 1) {
            listNode.appendChild(createItem(items[i]));
        }
    }

    function createItem(item) {
        var node = document.createElement('div');
        node.className = 'events-item';

        var colors = nodeColors(item.ip);
        node.style.backgroundColor = colors.background;
        node.style.borderColor = colors.border;

        var time = document.createElement('span');
        time.className = 'events-time';
        time.textContent = formatTime(item.datetime);
        node.appendChild(time);

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
                render(payload);
            })
            .catch(function () {
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
