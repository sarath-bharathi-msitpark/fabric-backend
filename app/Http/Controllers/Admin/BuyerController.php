<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    public function index()
    {
        $buyers = Buyer::orderBy('buyer_name')->paginate(20);
        return view('admin.buyers.index', compact('buyers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Buyer::class);
        $data = $request->validate([
            'buyer_name' => 'required|string|max:100|unique:buyers,buyer_name',
            'contact_person' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
        ]);
        $data['is_active'] = true;
        Buyer::create($data);
        return redirect()->route('admin.buyers.index')->with('success', 'Buyer added.');
    }

    public function update(Request $request, Buyer $buyer)
    {
        $this->authorize('update', $buyer);
        $data = $request->validate([
            'buyer_name' => 'required|string|max:100|unique:buyers,buyer_name,' . $buyer->id,
            'contact_person' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
        ]);
        $buyer->update($data);
        return redirect()->route('admin.buyers.index')->with('success', 'Buyer updated.');
    }

    public function toggleActive(Request $request, Buyer $buyer)
    {
        $this->authorize('update', $buyer);
        $buyer->update(['is_active' => !$buyer->is_active]);
        return response()->json(['success' => true, 'is_active' => $buyer->is_active]);
    }

    public function destroy(Buyer $buyer)
    {
        $this->authorize('delete', $buyer);
        $buyer->delete();
        return redirect()->route('admin.buyers.index')->with('success', 'Buyer deleted.');
    }
}
