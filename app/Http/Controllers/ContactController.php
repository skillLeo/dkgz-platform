<?php

namespace App\Http\Controllers;

use App\Jobs\SendContactEnquiryJob;
use App\Support\Content;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Public/Kontakt', [
            'content' => Content::page('kontakt'),
            'contact' => [
                'company' => Settings::get('contact.company_name'),
                'street' => Settings::get('contact.street'),
                'postal_code' => Settings::get('contact.postal_code'),
                'city' => Settings::get('contact.city'),
                'phone' => Settings::get('contact.phone'),
                'email' => Settings::get('contact.support_email'),
                'hours' => Settings::get('contact.office_hours'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'reference' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'privacy' => ['accepted'],
            // Honeypot — no third-party captcha, which would leak visitor data.
            'website' => ['nullable', 'size:0'],
        ], [
            'privacy.accepted' => 'Bitte stimmen Sie der Datenschutzerklärung zu.',
        ], [
            'name' => 'Ihr Name',
            'email' => 'Ihre E-Mail-Adresse',
            'subject' => 'der Betreff',
            'message' => 'Ihre Nachricht',
        ]);

        $validator->after(function (Validator $v) use ($request) {
            if ($request->filled('website')) {
                $v->errors()->add('website', 'Diese Nachricht konnte nicht verarbeitet werden.');
            }
        });

        $data = $validator->validate();

        SendContactEnquiryJob::dispatch($data);

        return back()->with('success', 'Vielen Dank. Wir haben Ihre Nachricht erhalten und melden uns zeitnah.');
    }
}
