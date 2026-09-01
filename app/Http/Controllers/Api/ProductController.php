<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

  public function store(Request $request)
{
    $validated = $request->validate([
        'name'        => 'required|string|max:255',
        'category'    => 'required|string', // Menyimpan 'makanan' atau 'minuman'
        'price'       => 'required|numeric',
        'description' => 'nullable|string', // Dibuat nullable agar deskripsi opsional
        'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);
    
    $validated['description'] = $request->input('description', '');

    if ($request->hasFile('image')) {
    $path = $request->file('image')->store('products', 'public');
    $validated['image'] = asset('storage/' . $path);
} else {
    // Mengarah ke URL server frontend tempat gambar disajikan
    $validated['image'] = 'http://localhost:5173/images/gambardefault.png';
}

    $product = Product::create($validated);
    return response()->json($product, 201);
}

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
        ];

        // Jika ada gambar baru yang diunggah
        if ($request->hasFile('image')) {
            // Hapus gambar lama dari storage jika ada
            $oldPath = str_replace(asset('storage/'), '', $product->image);
            Storage::disk('public')->delete($oldPath);

            // Simpan gambar baru
            $imagePath = $request->file('image')->store('products', 'public');
            $data['image'] = asset('storage/' . $imagePath);
        }

        $product->update($data);

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // Hapus file gambar dari storage
        $oldPath = str_replace(asset('storage/'), '', $product->image);
        Storage::disk('public')->delete($oldPath);

        $product->delete();

        return response()->json(['message' => 'Produk dihapus']);
    }
}