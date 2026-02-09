<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Schema;

// new library
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter; // untuk menampilkan data yang dihapus secara soft delete
use Filament\Tables\Actions\ForceDeleteBulkAction; // untuk menghapus data secara permanen
use Filament\Tables\Actions\RestoreBulkAction; // untuk mengembalikan data

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static function canCreate():bool
    {
        return auth()->user()->isSuperAdmin();
    }
    
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record):bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record):bool
    {
        return auth()->user()->isSuperAdmin();
    }

        public static function canDeleteAny():bool
    {
        return auth()->user()->isSuperAdmin();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->minLength(3)
                    ->maxLength(50)
                    ->columnSpanFull(),
                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->columnSpanFull()
                    ->directory('brands')
                    ->maxSize(1024)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('medium'),
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->square(),
            ])
            ->filters([
                TrashedFilter::make(),
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
                DeleteBulkAction::make(),
                // Soft Deletes Restore & delete (permanent)
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
