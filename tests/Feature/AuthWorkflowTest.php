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
                $table->unsignedSmallInteger('scan_attempts')->default(1);
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('documents', 'scan_attempts')) {
            \Illuminate\Support\Facades\Schema::table('documents', function ($table) {
                $table->unsignedSmallInteger('scan_attempts')->default(1);
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('document_chunks')) {
            \Illuminate\Support\Facades\Schema::create('document_chunks', function ($table) {
                $table->id();
                $table->unsignedBigInteger('document_id');
                $table->integer('page_number')->default(1);
                $table->text('chunk_text')->nullable();
                $table->text('embedding')->nullable();
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

        if (!\Illuminate\Support\Facades\Schema::hasTable('admin_activity_logs')) {
            \Illuminate\Support\Facades\Schema::create('admin_activity_logs', function ($table) {
                $table->id();
                $table->string('admin_email');
                $table->string('admin_name');
                $table->string('action', 50);
                $table->unsignedBigInteger('document_id')->nullable();
                $table->string('document_title');
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        User::truncate();
        \App\Models\Document::truncate();
        \App\Models\ThesisNotification::truncate();
        if (\Illuminate\Support\Facades\Schema::hasTable('admin_activity_logs')) {
            \App\Models\AdminActivityLog::truncate();
        }

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
        $response->assertSee('Email');
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
        $response->assertSee('Email');
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
        $this->assertEquals('itd', $data['department']);
        $this->assertEquals('bsit', $data['course_code']);
    }

    /**
     * Test stale unrevised submissions (>3 months / 90 days) are purged while active revisions and pending are kept.
     */
    public function test_purge_stale_revisions_command(): void
    {
        // 1. Create a 95-day-old unrevised document (>3 months)
        $staleDoc = \App\Models\Document::create([
            'title' => 'Stale Unrevised Manuscript Test',
            'author' => 'Test Author',
            'department' => 'itd',
            'course_code' => 'bsit',
            'abstract' => 'Sample abstract for stale manuscript',
            'status' => 'resubmit',
            'submitted_by_email' => 'student.test@sac.edu.ph',
            'submitted_by_name' => 'Test Student',
        ]);
        $staleDoc->updated_at = \Illuminate\Support\Carbon::now()->subDays(95);
        $staleDoc->saveQuietly();

        // 2. Create a 30-day-old document requiring revision (within 3-month window)
        $activeDoc = \App\Models\Document::create([
            'title' => 'Active Revision Manuscript Test',
            'author' => 'Active Author',
            'department' => 'itd',
            'course_code' => 'bsit',
            'abstract' => 'Sample abstract for active revision manuscript',
            'status' => 'resubmit',
            'submitted_by_email' => 'active.student@sac.edu.ph',
            'submitted_by_name' => 'Active Student',
        ]);
        $activeDoc->updated_at = \Illuminate\Support\Carbon::now()->subDays(30);
        $activeDoc->saveQuietly();

        // 3. Create a 100-day-old pending document (should NOT be purged; only resubmit is purged)
        $pendingDoc = \App\Models\Document::create([
            'title' => 'Old Pending Manuscript Test',
            'author' => 'Pending Author',
            'department' => 'itd',
            'course_code' => 'bsit',
            'abstract' => 'Sample abstract for old pending manuscript',
            'status' => 'pending',
            'submitted_by_email' => 'pending.student@sac.edu.ph',
            'submitted_by_name' => 'Pending Student',
        ]);
        $pendingDoc->updated_at = \Illuminate\Support\Carbon::now()->subDays(100);
        $pendingDoc->saveQuietly();

        // 4. Run the purge command
        $this->artisan('submissions:purge-stale-revisions')
            ->assertExitCode(0);

        // 5. Assert stale document was purged, active revision and pending documents preserved
        $this->assertDatabaseMissing('documents', ['id' => $staleDoc->id]);
        $this->assertDatabaseHas('documents', ['id' => $activeDoc->id]);
        $this->assertDatabaseHas('documents', ['id' => $pendingDoc->id]);

        // Cleanup test docs
        $activeDoc->delete();
        $pendingDoc->delete();
    }

    /**
     * Test revision notice pop-up is shown when student has a manuscript needing revision,
     * and is successfully dismissed via the dismissal endpoint.
     */
    public function test_student_revision_notice_popup_and_dismissal(): void
    {
        $revisionDoc = \App\Models\Document::create([
            'title' => 'Automated Operations System for Blood Donor-Recipient Matching',
            'author' => 'Ryan Figueroa',
            'department' => 'itd',
            'course_code' => 'bsit',
            'abstract' => 'Sample abstract for revision testing',
            'status' => 'resubmit',
            'turnitin_similarity' => '30%',
            'admin_notes' => 'Please revise flagged sections.',
            'submitted_by_email' => 'figueroaryan@sac.edu.ph',
            'submitted_by_name' => 'Ryan Figueroa',
        ]);

        // 1. Visit /documents with student session -> should see revisionNoticeModal
        $response = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Ryan Figueroa',
        ])->get('/documents');

        $response->assertOk();
        $response->assertSee('id="revisionNoticeModal"', false);
        $response->assertSee('Manuscript Revision Required');
        $response->assertSee('Go to Upload');
        $response->assertSee('Dismiss');
        $response->assertSee('Revision Deadline');
        $response->assertSee('Automated Operations System for Blood Donor-Recipient Matching');
        $response->assertSee('Please revise flagged sections.');
        $response->assertSee('Turnitin & Grammarly Scanning Quota');
        $response->assertSee('2 of 3 Free Tries Left');
        $response->assertSee('Scan 1');
        $response->assertSee('Scan 2');
        $response->assertSee('Scan 3');

        // 2. Dismiss the notice via POST endpoint
        $dismissRes = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Ryan Figueroa',
        ])->postJson('/backend/student/dismiss-revision-notice');

        $dismissRes->assertOk();
        $dismissRes->assertJson(['success' => true]);
        $dismissRes->assertCookie('sac_rev_dismissed', '1');

        // 3. Visit submit page -> should NEVER see modal because user is already on submit page
        $submitPageRes = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Ryan Figueroa',
        ])->get('/student/submit');

        $submitPageRes->assertOk();
        $submitPageRes->assertDontSee('id="revisionNoticeModal"', false);
        $submitPageRes->assertSessionHas('revision_notice_dismissed', true);

        // 4. Visit again with session containing revision_notice_dismissed -> should NOT see modal
        $responseAfter = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Ryan Figueroa',
            'revision_notice_dismissed' => true,
        ])->get('/documents');

        $responseAfter->assertOk();
        $responseAfter->assertDontSee('id="revisionNoticeModal"', false);

        // 5. Visit with cookie sac_rev_dismissed -> should NOT see modal
        $responseCookie = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Ryan Figueroa',
        ])->withCookie('sac_rev_dismissed', '1')->get('/documents');

        $responseCookie->assertOk();
        $responseCookie->assertDontSee('id="revisionNoticeModal"', false);

        // Cleanup
        $revisionDoc->delete();
    }

    /**
     * Test admin submissions page does not show a pop-up notice modal.
     */
    public function test_admin_submissions_no_popup_notice(): void
    {
        $response = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->get('/admin/submissions');

        $response->assertOk();
        $response->assertDontSee('id="adminPolicyModal"', false);
    }

    /**
     * Test admin profile user button, dropdown, and activity logs endpoints.
     */
    public function test_admin_profile_menu_and_activity_logs(): void
    {
        // 1. Visit admin submissions page -> should render admin profile button and activity logs modal
        $response = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->get('/admin/submissions');

        $response->assertOk();
        $response->assertSee('id="adminProfileMenuBtn"', false);
        $response->assertSee('id="adminProfileDropdown"', false);
        $response->assertSee('View Logs');
        $response->assertSee('id="adminActivityLogsModal"', false);
        $response->assertSee('Admin Activity Logs');

        // 2. Student should NOT see admin profile menu or activity logs modal
        $studentResponse = $this->withSession([
            'sac_user_email' => 'figueroaryan@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Ryan Figueroa',
        ])->get('/documents');

        $studentResponse->assertOk();
        $studentResponse->assertDontSee('id="adminProfileMenuBtn"', false);
        $studentResponse->assertDontSee('id="adminActivityLogsModal"', false);

        // 3. Unauthorized access to /backend/admin/activity-logs is blocked
        $guestLogs = $this->getJson('/backend/admin/activity-logs');
        $guestLogs->assertStatus(403);

        // 4. Record sample activity log and fetch via authenticated admin session
        \App\Models\AdminActivityLog::record(
            'approve',
            101,
            'Automated Operations System for Blood Donor-Recipient',
            'Approved manuscript clearance and published to repository (Turnitin: 10%)'
        );

        \App\Models\AdminActivityLog::record(
            'revision_request',
            102,
            'Smart Procurement System: QR-Based Asset Inventory',
            'Requested revisions (Turnitin: 30%) - Feedback: need to revise, exceeded from >28% threshold'
        );

        $adminLogs = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->getJson('/backend/admin/activity-logs');

        $adminLogs->assertOk();
        $adminLogs->assertJsonStructure([
            'success',
            'logs',
            'counts' => ['all', 'approve', 'revision', 'archive', 'edit'],
        ]);
        $data = $adminLogs->json();
        $this->assertCount(2, $data['logs']);
        $this->assertEquals(2, $data['counts']['all']);
        $this->assertEquals(1, $data['counts']['approve']);
        $this->assertEquals(1, $data['counts']['revision']);

        // 5. Test filter action
        $filtered = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->getJson('/backend/admin/activity-logs?action=approve');

        $filtered->assertOk();
        $this->assertCount(1, $filtered->json('logs'));
        $this->assertEquals('approve', $filtered->json('logs.0.action'));
    }

    /**
     * Test topic keywords are auto-generated when manuscripts are submitted without keywords,
     * and that administrators can manually update keywords in the repository.
     */
    public function test_auto_generate_topic_keywords_on_submission_and_admin_update(): void
    {
        // 1. Direct keyword extraction verification
        $directKeywords = \App\Services\GeminiService::extractOrGenerateKeywords(
            'ATTITUDES AND PRACTICES TOWARD FAMILY DISASTER PREPAREDNESS PLAN AMONG NURSING STUDENTS',
            'This descriptive-correlational study investigated emergency preparedness behaviors among nursing students in Antique.',
            'nursing'
        );
        $this->assertStringContainsString('Disaster Preparedness', $directKeywords);
        $this->assertStringContainsString('Nursing Students', $directKeywords);

        // 2. Explicit author keywords extraction from abstract
        $explicitKeywords = \App\Services\GeminiService::extractOrGenerateKeywords(
            'Comparative Analysis of Deep Learning Models',
            "Abstract text goes here.\nKeywords: Deep Learning, Neural Networks, Computer Vision, Pattern Recognition",
            'itd'
        );
        $this->assertEquals('Deep Learning, Neural Networks, Computer Vision, Pattern Recognition', $explicitKeywords);

        // 3. Auto-generation on student submission without explicit keywords
        $submissionResponse = $this->withSession([
            'sac_user_email' => 'student.topics@sac.edu.ph',
            'sac_user_role' => 'student',
            'sac_user_name' => 'Topic Test Student',
        ])->postJson('/backend/student/submit', [
            'title' => 'Smart Procurement System: QR-Based Asset Inventory and Tracking',
            'author' => 'Earl Benedict Xander P. Lacson',
            'department' => 'itd',
            'course_code' => 'bsit',
            'abstract' => 'This project developed an automated smart procurement and inventory system using QR codes.',
            'file_path' => 'student-submissions/test-submission.pdf',
        ]);

        $submissionResponse->assertStatus(201);
        $doc = \App\Models\Document::where('submitted_by_email', 'student.topics@sac.edu.ph')->first();
        $this->assertNotNull($doc);
        $this->assertNotEmpty($doc->keywords);
        $this->assertStringContainsString('Smart Procurement', $doc->keywords);

        // 4. Admin update of document metadata including keywords
        $adminUpdateResponse = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->putJson("/backend/admin/theses/{$doc->id}", [
            'title' => 'Smart Procurement System: QR-Based Asset Inventory and Tracking (Updated)',
            'author' => 'Earl Benedict Xander P. Lacson',
            'department' => 'itd',
            'course_code' => 'bsit',
            'publication_date' => '2026-10-01',
            'keywords' => 'Asset Tracking, QR Code Inventory, Procurement Automation',
            'abstract' => 'Updated abstract description.',
        ]);

        $adminUpdateResponse->assertOk();
        $doc->refresh();
        $this->assertEquals('Asset Tracking, QR Code Inventory, Procurement Automation', $doc->keywords);

        // Clean up test document
        $doc->delete();
    }

    /**
     * Test Turnitin and Grammarly 3 free scans quota lifecycle, deduction, and 4th scan payment notice.
     */
    public function test_turnitin_and_grammarly_three_free_scans_quota_lifecycle(): void
    {
        $studentEmail = 'scan.quota.test@sac.edu.ph';
        $studentName = 'Scan Quota Student';

        // 1. Initial thesis submission (Scan Attempt 1)
        $initialRes = $this->withSession([
            'sac_user_email' => $studentEmail,
            'sac_user_role' => 'student',
            'sac_user_name' => $studentName,
        ])->postJson('/backend/student/submit', [
            'title' => 'IoT Environmental Monitoring for Local Agriculture',
            'author' => $studentName,
            'department' => 'eng',
            'course_code' => 'bscpe',
            'abstract' => 'Comprehensive IoT sensor network for agricultural soil monitoring.',
            'file_path' => 'student-submissions/iot-env-v1.pdf',
        ]);

        $initialRes->assertStatus(201);
        $doc = \App\Models\Document::where('submitted_by_email', $studentEmail)->first();
        $this->assertNotNull($doc);
        $this->assertEquals(1, $doc->scan_attempts);
        $this->assertEquals('pending', $doc->status);

        // 2. Admin evaluates scan #1 and requests revision (Similarity: 32%)
        $reject1Res = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->postJson("/backend/admin/submissions/{$doc->id}/reject", [
            'admin_notes' => 'Similarity index exceeds threshold (32%). Please paraphrase chapter 2.',
            'turnitin_similarity' => '32%',
        ]);

        $reject1Res->assertOk();
        $doc->refresh();
        $this->assertEquals('resubmit', $doc->status);
        $this->assertEquals(1, $doc->scan_attempts);

        // Verify modal display after 1st failed scan (2 tries remaining)
        $modalRes1 = $this->withSession([
            'sac_user_email' => $studentEmail,
            'sac_user_role' => 'student',
            'sac_user_name' => $studentName,
        ])->get('/documents');

        $modalRes1->assertOk();
        $modalRes1->assertSee('Turnitin & Grammarly Scanning Quota');
        $modalRes1->assertSee('2 of 3 Free Tries Left');
        $modalRes1->assertSee('Scan 1');
        $modalRes1->assertSee('Used');

        // 3. Student uploads Revision 1 -> scan_attempts increments to 2
        $resubmit1Res = $this->withSession([
            'sac_user_email' => $studentEmail,
            'sac_user_role' => 'student',
            'sac_user_name' => $studentName,
        ])->postJson('/backend/student/submit', [
            'submission_id' => $doc->id,
            'title' => 'IoT Environmental Monitoring for Local Agriculture',
            'author' => $studentName,
            'department' => 'eng',
            'course_code' => 'bscpe',
            'abstract' => 'Updated abstract with revised literature review.',
            'file_path' => 'student-submissions/iot-env-v2.pdf',
        ]);

        $resubmit1Res->assertOk();
        $doc->refresh();
        $this->assertEquals('pending', $doc->status);
        $this->assertEquals(2, $doc->scan_attempts);

        // 4. Admin evaluates scan #2 and requests revision again (Similarity: 25%)
        $reject2Res = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->postJson("/backend/admin/submissions/{$doc->id}/reject", [
            'admin_notes' => 'Similarity index reduced to 25%, but still above 15% threshold.',
            'turnitin_similarity' => '25%',
        ]);

        $reject2Res->assertOk();
        $doc->refresh();
        $this->assertEquals('resubmit', $doc->status);

        // Verify modal display after 2nd failed scan (1 try remaining)
        $modalRes2 = $this->withSession([
            'sac_user_email' => $studentEmail,
            'sac_user_role' => 'student',
            'sac_user_name' => $studentName,
        ])->get('/documents');

        $modalRes2->assertOk();
        $modalRes2->assertSee('1 of 3 Free Tries Left');

        // 5. Student uploads Revision 2 -> scan_attempts increments to 3 (Final Free Try)
        $resubmit2Res = $this->withSession([
            'sac_user_email' => $studentEmail,
            'sac_user_role' => 'student',
            'sac_user_name' => $studentName,
        ])->postJson('/backend/student/submit', [
            'submission_id' => $doc->id,
            'title' => 'IoT Environmental Monitoring for Local Agriculture',
            'author' => $studentName,
            'department' => 'eng',
            'course_code' => 'bscpe',
            'abstract' => 'Final free revision attempt abstract.',
            'file_path' => 'student-submissions/iot-env-v3.pdf',
        ]);

        $resubmit2Res->assertOk();
        $doc->refresh();
        $this->assertEquals('pending', $doc->status);
        $this->assertEquals(3, $doc->scan_attempts);

        // 6. Admin evaluates scan #3 and requests revision (Similarity: 20%) -> 3rd scan failed!
        $reject3Res = $this->withSession([
            'sac_user_email' => 'admin@sac.edu.ph',
            'sac_user_role' => 'admin',
            'sac_user_name' => 'SAC Administrator',
        ])->postJson("/backend/admin/submissions/{$doc->id}/reject", [
            'admin_notes' => 'All 3 free scans evaluated. Similarity index is 20%. 4th scan requires cashier payment.',
            'turnitin_similarity' => '20%',
        ]);

        $reject3Res->assertOk();
        $doc->refresh();
        $this->assertEquals('resubmit', $doc->status);
        $this->assertEquals(3, $doc->scan_attempts);

        // 7. Verify modal display after 3 failed scans -> Free Quota Exhausted & Payment Required for 4th Scan
        $modalRes3 = $this->withSession([
            'sac_user_email' => $studentEmail,
            'sac_user_role' => 'student',
            'sac_user_name' => $studentName,
        ])->get('/documents');

        $modalRes3->assertOk();
        $modalRes3->assertSee('Free Tries Exhausted');
        $modalRes3->assertSee('Payment Required for 4th Scan');
        $modalRes3->assertSee('In accordance with RESO regulations, you must settle the re-screening fee');

        // 8. Verify submission page is clean and does NOT contain the scanning quota box
        $submitPageRes = $this->withSession([
            'sac_user_email' => $studentEmail,
            'sac_user_role' => 'student',
            'sac_user_name' => $studentName,
        ])->get('/student/submit');

        $submitPageRes->assertOk();
        $submitPageRes->assertDontSee('Scanning Quota Notice:');
        $submitPageRes->assertDontSee('Free Scans Exhausted');
        $submitPageRes->assertDontSee('Free Tries Exhausted');

        // Clean up test document
        \Illuminate\Support\Facades\DB::table('thesis_notifications')->where('user_email', $studentEmail)->delete();
        $doc->delete();
    }
}



