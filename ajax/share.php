<div class="shareUrl">
   <label><span>Link to this page</span>
      <input type="text" value="">
   </label>
</div>
<div class="shareButtons">
   <ul>
      <div class="sharePrimary">
         <li>
            <a class="shareIcon-fb" title="Share only this announcement to Facebook" target="_blank" href="http://www.facebook.com/sharer.php?u=<?php echo $_POST['url'];?>">
              <span>
                  Facebook
              </span>
            </a>
         </li>
         <li>
            <a class="shareIcon-tw" title="Share only this announcement to Twitter" target="_blank" href="http://twitter.com/share?url=<?php echo "{$_POST['url']}&text={$_POST['text']}&via=Educator";?>">
              <span>
                  Twitter
              </span>
            </a>
         </li>
         <li class="shareIcon-gp">
            <g:plusone size="small" count="false" url="<?php echo $_POST['url'];?>"></g:plusone>
         </li>
      </div>
   </ul>
</div>
<div class="closeBtn"></div>
<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
