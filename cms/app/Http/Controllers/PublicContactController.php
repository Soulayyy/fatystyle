<?php

namespace App\Http\Controllers;

use App\Mail\ContactConfirmationMail;
use App\Mail\ContactOwnerMail;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicContactController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+(). \-]{7,40}$/'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'request_type' => ['nullable', 'string', 'max:120', Rule::notIn(['undefined', 'null'])],
            'desired_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'privacy_consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],
            'form_started_at' => ['required', 'integer'],
        ]);

        abort_if(now()->timestamp - (int) $validated['form_started_at'] < 3, 422, 'Envoi trop rapide.');

        $contact = ContactRequest::create([
            'name' => Str::squish($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => Str::squish($validated['phone']),
            'request_type' => $validated['request_type'] ?? null,
            'desired_date' => $validated['desired_date'] ?? null,
            'message' => trim($validated['message']),
            'consent_at' => now(),
            'source' => 'website',
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'metadata' => ['referer' => Str::limit((string) $request->headers->get('referer'), 500, '')],
        ]);

        Mail::to(config('cms.contact_recipient'))->send(new ContactOwnerMail($contact));
        Mail::to($contact->email)->send(new ContactConfirmationMail($contact));

        return redirect()->away(rtrim((string) config('cms.public_site_url'), '/').'/message-envoye.html');
    }
}
