<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadConfig
{
    /**
     * Configure a standard image upload with consistent settings.
     *
     * @param  array<int, string>  $acceptedTypes
     */
    public static function image(
        string $name,
        string $label,
        string $directory = 'uploads',
        ?string $helperText = null,
        int $maxSize = 2048,
        array $acceptedTypes = ['image/jpeg', 'image/png', 'image/webp']
    ): FileUpload {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes($acceptedTypes)
            ->imagePreviewHeight('200')
            ->maxSize($maxSize)
            ->helperText($helperText ?? 'Recommended: max '.($maxSize / 1024).' MB. JPG, PNG, or WebP.')
            ->removeUploadedFileButtonPosition('right')
            ->uploadButtonPosition('right')
            ->loadingIndicatorPosition('right')
            ->uploadProgressIndicatorPosition('right')
            ->extraAttributes(['class' => 'product-image-upload'])
            ->validationMessages([
                'mimetypes' => 'Please upload a valid image (JPG, PNG, or WebP).',
                'max' => 'The image must not exceed '.(int) ($maxSize / 1024).' MB.',
                'image' => 'The uploaded file must be an image.',
            ]);
    }

    /**
     * Configure an avatar/profile photo upload.
     */
    public static function avatar(
        string $name = 'avatar',
        string $label = 'Profile Photo',
        string $directory = 'avatars',
        int $maxSize = 2048
    ): FileUpload {
        $maxSizeMB = $maxSize / 1024;

        return self::image($name, $label, $directory, "Upload a profile photo (max {$maxSizeMB}MB). JPG, PNG, or WebP.", $maxSize)
            ->avatar()
            ->extraAttributes(['class' => 'profile-avatar-upload'])
            ->placeholder('Upload photo')
            ->imagePreviewHeight('150');
    }

    /**
     * Configure a logo upload with integrated preview and display-fit validation.
     */
    public static function logo(
        string $name,
        string $label,
        string $helperText,
        string $directory = 'branding',
        int $maxSize = 2048,
    ): FileUpload {
        $disk = config('filesystems.default');
        $maxSizeMB = $maxSize / 1024;

        // Enforce retina-safe dimensions and a 1:4–4:1 aspect ratio for the sidebar logo; the wrapper prevents incorrect Filament parameter injection.
        $validateDisplayFit = static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($disk): void {
            $file = is_array($value) ? reset($value) : $value;

            if ($file instanceof UploadedFile) {
                $path = $file->getRealPath();
            } elseif (is_string($file) && Storage::disk($disk)->exists($file)) {
                $path = Storage::disk($disk)->path($file);
            } else {
                return;
            }

            $info = @getimagesize($path);
            if ($info === false) {
                $fail('The logo must be a valid PNG, JPG, or WebP image.');

                return;
            }

            [$width, $height] = $info;

            if (min($width, $height) < 120) {
                $fail('The logo must be at least 120px on its shortest side.');

                return;
            }

            if (max($width, $height) > 2000) {
                $fail('The logo must not exceed 2000px on its longest side.');

                return;
            }

            $ratio = $width / $height;
            if ($ratio < 0.25 || $ratio > 4) {
                $fail('The logo aspect ratio must be between 1:4 and 4:1.');
            }
        };

        return FileUpload::make($name)
            ->label($label)
            ->extraAttributes([
                'class' => 'logo-upload'.($name === 'dark_logo' ? ' logo-upload-dark' : ''),
            ])
            ->image()
            ->disk($disk)
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->maxSize($maxSize)
            ->helperText($helperText)
            ->imagePreviewHeight('200')
            ->panelLayout('integrated')
            ->rule($validateDisplayFit)
            ->removeUploadedFileButtonPosition('right')
            ->uploadButtonPosition('right')
            ->loadingIndicatorPosition('right')
            ->uploadProgressIndicatorPosition('right')
            ->validationMessages([
                'mimetypes' => 'Upload a valid PNG, JPG, or WebP image.',
                'max' => "The image must not exceed {$maxSizeMB} MB.",
                'image' => 'The uploaded file must be an image.',
            ]);
    }

    /**
     * Configure a favicon upload with a square preview.
     */
    public static function favicon(
        string $name = 'favicon',
        string $label = 'Favicon',
    ): FileUpload {
        $disk = config('filesystems.default');

        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk($disk)
            ->directory('branding')
            ->visibility('public')
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->maxSize(512)
            ->imagePreviewHeight('128')
            ->automaticallyCropImagesToAspectRatio('1:1')
            ->helperText('Square PNG or WebP, 512x512 recommended, max 512 KB.')
            ->removeUploadedFileButtonPosition('right')
            ->uploadButtonPosition('right')
            ->loadingIndicatorPosition('right')
            ->uploadProgressIndicatorPosition('right')
            ->validationMessages([
                'mimetypes' => 'Upload a valid PNG, JPG, or WebP image.',
                'max' => 'The favicon must not exceed 512 KB.',
                'image' => 'The uploaded file must be an image.',
            ]);
    }

    /**
     * Configure a product image upload with enhanced settings.
     */
    public static function productImage(
        string $name = 'image',
        string $label = 'Product Image',
        string $directory = 'products',
        int $maxSize = 2048
    ): FileUpload {
        $maxSizeMB = $maxSize / 1024;

        return self::image(
            $name,
            $label,
            $directory,
            "Recommended: 800×800px, max {$maxSizeMB}MB. JPG, PNG or WebP.",
            $maxSize,
            ['image/jpeg', 'image/png', 'image/webp', 'image/jpg']
        );
    }
}
