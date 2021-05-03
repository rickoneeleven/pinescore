<?php

    if ($tracerouteReports->num_rows < 1) {
        echo 'on noes me sad ;<br>please give me an hour to generate some traces for you.';
    } else {
        foreach ($tracerouteReports->result() as $row) {
            $better_format_date = strtotime($row->created_at);
            echo date('d-m-Y H:i:s', $better_format_date);
            echo "<pre>";
            echo $row->report;
            echo '</pre>';
            echo "<br><br>";
        }
    }
