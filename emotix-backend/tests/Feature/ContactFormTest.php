<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_success_with_valid_data()
    {
        // Happy Path (Skenario Sukses)
        $response = $this->postJson('/api/contact', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'subject' => 'Tanya Produk',
            'message' => 'Apakah stok ready?'
        ]);

        $response->assertStatus(201); // Created
        $this->assertDatabaseHas('contact_messages', ['email' => 'budi@example.com']);
    }

    public function test_contact_form_fails_if_email_invalid()
    {
        // Negative Path (Email format salah)
        $response = $this->postJson('/api/contact', [
            'name' => 'Budi',
            'email' => 'budi-bukan-email', // <--- Format salah
            'message' => 'Halo'
        ]);

        // Harapannya 422
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_contact_form_fails_if_message_empty()
    {
        // Negative Path (Pesan kosong)
        $response = $this->postJson('/api/contact', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'message' => '' // <--- Kosong
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }
}