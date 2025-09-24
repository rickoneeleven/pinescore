<?php
$groupId = isset($group_id) ? (int) $group_id : null;
$groupName = isset($group_name) ? $group_name : null;
$defaultWindow = isset($default_window) ? $default_window : '24h';
$config = [
    'endpoint' => site_url('events/json'),
    'groupId' => $groupId,
    'defaultWindow' => $defaultWindow,
    'liveInterval' => 60000,
    'limit' => 100,
];
?>
<div class="events-view" data-events-view>
    <h1>Events timeline</h1>
    <?php if ($groupName): ?>
    <div class="events-chip-group">
        Group: <?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?>
        <a href="<?php echo site_url('events'); ?>">Remove</a>
    </div>
    <?php else: ?>
    <div class="events-chip-group">Scope: All groups</div>
    <?php endif; ?>
    <div class="events-controls">
        <div class="events-control-group" data-events-window-buttons>
            <button type="button" class="events-control" data-window="24h">Last 24h</button>
            <button type="button" class="events-control" data-window="all">All</button>
        </div>
        <input type="text" class="events-search" placeholder="Search IP or note" data-events-search>
        <button type="button" class="events-control" data-events-refresh>Refresh</button>
        <label class="events-live">
            <input type="checkbox" data-events-live>
            Live
        </label>
    </div>
    <div class="events-status-message" data-events-status></div>
    <div class="events-items" data-events-items></div>
    <button type="button" class="events-load-more" data-events-load-more>Load more</button>
</div>
<script>
window.eventsViewConfig = <?php echo json_encode($config, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo base_url(); ?>js/events-view.js?v=<?php echo time(); ?>" defer></script>
