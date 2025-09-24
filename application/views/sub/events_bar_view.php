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
];
?>
<div class="events-bar" data-events-bar>
    <div class="events-bar-header">
        <span class="events-bar-title">Latest events</span>
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
