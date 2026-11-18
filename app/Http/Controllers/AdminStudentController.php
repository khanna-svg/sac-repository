<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminStudentController extends Controller
{
    /**
     * List all registered student accounts.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'student');

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhere('lrn', 'LIKE', "%{$search}%");
            });
        }

        $students = $query->orderBy('created_at', 'desc')->get([
            'id', 'name', 'email', 'lrn', 'role', 'created_at'
        ]);

        return response()->json([
            'error' => false,
            'students' => $students,
            'total' => $students->count(),
        ]);
    }

    /**
     * Store a newly registered student in Supabase.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'lrn' => [
                'required',
                'string',
                'regex:/^\d{12}$/',
                Rule::unique('users', 'lrn'),
            ],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'name.required' => 'Student full name is required.',
            'email.required' => 'Institutional email is required.',
            'email.email' => 'Please enter a valid email format.',
            'email.unique' => 'A user with this institutional email is already registered.',
            'lrn.required' => 'The 12-digit Learner Reference Number (LRN) is required.',
            'lrn.regex' => 'The LRN must be exactly 12 numeric digits.',
            'lrn.unique' => 'This LRN is already registered to another student.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        $email = strtolower(trim($request->email));

        if (!str_ends_with($email, '@sac.edu.ph')) {
            return response()->json([
                'error' => true,
                'message' => 'Only official @sac.edu.ph institutional email addresses are permitted.',
            ], 422);
        }

        $name = trim($request->name);
        $lrn = trim($request->lrn);
        $password = $request->filled('password') ? trim($request->password) : $lrn;

        $student = User::create([
            'name' => $name,
            'email' => $email,
            'lrn' => $lrn,
            'role' => 'student',
            'password' => Hash::make($password),
        ]);

        // Record in Admin Activity Logs
        AdminActivityLog::record(
            'add_student',
            null,
            "Student: {$student->name}",
            "Admin registered new student {$student->name} ({$student->email}) with LRN {$student->lrn}."
        );

        return response()->json([
            'error' => false,
            'message' => "Student {$student->name} ({$student->email}) successfully registered!",
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'lrn' => $student->lrn,
                'role' => $student->role,
                'created_at' => $student->created_at ? $student->created_at->format('M d, Y h:i A') : 'Just now',
            ],
        ], 201);
    }

    /**
     * Update/reset a student's password (e.g. when forgotten).
     */
    public function resetPassword(Request $request, $id)
    {
        $student = User::where('id', $id)->where('role', 'student')->first();
        if (!$student) {
            return response()->json([
                'error' => true,
                'message' => 'Student record not found.',
            ], 404);
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_password.required' => 'Please enter a new password.',
            'new_password.min' => 'Password must be at least 6 characters.',
        ]);

        $newPassword = trim($request->new_password);
        $student->password = Hash::make($newPassword);
        $student->save();

        AdminActivityLog::record(
            'reset_student_password',
            null,
            "Student: {$student->name}",
            "Admin updated password for student {$student->name} ({$student->email})."
        );

        return response()->json([
            'error' => false,
            'message' => "Password for {$student->name} has been successfully updated!",
        ]);
    }

    /**
     * Delete student account.
     */
    public function destroy($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);
        $name = $student->name;
        $email = $student->email;
        $lrn = $student->lrn;

        $student->delete();

        AdminActivityLog::record(
            'delete_student',
            null,
            "Student: {$name}",
            "Admin removed student {$name} ({$email}, LRN {$lrn})."
        );

        return response()->json([
            'error' => false,
            'message' => "Student {$name} removed successfully.",
        ]);
    }
}
