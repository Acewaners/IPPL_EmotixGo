<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20', // Tambahkan ini
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 1. Simpan ke Database
        $contact = ContactMessage::create($request->all());

        // 2. Kirim Email (Opsional - Uncomment jika SMTP sudah disetting)
        // Mail::to('admin@emotix.com')->send(new NewContactEmail($contact));

        return response()->json([
            'message' => 'Pesan Anda telah terkirim. Kami akan menghubungi Anda segera.',
            'data' => $contact
        ], 201);
    }
}