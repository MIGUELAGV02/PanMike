<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Muestra la lista de productos del inventario de MikePan.
     */
    public function index(Request $request)
    {
        $products = Product::latest()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        }

        return view('inventory.index', compact('products'));
    }

    /**
     * Almacena un nuevo producto en el inventario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio' => 'required|numeric|min:0',
            'cantidad_stock' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto registrado correctamente en el inventario.',
                'data' => $product,
            ], 201);
        }

        return redirect()->route('inventory.index')
            ->with('status', '¡Producto "'.$product->nombre.'" registrado exitosamente en el inventario de MikePan!');
    }
}
