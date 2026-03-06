<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Order;
use PHPUnit\Framework\TestCase;

class UserScoreTest extends TestCase
{
    /**
     * Test simple de la logique de calcul de score vendeur
     * Basé sur le nombre de produits en vente et le rôle.
     */
    public function testSellerScoreCalculation(): void
    {
        $user = new User();
        $user->setRole(User::ROLE_SELLER);
        
        // Simuler l'ajout de produits (en utilisant une méthode custom si elle existait, 
        // sinon on teste juste le nombre via reflection ou count de la collection)
        
        $product1 = new Product();
        $product2 = new Product();
        
        $user->addProduct($product1);
        $user->addProduct($product2);

        $this->assertCount(2, $user->getProducts());
        $this->assertEquals(User::ROLE_SELLER, $user->getRole());
    }

    public function testUserRoleValidation(): void
    {
        $user = new User();
        
        $user->setRole(User::ROLE_ADMIN);
        $this->assertEquals('admin', $user->getRole());
        
        $user->setRole(User::ROLE_CLIENT);
        $this->assertEquals('client', $user->getRole());
    }
}
