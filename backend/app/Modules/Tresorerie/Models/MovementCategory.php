<?php

namespace App\Modules\Tresorerie\Models;

use App\Modules\Tresorerie\Enums\MovementDirection;
use Illuminate\Database\Eloquent\Model;

/** Catégorie de mouvement. Le code identifie les deux catégories que le système utilise lui-même. */
class MovementCategory extends Model
{
    public const PAYMENT = 'payment';
    public const DEBOURS = 'debours';

    protected $table = 'movement_categories';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'direction' => MovementDirection::class,
            'is_system' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function isDebours(): bool
    {
        return $this->code === self::DEBOURS;
    }

    /** Catégorie « Paiement client », créée si elle manque. */
    public static function payment(): self
    {
        return self::query()->firstOrCreate(
            ['code' => self::PAYMENT],
            ['name' => 'Paiement client', 'direction' => MovementDirection::In->value, 'is_system' => true, 'position' => 0],
        );
    }
}