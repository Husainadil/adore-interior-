<?php
// Real HTTP/session/CSRF/database smoke check for this local development instance.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!$app->environment('local'))throw new RuntimeException('This smoke test is restricted to local development.');
$base=$argv[1]??'http://127.0.0.1:8766';
if(!preg_match('#^http://127\.0\.0\.1:\d+$#',$base))throw new RuntimeException('Loopback URL required.');
$cookies=tempnam(sys_get_temp_dir(),'adore-qa');
$email='qa-'.bin2hex(random_bytes(6)).'@example.com';$jobsBefore=Illuminate\Support\Facades\DB::table('jobs')->pluck('id')->all();
$call=function(string $path,?array $body=null)use($base,$cookies){$ch=curl_init($base.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookies,CURLOPT_COOKIEFILE=>$cookies,CURLOPT_NOPROXY=>'*']);if($body!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($body)]);$html=curl_exec($ch);if($html===false)throw new RuntimeException(curl_error($ch));return [curl_getinfo($ch,CURLINFO_HTTP_CODE),$html];};
try{
 [$status]=$call('/inquiries',[]);if($status!==419)throw new RuntimeException('CSRF rejection failed: '.$status);
 [$status,$html]=$call('/contact');if($status!==200||!preg_match('/name="_token" value="([^"]+)"/',$html,$matches))throw new RuntimeException('Contact page or token missing.');
 [$status]=$call('/inquiries',['_token'=>html_entity_decode($matches[1]),'name'=>'Local QA Visitor','phone'=>'+91 90000 00000','email'=>$email,'project_type'=>'Villa','location'=>'Ahmedabad','message'=>'Local automated verification of the inquiry journey.']);
 if($status!==302)throw new RuntimeException('Submission did not redirect: '.$status);
 if(!App\Models\Inquiry::where('email',$email)->where('status','new')->exists())throw new RuntimeException('Inquiry was not persisted.');
 $newJobs=Illuminate\Support\Facades\DB::table('jobs')->whereNotIn('id',$jobsBefore)->get();if(!$newJobs->contains(fn($job)=>str_contains($job->payload,'NewInquiry')))throw new RuntimeException('Notification was not queued.');
 [$status,$html]=$call('/contact');if($status!==200||!str_contains($html,'Your inquiry has been received'))throw new RuntimeException('Success confirmation missing.');
 foreach(['/','/about','/services','/projects','/design-process','/privacy','/terms','/sitemap.xml','/robots.txt','/admin/login'] as $path){[$status]=$call($path);if($status!==200)throw new RuntimeException($path.' returned '.$status);}
 [$status]=$call('/admin');if($status!==302)throw new RuntimeException('Guest admin access was not redirected.');
 echo "PASS: real HTTP pages, session, CSRF, inquiry persistence, queued notification, success feedback and admin redirect.\n";
}finally{
 App\Models\Inquiry::where('email',$email)->delete();
 // The queued notification serializes only the model identifier; match the new job IDs from this request in this isolated local check.
 if(isset($newJobs))Illuminate\Support\Facades\DB::table('jobs')->whereIn('id',$newJobs->filter(fn($job)=>str_contains($job->payload,'NewInquiry'))->pluck('id'))->delete();
 @unlink($cookies);
}
