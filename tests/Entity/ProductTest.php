<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductPriceValidation(): void
    {
        $product = new Product();
        
        // Test prix positif
        $product->setPrice(100.50);
        $this->assertEquals(100.50, $product->getPrice());

        // Test prix à zéro (cas limite)
        $product->setPrice(0.0);
        $this->assertEquals(0.0, $product->getPrice());
    }

    public function testProductNameLength(): void
    {
        $product = new Product();
        $product->setName("Clavier Gaming");
        
        $this->assertEquals("Clavier Gaming", $product->getName());
        $this->assertGreaterThanOrEqual(2, strlen($product->getName()));
    }
}
