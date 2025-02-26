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


            .summary {
                width: 100%;
                display: inline-block;
            }

            .summary table {
                float: right;
                width: 250px;
                padding-top: 5px;
                padding-bottom: 5px;
                background-color: #E9EFFC;
                white-space: nowrap;
            }
        </style>
    </head>

    <body>
        <div class="page">
            <div class="page-content">
                <!-- PDF Content -->
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
