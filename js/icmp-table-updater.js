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
    let animationInterval = null;
    let currentAnimationIndex = 0;
    let animationSpeed = 0; // Will be calculated based on node count
    let pendingData = null; // Store fetched data for sequential updates
    let previousDataByIp = {}; // Store previous values by IP for accurate comparison
    let animationStartTime = null; // Track when animation started
    
    const tableBodySelector = '#icmpTableBody';
    // Countdown removed - no longer needed with AJAX
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
        
        // Immediate first update
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
        if (!button || !updateInterval) return; // Only update if auto-refresh is active
        
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
            '.screenshots', // Screenshot divs
            '.signup_wrap' // Hide the entire header wrapper
        ];
        
        elementsToHide.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (!el.closest('#icmp_table')) {
                    el.style.display = 'none';
                }
            });
        });
        
        // Hide the Happy day message container but preserve healthMetrics
        const happyDayContainer = document.querySelector('div[style*="display: flex"]');
        if (happyDayContainer && happyDayContainer.querySelector('strong')) {
            const strongText = happyDayContainer.querySelector('strong').textContent;
            if (strongText.includes('Happy')) {
                // Move healthMetrics before hiding the container
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
        
        // Add full screen styling
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
            /* Hide br tags in icmp_table */
            .icmp-fullscreen #icmp_table > br {
                display: none;
            }
            /* Hide any br tags at the start of content */
            .icmp-fullscreen .content > br:first-child {
                display: none;
            }
            /* Hide br tags after ping_add_container */
            .icmp-fullscreen #ping_add_container ~ br {
                display: none;
            }
            /* Ensure table is at the top */
            .icmp-fullscreen #icmp_table table {
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
            '.screenshots',
            '.signup_wrap'
        ];
        
        elementsToShow.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.display = '';
            });
        });
        
        // Restore Happy day container and healthMetrics
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
        
        // Remove full screen styling
        const style = document.getElementById('icmp-fullscreen-style');
        if (style) {
            style.remove();
        }
    }
    
    function isFormInEditMode() {
        // Check if any table row contains input fields (indicates edit mode)
        const editInputs = document.querySelectorAll('#icmpTableBody input[type="text"], #icmpTableBody input[type="radio"]');
        return editInputs.length > 0;
    }
    
    function fetchAndUpdateTable() {
        if (isUpdating) return;
        
        // Don't update if any form is in edit mode (has input fields visible)
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
                
                // Store data for sequential updates
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
        lastCheckCell.innerHTML = data.lastcheck + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        row.appendChild(lastCheckCell);
        
        // IP column
        const ipCell = document.createElement('td');
        ipCell.textContent = ip;
        row.appendChild(ipCell);
        
        // Alert column (if owner)
        if (window.ownerMatchesTable) {
            const alertCell = document.createElement('td');
            const alertText = data.alert || '';
            
            // Apply same logic as PHP: count commas and show "X configured alerts" if multiple
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
        const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000);
        const scoreChangeTime = new Date(data.pinescore_change);
        
        const seconds = scoreChangeTime.getSeconds();
        if (seconds === 0 && scoreChangeTime > fifteenMinsAgo) {
            // Score dropped in last 15 minutes - red
            return '<font color="red"><strong>' + data.score + '</strong></font>';
        } else if (seconds === 1 && scoreChangeTime > twoHoursAgo) {
            // Score improved in last 2 hours - green
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
        stopSequentialUpdate(); // Clear any existing animation
        
        if (!pendingData || !pendingData.ips) return;
        
        const ipsArray = Object.entries(pendingData.ips);
        if (ipsArray.length === 0) return;
        
        // Static timing based on 18-row speed: 1000ms / 18 rows = ~55ms per row
        animationSpeed = 55; // Fixed speed regardless of table size
        currentAnimationIndex = 0;
        animationStartTime = Date.now();
        
        // Start the sequential update cycle
        animationInterval = setInterval(updateNextNode, animationSpeed);
    }
    
    function stopSequentialUpdate() {
        if (animationInterval) {
            clearInterval(animationInterval);
            animationInterval = null;
        }
        currentAnimationIndex = 0;
        
        // Clear any existing row animations
        const allRows = document.querySelectorAll('#icmpTableBody tr');
        allRows.forEach(row => {
            row.style.backgroundColor = '';
            row.style.transition = '';
            row.style.transform = '';
            row.style.boxShadow = '';
            row.style.border = '';
        });
    }
    
    function updateNextNode() {
        if (!pendingData || !pendingData.ips) {
            stopSequentialUpdate();
            return;
        }
        
        const ipsArray = Object.entries(pendingData.ips);
        if (ipsArray.length === 0) {
            stopSequentialUpdate();
            return;
        }
        
        // Check if we're running out of time (8.5 seconds elapsed)
        const timeElapsed = Date.now() - animationStartTime;
        if (timeElapsed > 8500 && currentAnimationIndex < ipsArray.length) {
            // Bulk update remaining rows and finish
            bulkUpdateRemainingRows(ipsArray, currentAnimationIndex);
            stopSequentialUpdate();
            return;
        }
        
        // Update current node
        if (currentAnimationIndex < ipsArray.length) {
            const [ip, data] = ipsArray[currentAnimationIndex];
            updateSingleRow(ip, data, currentAnimationIndex);
        }
        
        currentAnimationIndex++;
        
        // Stop after we've gone through all nodes once
        if (currentAnimationIndex >= ipsArray.length) {
            stopSequentialUpdate();
        }
    }
    
    function bulkUpdateRemainingRows(ipsArray, startIndex) {
        // Update all remaining rows without animation
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
        
        // Create new row data
        const newRow = createTableRow(ip, data);
        
        // Set row number
        const firstCell = newRow.querySelector('td:first-child');
        if (firstCell) {
            firstCell.textContent = rowIndex + 1;
        }
        
        // Check for 5-minute timeout styling
        const lastCheck = new Date(data.lastcheck);
        const now = new Date();
        const minutesDiff = (now - lastCheck) / (1000 * 60);
        if (minutesDiff > 5) {
            newRow.style.backgroundColor = 'yellow';
            newRow.style.color = 'black';
        }
        
        // Replace the row immediately (no animation delay)
        if (row.parentNode) {
            row.parentNode.replaceChild(newRow, row);
            
            // Apply LTA styling and change detection
            applyLtaStyling(newRow, data);
            animateCellChanges(newRow, oldData, data, ip);
            
            // Store the new data for this IP for next comparison
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
    
    function updateSingleRow(ip, data, rowIndex) {
        const rows = document.querySelectorAll('#icmpTableBody tr');
        if (rowIndex >= rows.length) return;
        
        const row = rows[rowIndex];
        
        // Apply the animation effect first
        applyRowAnimation(row, 'hover'); // Try: 'hover', 'pulse', 'glow', 'slide', 'rainbow'
        
        // Get the old values for THIS IP from our stored data
        const oldData = previousDataByIp[ip] || {};
        
        // Create new row data
        const newRow = createTableRow(ip, data);
        
        // Set row number
        const firstCell = newRow.querySelector('td:first-child');
        if (firstCell) {
            firstCell.textContent = rowIndex + 1;
        }
        
        // Check for 5-minute timeout styling
        const lastCheck = new Date(data.lastcheck);
        const now = new Date();
        const minutesDiff = (now - lastCheck) / (1000 * 60);
        if (minutesDiff > 5) {
            newRow.style.backgroundColor = 'yellow';
            newRow.style.color = 'black';
        }
        
        // Replace the row content after animation
        setTimeout(() => {
            if (row.parentNode) {
                row.parentNode.replaceChild(newRow, row);
                
                // Always apply LTA-based styling first (regardless of data changes)
                applyLtaStyling(newRow, data);
                
                // Then apply change animations to the new row
                animateCellChanges(newRow, oldData, data, ip);
                
                // Store the new data for this IP for next comparison
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
        }, 150); // Reduced from 300ms to minimize lag
    }
    
    function applyRowAnimation(row, animationType = 'hover') {
        if (!row) return;
        
        // Store original styles
        const originalBg = row.style.backgroundColor;
        const originalTransition = row.style.transition;
        const originalTransform = row.style.transform;
        const originalBoxShadow = row.style.boxShadow;
        
        switch(animationType) {
            case 'hover':
                // Simulate hover effect
                row.style.transition = 'background-color 0.2s ease-in-out';
                row.style.backgroundColor = '#f0f0f0';
                setTimeout(() => {
                    row.style.backgroundColor = originalBg;
                }, 400);
                break;
                
            case 'pulse':
                // Pulsing glow effect
                row.style.transition = 'box-shadow 0.3s ease-in-out';
                row.style.boxShadow = '0 0 10px rgba(0, 123, 255, 0.6)';
                setTimeout(() => {
                    row.style.boxShadow = originalBoxShadow;
                }, 500);
                break;
                
            case 'glow':
                // Blue glow border effect
                row.style.transition = 'border 0.2s ease-in-out, box-shadow 0.2s ease-in-out';
                row.style.border = '2px solid #007bff';
                row.style.boxShadow = '0 0 8px rgba(0, 123, 255, 0.4)';
                setTimeout(() => {
                    row.style.border = '';
                    row.style.boxShadow = originalBoxShadow;
                }, 400);
                break;
                
            case 'slide':
                // Slide animation
                row.style.transition = 'transform 0.3s ease-in-out';
                row.style.transform = 'translateX(10px)';
                setTimeout(() => {
                    row.style.transform = 'translateX(0)';
                }, 150);
                setTimeout(() => {
                    row.style.transform = originalTransform;
                }, 400);
                break;
                
            case 'rainbow':
                // Color cycle effect
                const colors = ['#ffcccc', '#ccffcc', '#ccccff', '#ffffcc', '#ffccff'];
                let colorIndex = 0;
                row.style.transition = 'background-color 0.1s ease-in-out';
                
                const colorInterval = setInterval(() => {
                    row.style.backgroundColor = colors[colorIndex % colors.length];
                    colorIndex++;
                    if (colorIndex >= colors.length * 2) {
                        clearInterval(colorInterval);
                        row.style.backgroundColor = originalBg;
                    }
                }, 60);
                break;
                
            default:
                // Default hover effect
                row.style.transition = 'background-color 0.2s ease-in-out';
                row.style.backgroundColor = '#f0f0f0';
                setTimeout(() => {
                    row.style.backgroundColor = originalBg;
                }, 400);
        }
        
        // Clean up transition after animation
        setTimeout(() => {
            row.style.transition = originalTransition;
        }, 600);
    }
    
    function animateCellChanges(newRow, oldData, newData, ip) {
        if (!newRow) return;
        
        const cells = newRow.cells;
        
        // Check if last checked time actually changed - if not, skip all bold logic
        const lastCheckChanged = oldData.lastCheck !== newData.lastcheck;
        if (!lastCheckChanged) {
            return; // Skip all bold logic if data hasn't actually refreshed
        }
        
        // Note (cell 1) - bold if changed
        if (cells[1] && oldData.note !== (newData.note || '')) {
            cells[1].style.fontWeight = 'bold'; // Keep bold until next refresh
        }
        
        // Status (cell 2) - special handling for count number
        if (cells[2]) {
            // Check if status or count changed
            if (oldData.status !== newData.last_email_status || oldData.count !== newData.count) {
                // Extract the count from the innerHTML
                const countMatch = cells[2].innerHTML.match(/\[([^\]]+)\]/);
                if (countMatch && countMatch[1]) {
                    const countHtml = countMatch[1];
                    const strongMatch = countHtml.match(/<strong>(\d+)<\/strong>/);
                    if (strongMatch) {
                        // Flash the count number yellow
                        const tempHtml = cells[2].innerHTML.replace(
                            /<strong>(\d+)<\/strong>/,
                            '<strong style="background-color: yellow; transition: background-color 0.3s;">$1</strong>'
                        );
                        cells[2].innerHTML = tempHtml;
                        
                        // Remove yellow after delay
                        setTimeout(() => {
                            cells[2].innerHTML = cells[2].innerHTML.replace(
                                /style="[^"]*"/,
                                ''
                            );
                        }, 500);
                    }
                }
                
                // Also keep the whole cell bold
                cells[2].style.fontWeight = 'bold'; // Keep bold until next refresh
            }
        }
        
        // PineScore (cell 3) - bold if changed (red color handled by createScoreCell)
        if (cells[3] && oldData.pineScore !== newData.score) {
            cells[3].style.fontWeight = 'bold'; // Keep bold until next refresh
            // Note: Red color for recent changes is already handled in createScoreCell function
        }
        
        // Recent ms (cell 4) - bold if last checked changed AND ms differs from LTA
        if (cells[4] && newData.last_email_status === 'Online') {
            const newMsNum = parseInt(newData.ms);
            const ltaNum = parseInt(newData.average_longterm_ms);
            
            // Bold if last checked changed AND ms is different from LTA
            if (lastCheckChanged && !isNaN(newMsNum) && !isNaN(ltaNum) && newMsNum !== ltaNum) {
                cells[4].style.fontWeight = 'bold'; // Keep bold until next refresh
            }
        }
        
        // LTA (cell 5) - bold if changed
        if (cells[5] && oldData.lta !== newData.average_longterm_ms) {
            cells[5].style.fontWeight = 'bold'; // Keep bold until next refresh
        }
        
        // Last Checked (cell 7) - flash bold when row is updated
        if (cells[7] && lastCheckChanged) {
            cells[7].style.fontWeight = 'bold'; // Keep bold until next refresh
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
        
        const msCell = row.cells[4]; // Recent ms column
        if (!msCell) return;
        
        const currentMs = parseInt(data.ms);
        const ltaMs = parseInt(data.average_longterm_ms);
        
        // Apply color based on LTA comparison for all online nodes
        if (!isNaN(currentMs) && !isNaN(ltaMs)) {
            if (currentMs > ltaMs) {
                msCell.style.color = 'red'; // Slower than LTA = red
            } else if (currentMs < ltaMs) {
                msCell.style.color = 'green'; // Faster than LTA = green
            }
            // If equal to LTA, keep default color
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