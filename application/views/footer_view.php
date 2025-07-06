<?php
$footer_note = "Happy ".date('l');
if(rand(1,111) === 111) $footer_note = "Man can do what he wills but he cannot will what he wills";
if(rand(1,111) === 110) $footer_note = "Foresight is bought at the price of anxiety, and when overused it 
    destroys its advantage";
if(rand(1,111) === 109) $footer_note = "Success is not final, failure is not fatal; it is the courage to continue that counts."; 

?>
</div> <!-- <div class="content"> -->
<a name="last"</a>
<footer class="footer"><p>
      <?php echo safe_mailto('contact@pinescore.com').' | Your captain for today, Ryan Partington'; ?>
      <br>
          pinescore.com Copyright &#169; 2014 | do more with less | <?php echo $footer_note;?>
      <br>
      <?php
      $this->benchmark->mark('code_end');

      echo "Page loaded in ".substr($this->benchmark->elapsed_time('code_start', 'code_end'),0,-3)." seconds";
      ?>
</p></footer>
</body>
</div> <!-- <div id="wrap"> -->
</html>
