<?php

namespace App\Console\Commands;

use App\Mail\ThesisClearanceMail;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailDelivery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sac:test-email {recipient=figueroaryan@sac.edu.ph}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending an official clearance email to Gmail / institutional email.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recipient = $this->argument('recipient');
        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $username = config('mail.mailers.smtp.username');

        $this->info("=== SAC Email Delivery Test ===");
        $this->line("Target Recipient : {$recipient}");
        $this->line("Active Mailer    : {$mailer}");
        if ($mailer === 'smtp') {
            $this->line("SMTP Host        : {$host}:{$port}");
            $this->line("SMTP Username    : {$username}");
        }

        if ($mailer === 'log') {
            $this->warn("⚠️ NOTICE: MAIL_MAILER is currently set to 'log' in .env!");
            $this->warn("Emails are written to storage/logs/laravel.log and NOT delivered to real Gmail inboxes.");
            $this->warn("To send real emails to your Gmail, configure SMTP in .env (e.g. smtp.gmail.com).");
        }

        $document = Document::whereNotNull('submitted_by_email')->latest()->first();
        if (!$document) {
            $document = new Document([
                'title' => 'Sample Thesis for Clearance Email Testing',
                'author' => 'Student Author',
                'department' => 'itd',
                'course_code' => 'bsit',
                'status' => 'cleared',
                'turnitin_similarity' => '11%',
                'admin_notes' => 'Test email: Plagiarism and Grammarly standards satisfied. Cleared for oral defense.',
            ]);
        }

        $this->info("Attempting to dispatch test email...");

        try {
            Mail::to($recipient)->send(
                new ThesisClearanceMail($document, 'passed', '11%', 'Test clearance email from SAC Institutional Repository.')
            );

            if ($mailer === 'log') {
                $this->info("✅ SUCCESS: Email rendered and recorded in storage/logs/laravel.log.");
            } else {
                $this->info("🎉 SUCCESS: Email successfully delivered via {$mailer} to {$recipient}!");
            }
            return 0;
        } catch (\Throwable $e) {
            $this->error("❌ FAILED to send email: " . $e->getMessage());
            return 1;
        }
    }
}
