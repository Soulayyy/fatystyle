<?php

namespace Tests\Feature;

use App\Mail\ContactConfirmationMail;
use App\Mail\ContactOwnerMail;
use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_contact_is_stored_and_both_messages_are_sent(): void
    {
        Mail::fake();
        config()->set('cms.public_site_url', 'https://www.fatystyle.fr');
        config()->set('cms.contact_recipient', 'fatystyle@hotmail.fr');

        $response = $this->post('/api/contact', [
            'name' => ' Marie Dupont ',
            'phone' => '06 12 34 56 78',
            'email' => 'MARIE@example.test',
            'request_type' => 'Création sur mesure',
            'message' => 'Je souhaite discuter de la création d’une robe sur mesure.',
            'privacy_consent' => '1',
            'website' => '',
            'form_started_at' => now()->subSeconds(5)->timestamp,
        ]);

        $response->assertRedirect('https://www.fatystyle.fr/message-envoye.html');
        $contact = ContactRequest::firstOrFail();
        $this->assertSame('Marie Dupont', $contact->name);
        $this->assertSame('marie@example.test', $contact->email);
        $this->assertNotNull($contact->consent_at);
        $this->assertNotNull($contact->ip_hash);
        Mail::assertSent(ContactOwnerMail::class, fn ($mail): bool => $mail->hasTo('fatystyle@hotmail.fr'));
        Mail::assertSent(ContactConfirmationMail::class, fn ($mail): bool => $mail->hasTo('marie@example.test'));
    }

    public function test_honeypot_and_missing_consent_are_rejected(): void
    {
        Mail::fake();
        $response = $this->postJson('/api/contact', [
            'name' => 'Robot', 'phone' => '0612345678', 'email' => 'robot@example.test',
            'message' => 'Un message suffisamment long.', 'website' => 'spam.example',
            'form_started_at' => now()->subSeconds(5)->timestamp,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['privacy_consent', 'website']);
        $this->assertDatabaseCount('contact_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_formsubmit_mode_stores_the_request_without_using_the_local_mailer(): void
    {
        Mail::fake();
        config()->set('cms.contact_delivery', 'formsubmit');

        $response = $this->postJson('/api/contact', [
            'name' => 'Cliente', 'phone' => '0612345678', 'email' => 'cliente@example.test',
            'message' => 'Je souhaite échanger au sujet d’une création personnalisée.',
            'privacy_consent' => '1', 'website' => '',
            'form_started_at' => now()->subSeconds(5)->timestamp,
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'delivery' => 'formsubmit']);
        $this->assertDatabaseCount('contact_requests', 1);
        Mail::assertNothingSent();
    }
}
