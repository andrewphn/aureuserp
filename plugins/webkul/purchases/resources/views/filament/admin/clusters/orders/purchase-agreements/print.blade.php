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
                                @if ($record->type == 'blanket_order')
                                    Blanket Order
                                @else
                                    Purchase Template
                                @endif
                                {{ $record->name }}
                            </b>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Information -->
            <table style="margin-bottom: 40px">
                <tbody>
                    <tr>
                        @if ($record->ends_at)
                            <td style="padding: 2px 18px;border:none;font-size: 16px;">
                                <b>
                                    Agreement Validity:
                                </b>

                                <div>
                                    {{ $record->ends_at }}
                                </div>
                            </td>
                        @endif

                        @if ($record->user_id)
                            <td style="padding: 2px 18px;border:none;font-size: 16px;">
                                <b>
                                    Contact:
                                </b>

                                <div>
                                    {{ $record->user->name }}
                                </div>
                            </td>
                        @endif

                        @if ($record->reference)
                            <td style="padding: 2px 18px;border:none;font-size: 16px;">
                                <b>
                                    Reference:
                                </b>

                                <div>
                                    {{ $record->reference }}
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
                                    Product
                                </th>

                                <th>
                                    Quantity
                                </th>

                                @if (app(\Webkul\Purchase\Settings\ProductSettings::class)->enable_uom)
                                    <th>
                                        Unit
                                    </th>
                                @endif

                                <th>
                                    Unit Price
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($record->lines as $item)
                                <tr>
                                    <td>
                                        {{ $item->product->name }}
                                    </td>

                                    <td>
                                        {{ $item->qty.' '.$item->product->uom->name }}
                                    </td>

                                    @if (app(\Webkul\Purchase\Settings\ProductSettings::class)->enable_uom)
                                        <td>
                                            {{ $item->uom?->name }}
                                        </td>
                                    @endif

                                    <td>
                                        {{ $item->price_unit }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
@endcomponent