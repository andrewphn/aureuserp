<?php

namespace App\Filament\Resources\ApiTokenResource\Pages;

use App\Filament\Resources\ApiTokenResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Actions;
use Illuminate\Support\HtmlString;

class ViewApiToken extends ViewRecord
{
    protected static string $resource = ApiTokenResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        $plainTextToken = request()->query('token');

        return $infolist
            ->schema([
                Components\Section::make('Token Information')
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label('Token Name'),

                        Components\TextEntry::make('tokenable.name')
                            ->label('User'),

                        Components\TextEntry::make('abilities')
                            ->label('Permissions')
                            ->badge()
                            ->formatStateUsing(function ($state) {
                                $abilities = ApiTokenResource::getAvailableAbilities();
                                if (is_array($state)) {
                                    return collect($state)
                                        ->map(fn ($ability) => $abilities[$ability] ?? $ability)
                                        ->join(', ');
                                }
                                return $state;
                            }),

                        Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),

                        Components\TextEntry::make('last_used_at')
                            ->label('Last Used')
                            ->dateTime()
                            ->placeholder('Never used'),

                        Components\TextEntry::make('expires_at')
                            ->label('Expires')
                            ->dateTime()
                            ->placeholder('Never'),
                    ])
                    ->columns(2),

                Components\Section::make('Your API Token')
                    ->schema([
                        Components\TextEntry::make('plain_token')
                            ->label('')
                            ->state($plainTextToken)
                            ->copyable()
                            ->copyMessage('Token copied!')
                            ->fontFamily('mono')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success')
                            ->helperText(new HtmlString(
                                '<span class="text-danger-600 font-semibold">Copy this token now! It will not be shown again.</span>'
                            )),
                    ])
                    ->visible(fn () => !empty($plainTextToken)),

                Components\Section::make('Usage Instructions')
                    ->schema([
                        Components\TextEntry::make('usage')
                            ->label('')
                            ->state(new HtmlString('
                                <div class="space-y-4 text-sm">
                                    <div>
                                        <h4 class="font-semibold">HTTP Header Authentication</h4>
                                        <code class="block bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1">
                                            Authorization: Bearer YOUR_TOKEN_HERE
                                        </code>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold">Example cURL Request</h4>
                                        <code class="block bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1 whitespace-pre-wrap">curl -X GET "' . url('/api/v1/projects') . '" \\
    -H "Authorization: Bearer YOUR_TOKEN_HERE" \\
    -H "Accept: application/json"</code>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold">n8n HTTP Request Node</h4>
                                        <p class="text-gray-600 dark:text-gray-400">
                                            In your n8n HTTP Request node, set Authentication to "Header Auth"
                                            with Name: <code>Authorization</code> and Value: <code>Bearer YOUR_TOKEN_HERE</code>
                                        </p>
                                    </div>
                                </div>
                            ')),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Revoke Token')
                ->modalHeading('Revoke API Token')
                ->modalDescription('Are you sure you want to revoke this token? Any applications using it will immediately lose access.'),
        ];
    }
}
