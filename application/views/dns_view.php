<?php 
    echo '<table style="background-color: rgba(160, 160, 160, .4);">'; //table start
    echo "<tr><td>All in One Lookup Tool (MX, PTR, A, NS, TXT and WHOIS)</td></tr>

    <tr><td>"; ?> 
       <form action="<?php echo base_url()?>tools/dns_form" method="post" accept-charset="utf-8" onsubmit="this.elements['mysubmit'].disabled=true;">
       <?php //we do this silly pho tag open and close business so we can disable the submit button when pressed and the syntax of that JS has "'"' all in same thing, this seems easiest way to get around that
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
            if($MX['1']['ec']>0) { //if there's no first record then there is no records at all, therefor error the error message ['em']
                echo $MX['1']['em']."<br>";
            } else {
                foreach($MX as $record_num => $record) {
                    //print_r($record);
                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['Target']." [Priority: ". $record['Priority']."] -> ";
                    if(isset($MX_A[$record_num]['1']['IP'])) {
                        echo $MX_A[$record_num]['1']['IP']." <strong>PTR</strong>-> ". $MX_PTR[$record_num] ."<br>"; //because the dns lookup function returns $data['record#'] but for the PTR lookup it will always be returning record#1 for each seperate lookup
                    } else {
                        echo '<font color="red"><strong>No A record setup for MX record</strong></font><br>';
                    }
                }
                //echo $MX['1']." -> ". $MX['2']." -> ". $MX_A['2'].$MX['6']."<br>";
            }
            echo "<br>";
            if($A['1']['ec']>0) { //if there's no first record then there is no records at all, therefor error the error message ['em']
                echo $A['1']['em']."<br>";
            } else {
                foreach($A as $record_num => $record) {
                    //print_r($record);
                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['IP']." <br>";
                }
            }
            echo "<br>";
            if($NS['1']['ec']>0) { //if there's no first record then there is no records at all, therefor error the error message ['em']
                echo $NS['1']['em']."<br>";
            } else {
                foreach($NS as $record_num => $record) {
                    //print_r($record);
                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['Target']." <br>";
                }
            }
            echo "<br>";
            if($TXT['1']['ec']>0) { //if there's no first record then there is no records at all, therefor error the error message ['em']
                echo $TXT['1']['em']."<br>";
            } else {
                foreach($TXT as $record_num => $record) {
                    //print_r($record);
                    echo "<strong>".$record['Type']."</strong>"." -> ".$record['Record']." <br>";
                }
            }
                echo "<br>".$whois;
                echo "</pre>";
        }  
    }
?>
	
