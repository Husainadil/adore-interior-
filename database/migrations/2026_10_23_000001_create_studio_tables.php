<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
 Schema::table('users', fn(Blueprint $t) => $t->boolean('is_admin')->default(false));
 Schema::create('project_categories', function(Blueprint $t){$t->id();$t->string('title');$t->string('slug')->unique();$t->timestamps();});
 foreach(['services','projects','banners','testimonials','before_after_galleries','pages'] as $table) {
 Schema::create($table,function(Blueprint $t) use($table){$t->id();$t->string('title');$t->string('slug')->unique();$t->text('description')->nullable();$t->longText('content')->nullable();$t->string('image')->nullable();$t->string('secondary_image')->nullable();$t->string('location')->nullable();$t->boolean('is_sample')->default(false);$t->boolean('published')->default(false)->index();$t->unsignedInteger('sort_order')->default(0);if($table==='projects')$t->foreignId('project_category_id')->nullable()->constrained()->nullOnDelete();$t->timestamps();$t->softDeletes();});
 }
 Schema::create('project_images',function(Blueprint $t){$t->id();$t->foreignId('project_id')->constrained()->cascadeOnDelete();$t->string('path');$t->string('alt');$t->unsignedInteger('sort_order')->default(0);$t->timestamps();});
 Schema::create('inquiries',function(Blueprint $t){$t->id();$t->string('name');$t->string('phone');$t->string('email');$t->string('project_type');$t->string('location');$t->string('budget')->nullable();$t->text('message');$t->string('status')->default('new')->index();$t->timestamps();});
 Schema::create('website_settings',function(Blueprint $t){$t->id();$t->string('key')->unique();$t->text('value')->nullable();$t->timestamps();});
 Schema::create('seo_metadata',function(Blueprint $t){$t->id();$t->string('path')->unique();$t->string('title');$t->text('description');$t->timestamps();});
 Schema::create('media_assets',function(Blueprint $t){$t->id();$t->string('path');$t->string('alt');$t->string('mime_type');$t->unsignedBigInteger('size');$t->timestamps();});
 }
 public function down(): void {foreach(['media_assets','seo_metadata','website_settings','inquiries','project_images','pages','before_after_galleries','testimonials','banners','projects','services','project_categories'] as $table)Schema::dropIfExists($table);Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('is_admin'));}
};
