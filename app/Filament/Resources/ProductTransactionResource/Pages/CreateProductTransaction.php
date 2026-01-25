<?php

namespace App\Filament\Resources\ProductTransactionResource\Pages;

use App\Filament\Resources\ProductTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use App\Models\Produk;

class CreateProductTransaction extends CreateRecord
{
    protected static string $resource = ProductTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // ambil data transaksi
        $record = $this->record;

        // ambil data produk
        $produk = Produk::find($record->produk_id);

        if ($produk) {
            // kurang stok
            $produk -> decrement('stock', $record->quantity);
        }


    }
}
