<?php

namespace App\Filament\Resources;

use App\Enums\TalkLength;
use App\Enums\TalkStatus;
use App\Filament\Resources\TalkResource\Pages;
use App\Filament\Resources\TalkResource\RelationManagers;
use App\Models\Talk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TalkResource extends Resource
{
    protected static ?string $model = Talk::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Talk::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->filtersTriggerAction(function ($action){
                return $action->button()->label('Filter');
            })
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->description(description: function (Talk $record) {
                        return Str::of($record->abstract)->limit(40);
                    }),
//                Tables\Columns\TextInputColumn::make('title')
//                    ->sortable()
//                    ->rules(['required', 'max:255'])
//                    ->searchable(),
                ImageColumn::make('speaker.avatar')
                    ->circular()
                    ->label('Speaker avater')
                ->defaultImageUrl(function ($record) {
                    return 'https://ui-avatars.com/api/?background=0D8ABC&color=fff&name='.urlencode($record->speaker->name);
                }),
                Tables\Columns\TextColumn::make('speaker.name')
                    ->sortable()
                    ->searchable()
                ,
                Tables\Columns\IconColumn::make('new_talk')
                ->boolean(),
                Tables\Columns\TextColumn::make('status')->badge()
                ->sortable()
                ->color(function ($state){
                    return $state->getColor();
                }),
                Tables\Columns\IconColumn::make('length')
                ->icon(function($state){
                    return match ($state) {
                        TalkLength::NORMAL => 'heroicon-o-megaphone',
                        TalkLength::LIGHTNING => 'heroicon-o-bolt',
                        TalkLength::KEYNOTE => 'heroicon-o-key',
                    };
                })
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('new_talk'),
                Tables\Filters\SelectFilter::make('speaker')
                    ->relationship('speaker', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('has_avatar')
                    ->toggle()
                    ->label('Show only speakers with Avatars')
                ->query(function (Builder $query) {
                    return $query->whereHas('speaker', function (Builder $query) {
                        $query->whereNotNull('avatar');
                    });
                })
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->visible(function ($record){
                            return $record->status === TalkStatus::SUBMITTED;
                        })
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Talk $talk) {
                            $talk->approve();
                        })
                        ->after(function (){
                            Notification::make()->success()->title('Talk Approved')
                                ->duration(1000)
                                ->body('The speaker has been notified and the talk has been approved.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('reject')
                        ->visible(function ($record){
                            return $record->status === TalkStatus::SUBMITTED;
                        })
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->action(function (Talk $talk) {
                            $talk->reject();
                        })
                        ->requiresConfirmation()
                        ->after(function (){
                            Notification::make()->danger()->title('Talk Rejected')
                                ->duration(1000)
                                ->body('The speaker has been notified and the talk has been rejected.')
                                ->send();
                        }),
                ]),


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                    ->action(function (Collection $records){
                        $records->each->approve();
                    }),
                    Tables\Actions\DeleteBulkAction::make(),
                    RestoreBulkAction::make()
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->tooltip('This will export the talk data')
                    ->action(function ($livewire){
                        ray($livewire->getFilteredTableQuery());
                    })
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
            'index' => Pages\ListTalks::route('/'),
            'create' => Pages\CreateTalk::route('/create'),
//            'edit' => Pages\EditTalk::route('/{record}/edit'),
        ];
    }
}
