<?php
session_name('connect');
session_start();
function upBox($type,$prep){
   if ($type!='document' && $camera) echo '
   <div class="uploadWrap">';
   echo <<<EOF
      <div id="uploadCont">
         <div id="uploadBox">       
           <noscript>          
               <p>Please enable JavaScript to use file uploader.</p>
           </noscript>         
         </div>
      </div>
EOF;
   if ($type!='document' && $camera) echo <<<EOF
   </div>
   <div class="uploadWrap">
      <div id="cameraCont">
      </div>
   </div>
EOF;
   echo <<<EOF
   <div id="groupUp">
      <div class="upImage">
         <img src="/image/icon/32/folder.png" alt="dir"><br/>
         <span></span>
      </div>
      <div class="inputWrap">
         <input type="text" name="pd_data[fgroup][]" mask="Name a folder for these files" value="">
      </div>
   </div>
   <div class="newUp">
      <div class="upImage">
         <img src="" alt=""><br/>
         <span></span>
      </div>
      <div class="inputWrap">
         <textarea rows="2" name="pd_data[fdesc][]" mask="Write a description for this $type"></textarea>
      </div>
      <input type="hidden" name="pd_data[fname][]">
   </div>
EOF;
}

?>
<html>
<head>
   <style type="text/css">div#post-tabs{display:none;}</style>
</head>
<body>
There is nothing here. Go away.
<div id="post-tabs">
<div id="pText">
   <div class="inputWrap">
      <textarea name="pd_data[ptext][]" id="postTxt" rows="1" mask="What's on your mind?"></textarea>
   </div>
</div>
<div id="pImages">
   <?php echo upBox('picture','a'); ?>
</div>
<div id="pDocs">
   <?php echo upBox('document','a'); ?>
</div>
<div id="pLinks">
   <div class="pLinksInput">
      <table class="skel">
         <tr>
            <td><div class="inputWrap">
               <input id="postLink" type="text" mask="http://">
               <input id="postLinkUrl" type="hidden" name="pd_data[ulink][]" value="">
            </div></td>
            <td><button>Attach</button></td>
         </tr>
      </table>
   </div>
</div>
<div id="upImage">
   <?php echo upBox('photo','a'); ?>
</div>
</div>
</body>
</html>
