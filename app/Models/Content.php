<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Content extends Model {
 use SoftDeletes;
 protected $guarded=['id'];
 protected function casts(): array {return ['published'=>'boolean','is_sample'=>'boolean'];}
 public static function for(string $table): static {return (new static)->setTable($table);}
 public function images(){return $this->hasMany(ProjectImage::class,'project_id')->orderBy('sort_order');}
 public function category(){return $this->belongsTo(ProjectCategory::class,'project_category_id');}
}
