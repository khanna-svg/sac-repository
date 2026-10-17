<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            \Illuminate\Support\Facades\Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('lrn', 30)->nullable();
                $table->string('password');
                $table->string('role')->default('student');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('documents')) {
            \Illuminate\Support\Facades\Schema::create('documents', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('author')->nullable();
                $table->text('abstract')->nullable();
                $table->string('department')->nullable();
                $table->string('academic_level')->nullable();
                $table->string('research_category')->nullable();
                $table->string('methodology')->nullable();
                $table->string('keywords')->nullable();
                $table->string('course_code')->nullable();
                $table->date('publication_date')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_url')->nullable();
                $table->string('status')->default('pending');
                $table->string('submitted_by_name')->nullable();
                $table->string('submitted_by_email')->nullable();
                $table->text('admin_notes')->nullable();
                $table->string('turnitin_similarity')->nullable();
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('thesis_notifications')) {
            \Illuminate\Support\Facades\Schema::create('thesis_notifications', function ($table) {
                $table->id();
                $table->string('user_email');
                $table->string('title');
                $table->text('message');
                $table->string('type')->default('info');
                $table->unsignedBigInteger('document_id')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }

        User::truncate();
        \App\Models\Document::truncate();
        \App\Models\ThesisNotification::truncate();

        User::create([
            'name' => 'Ryan Figueroa',
            'email' => 'figueroaryan@sac.edu.ph',
            'role' => 'student',
            'lrn' => '107384920182',
            'password' => Hash::make('107384920182'),
        ]);

        User::create([
            'name' => 'SAC Administrator',
            'email' => 'admin@sac.edu.ph',
            'role' => 'admin',
            'password' => Hash::make('admin123'),
        ]);
    }
    /**
     * Test GET /login renders student login page.
     */
    public function test_get_login_page_renders_student_portal(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('AIRIS');
        $response->assertSee('Student');
        $response->assertSee('Admin');
        $response->assertSee('Password');
    }

    /**
     * Test GET /admin/login renders administrator portal.
     */
    public function test_get_admin_login_page_renders_admin_center(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('AIRIS');
        $response->assertSee('Student');
        $response->assertSee('Admin');
        $response->assertSee('Password');
    }

    /**
     * Test student login with official @sac.edu.ph and 12-digit LRN.
     */
    public function test_student_login_with_valid_lrn(): void
    {
        $response = $this->post('/login', [
            'email' => 'figueroaryan@sac.edu.ph',
            'password' => '107384920182',
        ]);

        $response->assertRedirect(route('documents'));
        $this->assertEquals('student', session('sac_user_role'));
        $this->assertEquals('figueroaryan@sac.edu.ph', session('sac_user_email'));
        $this->assertEquals('107384920182', session('sac_user_lrn'));
    }

    /**
     * Test student login rejects non-sac institutional email.
     */
    public function test_student_login_rejects_non_sac_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'outsider@gmail.com',
            'password' => '123456789012',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test student login rejects administrator account and advises admin portal.
     */
    public function test_student_login_blocks_admin_account_with_notice(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@sac.edu.ph',
            'password' => 'admin123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('sac_user_role'));
    }

    /**
     * Test admin login with valid credentials.
     */
    public function test_admin_login_success(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@sac.edu.ph',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.analytics'));
        $this->assertEquals('admin', session('sac_user_role'));
        $this->assertEquals('admin@sac.edu.ph', session('sac_user_email'));
    }

    /**
     * Test admin login rejects student account and advises student portal.
     */
    public function test_admin_login_blocks_student_account_with_notice(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'figueroaryan@sac.edu.ph',
            'password' => '107384920182',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('sac_user_role'));
    }

    /**
     * Test unauthenticated access to admin routes redirects to /admin/login.
     */
    public function test_unauthenticated_admin_route_redirects_to_admin_login(): void
    {
        $response = $this->get('/admin/submissions');

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * Test unauthenticated access to student repository routes redirects to /login.
     */
    public function test_unauthenticated_student_route_redirects_to_login(): void
    {
        $response = $this->get('/documents');

        $response->assertRedirect(route('login'));
    }

    /**
     * Test student logout redirects to /login.
     */
    public function test_student_logout_redirects_to_student_login(): void
    {
        $response = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
        ])->get('/logout');

        $response->assertRedirect(route('login'));
        $this->assertNull(session('sac_user_role'));
    }

    /**
     * Test admin logout redirects to /admin/login.
     */
    public function test_admin_logout_redirects_to_admin_login(): void
    {
        $response = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
        ])->get('/logout');

        $response->assertRedirect(route('admin.login'));
        $this->assertNull(session('sac_user_role'));
    }

    /**
     * Test student submit page displays pending review banner when student has pending submission.
     */
    public function test_student_submit_page_renders_pending_status_banner(): void
    {
        \App\Models\Document::create([
            'title' => 'A Web-Based Automated Operations System for Blood Donor-Recipient Matching',
            'author' => 'Ryan Figueroa',
            'status' => 'pending',
            'submitted_by_email' => 'figueroaryan@sac.edu.ph',
            'submitted_by_name' => 'Ryan Figueroa',
        ]);

        $response = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Ryan Figueroa',
        ])->get('/student/submit');

        $response->assertOk();
        $response->assertSee('Submitted for plagiarism review. You will be notified once reviewed.');
        $response->assertSee('A Web-Based Automated Operations System for Blood Donor-Recipient Matching');
        $response->assertSee('Pending');
    }

    /**
     * Test extract-metadata endpoint requires student authentication.
     */
    public function test_extract_metadata_requires_auth(): void
    {
        $response = $this->postJson('/backend/student/extract-metadata', [
            'text' => 'Sample front matter text',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test extract-metadata endpoint extracts metadata from front matter.
     */
    public function test_extract_metadata_returns_extracted_data(): void
    {
        $sampleText = "ST. ANTHONY'S COLLEGE\nInformation Technology Department\n\nAutomated Student Attendance Tracking System\nby Ryan Figueroa\n\nABSTRACT\nThis study develops an automated attendance tracking system using computer vision to eliminate manual sheet logging in academic institutions.\n\nKeywords: Attendance, Computer Vision";

        $response = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
        ])->postJson('/backend/student/extract-metadata', [
            'text' => $sampleText,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('itd', $data['department']);
        $this->assertEquals('bsit', $data['course_code']);
    }
}


