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
                        <table style="margin-bottom: 40px">
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

                                <tr>
                                    <td style="width: 50%; padding: 2px 18px;border:none;">
                                        <b>
                                            Status:
                                        </b>

                                        <div>
                                            {{ $record->state->name }}
                                        </div>
                                    </td>

                                    <td style="width: 50%; padding: 2px 18px;border:none;">
                                        <b>
                                            Scheduled At:
                                        </b>

                                        <div>
                                            {{ $record->scheduled_at }}
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Items -->
                        @if (! $record->moveLines->isEmpty())
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

                                            <th>
                                                To
                                            </th>

                                            <th>
                                                Lot/Serial Number
                                            </th>

                                            <th>
                                                Product Barcode
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($record->moveLines as $item)
                                            <tr>
                                                <td>
                                                    {{ $item->product->name }}
                                                </td>

                                                <td>
                                                    {{ $item->qty.' '.$item->uom->name }}
                                                </td>

                                                <td>
                                                    {{ $item->destinationLocation->full_name }}

                                                    @if ($item->result_package_id)
                                                        - {{ $item->resultPackage->name }}
                                                    @endif
                                                </td>

                                                @if (app(\Webkul\Inventory\Settings\TraceabilitySettings::class)->enable_lots_serial_numbers)
                                                    <td>
                                                        @if ($item->lot)
                                                            <div class="barcode-container">
                                                                <div class="barcode">
                                                                    {!! DNS1D::getBarcodeHTML($item->lot->name, 'C128', 1.5, 33) !!}
                                                                </div>

                                                                <div class="name">{{ $item->lot->name }}</div>
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endif

                                                <td>
                                                    @if ($item->product->barcode)
                                                        <div class="barcode-container">
                                                            <div class="barcode">
                                                                {!! DNS1D::getBarcodeHTML($item->product->barcode, 'C128', 1.5, 33) !!}
                                                            </div>

                                                            <div class="name">{{ $item->product->barcode }}</div>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
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
