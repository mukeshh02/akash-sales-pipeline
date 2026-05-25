<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Akash Sales Pipeline</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: #f5f5f4;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / .1);
            max-width: 480px;
            width: 100%;
        }
        h1 { font-size: 1.5rem; font-weight: 700; color: #1c1917; }
        p { margin-top: 0.75rem; font-size: 0.875rem; color: #78716c; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('akashsalespipeline::akashsalespipeline.module_loaded') }}</h1>
        <p>Module: AkashSalesPipeline &nbsp;|&nbsp; Version: 1.0.0</p>
    </div>
</body>
</html>
