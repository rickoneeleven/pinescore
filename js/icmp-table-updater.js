

window.IcmpTableUpdater = window.IcmpTableUpdater || (function() {
    'use strict';
    
    let updateInterval = null;
    let refreshRate = 10000;
    let currentGroupId = null;
    let updateCount = 0;
    let isUpdating = false;
    let fullScreenMode = false;
    let pendingData = null;
    let previousDataByIp = {};
    let countdownInterval = null;
    let secondsRemaining = 0;
    let fetchInProgress = false;
    let pausedState = { manual: false, edit: false };
    let staleMinutes = 10;
    let lastSuccessAt = Date.now();
    let staleBannerEl = null;
    let staleApplied = false;
    
    // Parse a MySQL DATETIME string (YYYY-MM-DD HH:MM:SS) into a Date in local time.
    // Returns null if input is falsy or invalid.
    function parseMysqlDateTime(str) {
        if (!str || typeof str !== 'string') return null;
        // Expect formats like "2025-11-03 10:54:29" or with timezone omitted
        const m = str.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/);
        if (!m) {
            // Fallback: let Date parse, may be UTC depending on browser
            const d = new Date(str);
            return isNaN(d.getTime()) ? null : d;
        }
        const year = parseInt(m[1], 10);
        const month = parseInt(m[2], 10) - 1; // JS months 0-11
        const day = parseInt(m[3], 10);
        const hour = parseInt(m[4], 10);
        const minute = parseInt(m[5], 10);
        const second = parseInt(m[6], 10);
        const d = new Date(year, month, day, hour, minute, second, 0);
        return isNaN(d.getTime()) ? null : d;
    }
    
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }
    
    function withStrict(url) {
        if (window.ownerMatchesTable) {
            return url + (url.indexOf('?') === -1 ? '?strict=1' : '&strict=1');
        }
        return url;
    }

    function showAuthInIcmpTable() {
        const tableBody = document.querySelector('#icmpTableBody');
        if (!tableBody) return;
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 12;
        td.style.textAlign = 'center';
        td.appendChild(document.createTextNode('Session expired - refresh the page or '));
        const link = document.createElement('a');
        link.href = '/auth/user/login';
        link.textContent = 'sign in';
        td.appendChild(link);
        td.appendChild(document.createTextNode('.'));
        tr.appendChild(td);
        tableBody.innerHTML = '';
        tableBody.appendChild(tr);
    }

    function handleShowAll() {
        const showAllContainer = document.getElementById('show-all-container');
        if (showAllContainer) {
            showAllContainer.innerHTML = 'Loading all nodes...';
        }

        let url = currentGroupId 
            ? `/tools/getIcmpDataJson/${currentGroupId}` 
            : '/tools/getIcmpDataJson';
        url = withStrict(url);

        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (response && response.status === 401) {
                    showAuthInIcmpTable();
                    stopAutoRefresh();
                    throw { name: 'AuthError' };
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Show All API Error:', data.error);
                    if (showAllContainer) showAllContainer.innerHTML = 'Error loading nodes.';
                    return;
                }
                renderTable(data.ips);
                
                if (showAllContainer) {
                    showAllContainer.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Show All Fetch Error:', error);
                if (showAllContainer) showAllContainer.innerHTML = 'Error loading nodes.';
            });
    }
    
    function handleSearch(searchTerm) {
        let url = searchTerm 
            ? (currentGroupId 
                ? `/tools/searchNodes?term=${encodeURIComponent(searchTerm)}&group_id=${currentGroupId}`
                : `/tools/searchNodes?term=${encodeURIComponent(searchTerm)}`)
            : (currentGroupId ? `/tools/getIcmpDataJson/${currentGroupId}` : '/tools/getIcmpDataJson');
        url = withStrict(url);
        
        const tableBody = document.querySelector('#icmpTableBody');
        if(tableBody) tableBody.innerHTML = '<tr><td colspan="12" style="text-align:center;">Searching...</td></tr>';
        
        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (response && response.status === 401) {
                    showAuthInIcmpTable();
                    stopAutoRefresh();
                    throw { name: 'AuthError' };
                }
                return response.json();
            })
            .then(data => {
                if(data.error) {
                    console.error('Search API Error:', data.error);
                    if(tableBody) tableBody.innerHTML = '<tr><td colspan="12" style="text-align:center;">Error during search.</td></tr>';
                    return;
                }
                renderTable(data.ips);
                
                const showAllContainer = document.getElementById('show-all-container');
                if (showAllContainer) {
                    if (searchTerm) {
                        showAllContainer.style.display = 'none';
                    } else {
                        showAllContainer.style.display = 'block'; 
                    }
                }
            })
            .catch(error => {
                console.error('Search Fetch Error:', error);
                if(tableBody) tableBody.innerHTML = '<tr><td colspan="12" style="text-align:center;">Error during search.</td></tr>';
            });
    }
    
    const tableBodySelector = '#icmpTableBody';

    const autoRefreshToggleSelector = '#autoRefreshToggle';
    const fullscreenToggleSelector = '#fullscreenToggle';
    
    function init(options = {}) {
        refreshRate = options.refreshRate || refreshRate;
        currentGroupId = options.groupId || null;
        if (typeof options.staleMinutes === 'number' && options.staleMinutes > 0) {
            staleMinutes = options.staleMinutes;
        }
        lastSuccessAt = Date.now();
        
        if (options.autoStart) {
            // Always start auto-refresh except during active search
            startAutoRefresh();
        }
        
        bindEventHandlers();
    }
    
    function bindEventHandlers() {
        const toggleButton = document.querySelector(autoRefreshToggleSelector);
        if (toggleButton) {
            toggleButton.addEventListener('click', handleToggleClick);
        }
        
        const fullscreenButton = document.querySelector(fullscreenToggleSelector);
        if (fullscreenButton) {
            fullscreenButton.addEventListener('click', handleFullscreenToggle);
        }
        
        const searchInput = document.getElementById('node-search-input');
        if (searchInput) {
            const debouncedSearch = debounce(handleSearch, 300);
            
            searchInput.addEventListener('keyup', (e) => {
                const searchTerm = e.target.value.trim();
                
                if (searchTerm.length > 1 || searchTerm.length === 0) {
                    debouncedSearch(searchTerm);
                }
            });
        }
        
        const showAllBtn = document.getElementById('show-all-nodes-btn');
        if (showAllBtn) {
            showAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                handleShowAll();
            });
        }
    }
    
    function handleToggleClick(e) {
        e.preventDefault();
        
        if (updateInterval) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    }
    
    function handleFullscreenToggle(e) {
        e.preventDefault();
        
        if (fullScreenMode) {
            exitFullScreen();
        } else {
            enterFullScreen();
        }
        
        updateFullscreenButton(fullScreenMode);
    }
    
    function startAutoRefresh() {
        if (updateInterval) return;

        updateToggleButton(true);
        updateInterval = true; // Mark as active

        // If starting while the form is already in edit mode, do not fetch
        // and ensure the events bar is paused immediately.
        if (isFormInEditMode()) {
            pausedState.edit = true;
            updateToggleButtonForEditMode(true);
            try { document.dispatchEvent(new CustomEvent('icmp:pause', { detail: { reason: 'edit' } })); } catch (e) {}
            stopCountdown();
            return;
        }

        startCountdown();

        pausedState.manual = false;
        try { document.dispatchEvent(new CustomEvent('icmp:resume', { detail: { reason: 'manual' } })); } catch (e) {}
        fetchAndUpdateTable();
    }
    
    function stopAutoRefresh() {
        if (updateInterval) {
            updateInterval = null;
        }
        
        stopSequentialUpdate();
        stopCountdown();
        updateToggleButton(false);

        if (!pausedState.manual) {
            pausedState.manual = true;
            try { document.dispatchEvent(new CustomEvent('icmp:pause', { detail: { reason: 'manual' } })); } catch (e) {}
        }
        hideStaleBanner();
        removeStaleStyling();
    }
    
    function updateToggleButton(isActive) {
        const button = document.querySelector(autoRefreshToggleSelector);
        if (!button) return;
        
        if (isActive) {
            button.textContent = '[Stop Auto Refresh]';
            button.style.color = 'green';
        } else {
            button.textContent = '[Auto Refresh]';
            button.style.color = 'red';
        }
    }
    
    function updateFullscreenButton(isFullscreen) {
        const button = document.querySelector(fullscreenToggleSelector);
        if (!button) return;
        
        if (isFullscreen) {
            button.textContent = '[Exit Full Screen]';
            button.style.color = 'green';
        } else {
            button.textContent = '[Full Screen]';
            button.style.color = 'red';
        }
    }
    
    function updateToggleButtonForEditMode(inEditMode) {
        const button = document.querySelector(autoRefreshToggleSelector);
        if (!button || !updateInterval) return;
        
        if (inEditMode) {
            button.textContent = '[Paused - Edit Mode]';
            button.style.color = 'orange';
            updateCountdownDisplayForEditMode();
        } else {
            button.textContent = '[Stop Auto Refresh]';
            button.style.color = 'green';
            resetCountdown();
        }
    }
    
    function enterFullScreen() {
        fullScreenMode = true;
        document.body.classList.add('icmp-fullscreen');

        const elementsToHide = [
            '#ping_add_container',
            'form[action*="pingAdd_formProcess"]',
            '.signin_form',
            '.topleft',
            'hr.spaced',
            'div > h2',
            '.viewgroupmenu',
            '.full_width',
            '#pa_left',
            '#pa_right',
            '.screenshots',
            '.signup_wrap'
        ];
        
        elementsToHide.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (!el.closest('#icmp_table')) {
                    el.style.display = 'none';
                }
            });
        });

        const happyDayContainer = document.querySelector('div[style*="display: flex"]');
        if (happyDayContainer && happyDayContainer.querySelector('strong')) {
            const strongText = happyDayContainer.querySelector('strong').textContent;
            if (strongText.includes('Happy')) {

                const healthMetrics = document.getElementById('healthMetrics');
                if (healthMetrics) {
                    healthMetrics.style.position = 'fixed';
                    healthMetrics.style.top = '5px';
                    healthMetrics.style.right = '20px';
                    healthMetrics.style.zIndex = '1000';
                }
                happyDayContainer.style.display = 'none';
            }
        }

        const style = document.createElement('style');
        style.id = 'icmp-fullscreen-style';
        style.textContent = `
            .icmp-fullscreen {
                margin: 0;
                padding: 0;
            }
            .icmp-fullscreen body {
                margin: 0;
                padding: 0;
            }
            .icmp-fullscreen #wrap {
                margin: 0;
                padding: 0;
            }
            .icmp-fullscreen .content {
                margin: 0;
                padding: 0;
            }
            .icmp-fullscreen #icmp_table {
                margin-top: 0;
                padding-top: 5px;
            }

            .icmp-fullscreen #icmp_table > br {
                display: none;
            }

            .icmp-fullscreen .content > br:first-child {
                display: none;
            }

            .icmp-fullscreen #ping_add_container ~ br {
                display: none;
            }

            .icmp-fullscreen #icmp_table table {
                margin-top: 0;
            }
        `;
        document.head.appendChild(style);
    }
    
    function exitFullScreen() {
        fullScreenMode = false;
        document.body.classList.remove('icmp-fullscreen');

        const elementsToShow = [
            '#ping_add_container',
            'form[action*="pingAdd_formProcess"]',
            '.signin_form',
            '.topleft',
            'hr.spaced',
            'div > h2',
            '.viewgroupmenu',
            '.full_width',
            '#pa_left',
            '#pa_right',
            '.screenshots',
            '.signup_wrap'
        ];
        
        elementsToShow.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.display = '';
            });
        });

        const happyDayContainer = document.querySelector('div[style*="display: flex"]');
        if (happyDayContainer) {
            happyDayContainer.style.display = '';
        }
        const healthMetrics = document.getElementById('healthMetrics');
        if (healthMetrics) {
            healthMetrics.style.position = '';
            healthMetrics.style.top = '';
            healthMetrics.style.right = '';
            healthMetrics.style.zIndex = '';
        }

        const style = document.getElementById('icmp-fullscreen-style');
        if (style) {
            style.remove();
        }
    }
    
    function isFormInEditMode() {

        const editInputs = document.querySelectorAll('#icmpTableBody input[type="text"], #icmpTableBody input[type="radio"]');
        const deleteButton = document.querySelector('#icmpTableBody input[type="submit"][value="Delete"]');
        const confirmDeleteButton = document.querySelector('#icmpTableBody input[type="submit"][value="Confirm Delete"]');
        const updateButton = document.querySelector('#icmpTableBody input[type="submit"][value="Update"]');
        return editInputs.length > 0 || deleteButton !== null || confirmDeleteButton !== null || updateButton !== null;
    }
    
    function fetchAndUpdateTable() {
        if (isUpdating) return;

        if (isFormInEditMode()) {
            updateToggleButtonForEditMode(true);
            if (!pausedState.edit) {
                pausedState.edit = true;
                try { document.dispatchEvent(new CustomEvent('icmp:pause', { detail: { reason: 'edit' } })); } catch (e) {}
            }
            return;
        } else {
            if (pausedState.edit) {
                pausedState.edit = false;
                if (updateInterval && !pausedState.manual) {
                    try { document.dispatchEvent(new CustomEvent('icmp:resume', { detail: { reason: 'edit' } })); } catch (e) {}
                }
            }
            updateToggleButtonForEditMode(false);
        }
        
        isUpdating = true;
        
        const searchInput = document.getElementById('node-search-input');
        const searchTerm = searchInput ? searchInput.value.trim() : '';
        
        let url = searchTerm 
            ? (currentGroupId 
                ? `/tools/searchNodes?term=${encodeURIComponent(searchTerm)}&group_id=${currentGroupId}`
                : `/tools/searchNodes?term=${encodeURIComponent(searchTerm)}`)
            : (currentGroupId ? `/tools/getIcmpDataJson/${currentGroupId}` : '/tools/getIcmpDataJson');
        url = withStrict(url);
        
        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (response && response.status === 401) {
                    showAuthInIcmpTable();
                    stopAutoRefresh();
                    throw { name: 'AuthError' };
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('API Error:', data.error);
                    return;
                }

                pendingData = data;
                lastSuccessAt = Date.now();
                checkStaleness();
                updateHealthMetrics(data);
                updateGroupScores(data);
                
                // On first load, populate previousDataByIp to prevent everything being bold
                if (updateCount === 0 && data.ips) {
                    Object.entries(data.ips).forEach(([ip, ipData]) => {
                        previousDataByIp[ip] = {
                            note: ipData.note || '',
                            status: ipData.last_email_status,
                            count: ipData.count,
                            pineScore: ipData.score,
                            ms: ipData.ms,
                            lta: ipData.average_longterm_ms,
                            lastCheck: ipData.lastcheck
                        };
                    });
                }
                
                // Check if we need to respect 111-node limit on default page
                const showAllButton = document.querySelector('#show-all-nodes-btn');
                if (!currentGroupId && showAllButton && data.ips) {
                    // Limit to first 111 nodes to match PHP rendering
                    const limitedIps = {};
                    const entries = Object.entries(data.ips);
                    for (let i = 0; i < Math.min(111, entries.length); i++) {
                        limitedIps[entries[i][0]] = entries[i][1];
                    }
                    pendingData = { ...data, ips: limitedIps };
                } else {
                    pendingData = data;
                }
                
                return startSequentialUpdate();
            })
            .then(() => {
                updateCount++;
                startCountdown(); // Restart countdown for next cycle
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                checkStaleness();
                startCountdown(); // Restart countdown even on error
            })
            .finally(() => {
                isUpdating = false;
            });
    }
    
    function updateTable(ipsData) {
        const tableBody = document.querySelector(tableBodySelector);
        if (!tableBody) return;
        
        const fragment = document.createDocumentFragment();
        let rowCount = 0;
        
        Object.entries(ipsData).forEach(([ip, data]) => {
            rowCount++;
            const row = createTableRow(ip, data);

            const firstCell = row.querySelector('td:first-child');
            if (firstCell) {
                firstCell.textContent = rowCount;
            }

            const lastCheck = new Date(data.lastcheck);
            const now = new Date();
            const minutesDiff = (now - lastCheck) / (1000 * 60);
            if (minutesDiff > staleMinutes) {
                row.style.backgroundColor = 'yellow';
                row.style.color = 'black';
            }
            fragment.appendChild(row);
        });

        tableBody.innerHTML = '';
        tableBody.appendChild(fragment);
    }
    
    function createTableRow(ip, data) {
        const row = document.createElement('tr');
        const rowClass = getRowClass(data);
        if (rowClass) row.className = rowClass;

        const countCell = document.createElement('td');
        countCell.textContent = '';
        row.appendChild(countCell);

        const noteCell = document.createElement('td');
        noteCell.innerHTML = '<a href="/tools/report/' + data.id + '">' + (data.note || '') + '</a>';
        row.appendChild(noteCell);

        const statusCell = document.createElement('td');
        statusCell.innerHTML = createStatusCell(data);
        row.appendChild(statusCell);

        const scoreCell = document.createElement('td');
        scoreCell.innerHTML = createScoreCell(data);
        row.appendChild(scoreCell);

        const msCell = document.createElement('td');
        msCell.innerHTML = createMsCell(data);
        row.appendChild(msCell);

        const ltaCell = document.createElement('td');
        ltaCell.innerHTML = '<a href="/nc/storyTimeNode/' + data.id + '">' + 
            (data.average_longterm_ms || '??') + 'ms</a>';
        row.appendChild(ltaCell);

        const traceCell = document.createElement('td');
        traceCell.innerHTML = '<a href="/traceroute/routesthathavebeentraced/' + ip + '"> (tr) </a>';
        row.appendChild(traceCell);

        const lastCheckCell = document.createElement('td');
        lastCheckCell.innerHTML = data.lastcheck + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        row.appendChild(lastCheckCell);

        const ipCell = document.createElement('td');
        ipCell.textContent = ip;
        row.appendChild(ipCell);

        if (window.ownerMatchesTable) {
            const alertCell = document.createElement('td');
            const alertText = data.alert || '';

            if (alertText) {
                const numEmailAlerts = (alertText.match(/,/g) || []).length + 1;
                if (numEmailAlerts > 1) {
                    alertCell.textContent = numEmailAlerts + ' configured alerts';
                } else {
                    alertCell.textContent = alertText;
                }
            } else {
                alertCell.textContent = '';
            }
            row.appendChild(alertCell);
        }

        const publicCell = document.createElement('td');
        publicCell.textContent = data.public == 1 ? 'Yes' : 'No';
        row.appendChild(publicCell);

        if (window.ownerMatchesTable) {
            const actionsCell = document.createElement('td');
            actionsCell.innerHTML = createActionsCell(data, ip);
            row.appendChild(actionsCell);
        }
        
        return row;
    }
    
    function getRowClass(data) {
        const now = new Date();
        const lastOnlineToggle = new Date(data.last_online_toggle);
        const lastCheck = new Date(data.lastcheck);
        const timeDiff = now - lastCheck;
        const minutesDiff = timeDiff / (1000 * 60);
        const daysDiff = Math.floor((now - lastOnlineToggle) / (1000 * 60 * 60 * 24));

        if (minutesDiff > staleMinutes) {
            return '';
        }
        
        if (data.last_email_status === 'Online' && data.count == 1) {
            return 'orange';
        }
        
        if (data.count > 1) {
            return 'transition';
        }
        
        if (data.last_email_status === 'Offline') {
            if (daysDiff === 0) return 'red';
            if (daysDiff === 1) return 'onedayred';
            if (daysDiff > 1) return 'overonedayred';
        }
        
        if (data.lta_difference_algo != 0) {
            if (data.lta_difference_algo < -100) return 'orange';
            if (data.lta_difference_algo >= -100 && data.lta_difference_algo < 0) return 'green';
        }
        
        if (data.score < 50 && data.score > -1) return 'pink';
        if (data.score < 0) return 'darkerpink';
        
        return 'hover';
    }
    
    function createStatusCell(data) {
        let countWithColor = data.count;
        if (countWithColor) {
            const color = getCountColor(data);
            if (color) {
                countWithColor = '<font color="' + color + '"><strong>' + countWithColor + '</strong></font>';
            }
        }
        return data.last_email_status + ' [' + countWithColor + '] ';
    }
    
    function getCountColor(data) {
        if (data.last_email_status === 'Offline' && data.count_direction === 'Up') return 'green';
        if (data.last_email_status === 'Offline' && data.count_direction === 'Down') return 'red';
        if (data.last_email_status === 'Online' && data.count_direction === 'Down') return 'green';
        if (data.last_email_status === 'Online' && data.count_direction === 'Up') return 'red';
        return null;
    }
    
    function createScoreCell(data) {
        const fifteenMinsAgo = new Date(Date.now() - 15 * 60 * 1000);
        const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000);
        const scoreChangeTime = new Date(data.pinescore_change);
        
        const seconds = scoreChangeTime.getSeconds();
        if (seconds === 0 && scoreChangeTime > fifteenMinsAgo) {

            return '<font color="red"><strong>' + data.score + '</strong></font>';
        } else if (seconds === 1 && scoreChangeTime > twoHoursAgo) {

            return '<font color="green"><strong>' + data.score + '</strong></font>';
        }
        return data.score;
    }
    
    function createMsCell(data) {
        if (data.last_email_status === 'Offline') {
            const wentOffline = new Date(data.last_online_toggle);
            const formatted = wentOffline.toLocaleDateString('en-GB') + ' - ' + 
                            wentOffline.toLocaleTimeString('en-GB');
            return ' ' + formatted;
        }
        return data.ms + 'ms';
    }
    
    function createActionsCell(data, ip) {
        const form = '<form method="post" action="/tools/icmpEdit#' + data.id + '">' +
                    '<input type="hidden" name="note_edit" value="' + (data.note || '') + '">' +
                    '<input type="hidden" name="id_edit" value="' + data.id + '">' +
                    '<input type="hidden" name="ip_edit" value="' + ip + '">' +
                    '<input type="hidden" name="group_id" value="' + (currentGroupId || 0) + '">' +
                    '<input type="submit" name="action" value="Edit">' +
                    '</form>';
        return form;
    }
    
    function updateTableRows() {
        const rows = document.querySelectorAll('#icmpTableBody tr');
        rows.forEach((row, index) => {
            const firstCell = row.querySelector('td:first-child');
            if (firstCell) {
                firstCell.textContent = index + 1;
            }
        });
    }
    
    function updateHealthMetrics(data) {
        const metricsContainer = document.querySelector('#healthMetrics');
        if (!metricsContainer) return;

        const cyclesPerMin = parseInt(data.cycles_per_minute) || 0;
        const jobsPerMin = parseInt(data.jobs_per_minute) || 0;
        const failedJobs = parseInt(data.failed_jobs_past_day) || 0;
        const engineStatus = data.engine_status || 'unknown';

        const cyclesColor = cyclesPerMin >= 5 ? 'green' : (cyclesPerMin >= 2 ? 'orange' : 'red');
        const jobsColor = jobsPerMin < 1000 ? 'orange' : 'green';
        const failedColor = failedJobs === 0 ? 'green' : 'red';
        const engineColor = engineStatus === 'active' ? 'green' : 'red';

        metricsContainer.innerHTML =
            `<span style="color: ${cyclesColor}">cycles/min: ${cyclesPerMin}</span> | ` +
            `<span style="color: ${jobsColor}">pings/min: ${jobsPerMin.toLocaleString()}</span> | ` +
            `<span style="color: ${failedColor}">failed jobs (24h): ${failedJobs}</span> | ` +
            `<span style="color: ${engineColor}">engine: ${engineStatus}</span>`;
    }
    
    function updateGroupScores(data) {
        if (!data.groupscore) return;
        
        const scoreContainer = document.querySelector('#groupScore');
        if (scoreContainer) {
            scoreContainer.textContent = data.groupscore;
        }
    }
    
    function startSequentialUpdate() {
        stopSequentialUpdate();
        
        if (!pendingData || !pendingData.ips) return Promise.resolve();
        
        const ipsArray = Object.entries(pendingData.ips);
        if (ipsArray.length === 0) return Promise.resolve();

        // Skip sequential updates - go straight to bulk
        return bulkUpdateRemainingRows(ipsArray, 0);
    }
    
    function stopSequentialUpdate() {
        // No longer needed - keeping empty function to avoid breaking calls
    }
    
    
    function bulkUpdateRemainingRows(ipsArray, startIndex) {
        return new Promise((resolve) => {
            for (let i = startIndex; i < ipsArray.length; i++) {
                const [ip, data] = ipsArray[i];
                updateSingleRowBulk(ip, data, i);
            }
            secondsRemaining = (refreshRate / 1000) - 1;
            updateCountdownDisplay();
            resolve();
        });
    }
    
    function updateSingleRowBulk(ip, data, rowIndex) {
        const rows = document.querySelectorAll('#icmpTableBody tr');
        if (rowIndex >= rows.length) return;
        
        const row = rows[rowIndex];
        const oldData = previousDataByIp[ip] || {};

        const newRow = createTableRow(ip, data);

        const firstCell = newRow.querySelector('td:first-child');
        if (firstCell) {
            firstCell.textContent = rowIndex + 1;
        }

        const lastCheck = new Date(data.lastcheck);
        const now = new Date();
        const minutesDiff = (now - lastCheck) / (1000 * 60);
        if (minutesDiff > staleMinutes) {
            newRow.style.backgroundColor = 'yellow';
            newRow.style.color = 'black';
        }

        if (row.parentNode) {
            row.parentNode.replaceChild(newRow, row);

            applyLtaStyling(newRow, data);
            animateCellChanges(newRow, oldData, data, ip);

            previousDataByIp[ip] = {
                note: data.note || '',
                status: data.last_email_status,
                count: data.count,
                pineScore: data.score,
                ms: data.ms,
                lta: data.average_longterm_ms,
                lastCheck: data.lastcheck
            };
        }
    }
    
    
    function animateCellChanges(newRow, oldData, newData, ip) {
        if (!newRow) return;
        
        const cells = newRow.cells;

        const lastCheckChanged = oldData.lastCheck !== newData.lastcheck;
        if (!lastCheckChanged) {
            return;
        }

        if (cells[1] && oldData.note !== (newData.note || '')) {
            cells[1].style.fontWeight = 'bold';
        }

        if (cells[2]) {

            if (oldData.status !== newData.last_email_status || oldData.count !== newData.count) {

                const countMatch = cells[2].innerHTML.match(/\[([^\]]+)\]/);
                if (countMatch && countMatch[1]) {
                    const countHtml = countMatch[1];
                    const strongMatch = countHtml.match(/<strong>(\d+)<\/strong>/);
                    if (strongMatch) {

                        const tempHtml = cells[2].innerHTML.replace(
                            /<strong>(\d+)<\/strong>/,
                            '<strong style="background-color: yellow; transition: background-color 0.3s;">$1</strong>'
                        );
                        cells[2].innerHTML = tempHtml;

                        setTimeout(() => {
                            cells[2].innerHTML = cells[2].innerHTML.replace(
                                /style="[^"]*"/,
                                ''
                            );
                        }, 500);
                    }
                }

                cells[2].style.fontWeight = 'bold';
            }
        }

        if (cells[3] && oldData.pineScore !== newData.score) {
            cells[3].style.fontWeight = 'bold';

        }

        if (cells[4] && newData.last_email_status === 'Online') {
            const newMsNum = parseInt(newData.ms);
            const ltaNum = parseInt(newData.average_longterm_ms);

            if (lastCheckChanged && !isNaN(newMsNum) && !isNaN(ltaNum) && newMsNum !== ltaNum) {
                cells[4].style.fontWeight = 'bold';
            }
        }

        if (cells[5] && oldData.lta !== newData.average_longterm_ms) {
            cells[5].style.fontWeight = 'bold';
        }

        if (cells[7] && lastCheckChanged) {
            cells[7].style.fontWeight = 'bold';
        }
    }
    
    function flashCell(cell, effect = 'bold', duration = 400) {
        if (!cell) return;
        
        const originalWeight = cell.style.fontWeight;
        const originalBg = cell.style.backgroundColor;
        
        switch(effect) {
            case 'bold':
                cell.style.transition = 'font-weight 0.2s ease-in-out';
                cell.style.fontWeight = 'bold';
                
                setTimeout(() => {
                    cell.style.fontWeight = originalWeight;
                }, duration);
                break;
                
            case 'yellow':
                cell.style.transition = 'background-color 0.3s ease-in-out';
                cell.style.backgroundColor = 'yellow';
                
                setTimeout(() => {
                    cell.style.backgroundColor = originalBg;
                }, 500);
                break;
        }
    }
    
    function applyLtaStyling(row, data) {
        if (!row || !data || data.last_email_status !== 'Online') return;
        
        const msCell = row.cells[4];
        if (!msCell) return;
        
        const currentMs = parseInt(data.ms);
        const ltaMs = parseInt(data.average_longterm_ms);

        if (!isNaN(currentMs) && !isNaN(ltaMs)) {
            if (currentMs > ltaMs) {
                msCell.style.color = 'red';
            } else if (currentMs < ltaMs) {
                msCell.style.color = 'green';
            }

        }
    }
    
    function renderTable(ipsData) {
        const tableBody = document.querySelector('#icmpTableBody');
        if (!tableBody) {
            console.error('ICMP table body not found.');
            return;
        }
        
        // Clear existing rows and create new ones from scratch
        const fragment = document.createDocumentFragment();
        let rowCount = 0;
        
        Object.entries(ipsData || {}).forEach(([ip, data]) => {
            rowCount++;
            const row = createTableRow(ip, data);

            const firstCell = row.querySelector('td:first-child');
            if (firstCell) {
                firstCell.textContent = rowCount;
            }

            const lastCheck = parseMysqlDateTime(data.lastcheck) || new Date(0);
            const now = new Date();
            const minutesDiff = (now - lastCheck) / (1000 * 60);
            if (minutesDiff > staleMinutes) {
                row.style.backgroundColor = 'yellow';
                row.style.color = 'black';
            }
            fragment.appendChild(row);
        });

        tableBody.innerHTML = '';
        tableBody.appendChild(fragment);
    }
    
    function startCountdown() {
        stopCountdown();
        secondsRemaining = (refreshRate / 1000) - 1;
        updateCountdownDisplay();
        
        countdownInterval = setInterval(() => {
            secondsRemaining--;
            updateCountdownDisplay();
            checkStaleness();
            
            if (secondsRemaining <= 0) {
                fetchAndUpdateTable();
            }
        }, 1000);
    }
    
    function stopCountdown() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        updateCountdownDisplay();
    }
    
    function resetCountdown() {
        if (countdownInterval) {
            secondsRemaining = 0;
            updateCountdownDisplay();
        }
    }
    
    function updateCountdownDisplay() {
        const timerElement = document.getElementById('countdownTimer');
        if (!timerElement) return;
        
        if (countdownInterval && updateInterval) {
            const displaySeconds = Math.max(0, secondsRemaining);
            timerElement.textContent = displaySeconds + 's';
            timerElement.style.color = displaySeconds <= 3 ? 'red' : 'green';
        } else {
            timerElement.textContent = '';
        }
    }
    
    function updateCountdownDisplayForEditMode() {
        const timerElement = document.getElementById('countdownTimer');
        if (!timerElement) return;
        
        timerElement.textContent = 'Paused';
        timerElement.style.color = 'orange';
    }

    function ensureStaleBanner() {
        if (staleBannerEl) return staleBannerEl;
        const el = document.createElement('div');
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
        const el = ensureStaleBanner();
        const mins = Math.floor((Date.now() - lastSuccessAt) / 60000);
        el.textContent = 'No live updates for ' + mins + ' minutes. Data may be stale.';
        el.style.display = 'block';
    }

    function hideStaleBanner() {
        if (staleBannerEl) staleBannerEl.style.display = 'none';
    }

    function applyStaleStyling() {
        if (staleApplied) return;
        const rows = document.querySelectorAll('#icmpTableBody tr');
        rows.forEach(r => {
            r.style.backgroundColor = 'yellow';
            r.style.color = 'black';
            try { r.setAttribute('data-stale', '1'); } catch (e) {}
        });
        staleApplied = true;
    }

    function removeStaleStyling() {
        if (!staleApplied) return;
        const rows = document.querySelectorAll('#icmpTableBody tr[data-stale="1"]');
        rows.forEach(r => {
            r.style.backgroundColor = '';
            r.style.color = '';
            r.removeAttribute('data-stale');
        });
        staleApplied = false;
    }

    function checkStaleness() {
        // Do not show stale state if paused by user or editing
        const paused = !updateInterval || pausedState.manual || pausedState.edit;
        if (paused) {
            hideStaleBanner();
            removeStaleStyling();
            return;
        }
        const offline = (typeof navigator !== 'undefined' && navigator && navigator.onLine === false);
        const tooOld = (Date.now() - lastSuccessAt) > (staleMinutes * 60 * 1000);
        if (offline || tooOld) {
            showStaleBanner();
            applyStaleStyling();
        } else {
            hideStaleBanner();
            removeStaleStyling();
        }
    }

    return {
        init: init,
        start: startAutoRefresh,
        stop: stopAutoRefresh,
        refresh: fetchAndUpdateTable,
        renderTable: renderTable,
        setGroupId: function(id) { currentGroupId = id; }
    };
})();

document.addEventListener('DOMContentLoaded', function() {
    if (window.icmpRefreshConfig && window.IcmpTableUpdater) {
        window.IcmpTableUpdater.init(window.icmpRefreshConfig);
    }
});
