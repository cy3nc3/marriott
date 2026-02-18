<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentUser = User::where('email', 'student@marriott.edu')->first();
$parentUser = User::where('email', 'parent@marriott.edu')->first();

if (! $studentUser || ! $parentUser) {
    echo "Test users not found.\n";
    exit;
}

$student = \App\Models\Student::where('user_id', $studentUser->id)->first();

if (! $student) {
    echo "Test student record not found.\n";
    exit;
}

$linked = DB::table('parent_student')
    ->where('parent_id', $parentUser->id)
    ->where('student_id', $student->id)
    ->exists();

if ($linked) {
    echo "SUCCESS: Test Student linked to Test Parent.\n";
} else {
    echo "FAILURE: Link not found.\n";
}
