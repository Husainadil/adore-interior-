<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class ImageOptimizer {
 public function store(UploadedFile $file): array {
  if(!extension_loaded('gd'))return ['path'=>'/storage/'.$file->store('media','public'),'mime_type'=>$file->getMimeType(),'size'=>$file->getSize()];
  $image=imagecreatefromstring(file_get_contents($file->getRealPath()));
  abort_unless($image,422,'This image could not be decoded.');
  $width=imagesx($image);$height=imagesy($image);$scale=min(1,2400/max($width,$height));
  $output=imagecreatetruecolor((int)round($width*$scale),(int)round($height*$scale));
  imagealphablending($output,false);imagesavealpha($output,true);
  imagecopyresampled($output,$image,0,0,0,0,imagesx($output),imagesy($output),$width,$height);
  ob_start();imagewebp($output,null,84);$bytes=ob_get_clean();
  $path='media/'.Str::uuid().'.webp';Storage::disk('public')->put($path,$bytes);
  return ['path'=>'/storage/'.$path,'mime_type'=>'image/webp','size'=>strlen($bytes)];
 }
}
