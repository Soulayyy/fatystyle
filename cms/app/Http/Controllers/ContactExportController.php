<?php

namespace App\Http\Controllers;

use App\Models\ContactRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('contacts.export'), 403);

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Référence', 'Statut', 'Nom', 'Email', 'Téléphone', 'Type', 'Date souhaitée', 'Message', 'Reçue le'], ';');
            ContactRequest::query()->latest('received_at')->each(function (ContactRequest $contact) use ($stream): void {
                fputcsv($stream, [
                    $contact->reference, $contact->status->value, $contact->name, $contact->email,
                    $contact->phone, $contact->request_type, $contact->desired_date?->format('Y-m-d'),
                    $contact->message, $contact->received_at?->toIso8601String(),
                ], ';');
            });
            fclose($stream);
        }, 'demandes-fatystyle-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
