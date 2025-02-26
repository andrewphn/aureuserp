@component('support::layouts.pdf')
    @foreach ($records as $record)
        <div style="margin-bottom: 20px">
            <!-- Information -->
            <table style="margin-bottom: 40px">
                <tbody>
                    <tr>
                        <td style="width: 50%; padding: 2px 18px;border:none;font-size: 28px;">
                        </td>

                        <td style="width: 50%; padding: 2px 18px;border:none;">
                            <div>
                                {{ $record->partner->name }}
                            </div>

                            @if ($record->partner->addresses->count())
                                <div>
                                    {{ $record->partner->addresses->first()->street1 }}
                                </div>

                                <div>
                                    {{ $record->partner->addresses->first()->street2 }}
                                </div>
                                
                                <div>
                                    {{ $record->partner->addresses->first()->city.' '.$record->partner->addresses->first()->zip }}
                                </div>

                                <div>
                                    {{ $record->partner->addresses->first()->state?->name }}
                                </div>

                                <div>
                                    {{ $record->partner->addresses->first()->country?->name }}
                                </div>

                                </br>

                                <div>
                                    {{ $record->partner->addresses->first()->phone }}
                                </div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Information -->
            <table style="margin-bottom: 40px">
                <tbody>
                    <tr>
                        <td style="width: 50%; padding: 2px 18px;border:none;font-size: 28px;">
                            <b>
                                Request for Quotation #{{ $record->name }}
                            </b>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Information -->
            <table style="margin-bottom: 40px">
                <tbody>
                    <tr>
                        @if ($record->user_id)
                            <td style="padding: 2px 18px;border:none;font-size: 16px;">
                                <b>
                                    Buyer:
                                </b>

                                <div>
                                    {{ $record->user->name }}
                                </div>
                            </td>
                        @endif

                        @if ($record->partner_reference)
                            <td style="padding: 2px 18px;border:none;font-size: 16px;">
                                <b>
                                    Your Order Reference:
                                </b>

                                <div>
                                    {{ $record->partner_reference }}
                                </div>
                            </td>
                        @endif

                        @if ($record->ordered_at)
                            <td style="padding: 2px 18px;border:none;font-size: 16px;">
                                <b>
                                    Order Deadline:
                                </b>

                                <div>
                                    {{ $record->ordered_at }}
                                </div>
                            </td>
                        @endif

                        @if ($record->planned_at)
                            <td style="padding: 2px 18px;border:none;font-size: 16px;">
                                <b>
                                    Expected Arrival:
                                </b>

                                <div>
                                    {{ $record->planned_at }}
                                </div>
                            </td>
                        @endif
                    </tr>
                </tbody>
            </table>

            <!-- Items -->
            @if (! $record->lines->isEmpty())
                <div class="items">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    Description
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Unit Price
                                </th>

                                <th>
                                    Discount
                                </th>

                                <th>
                                    Taxes
                                </th>

                                <th>
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($record->lines as $item)
                                <tr>
                                    <td>
                                        {{ $item->name }}
                                    </td>

                                    <td>
                                        {{ $item->product_qty.' '.$item->product->uom->name }}
                                    </td>

                                    <td>
                                        {{ $item->price_unit }}
                                    </td>

                                    <td>
                                        {{ round($item->discount, 2) }}%
                                    </td>

                                    <td>
                                        {{ $item->taxes->pluck('name')->implode(', ') }}
                                    </td>

                                    <td>
                                        {{ $item->price_subtotal }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Summary Table -->
                    <div class="summary">
                        <table class="{{ app()->getLocale   () }}">
                            <tbody>
                                <tr>
                                    <td>Untaxed Amount</td>
                                    <td>-</td>
                                    <td>{{ $record->untaxed_amount }}</td>
                                </tr>
            
                                <tr>
                                    <td>Tax</td>
                                    <td>-</td>
                                    <td>{{ $record->tax_amount }}</td>
                                </tr>
            
                                <tr>
                                    <td><strong>Total</strong></td>
                                    <td><strong>-</strong></td>
                                    <td><strong>{{ $record->total_amount }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Information -->
            @if ($record->payment_term_id)
                <table style="margin-bottom: 40px">
                    <tbody>
                        <tr>
                            <td style="width: 50%; padding: 2px 18px;border:none;">
                                <b>
                                    Payment Terms:
                                </b>

                                {{ $record->paymentTerm->name }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
@endcomponent