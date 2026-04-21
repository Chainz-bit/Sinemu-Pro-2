<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\User\Claims\ClaimSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function __construct(private readonly ClaimSubmissionService $submissionService)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->submissionService->submit($request);
        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('user.claim-history')->with('status', $result['message']);
    }
}
