<?php
namespace App\Http\Controllers\Dashboard;
use App\Crud\ExpenseRegisterCrud;
use App\Http\Controllers\DashboardController;
use App\Models\ExpenseRegister;
use Carbon\Carbon;
use Illuminate\Http\Request;
class ExpenseRegisterController extends DashboardController
{
    public function listExpenses() { return ExpenseRegisterCrud::table(); }
    public function createExpense() { return ExpenseRegisterCrud::form(); }
    public function editExpense( ExpenseRegister $expense ) { return ExpenseRegisterCrud::form( $expense ); }
    public function summary( Request $request )
    {
        ns()->restrict( 'nexopos.read.expense-register' );

        $year = (int) $request->query( 'year', ns()->date->year );
        $month = (int) $request->query( 'month', ns()->date->month );
        abort_if( $year < 2000 || $year > 2100 || $month < 1 || $month > 12, 422, __( 'Invalid reporting period.' ) );
        $period = ExpenseRegister::query()->whereYear( 'expense_date', $year )->whereMonth( 'expense_date', $month );
        $sum = fn ( $query ) => (float) ( clone $query )->sum( 'amount' );
        $summary = [
            'paid_total' => $sum( ( clone $period )->where( 'status', 'paid' ) ),
            'pending_total' => $sum( ( clone $period )->where( 'status', 'pending' ) ),
            'operating_total' => $sum( ( clone $period )->where( 'status', 'paid' )->where( 'classification', 'operation' ) ),
            'inventory_total' => $sum( ( clone $period )->where( 'status', 'paid' )->where( 'classification', 'inventory' ) ),
            'financing_total' => $sum( ( clone $period )->where( 'status', 'paid' )->where( 'classification', 'financing' ) ),
            'movements' => ( clone $period )->count(),
        ];
        $categories = ( clone $period )->select( 'category' )
            ->selectRaw( "SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_total" )
            ->selectRaw( "SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_total" )
            ->selectRaw( "CASE WHEN SUM(amount) > 0 THEN SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) * 100.0 / SUM(amount) ELSE 0 END as paid_percentage" )
            ->groupBy( 'category' )->orderBy( 'category' )->get();
        $yearExpenses = ExpenseRegister::query()->whereYear( 'expense_date', $year )->get();
        $expensesByMonth = $yearExpenses->groupBy( fn ( $expense ) => $expense->expense_date->month );
        $monthly = [];

        for ( $index = 1; $index <= 12; $index++ ) {
            $date = Carbon::create( $year, $index, 1 );
            $entries = $expensesByMonth->get( $index, collect() );
            $paidEntries = $entries->where( 'status', 'paid' );

            $monthly[] = [
                'label' => $date->translatedFormat( 'F' ),
                'paid' => (float) $paidEntries->sum( 'amount' ),
                'operating' => (float) $paidEntries->where( 'classification', 'operation' )->sum( 'amount' ),
                'inventory' => (float) $paidEntries->where( 'classification', 'inventory' )->sum( 'amount' ),
                'pending' => (float) $entries->where( 'status', 'pending' )->sum( 'amount' ),
            ];
        }
        return view( 'pages.dashboard.expenses.summary', compact( 'year', 'month', 'summary', 'categories', 'monthly' ) );
    }
}
