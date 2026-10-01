<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectTitle ?? 'AIRIS Thesis Clearance Update' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 30px 15px;">
        <tr>
            <td align="center">
                
                <!-- Main Container Card -->
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    
                    <!-- AIRIS Official Brand Header -->
                    <tr>
                        <td style="background-color: #0A2549; padding: 24px 30px; text-align: left; border-bottom: 3px solid #CBA144;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; color: #CBA144; font-size: 18px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;">
                                            AIRIS &bull; St. Anthony's College
                                        </h1>
                                        <p style="margin: 3px 0 0 0; color: #ffffff; font-size: 11px; font-weight: 500; opacity: 0.9; letter-spacing: 0.03em; text-transform: uppercase;">
                                            Automated Institutional Research &amp; Information System &bull; Clearance Notification
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Email Content Body -->
                    <tr>
                        <td style="padding: 32px 30px;">
                            
                            <!-- Greeting -->
                            <p style="margin: 0 0 16px 0; font-size: 15px; font-weight: 600; color: #334155;">
                                Hello, <span style="color: #0A2549; font-weight: 700;">{{ $document->submitted_by_name ?: 'SAC Student' }}</span>
                            </p>

                            @if($type === 'passed')
                                <!-- PASSED STATUS BANNER -->
                                <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 14px; padding: 18px 20px; margin-bottom: 24px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="vertical-align: top; width: 34px;">
                                                <div style="background-color: #10b981; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; font-size: 15px; font-weight: bold;">
                                                    &check;
                                                </div>
                                            </td>
                                            <td style="vertical-align: top; padding-left: 10px;">
                                                <span style="display: inline-block; background-color: #059669; color: #ffffff; font-size: 10px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; margin-bottom: 4px;">
                                                    Clearance Passed
                                                </span>
                                                <h2 style="margin: 4px 0 0 0; color: #065f46; font-size: 16px; font-weight: 700;">
                                                    Turnitin &amp; Grammarly Review Passed!
                                                </h2>
                                                <p style="margin: 6px 0 0 0; color: #047857; font-size: 13px; line-height: 1.5;">
                                                    Your thesis manuscript has successfully passed the academic originality screening and language standards.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @elseif($type === 'resubmit')
                                <!-- REVISIONS REQUIRED BANNER -->
                                <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 14px; padding: 18px 20px; margin-bottom: 24px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="vertical-align: top; width: 34px;">
                                                <div style="background-color: #e11d48; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; font-size: 15px; font-weight: bold;">
                                                    !
                                                </div>
                                            </td>
                                            <td style="vertical-align: top; padding-left: 10px;">
                                                <span style="display: inline-block; background-color: #e11d48; color: #ffffff; font-size: 10px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; margin-bottom: 4px;">
                                                    Action Required
                                                </span>
                                                <h2 style="margin: 4px 0 0 0; color: #9f1239; font-size: 16px; font-weight: 700;">
                                                    Turnitin / Grammarly Revisions Needed
                                                </h2>
                                                <p style="margin: 6px 0 0 0; color: #be123c; font-size: 13px; line-height: 1.5;">
                                                    Your thesis manuscript requires revisions before official clearance can be granted. Please see reviewer feedback below.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @else
                                <!-- SUBMISSION RECEIVED BANNER -->
                                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 18px 20px; margin-bottom: 24px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="vertical-align: top; width: 34px;">
                                                <div style="background-color: #2563eb; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; font-size: 15px; font-weight: bold;">
                                                    &bull;
                                                </div>
                                            </td>
                                            <td style="vertical-align: top; padding-left: 10px;">
                                                <span style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 10px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; margin-bottom: 4px;">
                                                    Under Review
                                                </span>
                                                <h2 style="margin: 4px 0 0 0; color: #1e40af; font-size: 16px; font-weight: 700;">
                                                    Manuscript Received for Screening
                                                </h2>
                                                <p style="margin: 6px 0 0 0; color: #1d4ed8; font-size: 13px; line-height: 1.5;">
                                                    Your manuscript has been queued for Turnitin similarity and Grammarly verification.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            @endif

                            <!-- Manuscript Details Card -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                                <h3 style="margin: 0 0 12px 0; font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">
                                    Manuscript Evaluation Details
                                </h3>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 13px;">
                                    <tr>
                                        <td style="padding: 6px 0; color: #64748b; width: 130px; font-weight: 600;">Research Title:</td>
                                        <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ $document->title }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Author(s):</td>
                                        <td style="padding: 6px 0; color: #334155;">{{ $document->author }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Department:</td>
                                        <td style="padding: 6px 0; color: #334155;">{{ strtoupper($document->department) }} ({{ strtoupper($document->course_code) }})</td>
                                    </tr>

                                    @if(!empty($similarity) || !empty($document->turnitin_similarity))
                                    <tr>
                                        <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Turnitin Score:</td>
                                        <td style="padding: 6px 0;">
                                            <span style="display: inline-block; background-color: {{ $type === 'passed' ? '#dcfce7' : '#fee2e2' }}; color: {{ $type === 'passed' ? '#15803d' : '#b91c1c' }}; font-weight: 800; padding: 2px 8px; border-radius: 6px; font-family: monospace;">
                                                {{ $similarity ?? $document->turnitin_similarity }}
                                            </span>
                                            <span style="font-size: 11px; color: #64748b; margin-left: 6px;">
                                                (SAC Institutional Threshold: &le; 15%)
                                            </span>
                                        </td>
                                    </tr>
                                    @endif

                                    @if(!empty($notes) || !empty($document->admin_notes))
                                    <tr>
                                        <td style="padding: 8px 0 4px 0; color: #64748b; font-weight: 600; vertical-align: top;">Reviewer Notes:</td>
                                        <td style="padding: 8px 0 4px 0; color: #0f172a;">
                                            <div style="background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-size: 12px; color: #334155; line-height: 1.5; white-space: pre-line;">
                                                {{ $notes ?? $document->admin_notes }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>

                            <!-- Next Steps Advice -->
                            <div style="margin-bottom: 28px; font-size: 13px; color: #475569; line-height: 1.6;">
                                @if($type === 'passed')
                                    <p style="margin: 0;">
                                        <strong>Next Step:</strong> You have satisfied the academic integrity screening requirements. Please keep a copy of this clearance for your defense application and final thesis submission.
                                    </p>
                                @elseif($type === 'resubmit')
                                    <p style="margin: 0;">
                                        <strong>Next Step:</strong> Please address the reviewer's feedback, paraphrase all flagged text, verify in-text citations, and re-upload your updated PDF manuscript via the link below.
                                    </p>
                                @else
                                    <p style="margin: 0;">
                                        <strong>Next Step:</strong> The review process typically takes 1&ndash;3 working days. You will receive an automated email notification once the Turnitin similarity check and Grammarly evaluation are finalized.
                                    </p>
                                @endif
                            </div>

                            <!-- Call to Action Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/student/submit') }}" target="_blank" style="display: inline-block; background-color: #0A2549; color: #CBA144; font-size: 13px; font-weight: 700; text-decoration: none; padding: 12px 28px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(10, 37, 73, 0.2); letter-spacing: 0.02em;">
                                            {{ $type === 'resubmit' ? 'Revise & Resubmit Manuscript' : 'View in Student Portal' }} &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Institutional Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b; line-height: 1.5;">
                            <p style="margin: 0 0 4px 0; font-weight: 600; color: #475569;">
                                AIRIS &bull; St. Anthony's College Research Repository
                            </p>
                            <p style="margin: 0 0 6px 0;">
                                San Jose de Buenavista, Antique, Philippines
                            </p>
                            <p style="margin: 0; font-size: 10px; color: #94a3b8;">
                                This is an automated academic notification sent to <span style="font-family: monospace; color: #64748b;">{{ $document->submitted_by_email }}</span>.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
