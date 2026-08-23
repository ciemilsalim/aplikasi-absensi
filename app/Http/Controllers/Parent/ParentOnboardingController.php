<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\ParentStudentRequest;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentOnboardingController extends Controller
{
    /**
     * Tampilkan laman wizard onboarding 3 langkah untuk orang tua baru.
     */
    public function index()
    {
        $user = Auth::user();
        $parent = $user->parent;

        if (!$parent) {
            $parent = ParentModel::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'is_onboarding_completed' => false,
            ]);
        }

        // Ambil daftar kelas beserta siswanya untuk selector pencarian ramah orang tua
        $classes = SchoolClass::with(['students' => function ($q) {
            $q->orderBy('name', 'asc');
        }])->orderBy('name', 'asc')->get();

        // Data siswa yang sudah terhubung
        $connectedStudents = $parent->students()->with('schoolClass')->get();

        // Data pengajuan yang masih pending
        $pendingRequests = ParentStudentRequest::with(['student.schoolClass'])
            ->where('parent_id', $parent->id)
            ->where('status', 'pending')
            ->get();

        return view('parent.onboarding', compact('parent', 'classes', 'connectedStudents', 'pendingRequests'));
    }

    /**
     * Langkah 1: Simpan data kontak (No. WhatsApp & Alamat)
     */
    public function updateContact(Request $request)
    {
        $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $parent = Auth::user()->parent;
        $parent->update([
            'phone_number' => $request->phone_number,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data kontak berhasil disimpan.',
            'parent' => $parent,
        ]);
    }

    /**
     * Langkah 2: Proses verifikasi & pengajuan klaim anak (Auto-match vs Manual Pending)
     */
    public function verifyStudent(Request $request)
    {
        $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'student_id' => ['required', 'exists:students,id'],
            'verification_code' => ['nullable', 'string', 'max:100'],
            'relationship' => ['nullable', 'string', 'max:50'],
        ]);

        $parent = Auth::user()->parent;
        $student = Student::where('id', $request->student_id)
            ->where('school_class_id', $request->school_class_id)
            ->firstOrFail();

        // Cek jika siswa sudah terhubung sebelumnya
        if ($parent->students()->where('student_id', $student->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa ini sudah terhubung dengan akun Anda.',
            ], 422);
        }

        // Cek jika sudah ada pengajuan pending
        $existingRequest = ParentStudentRequest::where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan untuk siswa ini sudah ada dan sedang menunggu verifikasi sekolah.',
            ], 422);
        }

        $inputCode = trim($request->verification_code ?? '');
        $isAutoMatch = false;

        // Logika Auto-Match Hibrida: Cocokkan NIS atau Kode Unik QR (Case Insensitive)
        if (!empty($inputCode)) {
            $nisMatch = !empty($student->nis) && strtolower($inputCode) === strtolower(trim($student->nis));
            $uniqueMatch = !empty($student->unique_id) && strtolower($inputCode) === strtolower(trim($student->unique_id));

            if ($nisMatch || $uniqueMatch) {
                $isAutoMatch = true;
            }
        }

        if ($isAutoMatch) {
            // TERVERIFIKASI OTOMATIS: Hubungkan langsung ke pivot parent_student
            $parent->students()->syncWithoutDetaching([$student->id]);

            ParentStudentRequest::updateOrCreate(
                ['parent_id' => $parent->id, 'student_id' => $student->id],
                [
                    'relationship' => $request->relationship ?? 'Orang Tua',
                    'verification_code' => $inputCode,
                    'status' => 'approved',
                    'notes' => 'Terverifikasi otomatis via NIS/Token cocok.',
                    'verified_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'auto_approved' => true,
                'message' => 'Selamat! Data siswa berhasil terverifikasi dan terhubung secara otomatis.',
                'student' => $student->load('schoolClass'),
            ]);
        } else {
            // PENGAJUAN MANUAL (PENDING): Simpan ke antrean verifikasi untuk disetujui Admin/Wali Kelas
            ParentStudentRequest::create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'relationship' => $request->relationship ?? 'Orang Tua',
                'verification_code' => $inputCode,
                'status' => 'pending',
                'notes' => 'Pengajuan dari laman onboarding orang tua baru.',
            ]);

            return response()->json([
                'success' => true,
                'auto_approved' => false,
                'message' => 'Pengajuan pengikatan anak berhasil dikirim! Pihak sekolah (Admin / Wali Kelas) akan melakukan verifikasi.',
                'student' => $student->load('schoolClass'),
            ]);
        }
    }

    /**
     * Batalkan pengajuan pending atau lepaskan pengikatan anak dari onboarding
     */
    public function removeRequest(Request $request, $studentId)
    {
        $parent = Auth::user()->parent;

        ParentStudentRequest::where('parent_id', $parent->id)
            ->where('student_id', $studentId)
            ->delete();

        $parent->students()->detach($studentId);

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan anak berhasil dibatalkan.',
        ]);
    }

    /**
     * Langkah 3: Selesaikan onboarding & arahkan ke dasbor
     */
    public function complete()
    {
        $parent = Auth::user()->parent;
        $parent->update(['is_onboarding_completed' => true]);

        return redirect()->route('parent.dashboard')->with('success', 'Selamat datang di Portal Absensi Digital! Onboarding akun Anda telah selesai.');
    }
}
