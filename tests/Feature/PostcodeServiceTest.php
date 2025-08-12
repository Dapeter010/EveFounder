<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostcodeServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_it_fetches_address_from_postcode(){
        $response = $this->post('/api/address/search', ['postcode'=>'WV10 0AP']);
        $response->assertStatus(200);
        $response->assertJsonStructure(['address']);
    }
}
