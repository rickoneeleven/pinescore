<div class="reg_form">
    <div class="form_title">Register</div>
    <div class="form_sub_title"></br></div>
     <?php echo validation_errors('<p class="error">');
     echo $this->session->flashdata('message'); //this gets set with form submission output
     ?>
     
     <?php echo form_open("auth/user/registration"); ?>
      <p>
      <label for="email_address">Email:</label>
      <input type="text" id="email_address" name="email_address" value="<?php echo set_value('email_address'); ?>" />
      </p>
      <p>
      <label for="password">Password:</label>
      <input type="password" id="password" name="password" value="<?php echo set_value('password'); ?>" />
      </p>
      <p>
      <label for="con_password">Confirm Password:</label>
      <input type="password" id="con_password" name="con_password" value="<?php echo set_value('con_password'); ?>" />
      </p>
      <p>
      <input type="submit" class="greenButton" value="Submit" />
      </p>
    <p>
        <?php
            if(isset($captcha_requested)) {
                echo form_label("<tr><td>one hundred and eleven as number: </td> <td>", "verify");
                $data = array("name" => "verify",
                      "id" => "verify",
                      "value" => ""
                      );
                echo form_input($data); ?>
    </p>
    <p>
                <?php echo $cap_img;
                echo form_hidden('image', '111');
            } ?>
    </p>
    <?php echo form_close(); ?>
    
</div><!--<div class="reg_form">-->
