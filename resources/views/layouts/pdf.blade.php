<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Document')</title>
    <style>
        /* Modern Reset & Base */
        @page { margin: 0; }
        body {
            margin: 0;
            padding: 40px;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937; /* Gray 800 */
            background-color: #ffffff;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 { margin: 0; font-weight: 700; color: #111827; }
        h1 { font-size: 24px; margin-bottom: 10px; }
        h2 { font-size: 18px; margin-bottom: 8px; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; }
        h3 { font-size: 14px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; color: #4b5563; }
        p { margin: 0 0 10px 0; }
        .text-sm { font-size: 10px; }
        .text-xs { font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-gray-500 { color: #6b7280; }
        .text-gray-400 { color: #9ca3af; }

        /* Colors */
        .text-primary { color: #4f46e5; } /* Indigo 600 */
        .bg-primary { background-color: #4f46e5; color: white; }
        .text-success { color: #059669; } /* Emerald 600 */
        .bg-success { background-color: #d1fae5; color: #065f46; }
        .text-danger { color: #dc2626; } /* Red 600 */
        .bg-danger { background-color: #fee2e2; color: #991b1b; }

        /* Layout Utilities */
        .header { margin-bottom: 30px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        
        .logo { font-size: 20px; font-weight: bold; color: #4f46e5; text-transform: uppercase; }
        .meta-table { float: right; }
        .meta-table td { padding: 2px 0 2px 15px; text-align: right; }

        .section { margin-bottom: 25px; }
        .grid-2 { display: table; width: 100%; table-layout: fixed; }
        .col { display: table-cell; vertical-align: top; padding-right: 20px; }
        
        /* Data Tables */
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { 
            background-color: #f3f4f6; 
            color: #374151; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 10px; 
            padding: 8px 10px; 
            text-align: left; 
            border-bottom: 1px solid #e5e7eb;
        }
        .table td { 
            padding: 8px 10px; 
            border-bottom: 1px solid #f3f4f6; 
            vertical-align: top;
        }
        .table tr:last-child td { border-bottom: none; }
        .table .num { text-align: right; font-feature-settings: "tnum"; }
        .table-striped tr:nth-child(even) { background-color: #f9fafb; }

        /* Summary/Totals */
        .totals { width: 40%; margin-left: auto; border-top: 2px solid #e5e7eb; padding-top: 10px; }
        .totals-row { display: table; width: 100%; margin-bottom: 5px; }
        .totals-label { display: table-cell; font-weight: 600; color: #4b5563; }
        .totals-value { display: table-cell; text-align: right; font-weight: 700; color: #111827; }
        .grand-total { font-size: 14px; color: #4f46e5; border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 8px; }

        /* Footer */
        .footer { 
            position: fixed; 
            bottom: -30px; 
            left: 0; 
            right: 0; 
            height: 30px; 
            text-align: center; 
            font-size: 9px; 
            color: #9ca3af; 
            border-top: 1px solid #f3f4f6; 
            padding-top: 10px;
        }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">{{ config('app.name', 'KingWine') }}</div>
            <div class="text-sm text-gray-500 mt-1">
                {{ config('app.address', '123 Business Road, Kigali, Rwanda') }}<br>
                {{ config('app.phone', '+250 788 000 000') }} | {{ config('app.email', 'info@kingwine.com') }}
            </div>
        </div>
        <div class="header-right">
            @yield('header-meta')
        </div>
    </div>

    @yield('content')

    <div class="footer">
        Generated on {{ now()->format('d M Y, H:i') }} &bull; Page <span class="page-number"></span>
    </div>
</body>
</html>
