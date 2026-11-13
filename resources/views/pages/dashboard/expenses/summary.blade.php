@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body.with-title' )
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold">{{ __( 'Expense Summary' ) }}</h2>
                <p class="text-sm opacity-70">{{ __( 'Review paid expenses, inventory purchases, financing payments and outstanding bills.' ) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a class="ns-button info" href="{{ ns()->url( '/dashboard/accounting/expenses' ) }}">{{ __( 'Expense Register' ) }}</a>
                <a class="ns-button success" href="{{ ns()->url( '/dashboard/accounting/expenses/create' ) }}">{{ __( 'Register Expense' ) }}</a>
            </div>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-lg border p-4">
            <div>
                <label class="mb-1 block text-sm">{{ __( 'Year' ) }}</label>
                <input class="ns-input" type="number" name="year" min="2000" max="2100" value="{{ $year }}">
            </div>
            <div>
                <label class="mb-1 block text-sm">{{ __( 'Month' ) }}</label>
                <select class="ns-input" name="month">
                    @foreach ( range( 1, 12 ) as $monthOption )
                        <option value="{{ $monthOption }}" @selected( $monthOption === $month )>{{ \Carbon\Carbon::create( $year, $monthOption, 1 )->translatedFormat( 'F' ) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="ns-button primary" type="submit">{{ __( 'Apply' ) }}</button>
        </form>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ( [
                [ 'Paid Outflows', $summary['paid_total'] ],
                [ 'Operating Expenses', $summary['operating_total'] ],
                [ 'Inventory Purchases', $summary['inventory_total'] ],
                [ 'Loan Repayments', $summary['financing_total'] ],
                [ 'Pending Payments', $summary['pending_total'] ],
                [ 'Recorded Movements', $summary['movements'] ],
            ] as [ $label, $value ] )
                <div class="rounded-lg border p-4">
                    <div class="text-sm opacity-70">{{ __( $label ) }}</div>
                    <div class="mt-2 text-2xl font-semibold">{{ $label === 'Recorded Movements' ? number_format( $value ) : '$' . number_format( $value, 0, ',', '.' ) }}</div>
                </div>
            @endforeach
        </div>
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <section class="rounded-lg border p-4">
                <h3 class="mb-3 font-semibold">{{ __( 'Breakdown by Category' ) }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="py-2">{{ __( 'Category' ) }}</th><th class="py-2 text-right">{{ __( 'Paid' ) }}</th><th class="py-2 text-right">{{ __( 'Pending' ) }}</th><th class="py-2 text-right">{{ __( 'Paid %' ) }}</th></tr></thead>
                        <tbody>
                            @forelse ( $categories as $category )
                                <tr class="border-b"><td class="py-2">{{ $category->category }}</td><td class="py-2 text-right">${{ number_format( $category->paid_total, 0, ',', '.' ) }}</td><td class="py-2 text-right">${{ number_format( $category->pending_total, 0, ',', '.' ) }}</td><td class="py-2 text-right">{{ number_format( $category->paid_percentage, 1 ) }}%</td></tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center opacity-60">{{ __( 'No expenses for this period.' ) }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="rounded-lg border p-4">
                <h3 class="mb-3 font-semibold">{{ __( 'Monthly Evolution' ) }} · {{ $year }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b text-left"><th class="py-2">{{ __( 'Month' ) }}</th><th class="py-2 text-right">{{ __( 'Paid' ) }}</th><th class="py-2 text-right">{{ __( 'Operating' ) }}</th><th class="py-2 text-right">{{ __( 'Inventory' ) }}</th><th class="py-2 text-right">{{ __( 'Pending' ) }}</th></tr></thead>
                        <tbody>
                            @foreach ( $monthly as $row )
                                <tr class="border-b"><td class="py-2 capitalize">{{ $row['label'] }}</td><td class="py-2 text-right">${{ number_format( $row['paid'], 0, ',', '.' ) }}</td><td class="py-2 text-right">${{ number_format( $row['operating'], 0, ',', '.' ) }}</td><td class="py-2 text-right">${{ number_format( $row['inventory'], 0, ',', '.' ) }}</td><td class="py-2 text-right">${{ number_format( $row['pending'], 0, ',', '.' ) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <p class="text-xs opacity-70">{{ __( 'Inventory purchases and loan principal repayments are shown separately from operating expenses. This register does not automatically adjust stock or replace statutory accounting.' ) }}</p>
    </div>
@endsection
