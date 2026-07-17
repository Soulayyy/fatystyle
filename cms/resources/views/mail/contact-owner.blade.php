<h1>Nouvelle demande depuis le site Faty Style</h1>
<p><strong>Référence :</strong> {{ $contact->reference }}</p>
<p><strong>Nom :</strong> {{ $contact->name }}<br>
<strong>Email :</strong> {{ $contact->email }}<br>
<strong>Téléphone :</strong> {{ $contact->phone }}<br>
<strong>Type :</strong> {{ $contact->request_type ?: 'Non précisé' }}<br>
<strong>Date souhaitée :</strong> {{ $contact->desired_date?->format('d/m/Y') ?: 'Non précisée' }}</p>
<p><strong>Message :</strong></p>
<p>{!! nl2br(e($contact->message)) !!}</p>
