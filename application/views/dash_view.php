<?php
    $slow = 350;
    foreach($ips as $ip => $latest) {
        echo'
            <div class="clientcard">
                <div class="cli_spacer">
                    <div class="cli_banner">
                        <p style="text-align:center">'.$latest['note'].' | pinescore: '.$latest['score'].'<br>
                        '.$ip.'</p>
                    </div>
                    <div class="cli_mon_contain">
                        <div class="cli_mon">
                            <p style="text-align:center">ICMP<br>
                            '.$latest['ms'].'ms</p>
                        </div>
                        <div class="cli_mon">
                            <p style="text-align:center">Email<br>
                            Online</p>
                        </div>
                        <div class="cli_mon">
                            <p style="text-align:center">Another<br>
                            Monitor</p>
                        </div>
                        <div class="cli_mon">
                            <p style="text-align:center">Another<br>
                            Monitor</p>
                        </div>
                        <div class="cli_mon">
                            <p style="text-align:center">Another<br>
                            Monitor</p>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }
?>