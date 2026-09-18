<?php

declare(strict_types=1);

namespace App\Services\Admin\Product;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    public function __construct(
        private readonly ImageManager $manager = new ImageManager(new Driver())
    ) {}

    public function uploadProductImage(UploadedFile $imageFile): string
    {
        $filename = Str::uuid() . '.webp';
        $image = $this->manager->read($imageFile->getPathname());
        $encoded = $image->toWebp(80);
        
        Storage::disk('public')->put('productos/' . $filename, $encoded->toString());
        
        return 'productos/' . $filename;
    }
    
    public function formatExistingImageUrl(string $imageUrl): string
    {
        return str_replace('/storage/', '', $imageUrl);
    }
}
