<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    // untuk mengarahkan ke halaman list setelah membuat brand baru
    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }
}
