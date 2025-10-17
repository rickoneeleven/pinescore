<?php
$color_nscore = 'black';
$color_ms = 'black';
$link_space = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

// Simple navigation helpers
$limit = isset($nav['limit']) ? (int) $nav['limit'] : 500;
$order = (isset($nav['order']) && $nav['order'] === 'asc') ? 'asc' : 'desc';
$base  = isset($nav['base']) ? $nav['base'] : '';
$from  = isset($nav['from']) ? $nav['from'] : null;
$to    = isset($nav['to']) ? $nav['to'] : null;
$month = isset($nav['month']) ? $nav['month'] : null;
$user_range = isset($nav['user_range']) ? (bool)$nav['user_range'] : false;

$qs = function(array $params = []) use ($limit, $order, $from, $to, $user_range) {
    $p = ['limit' => $limit, 'order' => $order];
    if ($user_range) {
        if ($from) { $p['from'] = $from; }
        if ($to)   { $p['to']   = $to;   }
    }
    $p = array_merge($p, $params);
    return '?' . http_build_query($p);
};

// Build query string without any date range, for global paging across months/years
$qs_no_range = function(array $params = []) use ($limit, $order) {
    $p = ['limit' => $limit, 'order' => $order];
    $p = array_merge($p, $params);
    return '?' . http_build_query($p);
};

$currentMonth = $month ? $month : date('Y-m');
$prevMonth = date('Y-m', strtotime($currentMonth . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($currentMonth . '-01 +1 month'));
$nextMonthCap = ($nextMonth > date('Y-m')) ? null : $nextMonth;

echo '<a href="' . $base . $qs(['order' => 'desc', 'newest' => 1]) . '">Newest</a>' . $link_space;
echo '<a href="' . $base . $qs(['order' => 'asc', 'oldest' => 1]) . '">Oldest</a>' . $link_space;
// Quick filter for last 30 days
$last30_from = date('Y-m-d', strtotime('-30 days'));
$last30_to = date('Y-m-d');
echo '<a href="' . $base . $qs(['from' => $last30_from, 'to' => $last30_to]) . '">Last 30 days</a>' . $link_space;
echo '<a href="' . $base . '?month=' . $prevMonth . '&limit=' . $limit . '">Prev month</a>' . $link_space;
if ($nextMonthCap) {
    echo '<a href="' . $base . '?month=' . $nextMonthCap . '&limit=' . $limit . '">Next month</a>' . $link_space;
}
echo '<a href="#last">Jump Bottom</a>' . $link_space;
?>
<a name="first"></a>
<p>
<table>
<tr>
    <th width="150px">Node</th>
    <th width="100px">pinescore</th>
    <th width="100px">Round Time</th>
    <th width="150px">Logged</th>
    <th width="150px">Day of Week</th>
</tr>
<?php
$first_id = null;
$last_id = null;
$row_count = 0;
foreach ($historic_pinescoreTable->result() as $row) {
    $row_count++;
    if ($first_id === null) { $first_id = $row->id; }
    $last_id = $row->id;
    $color_nscore = 'black';
    $color_ms = 'black';
    if ($row->pinescore < 50) {
        $color_nscore = '#664400';
    }
    if ($row->pinescore < 0) {
        $color_nscore = 'red';
    }
    if ($row->ms > 100) {
        $color_ms = '#664400';
    }
    if ($row->ms > 1000) {
        $color_ms = 'red';
    }
    if ($row->ms == 0) {
        $row->ms = 'Offline';
    }
    echo "<tr>
            <td>$row->ip</td>
            <td><font color=\"$color_nscore\">$row->pinescore</font></td>
            <td><font color=\"$color_ms\">$row->ms</font></td>
            <td>$row->logged</td>
            <td>".date('l', strtotime($row->logged)).'
        </tr>';
}
?>
</table>
<?php
if ($row_count === 0) {
    echo '<p>No results for the selected range.</p>';
}
echo '<a name="last"></a>';
// Pager controls
if ($last_id) {
    if ($order === 'desc') {
        // Older should cross months/years: use global paging without date filters
        echo '<p><a href="' . $base . $qs_no_range(['before_id' => $last_id]) . '">Older</a>' . $link_space;
    } else {
        echo '<p><a href="' . $base . $qs_no_range(['after_id' => $last_id, 'order' => 'asc']) . '">Newer</a>' . $link_space;
    }
}
echo '<a href="' . $base . $qs(['order' => 'desc', 'newest' => 1]) . '">Newest</a>' . $link_space;
echo '<a href="' . $base . $qs(['order' => 'asc', 'oldest' => 1]) . '">Oldest</a>' . $link_space;
echo '<a href="#first">Jump Top</a>' . $link_space;
