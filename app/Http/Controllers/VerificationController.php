<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewVerificationRequest;
use App\Http\Requests\SubmitVerificationRequest;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function index(Request $r)
    {
        return view('verification.index', ['submissions' => $r->user()->verifications()->latest('id')->paginate(10), 'current' => $r->user()->verifications()->latest('id')->first()]);
    }

    public function store(SubmitVerificationRequest $r, VerificationService $service)
    {
        $service->submit($r->user(), $r->file('document'));

        return back()->with('success', 'Your ID is pending review.');
    }

    public function admin()
    {
        return view('admin.verifications', ['submissions' => VerificationRequest::with('student', 'reviewer')->latest('id')->paginate(15)]);
    }

    public function show(VerificationRequest $verification)
    {
        Gate::authorize('review', $verification);

        return view('admin.verification', compact('verification'));
    }

    public function document(VerificationRequest $verification)
    {
        Gate::authorize('view', $verification);

        return Storage::disk('identity')->response($verification->document_path, 'university-id.'.$this->extension($verification), ['Content-Type' => $verification->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "default-src 'none'; sandbox"], 'inline');
    }

    private function extension(VerificationRequest $v): string
    {
        return match ($v->mime_type) {
            'application/pdf' => 'pdf','image/png' => 'png',default => 'jpg'
        };
    }

    public function review(ReviewVerificationRequest $r, VerificationRequest $verification, VerificationService $service)
    {
        Gate::authorize('review', $verification);
        $service->review($verification, $r->user(), $r->validated());

        return back()->with('success','Review recorded.');
    }
}
