<?php
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\{text,password,confirm};
Artisan::command('studio:admin',function(){
 $name=text('Administrator name',required:true);
 $email=text('Administrator email',required:true,validate:['email']);
 if(User::where('email',$email)->exists()){$this->error('A user with this email already exists. Use the profile screen to update an existing admin.');return 1;}
 $secret=password('Password (minimum 12 characters)',required:true,validate:['min:12']);
 $repeat=password('Confirm password',required:true);
 if($secret!==$repeat){$this->error('Passwords do not match.');return 1;}
 $user=new User;$user->name=$name;$user->email=$email;$user->password=Hash::make($secret);$user->is_admin=true;$user->save();$this->info('Administrator created. Sign in at /admin/login.');
})->purpose('Create a private studio administrator without public registration');
