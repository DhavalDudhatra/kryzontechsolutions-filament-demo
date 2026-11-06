<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use App\Models\EmailTemplate;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        $mergeTags = EmailTemplate::getMergeTags();

        return $schema->components([
            Section::make('Template Details')
                ->description('Configure the email name, subject, recipients, and content.')
                ->schema([
                    TextInput::make('template_name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $set('template_key', Str::snake($state ?? ''));
                        }),

                    TextInput::make('email_subject')
                        ->label('Email Subject')
                        ->required()
                        ->maxLength(255),

                    TagsInput::make('cc')
                        ->label('CC')
                        ->placeholder('Add CC email')
                        ->helperText('Optional. Enter an email and press Enter to add another.')
                        ->separator(',')
                        ->nestedRecursiveRules(['email'])
                        ->afterStateUpdated(static function (?array $state, Set $set): void {
                            // Trim so a pasted "a@x.com, b@x.com" validates.
                            $set('cc', array_values(array_unique(array_filter(array_map(trim(...), $state ?? [])))));
                        }),

                    TagsInput::make('bcc')
                        ->label('BCC')
                        ->placeholder('Add BCC email')
                        ->helperText('Optional. Enter an email and press Enter to add another.')
                        ->separator(',')
                        ->nestedRecursiveRules(['email'])
                        ->afterStateUpdated(static function (?array $state, Set $set): void {
                            $set('bcc', array_values(array_unique(array_filter(array_map(trim(...), $state ?? [])))));
                        }),

                    RichEditor::make('email_body')
                        ->label('Email Body')
                        ->extraAttributes(['class' => 'email-template-editor'])
                        ->placeholder('Write your email here. Use merge tags to insert dynamic values.')
                        ->helperText('Merge tags are replaced with their corresponding values when the email is sent.')
                        ->required()
                        ->minHeight('12rem')
                        ->maxHeight('24rem')
                        ->stickyToolbar()
                        ->stickyPanels()
                        ->stickyOffset('5rem')
                        ->mergeTags($mergeTags)
                        ->toolbarButtons([
                            ['undo', 'redo'],
                            ['bold', 'italic', 'underline', 'strike', 'link'],
                            [
                                ToolbarButtonGroup::make(
                                    'Heading',
                                    ['paragraph', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
                                )->textualButtons(),
                            ],
                            [
                                ToolbarButtonGroup::make(
                                    'Alignment',
                                    ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                                ),
                            ],
                            ['grid'],
                            ['bulletList', 'orderedList', 'blockquote', 'horizontalRule', 'codeBlock'],
                            ['textColor', 'highlight', 'lead'],
                            ['table', 'attachFiles', 'mergeTags'],
                            ['clearFormatting'],
                        ])
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('email-images')
                        ->fileAttachmentsMaxSize(2048)
                        ->resizableImages()
                        ->columnSpanFull(),
                    // ->activePanel('mergeTags'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Only active templates can be sent.')
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
