<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\Content;
use App\Models\Inquiry;
use App\Models\User;
use App\Notifications\NewInquiry;
use Database\Seeders\StudioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_only_shows_genuine_distinct_before_after_pairs(): void
    {
        $this->get('/')->assertOk()->assertDontSee('class="comparison-study"', false);

        $comparison = Content::for('before_after_galleries')->firstOrFail();
        $comparison->update(['is_sample' => false, 'image' => '/images/living.jpg', 'secondary_image' => '/images/bedroom.jpg']);
        $this->get('/')->assertOk()->assertSee('class="comparison-study"', false);

        $comparison->update(['secondary_image' => '/images/living.jpg']);
        $this->get('/')->assertOk()->assertDontSee('class="comparison-study"', false);
    }

    public function test_redesign_preserves_admin_managed_hero_content(): void
    {
        Content::for('banners')->where('slug', 'home-hero')->firstOrFail()->update([
            'title' => 'An edited studio headline',
            'content' => 'A personal perspective.',
            'description' => 'A CUSTOM STUDIO CHAPTER',
            'image' => '/images/office.jpg',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('An edited studio headline')
            ->assertSee('A personal perspective.')
            ->assertSee('A CUSTOM STUDIO CHAPTER')
            ->assertSee('src="/images/office.jpg"', false)
            ->assertDontSee('Spaces designed');
    }

    public function test_redesign_does_not_replace_custom_project_photography(): void
    {
        Content::for('projects')->where('slug', 'the-still-residence')->firstOrFail()->update([
            'image' => '/images/detail.jpg',
            'is_sample' => false,
        ]);

        $this->get('/projects')->assertOk()
            ->assertSee('src="/images/detail.jpg"', false)
            ->assertDontSee('src="/images/villa.jpg"', false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StudioSeeder::class);
        $this->withoutVite();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    public function test_public_pages_render_and_private_drafts_stay_hidden(): void
    {
        foreach (['/', '/about', '/services', '/projects', '/design-process', '/contact', '/privacy', '/terms', '/services/modular-kitchen-design', '/projects/the-still-residence', '/sitemap.xml'] as $path) {
            $this->get($path)->assertOk();
        }
        $project = Content::for('projects')->first();
        $project->update(['published' => false]);
        $this->get('/projects/'.$project->slug)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/projects/'.$project->slug);
    }

    public function test_inquiry_is_persisted_and_notification_is_queued(): void
    {
        Notification::fake();
        $this->post('/inquiries', ['name' => 'Test Visitor', 'phone' => '+91 98765 43210', 'email' => 'visitor@example.com', 'project_type' => 'Villa', 'location' => 'Ahmedabad', 'budget' => 'To discuss', 'message' => 'We are planning a new home interior.'])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('inquiries', ['email' => 'visitor@example.com', 'status' => 'new']);
        Notification::assertSentOnDemand(NewInquiry::class);
    }

    public function test_inquiry_rejects_spam_and_invalid_input(): void
    {
        $this->post('/inquiries', ['website' => 'spam.example'])->assertSessionHasErrors(['name', 'phone', 'email', 'website']);
        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_admin_requires_role_and_has_no_public_registration(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->get('/register')->assertNotFound();
    }

    public function test_admin_login_requires_an_administrator_and_logs_out(): void
    {
        $admin = $this->admin();
        $admin->password = Hash::make('a-strong-test-password');
        $admin->save();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'a-strong-test-password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_admin_can_manage_content_and_all_modules_render(): void
    {
        $this->actingAs($this->admin());
        foreach (AdminController::TABLES as $module) {
            $this->get('/admin/'.$module)->assertOk();
            if ($module !== 'inquiries') {
                $this->get('/admin/'.$module.'/create')->assertOk();
            }
        }$this->get('/admin/profile')->assertOk();
        $this->post('/admin/services', ['title' => 'Test Service', 'slug' => 'test-service', 'description' => 'Test description', 'sort_order' => 0, 'published' => 1])->assertRedirect('/admin/services');
        $row = Content::for('services')->where('slug', 'test-service')->firstOrFail();
        $this->get('/services/test-service')->assertOk();
        $this->delete('/admin/services/'.$row->id)->assertRedirect();
        $this->get('/services/test-service')->assertNotFound();
    }

    public function test_only_allowed_inquiry_status_can_be_saved(): void
    {
        $inquiry = Inquiry::create(['name' => 'Visitor', 'phone' => '1234567890', 'email' => 'a@example.com', 'project_type' => 'Home', 'location' => 'Ahmedabad', 'message' => 'A new home design']);
        $this->actingAs($this->admin())->post('/admin/inquiries/'.$inquiry->id, ['status' => 'contacted'])->assertRedirect();
        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id, 'status' => 'contacted']);
        $this->post('/admin/inquiries/'.$inquiry->id, ['status' => 'invalid'])->assertSessionHasErrors('status');
    }

    public function test_uploads_reject_executable_files(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post('/admin/media_assets', ['alt' => 'Bad upload', 'upload' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php')])->assertSessionHasErrors('upload');
    }

    public function test_unsafe_image_paths_are_rejected(): void
    {
        $this->actingAs($this->admin())->post('/admin/services', ['title' => 'Unsafe', 'slug' => 'unsafe', 'sort_order' => 0, 'image' => 'javascript:alert(1)'])->assertUnprocessable();
    }

    public function test_metadata_is_valid_json_and_robots_excludes_admin(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $schema = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin');
    }

    public function test_uploaded_image_is_optimized_and_indexed(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD is required for image optimization.');
        }Storage::fake('public');
        $this->actingAs($this->admin())->post('/admin/media_assets', ['alt' => 'Studio test image', 'upload' => UploadedFile::fake()->image('room.jpg', 1000, 800)])->assertRedirect('/admin/media_assets');
        $this->assertDatabaseHas('media_assets', ['alt' => 'Studio test image', 'mime_type' => 'image/webp']);
    }

    public function test_whatsapp_consultation_is_saved_before_redirect_with_project_and_budget(): void
    {
        Notification::fake();
        $response = $this->post('/inquiries', [
            'name' => 'Consultation Visitor',
            'phone' => '+91 98765 43210',
            'email' => 'consultation@example.com',
            'project_type' => 'Villa',
            'location' => 'Ahmedabad',
            'budget' => '25–50 lakh',
            'message' => 'A warm neutral interior with natural materials.',
            'intent' => 'whatsapp',
        ]);
        $inquiry = Inquiry::where('email', 'consultation@example.com')->firstOrFail();
        $response->assertRedirect();
        $destination = $response->headers->get('Location');
        $this->assertSame('wa.me', parse_url($destination, PHP_URL_HOST));
        $this->assertSame('/919173774898', parse_url($destination, PHP_URL_PATH));
        parse_str(parse_url($destination, PHP_URL_QUERY), $query);
        $this->assertStringContainsString('Inquiry reference: #'.$inquiry->id, $query['text']);
        $this->assertStringContainsString('Project: Villa', $query['text']);
        $this->assertStringContainsString('Budget: 25–50 lakh', $query['text']);
        $this->assertStringNotContainsString('consultation@example.com', $query['text']);
        Notification::assertSentOnDemand(NewInquiry::class);
        $this->actingAs($this->admin())->get('/admin/inquiries')->assertOk()->assertSee('Consultation Visitor')->assertSee('25–50 lakh');
    }

    public function test_invalid_consultation_action_does_not_save_a_lead(): void
    {
        $this->post('/inquiries', [
            'name' => 'Visitor', 'phone' => '9876543210', 'email' => 'visitor@example.com',
            'project_type' => 'Villa', 'location' => 'Ahmedabad',
            'message' => 'A complete home interior consultation.', 'intent' => 'external-redirect',
        ])->assertSessionHasErrors('intent');
        $this->assertDatabaseCount('inquiries', 0);
    }
}
