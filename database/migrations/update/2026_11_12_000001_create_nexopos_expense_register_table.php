<?php

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfMissing( 'nexopos_expense_register', function ( Blueprint $table ) {
            $table->bigIncrements( 'id' );
            $table->date( 'expense_date' );
            $table->string( 'category' );
            $table->string( 'description' );
            $table->decimal( 'amount', 18, 2 );
            $table->string( 'payment_method' );
            $table->string( 'responsible' )->nullable();
            $table->enum( 'expense_type', [ 'fixed', 'variable' ] )->default( 'variable' );
            $table->enum( 'status', [ 'paid', 'pending' ] )->default( 'paid' );
            $table->string( 'receipt_reference' )->nullable();
            $table->text( 'notes' )->nullable();
            $table->enum( 'classification', [ 'operation', 'inventory', 'financing' ] )->default( 'operation' );
            $table->unsignedBigInteger( 'author_id' );
            $table->timestamps();

            $table->index( [ 'expense_date', 'status' ], 'expense_register_date_status_index' );
            $table->index( [ 'category', 'expense_date' ], 'expense_register_category_date_index' );
            $table->index( 'classification', 'expense_register_classification_index' );
        } );
    }

    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_expense_register' );
    }
};
