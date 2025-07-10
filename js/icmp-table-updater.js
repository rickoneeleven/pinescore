

const IcmpTableUpdater = (function() {
    'use strict';
    
    let updateInterval = null;
    let refreshRate = 10000;
    let currentGroupId = null;
    let updateCount = 0;
    let isUpdating = false;
    let fullScreenMode = false;
    let pendingData = null;
    let previousDataByIp = {};
    
    const tableBodySelector = '#icmpTableBody';

    const autoRefreshToggleSelector = '#autoRefreshToggle';
    const fullscreenToggleSelector = '#fullscreenToggle';
    
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
        
        const fullscreenButton = document.querySelector(fullscreenToggleSelector);
        if (fullscreenButton) {
            fullscreenButton.addEventListener('click', handleFullscreenToggle);
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
        
        updateInterval = setInterval(fetchAndUpdateTable, refreshRate);
        updateToggleButton(true);

        fetchAndUpdateTable();
    }
    
    function stopAutoRefresh() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
        
        stopSequentialUpdate();
        updateToggleButton(false);
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
        } else {
            button.textContent = '[Stop Auto Refresh]';
            button.style.color = 'green';
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
        return editInputs.length > 0;
    }
    
    function fetchAndUpdateTable() {
        if (isUpdating) return;

        if (isFormInEditMode()) {
            updateToggleButtonForEditMode(true);
            return;
        } else {
            updateToggleButtonForEditMode(false);
        }
        
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

                pendingData = data;
                updateHealthMetrics(data);
                updateGroupScores(data);
                startSequentialUpdate();
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

            const firstCell = row.querySelector('td:first-child');
            if (firstCell) {
                firstCell.textContent = rowCount;
            }

            const lastCheck = new Date(data.lastcheck);
            const now = new Date();
            const minutesDiff = (now - lastCheck) / (1000 * 60);
            if (minutesDiff > 5) {
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

        if (minutesDiff > 5) {
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
    
    function startSequentialUpdate() {
        stopSequentialUpdate();
        
        if (!pendingData || !pendingData.ips) return;
        
        const ipsArray = Object.entries(pendingData.ips);
        if (ipsArray.length === 0) return;

        // Skip sequential updates - go straight to bulk
        bulkUpdateRemainingRows(ipsArray, 0);
        pulsePageBackground();
    }
    
    function stopSequentialUpdate() {
        // No longer needed - keeping empty function to avoid breaking calls
    }
    
    
    function bulkUpdateRemainingRows(ipsArray, startIndex) {

        for (let i = startIndex; i < ipsArray.length; i++) {
            const [ip, data] = ipsArray[i];
            updateSingleRowBulk(ip, data, i);
        }
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
        if (minutesDiff > 5) {
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
    
    
    function pulsePageBackground() {
        const content = document.querySelector('.content');
        if (!content) return;
        
        const originalBg = content.style.backgroundColor || '';
        
        content.style.backgroundColor = '#f0f0f0';
        
        setTimeout(() => {
            content.style.backgroundColor = originalBg;
        }, 300);
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

    return {
        init: init,
        start: startAutoRefresh,
        stop: stopAutoRefresh,
        refresh: fetchAndUpdateTable
    };
})();

document.addEventListener('DOMContentLoaded', function() {
    if (window.icmpRefreshConfig) {
        IcmpTableUpdater.init(window.icmpRefreshConfig);
    }
});