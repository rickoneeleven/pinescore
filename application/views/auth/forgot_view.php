<div class="reg_form">
    <?php
    if(isset($sent)) {
        echo "If your email address exists in our system, the next steps will have been sent to you. If you can't remember your account details at all, please contact us using the link at the bottom of the site for further assistance.";
    } else { ?>
        <div class="form_title">Password Reset</div>
        <div class="form_sub_title"></br></div>
         <?php echo validation_errors('<p class="error">');
         echo $this->session->flashdata('message'); //this gets set with form submission output
         ?>
         
         <?php echo form_open("auth/user/forgotFormProcess"); ?>
          <p>
          <label for="email">E-mail:</label>
          <input type="text" id="email" name="email" value="<?php echo set_value('email'); ?>" />
          </p>
        <p>
          <input type="submit" class="greenButton" value="Submit" />
          </p>
        <p>
            <?php
                if(isset($captcha_requested)) {
                    echo form_label("<tr><td>Verify Image: </td> <td>", "verify");
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
        <?php echo form_close();
    } ?>
</div><!--<div class="reg_form">-->
