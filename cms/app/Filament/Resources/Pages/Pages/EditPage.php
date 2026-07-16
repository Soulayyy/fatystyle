<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Services\Content\PageVersionService;
use App\Services\Content\PageWorkflow;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['_lock_version'] = $this->record->lock_version;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $submittedVersion = (int) ($data['_lock_version'] ?? 0);
        $currentVersion = (int) Page::query()->whereKey($this->record)->value('lock_version');
        unset($data['_lock_version']);

        if ($submittedVersion !== $currentVersion) {
            throw ValidationException::withMessages([
                'data._lock_version' => 'Cette page a été modifiée par une autre personne. Rechargez-la avant de continuer.',
            ]);
        }

        $data['lock_version'] = $currentVersion + 1;
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        app(PageVersionService::class)->capture($this->record, 'Modification du contenu');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')->label('Prévisualiser')->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => route('preview.pages.show', $this->record))
                ->openUrlInNewTab(),
            $this->transitionAction('submit', 'Soumettre à validation', ContentStatus::InReview, 'pages.update'),
            $this->transitionAction('approve', 'Valider', ContentStatus::Approved, 'pages.publish'),
            $this->transitionAction('publish', 'Publier', ContentStatus::Published, 'pages.publish'),
            $this->transitionAction('hide', 'Masquer', ContentStatus::Hidden, 'pages.publish'),
            $this->transitionAction('draft', 'Repasser en brouillon', ContentStatus::Draft, 'pages.update'),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    private function transitionAction(string $name, string $label, ContentStatus $target, string $permission): Action
    {
        return Action::make($name)
            ->label($label)
            ->visible(fn (): bool => auth()->user()?->can($permission)
                && in_array($target, app(PageWorkflow::class)->allowedTargets($this->record), true))
            ->requiresConfirmation()
            ->action(function () use ($target, $label): void {
                app(PageWorkflow::class)->transition($this->record, $target, $label);
                Notification::make()->title('Statut mis à jour')->success()->send();
                $this->redirect(PageResource::getUrl('edit', ['record' => $this->record]));
            });
    }
}
