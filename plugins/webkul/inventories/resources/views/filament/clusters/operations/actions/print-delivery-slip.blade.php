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
                                        <b>
                                            Shipping Date:
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
                                                Lot/Serial Number
                                            </th>

                                            <th>
                                                Quantity
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($record->moveLines as $item)
                                            <tr>
                                                <td>
                                                    {{ $item->product->name }}
                                                </td>

                                                @if (app(\Webkul\Inventory\Settings\TraceabilitySettings::class)->enable_lots_serial_numbers)
                                                    <td>
                                                        {{ $item->lot?->name }}
                                                    </td>
                                                @endif

                                                <td>
                                                    {{ $item->qty.' '.$item->product->uom->name }}
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
