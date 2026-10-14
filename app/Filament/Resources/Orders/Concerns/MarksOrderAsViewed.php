<?php

namespace App\Filament\Resources\Orders\Concerns;

use App\Models\OrderAdminView;

trait MarksOrderAsViewed
{
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->markOrderAsViewed();
    }

    protected function markOrderAsViewed(): void
    {
        $userId = auth()->id();

        if (! $userId) {
            return;
        }

        OrderAdminView::query()->updateOrCreate(
            [
                'order_id' => $this->record->id,
                'user_id' => $userId,
            ],
            [
                'viewed_at' => now(),
            ]
        );
    }
}
