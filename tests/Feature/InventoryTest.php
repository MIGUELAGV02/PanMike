<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valida que la pantalla de inventario de MikePan responde con 200 y carga la vista correspondiente.
     */
    public function test_inventory_page_can_be_rendered(): void
    {
        $response = $this->get('/inventory');

        $response->assertStatus(200);
        $response->assertSeeText('MikePan');
        $response->assertSeeText('Registrar Producto / Pan');
    }

    /**
     * Valida que un nuevo pan/producto puede ser registrado exitosamente mediante formulario web.
     */
    public function test_can_create_product_via_web_form(): void
    {
        $productData = [
            'nombre' => 'Concha de Chocolate',
            'descripcion' => 'Pan dulce tradicional con cobertura crujiente de chocolate',
            'precio' => 15.50,
            'cantidad_stock' => 40,
        ];

        $response = $this->post('/inventory', $productData);

        $response->assertRedirect('/inventory');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('products', [
            'nombre' => 'Concha de Chocolate',
            'precio' => 15.50,
            'cantidad_stock' => 40,
        ]);
    }

    /**
     * Valida que el endpoint API JSON devuelve la lista de productos y código 200.
     */
    public function test_can_list_products_as_json(): void
    {
        Product::create([
            'nombre' => 'Baguette Rústica',
            'descripcion' => 'Pan crujiente de fermentación lenta',
            'precio' => 28.00,
            'cantidad_stock' => 15,
        ]);

        $response = $this->getJson('/inventory');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Baguette Rústica');
    }

    /**
     * Valida la creación de producto a través de petición JSON (API).
     */
    public function test_can_create_product_via_json_api(): void
    {
        $payload = [
            'nombre' => 'Cuerno de Mantequilla',
            'descripcion' => 'Croissant tradicional de mantequilla francesa',
            'precio' => 22.00,
            'cantidad_stock' => 25,
        ];

        $response = $this->postJson('/inventory', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Cuerno de Mantequilla');

        $this->assertDatabaseHas('products', [
            'nombre' => 'Cuerno de Mantequilla',
        ]);
    }

    /**
     * Valida que el formulario rechaza datos incompletos o inválidos.
     */
    public function test_product_validation_rules(): void
    {
        $response = $this->post('/inventory', [
            'nombre' => '',
            'precio' => 'no-es-precio',
            'cantidad_stock' => -5,
        ]);

        $response->assertSessionHasErrors(['nombre', 'precio', 'cantidad_stock']);
    }
}
