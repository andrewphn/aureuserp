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
                                    Expected Date
                                </th>

                                <th>
                                    Qty
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
                                        {{ $item->planned_at }}
                                    </td>

                                    <td>
                                        {{ $item->product_qty.' '.$item->uom->name }}
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