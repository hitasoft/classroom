<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	
	<title>Auto Generating Photo Gallery</title>
	
	<link rel="stylesheet" type="text/css" href="/image/agpg/style.css" />
	<link rel="stylesheet" type="text/css" href="/image/agpg/resources/fancy.css" />
	
</head>

<body>

	<div id="page-wrap">
	
	<img src="/image/agpg/resources/header.png" alt="Photo Gallery" /><br />

	<?php
		
		/* settings */
		$image_dir = 'icon/16/';
		$per_column = 10;
		
		
		/* step one:  read directory, make array of files */
		if ($handle = opendir($image_dir)) {
			while (false !== ($file = readdir($handle))) 
			{
				if ($file != '.' && $file != '..') 
				{
					$files[] = $file;
				}
			}
			closedir($handle);
		}
		
		/* step two: loop through, format gallery */
		if(count($files))
		{
         asort($files);
			foreach($files as $file)
			{
				$count++;
            $stem=pathinfo($file,PATHINFO_FILENAME);
            $href2='';
            if (file_exists('icon/32/'.$file)){
               $href="class='sz32' href='icon/32/$file' title='$stem:32px available'";
               if (file_exists('icon/24/'.$file)){
                  $href2='<a target="_blank" class="second-link" href="icon/24/'.$file.'">24px</a>';
               }
            }elseif (file_exists('icon/24/'.$file)){
               $href="class='sz24' href='icon/24/$file' title='$stem:24px available'";
            }else{
               $href="title='$stem'";
            }
				echo "<div class='photo-link'><a target='_blank' $href><img src='$image_dir$file' width='16' height='16' /></a>";
            echo "$href2</div>";
				if($count % $per_column == 0) { echo '<div class="clear"></div>'; }
			}
		}
		else
		{
			echo '<p>There are no images in this gallery.</p>';
		}
		
	?>
	
	</div>

</body>

</html>
