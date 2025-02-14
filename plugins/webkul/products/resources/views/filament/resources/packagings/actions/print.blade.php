<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            margin: 0;
            padding: 15px;
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            table-layout: fixed;
        }
        td {
            width: 33%; /* Fixed width regardless of content */
            vertical-align: top;
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
            overflow: hidden;
        }
        /* Empty cell styling */
        td.empty {
            border: none;
            background: transparent;
        }
        .record-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
            text-align: center;
        }
        .barcode-container {
            margin-top: 5px;
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
    <table>
        @foreach ($records->chunk(3) as $chunk)
            <tr>
                @foreach ($chunk as $record)
                    <td style="text-align: center;">
                        <div class="record-name">{{ $record->name }}</div>

                        <div class="record-name">{{ $record->product->name }}</div>
                        
                        <div class="record-name">Qty: {{ $record->qty.' '.$record->product->uom->name }}</div>
                        
                        @if ($record->barcode)
                            <div class="barcode-container">
                                <div class="barcode">
                                    {!! DNS1D::getBarcodeHTML($record->barcode, 'C128', 1, 30) !!}
                                </div>

                                <div class="name">{{ $record->barcode }}</div>
                            </div>
                        @endif
                    </td>
                @endforeach
                
                @for ($i = $chunk->count(); $i < 3; $i++)
                    <td class="empty"></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>