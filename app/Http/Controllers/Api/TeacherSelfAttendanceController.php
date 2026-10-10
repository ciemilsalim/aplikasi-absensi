<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TeacherSelfAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeacherSelfAttendanceController extends Controller
{
    public function __construct(
        protected TeacherSelfAttendanceService $selfAttendanceService
    ) {}

    /**
     * Get attendance status for the teacher today.
     */
    public function getStatus(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Data guru tidak ditemukan.'], 404);
        }

        $data = $this->selfAttendanceService->getTodayStatus($teacher);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Store teacher self-attendance (Clock In).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'photo'            => 'required|string', // Base64
            'accuracy'         => 'nullable|numeric',
            'is_mock_location' => 'nullable|boolean',
            'client_timestamp' => 'nullable',
        ]);

        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Data guru tidak ditemukan.'], 404);
        }

        try {
            $attendance = $this->selfAttendanceService->clockIn(
                $teacher,
                $validated,
                $request->header('User-Agent')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Absensi datang berhasil dicatat!',
                'data'    => $attendance,
            ]);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? 'Validasi absensi gagal.';
            return response()->json([
                'status'  => 'error',
                'message' => $firstMessage,
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    /**
     * Store teacher self-attendance (Clock Out).
     */
    public function clockOut(Request $request)
    {
        $validated = $request->validate([
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'photo'            => 'required|string', // Base64
            'accuracy'         => 'nullable|numeric',
            'is_mock_location' => 'nullable|boolean',
            'client_timestamp' => 'nullable',
        ]);

        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Data guru tidak ditemukan.'], 404);
        }

        try {
            $attendance = $this->selfAttendanceService->clockOut(
                $teacher,
                $validated,
                $request->header('User-Agent')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Absensi pulang berhasil dicatat!',
                'data'    => $attendance,
            ]);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? 'Validasi absensi pulang gagal.';
            return response()->json([
                'status'  => 'error',
                'message' => $firstMessage,
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    /**
     * API for Face Registration (Individual).
     */
    public function registerFace(Request $request)
    {
        $request->validate([
            'photo' => 'required|string', // Base64
        ]);

        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Data guru tidak ditemukan.'], 404);
        }

        $photoPath = $this->selfAttendanceService->registerFace($teacher, $request->photo);

        return response()->json([
            'status'    => 'success',
            'message'   => 'Foto wajah berhasil didaftarkan!',
            'photo_url' => asset('storage/' . $photoPath),
        ]);
    }

    /**
     * Get attendance history for the teacher.
     */
    public function getHistory(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (!$teacher) {
            return response()->json(['status' => 'error', 'message' => 'Data guru tidak ditemukan.'], 404);
        }

        $limit = (int) $request->query('limit', 30);
        $history = $this->selfAttendanceService->getHistory($teacher, $limit);

        return response()->json([
            'status' => 'success',
            'data'   => $history,
        ]);
    }
}
