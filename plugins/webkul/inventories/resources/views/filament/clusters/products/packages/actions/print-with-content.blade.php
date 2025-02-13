<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <!-- meta tags -->
        <meta http-equiv="Cache-control" content="no-cache">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <!-- lang supports inclusion -->
        <style type="text/css">
            body {
                font-size: 16px;
                color: #091341;
            }

            .page-content {
                padding: 12px;
            }

            table {
                width: 100%;
                border-spacing: 1px 0;
                border-collapse: separate;
                margin-bottom: 20px;
            }
            
            table thead th {
                background-color: #E9EFFC;
                padding: 6px 18px;
                text-align: left;
            }

            table.rtl thead tr th {
                text-align: right;
            }

            table tbody td {
                padding: 9px 18px;
                border-bottom: 1px solid #E9EFFC;
                text-align: left;
                vertical-align: top;
            }

            table.rtl tbody tr td {
                text-align: right;
            }

            .barcode-container {
                display: inline-block;
            }

            .barcode > div {
                display: inline-block;
            }

            .barcode-container .name {
                font-size: 14px;
                text-align: center;
            }
        </style>
    </head>

    <body>
        <div class="page">
            <div class="page-content">
                @foreach ($records as $record)
                    <div style="margin-bottom: 20px">
                        <!-- Information -->
                        <table>
                            <tbody>
                                <tr>
                                    <td style="width: 50%; padding: 2px 18px;border:none;font-size: 28px;">
                                        <b>
                                            {{ $record->name }}
                                        </b>
                                    </td>

                                    <td style="width: 50%; padding: 2px 18px;border:none;">
                                        <div class="barcode">
                                            {!! DNS1D::getBarcodeHTML($record->name, 'C128', 2, 44) !!}
                                        </div>
                                    </td>
                                </tr>

                                @if ($record->package_type_id || $record->pack_date)
                                    <tr>
                                        @if ($record->package_type_id)
                                            <td style="width: 50%; padding: 2px 18px;border:none;">
                                                <b>
                                                    Package Type:
                                                </b>

                                                <span>
                                                    {{ $record->packageType->name }}
                                                </span>
                                            </td>
                                        @endif

                                        @if ($record->pack_date)
                                            <td style="width: 50%; padding: 2px 18px;border:none;">
                                                <b>
                                                    Pack Date:
                                                </b>

                                                <span>
                                                    {{ $record->pack_date }}
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        <!-- Items -->
                        @if (! $record->quantities->isEmpty())
                            <div class="items">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>
                                                Barcode
                                            </th>

                                            <th>
                                                Product
                                            </th>

                                            <th>
                                                Quantity
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($record->quantities as $item)
                                            <tr>
                                                <td>
                                                    @if ($item->product->barcode)
                                                        <div class="barcode-container">
                                                            <div class="barcode">
                                                                {!! DNS1D::getBarcodeHTML($item->product->barcode, 'C128', 1, 30) !!}
                                                            </div>

                                                            <div class="name">{{ $item->product->barcode }}</div>
                                                        </div>
                                                    @endif
                                                </td>

                                                <td>
                                                    {{ $item->product->name }}
                                                </td>

                                                <td>
                                                    {{ $item->quantity.' '.$item->product->uom->name }}
                                                </td>
                                            </tr>

                                            @if (
                                                app(\Webkul\Inventory\Settings\TraceabilitySettings::class)->enable_lots_serial_numbers
                                                && $item->lot
                                            )
                                                <tr>
                                                    <td colspan="3">
                                                        <div style="margin-bottom: 5px">Qty: {{ $item->quantity.' '.$item->product->uom->name }}</div>

                                                        <div class="barcode-container">
                                                            <div class="barcode">
                                                                {!! DNS1D::getBarcodeHTML($item->lot->name, 'C128', 2, 33) !!}
                                                            </div>

                                                            <div class="name">{{ $item->lot->name }}</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </body>
</html>
