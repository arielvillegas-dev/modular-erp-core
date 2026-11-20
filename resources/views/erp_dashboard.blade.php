<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modular ERP Core</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 24px 16px; }
        .wrap { max-width: 1050px; margin: 0 auto; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 18px; margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .num { font-size: 1.8rem; font-weight: 700; color: #38bdf8; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #334155; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Modular ERP Core (Multi-Branch Inventory, Sales & AR)</h1>
        <p>Independent Laravel 13 ERP architecture sandbox featuring multi-branch stock transfers, unit conversions, Kardex audit trails, multi-tier pricing, credit receivables, and branch-scoped RBAC.</p>
    </div>
    <div class="grid">
        <div class="card"><div>Branches</div><div class="num">{{ $branches->count() }}</div></div>
        <div class="card"><div>Invoices</div><div class="num">{{ $invoices->count() }}</div></div>
        <div class="card"><div>Open Receivables</div><div class="num">{{ $receivablesCount }}</div></div>
    </div>
    <div class="card">
        <h2>Branch Stock Ledger</h2>
        <table>
            <thead><tr><th>Branch</th><th>SKU</th><th>Product</th><th>Base Qty</th></tr></thead>
            <tbody>
                @forelse ($stocks as $st)
                    <tr>
                        <td>{{ $st->branch?->code }}</td>
                        <td>{{ $st->product?->sku }}</td>
                        <td>{{ $st->product?->name }}</td>
                        <td>{{ $st->quantity_base }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No inventory records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
