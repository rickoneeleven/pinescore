<?php
$groupId = isset($group_id) ? (int) $group_id : null;
$groupName = isset($group_name) ? $group_name : null;
$timelineUrl = site_url('events');
if ($groupId) {
    $timelineUrl .= '?group=' . $groupId;
}
$config = [
    'endpoint' => site_url('events/bar'),
    'groupId' => $groupId,
    'pollInterval' => 10000,
    'defaultFilter' => 'twoPlus',
    'limit' => 5,
    'defaultMinScore' => 1,
];
?>
<div class="events-bar" data-events-bar>
    <div class="events-bar-header">
        <div class="events-bar-heading">
            <span class="events-bar-title">Latest events</span>
            <div class="events-bar-filter-group" data-events-bar-filters>
                <button type="button" class="events-bar-filter" data-filter="onePlus">1+</button>
                <button type="button" class="events-bar-filter" data-filter="twoPlus">2+</button>
                <button type="button" class="events-bar-filter" data-filter="tenPlus">10+</button>
            </div>
            <div class="events-bar-filter-group" data-events-bar-score-filter>
                <button type="button" class="events-bar-filter" data-min-score="1">Score > 0</button>
                <button type="button" class="events-bar-filter" data-min-score="0">All</button>
            </div>
        </div>
        <a class="events-bar-link" href="<?php echo $timelineUrl; ?>">View timeline</a>
    </div>
    <?php if ($groupName): ?>
    <div class="events-chip-group">Group: <?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <div class="events-bar-items" data-events-bar-items></div>
</div>
<script>
window.eventsBarConfig = <?php echo json_encode($config, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo base_url(); ?>js/events-bar.js?v=<?php echo time(); ?>" defer></script>
