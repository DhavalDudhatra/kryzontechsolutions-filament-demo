<?php

namespace App\Filament\Resources\EmailTemplates\Tables;

use App\Enums\Permission;
use App\Mail\EmailTemplateMailable;
use App\Models\EmailTemplate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Throwable;

class EmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('template_name')
                    ->label('Title')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('email_subject')
                    ->label('Subject')
                    ->limit(50)
                    ->placeholder('—'),

                TextColumn::make('cc')
                    ->label('CC')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('bcc')
                    ->label('BCC')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Status')
                    ->alignCenter()
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('send')->iconButton()->tooltip('Send Mail')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->authorize(Permission::EmailTemplatesUpdate->value)
                    ->modalHeading('Send Test Email')
                    ->modalDescription('Send a test email using this template and its configured recipients.')
                    ->modalSubmitActionLabel('Send')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('email')
                                    ->label('Test Recipient')
                                    ->email()
                                    ->required()
                                    ->default('test@example.com')
                                    ->placeholder('Enter recipient email address')
                                    ->helperText('The test email will be sent to this address.'),

                                TextInput::make('subject')
                                    ->label('Subject')
                                    ->default(fn (EmailTemplate $record): string => (string) $record->email_subject)
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('cc')
                                    ->label('CC Recipients')
                                    ->default(fn (EmailTemplate $record): string => implode(', ', EmailTemplateMailable::recipients($record->cc)))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn (EmailTemplate $record): bool => filled(
                                        EmailTemplateMailable::recipients($record->cc),
                                    )),

                                TextInput::make('bcc')
                                    ->label('BCC Recipients')
                                    ->default(fn (EmailTemplate $record): string => implode(', ', EmailTemplateMailable::recipients($record->bcc)))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn (EmailTemplate $record): bool => filled(
                                        EmailTemplateMailable::recipients($record->bcc),
                                    )),
                            ]),
                    ])
                    ->action(function (EmailTemplate $record, array $data) {
                        try {
                            $sent = EmailTemplateMailable::sendTo($record->template_key, $data['email'], [
                                'user' => [
                                    'name' => 'Test User',
                                    'email' => $data['email'],
                                ],
                            ]);
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Test email failed')
                                ->body('The mail transport rejected the message. Check the logs for details.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (! $sent) {
                            Notification::make()
                                ->title('Email template is inactive')
                                ->body('Activate the template before sending a test email.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Test email sent. Check Mailpit.')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->iconButton()->tooltip('Edit'),
                DeleteAction::make()->iconButton()->tooltip('Delete'),
            ])->recordActionsColumnLabel('Actions')
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
