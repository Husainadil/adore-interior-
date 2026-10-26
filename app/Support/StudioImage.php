<?php
namespace App\Support;
class StudioImage {
 public static function srcset(?string $path): string {
  if(!$path || !preg_match('#^/(images|storage/media)/[a-zA-Z0-9_./-]+$#',$path) || str_contains($path,'..'))return '';
  $base=preg_replace('/\.[^.]+$/','',$path);$sources=[];
  foreach([640,1024,1600] as $width){$variant=$base.'-'.$width.'.webp';if(is_file(public_path($variant)))$sources[]=$variant.' '.$width.'w';}
  return implode(', ',$sources);
 }
}
