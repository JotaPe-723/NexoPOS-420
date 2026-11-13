<?php

namespace App\Crud;

use App\Classes\CrudForm;
use App\Classes\CrudTable;
use App\Classes\FormInput;
use App\Models\ExpenseRegister;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ExpenseRegisterCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'ns.expense-register';

    protected $table = 'nexopos_expense_register';

    protected $mainRoute = 'ns.expense-register';

    protected $namespace = 'ns.expense-register';

    protected $model = ExpenseRegister::class;

    protected $permissions = [
        'create' => 'nexopos.create.expense-register',
        'read' => 'nexopos.read.expense-register',
        'update' => 'nexopos.update.expense-register',
        'delete' => 'nexopos.delete.expense-register',
    ];

    public $fillable = [];

    public function getLabels()
    {
        return CrudTable::labels(
            list_title: __( 'Expense Register' ),
            list_description: __( 'Record and review the store\'s day-to-day expenses.' ),
            no_entry: __( 'No expenses have been registered.' ),
            create_new: __( 'Register Expense' ),
            create_title: __( 'Register Expense' ),
            create_description: __( 'Enter one row for each payment or outstanding bill.' ),
            edit_title: __( 'Edit Expense' ),
            edit_description: __( 'Update the expense details.' ),
            back_to_list: __( 'Return to Expenses' ),
        );
    }

    public function getForm( $entry = null )
    {
        $categories = array_map( fn ( $value ) => [ 'label' => __( $value ), 'value' => $value ], ExpenseRegister::CATEGORIES );
        $paymentMethods = array_map( fn ( $value ) => [ 'label' => __( $value ), 'value' => $value ], ExpenseRegister::PAYMENT_METHODS );

        return CrudForm::form(
            main: FormInput::text(
                label: __( 'Description' ),
                name: 'description',
                value: $entry->description ?? '',
                validation: 'required',
                description: __( 'What was paid or what bill is pending?' ),
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'details',
                    label: __( 'Expense Details' ),
                    fields: CrudForm::fields(
                        FormInput::date(
                            label: __( 'Date' ),
                            name: 'expense_date',
                            value: $entry?->expense_date?->format( 'Y-m-d' ) ?? ns()->date->format( 'Y-m-d' ),
                            validation: 'required',
                        ),
                        FormInput::select(
                            label: __( 'Category' ),
                            name: 'category',
                            options: $categories,
                            value: $entry->category ?? '',
                            validation: 'required',
                        ),
                        FormInput::number(
                            label: __( 'Amount (COP)' ),
                            name: 'amount',
                            value: $entry->amount ?? '',
                            validation: 'required|min:0.01',
                            description: __( 'Enter the amount as a number, without currency symbols.' ),
                        ),
                        FormInput::select(
                            label: __( 'Payment Method' ),
                            name: 'payment_method',
                            options: $paymentMethods,
                            value: $entry->payment_method ?? 'Efectivo',
                            validation: 'required',
                        ),
                        FormInput::text(
                            label: __( 'Responsible' ),
                            name: 'responsible',
                            value: $entry->responsible ?? '',
                            description: __( 'Person responsible for this expense.' ),
                        ),
                        FormInput::select(
                            label: __( 'Expense Type' ),
                            name: 'expense_type',
                            options: [
                                [ 'label' => __( 'Fixed' ), 'value' => 'fixed' ],
                                [ 'label' => __( 'Variable' ), 'value' => 'variable' ],
                            ],
                            value: $entry->expense_type ?? 'variable',
                            validation: 'required',
                        ),
                        FormInput::select(
                            label: __( 'Status' ),
                            name: 'status',
                            options: [
                                [ 'label' => __( 'Paid' ), 'value' => 'paid' ],
                                [ 'label' => __( 'Pending' ), 'value' => 'pending' ],
                            ],
                            value: $entry->status ?? 'paid',
                            validation: 'required',
                        ),
                        FormInput::text(
                            label: __( 'Receipt / Reference' ),
                            name: 'receipt_reference',
                            value: $entry->receipt_reference ?? '',
                            description: __( 'Invoice number, receipt reference, or supporting document name.' ),
                        ),
                        FormInput::textarea(
                            label: __( 'Notes' ),
                            name: 'notes',
                            value: $entry->notes ?? '',
                        ),
                    )
                )
            )
        );
    }

    protected function validateExpense( array $inputs ): void
    {
        Validator::make( $inputs, [
            'expense_date' => [ 'required', 'date' ],
            'description' => [ 'required', 'string', 'max:255' ],
            'category' => [ 'required', Rule::in( ExpenseRegister::CATEGORIES ) ],
            'amount' => [ 'required', 'numeric', 'gt:0' ],
            'payment_method' => [ 'required', Rule::in( ExpenseRegister::PAYMENT_METHODS ) ],
            'responsible' => [ 'nullable', 'string', 'max:255' ],
            'expense_type' => [ 'required', Rule::in( array_keys( ExpenseRegister::TYPES ) ) ],
            'status' => [ 'required', Rule::in( array_keys( ExpenseRegister::STATUSES ) ) ],
            'receipt_reference' => [ 'nullable', 'string', 'max:255' ],
            'notes' => [ 'nullable', 'string' ],
        ] )->validate();
    }

    public function beforePost( $inputs )
    {
        $this->validateExpense( $inputs );
    }

    public function beforePut( $inputs, ExpenseRegister $entry )
    {
        $this->validateExpense( $inputs );
    }

    public function filterPostInputs( $inputs )
    {
        $inputs['classification'] = ExpenseRegister::classify( $inputs['category'] ?? null );

        return $inputs;
    }

    public function filterPutInputs( $inputs, ExpenseRegister $entry )
    {
        if ( array_key_exists( 'category', $inputs ) ) {
            $inputs['classification'] = ExpenseRegister::classify( $inputs['category'] );
        }

        return $inputs;
    }

    public function hook( $query ): void
    {
        $query->orderBy( 'expense_date', 'desc' )->orderBy( 'id', 'desc' );
    }

    public function get( $param )
    {
        return match ( $param ) {
            'model' => $this->model,
            default => null,
        };
    }

    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column( __( 'Date' ), 'expense_date' ),
            CrudTable::column( __( 'Category' ), 'category' ),
            CrudTable::column( __( 'Description' ), 'description' ),
            CrudTable::column( __( 'Amount' ), 'amount' ),
            CrudTable::column( __( 'Payment Method' ), 'payment_method' ),
            CrudTable::column( __( 'Responsible' ), 'responsible' ),
            CrudTable::column( __( 'Type' ), 'expense_type' ),
            CrudTable::column( __( 'Status' ), 'status' ),
            CrudTable::column( __( 'Classification' ), 'classification' ),
        );
    }

    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->amount = ns()->currency->define( $entry->amount )->format();
        $entry->expense_type = $entry->expense_type === 'fixed' ? __( 'Fixed' ) : __( 'Variable' );
        $entry->status = $entry->status === 'paid' ? __( 'Paid' ) : __( 'Pending' );
        $entry->classification = match ( $entry->classification ) {
            'inventory' => __( 'Inventory' ),
            'financing' => __( 'Financing' ),
            default => __( 'Operation' ),
        };

        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/accounting/expenses/edit/' . $entry->id ),
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.expense-register/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this expense?' ),
            ],
        );

        return $entry;
    }

    public function bulkAction( Request $request )
    {
        if ( $request->input( 'action' ) === 'delete_selected' ) {
            ns()->restrict( $this->permissions['delete'] );

            $status = [ 'success' => 0, 'error' => 0 ];

            foreach ( $request->input( 'entries', [] ) as $id ) {
                $entry = ExpenseRegister::find( $id );

                if ( $entry instanceof ExpenseRegister ) {
                    $entry->delete();
                    $status['success']++;
                } else {
                    $status['error']++;
                }
            }

            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url( 'dashboard/accounting/expenses' ),
            create: ns()->url( 'dashboard/accounting/expenses/create' ),
            edit: ns()->url( 'dashboard/accounting/expenses/edit/{id}' ),
            post: ns()->url( 'api/crud/ns.expense-register' ),
            put: ns()->url( 'api/crud/ns.expense-register/{id}' ),
        );
    }

    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label' => __( 'Delete Selected Expenses' ),
                'identifier' => 'delete_selected',
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [ 'namespace' => $this->namespace ] ),
            ],
        ] );
    }

    public function getExports()
    {
        return [];
    }
}
