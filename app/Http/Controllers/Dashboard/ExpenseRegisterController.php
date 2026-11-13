<?php

namespace App\Http\Controllers\Dashboard;

use App\Crud\ExpenseRegisterCrud;
use App\Http\Controllers\DashboardController;
use App\Models\ExpenseRegister;

class ExpenseRegisterController extends DashboardController
{
    public function listExpenses()
    {
        return ExpenseRegisterCrud::table();
    }

    public function createExpense()
    {
        return ExpenseRegisterCrud::form();
    }

    public function editExpense( ExpenseRegister $expense )
    {
        return ExpenseRegisterCrud::form( $expense );
    }
}
