<?php
/*
 * feedback.php : feedback contact form popup
 *      
 */
session_name('connect');
session_start();

if ($_SERVER['REQUEST_METHOD']=="POST"){
   if ($_POST['c_email']){
      if ($_POST['c_name'])
         $from=$_POST['c_name'];
      else
         $from="No Name";
		$headers .= "From: ".$_POST['c_email']."\r\n";
		$headers .= "Content-type: text/plain; charset=ISO-8859-1";
		$subject = "$from has sent a contact message from Educator|Connect";
		$to = 'live.educator@gmail.com';
		$message = "\r\n--------------------------------------------------------------------------------".
         "\r\nFrom: $from".
         "\r\nEmail address: ".$_POST['c_email'].
         "\r\nArea of Interest: ".$_POST['c_area'].
         "\r\n--------------------------------------------------------------------------------".
         "\r\n".$_POST['c_message'];

		if (mail($to,$subject,$message,$headers) ) {
         echo "success";
      }else{
         echo "Could not send message. Please try again.";
      }
   }
}else{
   echo <<<EOF
   <div class="closeBtn"><a class="close"></a></div>
   <div id="fbdForm" class="dlgForm">
      <h2>Send Feedback</h2>
      <form method="post">
         <table>
            <tr class="dataRow">
               <th><label for="c_name">Name:</label></th>
               <td><input type="text" id="c_name" name="c_name" value=""></td>
            </tr>
            <tr class="dataRow">
               <th><label for="c_email">Email:<span>*</span></label></th>
               <td><input type="text" id="c_email" name="c_email" value=""></td>
            </tr>
            <tr class="dataRow">
               <th colspan="2" class="left"><label for="c_area">Area of Interest:<span>*</span></label></th>
            </tr>
            <tr class="dataRow">
               <td colspan="2">
                  <select id="c_area" name="c_area">
                     <option value="0">&mdash; please select a program &mdash;</option>
                     <option value="bug report">Report a bug</option>
                     <option value="suggestions">New feature suggestions</option>
                     <option value="others">Others (explain below)</option>
                  </select>
               </td>
            </tr>
            <tr class="dataRow">
               <th colspan="2" class="left"><label for="c_message">Comments &amp; Questions:<span>*</span></label></th>
            </tr>
            <tr class="dataRow">
               <td colspan="2">
                  <textarea id="c_message" name="c_message"></textarea>
               </td>
            </tr>
            <tr class="actionRow">
               <th></th>
               <td><input type="submit" value="Send your comments"></td>
            </tr>
         </table>
      </form>
   </div>
EOF;
}
?>
