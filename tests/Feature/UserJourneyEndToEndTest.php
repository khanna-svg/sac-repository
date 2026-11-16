<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserJourneyEndToEndTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
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

        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function ($table) {
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

        if (!Schema::hasColumn('documents', 'scan_attempts')) {
            Schema::table('documents', function ($table) {
                $table->unsignedSmallInteger('scan_attempts')->default(1);
            });
        }

        if (!Schema::hasTable('bookmarks')) {
            Schema::create('bookmarks', function ($table) {
                $table->id();
                $table->string('user_email');
                $table->unsignedBigInteger('document_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('document_chunks')) {
            Schema::create('document_chunks', function ($table) {
                $table->id();
                $table->unsignedBigInteger('document_id');
                $table->integer('page_number')->default(1);
                $table->text('chunk_text')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('thesis_notifications')) {
            Schema::create('thesis_notifications', function ($table) {
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
    }

    /**
     * Complete end-to-end automated user journey:
     * 1. Student login validation
     * 2. Repository catalog browsing
     * 3. Manuscript viewing and bookmarking
     * 4. Thesis clearance submission
     * 5. Revision notice and resubmission
     * 6. Clearance approval verification
     * 7. Notifications and logout
     */
    public function test_complete_user_side_journey_from_login_to_finish(): void
    {
        // 1. Student Login
        $studentUser = User::firstOrCreate(
            ['email' => 'journey.student@sac.edu.ph'],
            [
                'name' => 'Demo Student',
                'lrn' => '123456789012',
                'password' => Hash::make('secret123'),
                'role' => 'student',
            ]
        );

        $loginResponse = $this->post('/login', [
            'email' => 'journey.student@sac.edu.ph',
            'password' => 'secret123',
        ]);
        $loginResponse->assertRedirect('/documents');

        $sessionData = [
            'sac_user_email' => 'journey.student@sac.edu.ph',
            'sac_user_name' => 'Demo Student',
            'sac_user_role' => 'student',
        ];

        // 2. Catalog & Discovery Browsing
        $catalogResponse = $this->withSession($sessionData)->get('/documents');
        $catalogResponse->assertOk();

        // 3. View Manuscript & Bookmark
        $sampleDoc = Document::create([
            'title' => 'Family Disaster Preparedness Among Nursing Students',
            'author' => 'Riarose Flores',
            'department' => 'nursing',
            'course_code' => 'bsn',
            'abstract' => 'Descriptive study on disaster preparedness behaviors.',
            'keywords' => 'Disaster Preparedness, Nursing Education',
            'status' => 'approved',
        ]);

        $detailResponse = $this->withSession($sessionData)->get("/documents/{$sampleDoc->id}");
        $detailResponse->assertOk();
        $detailResponse->assertSee('Family Disaster Preparedness');

        // Toggle Bookmark
        $bookmarkResponse = $this->withSession($sessionData)->postJson('/backend/bookmarks/toggle', [
            'document_id' => $sampleDoc->id,
        ]);
        $bookmarkResponse->assertOk();

        // Visit Bookmarks
        $bookmarksPage = $this->withSession($sessionData)->get('/bookmarks');
        $bookmarksPage->assertOk();

        // 4. Submit Thesis Clearance
        $submissionResponse = $this->withSession($sessionData)->postJson('/backend/student/submit', [
            'title' => 'Smart Asset Management System',
            'author' => 'Demo Student',
            'department' => 'itd',
            'course_code' => 'bsit',
            'abstract' => 'Automated tracking system using QR codes.',
            'file_path' => 'student-submissions/demo-thesis.pdf',
        ]);
        $submissionResponse->assertStatus(201);

        $submittedDoc = Document::where('submitted_by_email', 'journey.student@sac.edu.ph')
            ->where('title', 'Smart Asset Management System')
            ->first();
        $this->assertNotNull($submittedDoc);
        $this->assertEquals('pending', $submittedDoc->status);
        $this->assertNotEmpty($submittedDoc->keywords);

        // 5. Admin Requests Revision (e.g. Turnitin > 28%)
        $submittedDoc->update([
            'status' => 'resubmit',
            'turnitin_similarity' => '30%',
            'admin_notes' => 'need to revise, exceeded from >28% threshold',
        ]);

        $statusPage = $this->withSession($sessionData)->get('/student/submit');
        $statusPage->assertOk();
        $statusPage->assertSee('Needs Revision');
        $statusPage->assertSee('need to revise, exceeded from >28% threshold');

        // Student Resubmits Revised Manuscript
        $resubmitResponse = $this->withSession($sessionData)->postJson('/backend/student/submit', [
            'title' => 'Smart Asset Management System',
            'author' => 'Demo Student',
            'department' => 'itd',
            'course_code' => 'bsit',
            'abstract' => 'Automated tracking system using QR codes with improved citations.',
            'file_path' => 'student-submissions/demo-thesis-revised.pdf',
        ]);
        $resubmitResponse->assertOk();

        $submittedDoc->refresh();
        $this->assertEquals('pending', $submittedDoc->status);
        $this->assertNull($submittedDoc->turnitin_similarity);

        // 6. Admin Approves Clearance
        $submittedDoc->update([
            'status' => 'approved',
            'turnitin_similarity' => '10%',
            'admin_notes' => 'approved and uploaded to repository',
        ]);

        $approvedStatusPage = $this->withSession($sessionData)->get('/student/submit');
        $approvedStatusPage->assertOk();
        $approvedStatusPage->assertSee('Approved');
        $approvedStatusPage->assertSee('View Published in Repository');

        // 7. Check Notifications & Logout
        $notif = \App\Models\ThesisNotification::create([
            'user_email' => 'journey.student@sac.edu.ph',
            'title' => 'Thesis Clearance Approved',
            'message' => 'Your thesis clearance has been approved and published to the repository.',
            'type' => 'success',
            'document_id' => $submittedDoc->id,
            'is_read' => false,
        ]);

        $notifResponse = $this->withSession($sessionData)->getJson('/backend/notifications');
        $notifResponse->assertOk();

        $logoutResponse = $this->withSession($sessionData)->get('/logout');
        $logoutResponse->assertRedirect('/login');

        // Clean up test records
        $sampleDoc->delete();
        $submittedDoc->delete();
        $notif->delete();
        $studentUser->delete();
    }
}
