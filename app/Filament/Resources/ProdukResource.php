<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdukResource\Pages;
use App\Filament\Resources\ProdukResource\RelationManagers;
use App\Models\Produk;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// new library
use Filament\Forms\Components\TextInput; // komponen input teks standar
use Filament\Forms\Components\FileUpload; // untuk mengunggah file (gambar atau dokumen)
use Filament\Forms\Components\Repeater; // untuk membuat inputan yang bisa ditambah/duplikasi berkali-kali
use Filament\Forms\Components\Select; // untuk dropdown pilihan (bisa mengambil data statis atau relasi dari database lain)
use Filament\Forms\Components\Fieldset; // untuk mengelompokkan beberapa inputan ke dalam satu kotak dengan label (agar form yang panjang terlihat lebih rapi dan terorganisir)
use Filament\Tables\Columns\ImageColumn; // untuk menampilkan thumbnail gambar langsung di tabel
use Filament\Tables\Columns\TextColumn; // untuk menampilkan data berupa teks biasa
use Filament\Tables\Columns\IconColumn; // untuk menampilkan ikon (dari heroicons) 
use Filament\Tables\Actions\EditAction; // tombol otomatis untuk membuka modal atau halaman edit data pada baris tersebut
use Filament\Tables\Actions\DeleteAction; // tombol untuk menghapus satu baris data tertentu dengan konfirmasi
use Filament\Tables\Actions\BulkActionGroup; // wadah untuk mengelompokkan aksi-aksi yang dilakukan pada banyak data sekaligus
use Filament\Tables\Actions\DeleteBulkAction; // aksi khusus di dalam grup untuk menghapus semua data yang sedang dicentang (selected) secara bersamaan
use Filament\Tables\Actions\ActionGroup; // wadah untuk mengelompokkan beberapa aksi menjadi satu dropdown
use Filament\Tables\Actions\ViewAction; // tombol untuk melihat detail data pada baris tersebut

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function canCreate(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->isSuperAdmin();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Fieldset Informasi Produk
                Fieldset::make('Informasi Produk')
                    ->schema([

                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->minLength(5)
                            ->maxLength(255),

                        TextInput::make('price')
                            ->label('Harga Produk')
                            ->prefix('Rp')
                            ->minValue(100000)
                            ->maxLength(20)
                            ->numeric()
                            ->required(),

                        FileUpload::make('thumbnail')
                            ->label('Gambar Produk')
                            ->image()
                            ->directory('produk/thumbnail')
                            ->maxSize(1024)
                            ->columnSpanFull()
                            ->required(),

                        // galeri produk
                        Repeater::make('photos')
                            ->relationship()
                            ->label('Galeri Produk')
                            ->schema([
                                FileUpload::make('photo')
                                    ->label('Tambahkan Gambar Produk Lainnya')
                                    ->image()
                                    ->required()
                                    ->directory('produk/gallery')
                                    ->maxSize(1024)
                            ])
                            ->addActionLabel('Tambah Gambar'),

                        // ukuran produk
                        Repeater::make('sizes') // hasMany relationship 'sizes'
                            ->relationship()
                            ->label('Ukuran Produk')
                            ->schema([
                                Select::make('size')
                                    ->label('Ukuran')
                                    ->required()
                                    ->distinct() // mencegah duplikasi ukuran yang sama
                                    ->options([
                                        '36' => '36',
                                        '37' => '37',
                                        '38' => '38',
                                        '39' => '39',
                                        '40' => '40',
                                        '41' => '41',
                                        '42' => '42',
                                        '43' => '43',
                                        '44' => '44',
                                    ])
                            ])
                            ->addActionLabel('Tambah Ukuran'),
                    ]),

                // Fieldset Informasi Tambahan
                Fieldset::make('Informasi Tambahan')
                    ->schema([

                        Textarea::make('about')
                            ->label('Deskripsi Produk')
                            ->columnSpanFull()
                            ->maxLength(1000)
                            ->required(),

                        Select::make('is_populer')
                            ->label('Produk Populer?')
                            ->options([
                                true => 'Ya',
                                false => 'Tidak'
                            ]),

                        // kategori produk
                        Select::make('category_id')
                            ->label('Kategori Produk')
                            ->relationship('category', 'name')
                            ->required(),

                        // brand produk
                        Select::make('brand_id')
                            ->label('Brand Produk')
                            ->relationship('brand', 'name')
                            ->required(),

                        // stok produk
                        TextInput::make('stock')
                            ->label('Stok Produk')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->suffix('pcs'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Gambar')
                    ->size(50),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('medium')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable(),

                IconColumn::make('is_populer')
                    ->label('Populer')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Opsi')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
            'edit' => Pages\EditProduk::route('/{record}/edit'),
        ];
    }
}
