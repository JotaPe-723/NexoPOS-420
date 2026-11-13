<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseRegister extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_expense_register';

    protected $guarded = [];

    protected $casts = [
        'expense_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public const CATEGORIES = [
        'Compra de inventario',
        'Arriendo',
        'Servicios',
        'Personal',
        'Domicilios y transporte',
        'Publicidad',
        'Operación e insumos',
        'Tecnología',
        'Gastos financieros',
        'Abono a préstamo',
        'Impuestos y trámites',
        'Otros',
    ];

    public const PAYMENT_METHODS = [
        'Efectivo',
        'Transferencia',
        'Tarjeta débito',
        'Tarjeta crédito',
        'Otro',
    ];

    public const TYPES = [
        'fixed' => 'Fijo',
        'variable' => 'Variable',
    ];

    public const STATUSES = [
        'paid' => 'Pagado',
        'pending' => 'Pendiente',
    ];

    public static function classify( ?string $category ): string
    {
        return match ( $category ) {
            'Compra de inventario' => 'inventory',
            'Abono a préstamo' => 'financing',
            default => 'operation',
        };
    }

    public function author()
    {
        return $this->belongsTo( User::class, 'author_id' );
    }
}
