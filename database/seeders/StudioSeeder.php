<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Content;
class StudioSeeder extends Seeder {
 public function run(): void {
 foreach(config('studio_content') as $group=>$entries){$i=0;foreach($entries as $title=>$description){$i++;Content::for('pages')->firstOrCreate(['slug'=>$group.'-'.str_pad($i,2,'0',STR_PAD_LEFT)],['title'=>$title,'description'=>$description,'sort_order'=>$i,'published'=>true]);}}
 foreach(['Residential','Apartments','Villas','Modular Kitchens','Commercial','Renovation'] as $title)DB::table('project_categories')->updateOrInsert(['slug'=>Str::slug($title)],['title'=>$title,'created_at'=>now(),'updated_at'=>now()]);
 $services=[
 ['Complete Home Interior Design','living','A complete vision for the place you call home.','From a welcoming entrance to your most personal retreat, we bring each room into a cohesive whole. Our approach begins with your lifestyle and develops through thoughtful planning, material choices, lighting and furniture.'],
 ['Modular Kitchen Design','kitchen','The heart of your home, thoughtfully organized.','A kitchen should feel as good to use as it looks. We consider working zones, storage, surfaces and lighting to create a tailored setting for cooking, gathering and everyday rituals.'],
 ['Luxury Bedroom Interiors','bedroom','A personal retreat, designed for quiet moments.','We work with soft textures, considered lighting and functional layouts to create a bedroom that feels calm and personal. Wardrobes, bedside details and a balanced palette bring comfort into focus.'],
 ['Living & Dining Spaces','dining','Room for togetherness, with a sense of occasion.','We plan connected spaces for conversation, gathering and the rhythm of everyday life. Proportions, furniture, lighting and materials work together to make every moment feel at home.'],
 ['Wardrobe & Storage Solutions','detail','A place for everything, beautifully integrated.','Storage can become part of a room’s architecture. We study your needs and explore internal organization, finishes and proportions to create practical, considered solutions.'],
 ['False Ceiling & Lighting Design','living','Light that shapes the way a space feels.','Layered lighting and carefully planned ceilings create depth and atmosphere. We consider task lighting, ambient illumination and architectural details as part of a coherent interior.'],
 ['Office & Commercial Interiors','office','Purposeful spaces for people and possibility.','We shape workplaces and commercial interiors around how people move, collaborate and experience your business. Function, identity and a considered material palette inform the design.'],
 ['Renovation & Space Transformation','detail','A fresh perspective on the space you already love.','We look for the potential in existing rooms, studying layout, natural light and the elements worth preserving. The design develops around your needs and the possibilities of the property.'],
 ['Custom Furniture & Finishing','dining','Individual pieces. A beautifully cohesive whole.','Bespoke furniture and thoughtful finishes bring scale, texture and personality to a room. We coordinate proportions and material details with the wider interior vision.'],
 ['Turnkey Interior Solutions','kitchen','One connected vision, from concept to completion.','A coordinated design journey brings planning, materials, execution and final styling together. We define the scope with you and coordinate the design intent through each stage.']];
 foreach($services as $i=>[$title,$image,$description,$content])Content::for('services')->firstOrCreate(['slug'=>Str::slug($title)],compact('title','description','content')+['image'=>'/images/'.$image.'.jpg','published'=>true,'sort_order'=>$i]);
 $projects=[['The Still Residence','Residential','living','A warm, understated study in natural materials, soft light and thoughtful spatial balance.'],['A Quiet Retreat','Apartments','bedroom','A restful bedroom concept with layered textures, warm timber and gentle architectural lines.'],['The Gathering Place','Villas','dining','An expressive dining concept that pairs sculptural furniture with a calm, natural palette.'],['The Contemporary Kitchen','Modular Kitchens','kitchen','A kitchen concept exploring clean proportions, tactile surfaces and practical organization.'],['Room to Create','Commercial','office','A contemporary workplace concept built around focus, collaboration and material warmth.'],['A New Perspective','Renovation','detail','A moodboard for reimagining an existing space with texture, light and a considered palette.']];
 foreach($projects as $i=>[$title,$category,$image,$description]){$project=Content::for('projects')->firstOrCreate(['slug'=>Str::slug($title)],['title'=>$title,'description'=>$description,'content'=>'This illustrative design concept explores the relationship between materials, light and the way we live. A restrained palette creates continuity, while carefully considered furniture and lighting bring character to the space.

Design highlights: natural textures, a cohesive neutral palette, balanced proportions and a thoughtful approach to everyday functionality.

This is sample content with stock photography, not a completed Adore Interior project.','image'=>'/images/'.$image.'.jpg','is_sample'=>true,'published'=>true,'sort_order'=>$i,'project_category_id'=>DB::table('project_categories')->where('title',$category)->value('id')]);foreach([$image,'detail','dining'] as $j=>$photo)DB::table('project_images')->updateOrInsert(['project_id'=>$project->id,'sort_order'=>$j],['path'=>'/images/'.$photo.'.jpg','alt'=>$title.' — illustrative interior inspiration','created_at'=>now(),'updated_at'=>now()]);}
 Content::for('banners')->firstOrCreate(['slug'=>'home-hero'],['title'=>'Where spaces','description'=>'CRAFTING TIMELESS SPACES','content'=>'become stories.','image'=>'/images/living.jpg','published'=>true]);
 Content::for('before_after_galleries')->firstOrCreate(['slug'=>'comparison-demo'],['title'=>'Colour & atmosphere — interactive demo','image'=>'/images/living.jpg','secondary_image'=>'/images/living.jpg','is_sample'=>true,'published'=>true]);
 foreach(['about'=>['A considered approach to living.','At Adore Interior, we design complete residential and commercial interiors with a belief that spaces should reflect the people who use them.','Based in Science City, Ahmedabad, our studio brings a connected approach to space planning, materials, furniture and finishing. We begin by listening and develop every decision around your needs.

Our design language balances natural warmth with architectural clarity. From intimate residential rooms to complete commercial environments, we consider the whole experience — how a space looks, functions and feels.']] as $slug=>[$title,$description,$content])Content::for('pages')->firstOrCreate(['slug'=>$slug],compact('title','description','content')+['published'=>true]);
 }
}
