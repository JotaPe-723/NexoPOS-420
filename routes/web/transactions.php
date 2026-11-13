<?php

use App\Http\Controllers\Dashboard\ExpenseRegisterController;
use App\Http\Controllers\Dashboard\TransactionController;
use App\Http\Controllers\Dashboard\TransactionsAccountController;
use Illuminate\Support\Facades\Route;

Route::get( '/accounting/expenses', [ ExpenseRegisterController::class, 'listExpenses' ] )->name( ns()->routeName( 'ns.dashboard.expense-register' ) );
Route::get( '/accounting/expenses/create', [ ExpenseRegisterController::class, 'createExpense' ] )->name( ns()->routeName( 'ns.dashboard.expense-register.create' ) );
Route::get( '/accounting/expenses/summary', [ ExpenseRegisterController::class, 'summary' ] )->name( ns()->routeName( 'ns.dashboard.expense-register.summary' ) );
Route::get( '/accounting/expenses/edit/{expense}', [ ExpenseRegisterController::class, 'editExpense' ] )->name( ns()->routeName( 'ns.dashboard.expense-register.edit' ) );

Route::get( '/accounting/transactions', [ TransactionController::class, 'listTransactions' ] )->name( ns()->routeName( 'ns.dashboard.transactions' ) );
Route::get( '/accounting/transactions/create', [ TransactionController::class, 'createTransaction' ] )->name( ns()->routeName( 'ns.dashboard.transactions.create' ) );
Route::get( '/accounting/transactions/edit/{transaction}', [ TransactionController::class, 'editTransaction' ] )->name( ns()->routeName( 'ns.dashboard.transactions.edit' ) );
Route::get( '/accounting/transactions/history/{transaction}', [ TransactionController::class, 'getTransactionHistory' ] )->name( ns()->routeName( 'ns.dashboard.transactions.history' ) );
Route::get( '/accounting/transactions/history', [ TransactionController::class, 'transactionsHistory' ] )->name( ns()->routeName( 'ns.dashboard.cash-flow.history' ) );

Route::get( '/accounting/accounts', [ TransactionsAccountController::class, 'listTransactionsAccounts' ] )->name( ns()->routeName( 'ns.dashboard.transactions-account' ) );
Route::get( '/accounting/accounts/create', [ TransactionsAccountController::class, 'createTransactionsAccounts' ] )->name( ns()->routeName( 'ns.dashboard.transactions-account.create' ) );
Route::get( '/accounting/accounts/edit/{account}', [ TransactionsAccountController::class, 'editTransactionsAccounts' ] )->name( ns()->routeName( 'ns.dashboard.transactions-account.edit' ) );
Route::get( '/accounting/rules', [ TransactionsAccountController::class, 'listTransactionsRules' ] )->name( ns()->routeName( 'ns.dashboard.transactions-rules' ) );
