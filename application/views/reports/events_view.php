<?php
$groupId = isset($group_id) ? (int) $group_id : null;
$groupName = isset($group_name) ? $group_name : null;
$defaultWindow = 'all';
$config = [
    'endpoint' => site_url('events/json'),
    'exportEndpoint' => site_url('events/export'),
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
        <div class="events-control-group" data-events-view-filters>
            <button type="button" class="events-control" data-filter="onePlus">1+</button>
            <button type="button" class="events-control" data-filter="twoPlus">2+</button>
            <button type="button" class="events-control" data-filter="tenPlus">10+</button>
        </div>
        <input type="text" class="events-search" placeholder="Search IP or note" data-events-search>
        <button type="button" class="events-control" data-events-refresh>Refresh</button>
        <button type="button" class="events-control" data-events-export>Export CSV</button>
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
