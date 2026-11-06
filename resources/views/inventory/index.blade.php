<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikePan - Inventario de Panadería</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Figtree', sans-serif;
            background: #faf5ef;
            color: #2d261e;
            padding: 30px 20px;
        }
        .container {
            max-width: 1050px;
            margin: 0 auto;
        }
        header {
            background: #ffffff;
            padding: 24px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 6px solid #d97706;
        }
        .header-title h1 {
            font-size: 26px;
            color: #78350f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-title p {
            color: #78716c;
            font-size: 14px;
            margin-top: 4px;
        }
        .badge-env {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .alert {
            background: #dcfce7;
            color: #166534;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fecaca;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
        }
        @media (max-width: 820px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .card h2 {
            font-size: 18px;
            color: #451a03;
            margin-bottom: 18px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #44403c;
            margin-bottom: 6px;
        }
        input, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d6d3d1;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15);
        }
        button.btn-submit {
            background: #d97706;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            transition: background 0.2s;
        }
        button.btn-submit:hover {
            background: #b45309;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px 14px;
            font-size: 14px;
        }
        th {
            background: #fdfaf7;
            color: #78716c;
            font-weight: 600;
            border-bottom: 2px solid #f3f4f6;
        }
        tr:not(:last-child) td {
            border-bottom: 1px solid #f5f5f4;
        }
        .price {
            font-weight: 600;
            color: #15803d;
        }
        .stock-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .stock-high {
            background: #dcfce7;
            color: #166534;
        }
        .stock-low {
            background: #fee2e2;
            color: #991b1b;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #a8a29e;
            font-size: 14px;
        }
        footer {
            margin-top: 30px;
            text-align: center;
            color: #a8a29e;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-title">
                <h1>🥖 Panadería "MikePan"</h1>
                <p>Módulo de Control de Inventario y Producción Diaria</p>
            </div>
            <div>
                <span class="badge-env">Entorno: {{ config('app.env') }}</span>
            </div>
        </header>

        @if (session('status'))
            <div class="alert">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul style="margin-left: 20px; margin-top: 6px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid">
            <!-- Formulario de Registro -->
            <div class="card">
                <h2>Registrar Producto / Pan</h2>
                <form action="{{ route('inventory.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="nombre">Nombre del Pan / Producto *</label>
                        <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Ej. Concha de Vainilla" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Ingredientes, horneado, etc.">{{ old('descripcion') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="precio">Precio Unitario ($) *</label>
                        <input type="number" step="0.50" min="0" id="precio" name="precio" value="{{ old('precio') }}" placeholder="Ej. 12.50" required>
                    </div>

                    <div class="form-group">
                        <label for="cantidad_stock">Cantidad en Stock *</label>
                        <input type="number" min="0" id="cantidad_stock" name="cantidad_stock" value="{{ old('cantidad_stock', 0) }}" placeholder="Ej. 50" required>
                    </div>

                    <button type="submit" class="btn-submit">Guardar en Inventario</button>
                </form>
            </div>

            <!-- Tabla de Inventario -->
            <div class="card">
                <h2>Catálogo de Existencias ({{ $products->count() }})</h2>
                @if ($products->isEmpty())
                    <div class="empty-state">
                        <p>No hay productos registrados en el inventario de MikePan actualmente.</p>
                        <p style="margin-top: 8px;">Utiliza el formulario de la izquierda para registrar el primer lote horneado.</p>
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Última act.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td><strong>{{ $product->nombre }}</strong></td>
                                    <td>{{ $product->descripcion ?? 'Sin descripción' }}</td>
                                    <td class="price">${{ number_format($product->precio, 2) }}</td>
                                    <td>
                                        <span class="stock-badge {{ $product->cantidad_stock > 10 ? 'stock-high' : 'stock-low' }}">
                                            {{ $product->cantidad_stock }} pzas
                                        </span>
                                    </td>
                                    <td>{{ $product->updated_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <footer>
            Panadería MikePan &bull; Sistema de Gestión Escolar CI/CD &bull; Laravel {{ app()->version() }} &bull; SQLite Engine
        </footer>
    </div>
</body>
</html>
