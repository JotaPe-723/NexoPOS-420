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
            ->groupBy( 'category' )->orderBy( 'category' )->get();
        $monthly = [];
        for ( $index = 1; $index <= 12; $index++ ) {
            $date = Carbon::create( $year, $index, 1 );
            $monthQuery = ExpenseRegister::query()->whereYear( 'expense_date', $year )->whereMonth( 'expense_date', $index );
            $monthly[] = [
                'label' => $date->translatedFormat( 'F' ),
                'paid' => $sum( ( clone $monthQuery )->where( 'status', 'paid' ) ),
                'operating' => $sum( ( clone $monthQuery )->where( 'status', 'paid' )->where( 'classification', 'operation' ) ),
                'inventory' => $sum( ( clone $monthQuery )->where( 'status', 'paid' )->where( 'classification', 'inventory' ) ),
                'pending' => $sum( ( clone $monthQuery )->where( 'status', 'pending' ) ),
            ];
        }
        return view( 'pages.dashboard.expenses.summary', compact( 'year', 'month', 'summary', 'categories', 'monthly' ) );
    }
}
