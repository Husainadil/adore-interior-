<?php
// Run locally with PHP GD enabled. Source photographs remain available as fallbacks.
foreach(glob(__DIR__.'/public/images/*.jpg') as $path){
 $image=imagecreatefromjpeg($path);if(!$image)continue;
 foreach([640,1024,1600] as $size){$width=min($size,imagesx($image));$height=(int)round(imagesy($image)*$width/imagesx($image));$output=imagecreatetruecolor($width,$height);imagecopyresampled($output,$image,0,0,0,0,$width,$height,imagesx($image),imagesy($image));imagewebp($output,preg_replace('/\.jpg$/','-'.$size.'.webp',$path),82);}
 echo basename($path)." optimized\n";
}
