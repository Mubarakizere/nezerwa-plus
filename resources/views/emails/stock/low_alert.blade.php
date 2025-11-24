<!DOCTYPE html>
<html>
<head>
    <title>Low Stock Alert</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .header { background: #fff1f2; color: #be123c; padding: 10px; text-align: center; border-bottom: 1px solid #fecdd3; }
        .content { padding: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f9fafb; }
        .alert { color: #e11d48; font-weight: bold; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Low Stock Alert</h2>
        </div>
        <div class="content">
            <p>Hello Admin,</p>
            <p>The following products have reached low stock levels:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Current Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku ?? '-' }}</td>
                            <td class="alert">{{ $product->formatted_stock }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p>Please arrange for restocking soon.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} KingWine. All rights reserved.
        </div>
    </div>
</body>
</html>
