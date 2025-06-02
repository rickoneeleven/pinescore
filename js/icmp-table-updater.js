/**
 * ICMP Table Dynamic Updater
 * Replaces HTML meta refresh with AJAX updates
 */
const IcmpTableUpdater = (function() {
    'use strict';
    
    let updateInterval = null;
    let refreshRate = 10000; // 10 seconds default
    let currentGroupId = null;
    let updateCount = 0;
    let isUpdating = false;
    let fullScreenMode = false;
    
    const tableBodySelector = '#icmpTableBody';
    // Countdown removed - no longer needed with AJAX
    const autoRefreshToggleSelector = '#autoRefreshToggle';
    
    function init(options = {}) {
        refreshRate = options.refreshRate || refreshRate;
        currentGroupId = options.groupId || null;
        
        if (options.autoStart) {
            startAutoRefresh();
        }
        
        bindEventHandlers();
    }
    
    function bindEventHandlers() {
        const toggleButton = document.querySelector(autoRefreshToggleSelector);
        if (toggleButton) {
            toggleButton.addEventListener('click', handleToggleClick);
        }
    }
    
    function handleToggleClick(e) {
        e.preventDefault();
        
        if (updateInterval) {
            stopAutoRefresh();
            exitFullScreen();
        } else {
            startAutoRefresh();
            enterFullScreen();
        }
    }
    
    function startAutoRefresh() {
        if (updateInterval) return;
        
        updateInterval = setInterval(fetchAndUpdateTable, refreshRate);
        updateToggleButton(true);
        
        // Immediate first update
        fetchAndUpdateTable();
    }
    
    function stopAutoRefresh() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
        
        updateToggleButton(false);
    }
    
    function updateToggleButton(isActive) {
        const button = document.querySelector(autoRefreshToggleSelector);
        if (!button) return;
        
        if (isActive) {
            button.textContent = '[Exit Full Screen]';
            button.style.color = 'green';
        } else {
            button.textContent = '[Auto Refresh]';
            button.style.color = 'red';
        }
    }
    
    function enterFullScreen() {
        fullScreenMode = true;
        document.body.classList.add('icmp-fullscreen');
        
        // Hide navigation and forms
        const elementsToHide = [
            '#ping_add_container', // Main pingAdd container
            'form[action*="pingAdd_formProcess"]', // pingAdd form
            '.signin_form', // Login form in header
            '.topleft', // Logo area
            'hr.spaced', // Horizontal rules from navTop
            'div > h2', // Page titles in navTop
            '.viewgroupmenu', // Group menu
            '.full_width', // Group menu container
            '#pa_left', // Left panel of pingAdd
            '#pa_right', // Right panel with groups
            '.screenshots' // Screenshot divs
        ];
        
        elementsToHide.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (!el.closest('#icmp_table')) {
                    el.style.display = 'none';
                }
            });
        });
        
        // Add full screen styling
        const style = document.createElement('style');
        style.id = 'icmp-fullscreen-style';
        style.textContent = `
            .icmp-fullscreen .content {
                margin: 10px;
                padding: 10px;
            }
            .icmp-fullscreen #icmp_table {
                margin-top: 0;
            }
        `;
        document.head.appendChild(style);
    }
    
    function exitFullScreen() {
        fullScreenMode = false;
        document.body.classList.remove('icmp-fullscreen');
        
        // Show hidden elements
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
            '.screenshots'
        ];
        
        elementsToShow.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.display = '';
            });
        });
        
        // Remove full screen styling
        const style = document.getElementById('icmp-fullscreen-style');
        if (style) {
            style.remove();
        }
    }
    
    function fetchAndUpdateTable() {
        if (isUpdating) return;
        
        isUpdating = true;
        const url = currentGroupId 
            ? `/tools/getIcmpDataJson/${currentGroupId}`
            : '/tools/getIcmpDataJson';
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('API Error:', data.error);
                    return;
                }
                
                updateTable(data.ips);
                updateHealthMetrics(data);
                updateGroupScores(data);
                updateCount++;
            })
            .catch(error => {
                console.error('Fetch Error:', error);
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
            // Set row number
            const firstCell = row.querySelector('td:first-child');
            if (firstCell) {
                firstCell.textContent = rowCount;
            }
            // Check for 5-minute timeout
            const lastCheck = new Date(data.lastcheck);
            const now = new Date();
            const minutesDiff = (now - lastCheck) / (1000 * 60);
            if (minutesDiff > 5) {
                row.style.backgroundColor = 'yellow';
                row.style.color = 'black';
            }
            fragment.appendChild(row);
        });
        
        // Clear and update table
        tableBody.innerHTML = '';
        tableBody.appendChild(fragment);
    }
    
    function createTableRow(ip, data) {
        const row = document.createElement('tr');
        const rowClass = getRowClass(data);
        if (rowClass) row.className = rowClass;
        
        // Row counter
        const countCell = document.createElement('td');
        countCell.textContent = ''; // Will be set by parent
        row.appendChild(countCell);
        
        // Note column
        const noteCell = document.createElement('td');
        noteCell.innerHTML = '<a href="/tools/report/' + data.id + '">' + (data.note || '') + '</a>';
        row.appendChild(noteCell);
        
        // Status column with count
        const statusCell = document.createElement('td');
        statusCell.innerHTML = createStatusCell(data);
        row.appendChild(statusCell);
        
        // PineScore column
        const scoreCell = document.createElement('td');
        scoreCell.innerHTML = createScoreCell(data);
        row.appendChild(scoreCell);
        
        // Recent ms/Last online column
        const msCell = document.createElement('td');
        msCell.innerHTML = createMsCell(data);
        row.appendChild(msCell);
        
        // LTA column
        const ltaCell = document.createElement('td');
        ltaCell.innerHTML = '<a href="/nc/storyTimeNode/' + data.id + '">' + 
            (data.average_longterm_ms || '??') + 'ms</a>';
        row.appendChild(ltaCell);
        
        // Trace column
        const traceCell = document.createElement('td');
        traceCell.innerHTML = '<a href="/traceroute/routesthathavebeentraced/' + ip + '"> (tr) </a>';
        row.appendChild(traceCell);
        
        // Last Checked column
        const lastCheckCell = document.createElement('td');
        lastCheckCell.textContent = data.lastcheck + ' ';
        row.appendChild(lastCheckCell);
        
        // IP column
        const ipCell = document.createElement('td');
        ipCell.textContent = ip;
        row.appendChild(ipCell);
        
        // Alert column (if owner)
        if (window.ownerMatchesTable) {
            const alertCell = document.createElement('td');
            alertCell.textContent = data.alert || '';
            row.appendChild(alertCell);
        }
        
        // Public column
        const publicCell = document.createElement('td');
        publicCell.textContent = data.public == 1 ? 'Yes' : 'No';
        row.appendChild(publicCell);
        
        // Actions column (if owner)
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
        
        // Check if lastcheck is older than 5 minutes
        if (minutesDiff > 5) {
            return ''; // Will be styled with inline style
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
        const scoreChangeTime = new Date(data.pinescore_change);
        
        if (scoreChangeTime > fifteenMinsAgo) {
            return '<font color="red"><strong>' + data.score + '</strong></font>';
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
        
        const jobsPerMin = parseInt(data.jobs_per_minute) || 0;
        const failedJobs = parseInt(data.failed_jobs_past_day) || 0;
        const engineStatus = data.engine_status || 'unknown';
        
        const jobsColor = jobsPerMin < 1000 ? 'orange' : 'green';
        const failedColor = failedJobs === 0 ? 'green' : 'red';
        const engineColor = engineStatus === 'active' ? 'green' : 'red';
        
        metricsContainer.innerHTML = 
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
    
    // Countdown and reminder functionality removed - no longer needed with AJAX
    
    // Public API
    return {
        init: init,
        start: startAutoRefresh,
        stop: stopAutoRefresh,
        refresh: fetchAndUpdateTable
    };
})();

// Auto-initialize if config is present
document.addEventListener('DOMContentLoaded', function() {
    if (window.icmpRefreshConfig) {
        IcmpTableUpdater.init(window.icmpRefreshConfig);
    }
});