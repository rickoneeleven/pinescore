<?php 
    echo '<table style="background-color: rgba(160, 160, 160, .4);">';
    echo "<tr><td>All in One Lookup Tool (MX, PTR, A, NS, TXT and WHOIS)</td></tr>

    <tr><td>"; ?> 
       <form action="<?php echo base_url()?>tools/dns_form" method="post" accept-charset="utf-8" onsubmit="this.elements['mysubmit'].disabled=true;">
       <?php
       echo 'Domain: <input type="text" name="host" value="'.$dom_bestguess.'"/> '.
       '<input type="submit" name="mysubmit" onClick="this.form.submit(); this.disabled=true; this.value=\'Processing…\'; " value="Lookup"/>'.'</td>
    </form></tr>
    ';
    echo "</table><br>";
    
    if(isset($dns)) {
        if($dns == 1) {
            echo "<pre>";
            if(isset($PTR)) {
                echo "<strong>PTR</strong>"." -> ".$PTR." <br>";
            }
            echo "<br>";
            if($MX['1']['ec']>0) {
                echo $MX['1']['em']."<br>";
            } else {
                foreach($MX as $record_num => $record) {

                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['Target']." [Priority: ". $record['Priority']."] -> ";
                    if(isset($MX_A[$record_num]['1']['IP'])) {
                        echo $MX_A[$record_num]['1']['IP']." <strong>PTR</strong>-> ". $MX_PTR[$record_num] ."<br>";
                    } else {
                        echo '<font color="red"><strong>No A record setup for MX record</strong></font><br>';
                    }
                }

            }
            echo "<br>";
            if($A['1']['ec']>0) {
                echo $A['1']['em']."<br>";
            } else {
                foreach($A as $record_num => $record) {

                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['IP']." <br>";
                }
            }
            echo "<br>";
            if($NS['1']['ec']>0) {
                echo $NS['1']['em']."<br>";
            } else {
                foreach($NS as $record_num => $record) {

                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['Target']." <br>";
                }
            }
            echo "<br>";
            if($TXT['1']['ec']>0) {
                echo $TXT['1']['em']."<br>";
            } else {
                foreach($TXT as $record_num => $record) {

                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['Record']." <br>";
                }
            }
                echo "<br>".$whois;
                echo "</pre>";
        }  
    }
?>
	
