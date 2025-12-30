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
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contact = ContactMessage::create($request->all());

        return response()->json([
            'message' => 'Pesan Anda telah terkirim. Kami akan menghubungi Anda segera.',
            'data' => $contact
        ], 201);
    }
}
