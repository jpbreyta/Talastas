<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LegalPolicies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegalController extends Controller
{
    public function show(Request $request, LegalPolicies $policies)
    {
        $document = $policies->document($request->route('kind'), $request->route('version'));
        if ($request->is('api/*') || $request->expectsJson()) {
            unset($document['snapshot']);

            return response()->json($document);
        }

        return response()->view('legal.document', compact('document'));
    }

    public function review(Request $request, LegalPolicies $policies)
    {
        $status = $policies->status($request->user());
        $receipts = $policies->receipts($request->user());
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['data' => $status, 'receipts' => $receipts])
                ->header('Cache-Control', 'private, no-store');
        }

        return response()->view('legal.review', [
            'document' => $policies->document('terms'), 'status' => $status, 'receipts' => $receipts,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function accept(Request $request, LegalPolicies $policies)
    {
        $validated = $request->validate([
            'accepted' => ['required', 'accepted'],
            'version' => ['required', 'string', 'max:80'],
            'content_hash' => ['required', 'string', 'size:64'],
            'user_id' => ['prohibited'],
            'accepted_at' => ['prohibited'],
        ], [
            'accepted.required' => 'Select the acknowledgement checkbox before accepting and continuing.',
            'accepted.accepted' => 'Select the acknowledgement checkbox before accepting and continuing.',
        ]);
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $receipt = $policies->accept($user, $validated['version'], $validated['content_hash']);
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['data' => $policies->status($user), 'receipt' => [
                'id' => $receipt->id, 'version' => $receipt->policy_version,
                'content_hash' => $receipt->content_hash, 'accepted_at' => $receipt->accepted_at,
            ]])->header('Cache-Control', 'private, no-store');
        }

        return redirect(app(\App\Services\AuthenticationDestination::class)->pull($request))->with('success', 'Your Terms acknowledgement was recorded.');
    }

    public function decline(Request $request)
    {
        // Declining never writes a receipt. Revoke the credentials used for this request.
        $user = $request->user();
        $token = $user?->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'You declined the Terms and have been signed out.'])
                ->header('Cache-Control', 'private, no-store');
        }

        return redirect()->route('login')->with('status', 'Accepting the Terms is required to access the system.');
    }

    public function receipt(Request $request, string $receipt, LegalPolicies $policies)
    {
        abort_unless(Schema::hasTable('terms_acceptances'), 503, 'Terms storage is not installed.');
        $row = DB::table('terms_acceptances')
            ->where('user_id', $request->user()->getAuthIdentifier())->where('id', $receipt)->first();
        abort_unless($row, 404);
        $document = $policies->receiptDocument($row);
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['document' => $document, 'accepted_at' => $row->accepted_at])
                ->header('Cache-Control', 'private, no-store');
        }

        return response()->view('legal.document', compact('document'))
            ->header('Cache-Control', 'private, no-store');
    }
}
