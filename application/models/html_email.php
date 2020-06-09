<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class html_email extends CI_model {

    /**
     * 'body' =>
     */
    public function htmlFormatted($array) {
    $formatted_email = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>pinescore.com notification</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
</html>
<!--https://webdesign.tutsplus.com/articles/build-an-html-email-template-from-scratch--webdesign-12770-->
<body style="margin: 0; padding: 0;">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td>
<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
<tr>
<td align="center" bgcolor="#FFF" style="padding: 40px 0 30px 0;">
 <img src="https://pinescore.com/111/ns_trans_long.png" alt="pinescore.com" width="300" height="36.76" style="display: block;" />
</td>
</tr>
<tr>
<td bgcolor="#ffffff" style="padding: 40px 30px 40px 30px;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
     <tr>
      <td>
       <p>Je m\'appelle comrade</p>
      </td>
     </tr>
     <tr>
      <td style="padding: 20px 0 30px 0;">
       <p>'.$array['body'].'</p>
       <br><br><br>
      </td>
     </tr>
    </table>
</td>
</tr>
<tr>
<td bgcolor="#ee4c50" style="padding: 15px 15px 15px 15px;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
     <tr>
        <td>
         <p>&reg; pinescore.com 2014<br/></p>
        </td>
     </tr>
    </table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
';
    return $formatted_email;
    }
}
