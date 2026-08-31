<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\InspectionDetail;
use App\Models\Style;
use Illuminate\Http\Request;

class StyleController extends Controller
{
    public function index()
    {
        $styles = Style::with('buyer')->orderBy('style_number')->paginate(20);
        return view('admin.styles.index', compact('styles'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Style::class);
        $data = $request->validate([
            'style_number' => 'required|string|max:50|unique:styles,style_number',
            'buyer_id' => 'required|exists:buyers,id',
            'order_quantity' => 'required|numeric|min:0',
            'target_date' => 'required|date',
            'status' => 'nullable|in:planning,in_progress,completed,on_hold',
        ]);
        $data['status'] = $data['status'] ?? 'planning';
        Style::create($data);
        return redirect()->route('admin.styles.index')->with('success', 'Style added.');
    }

    public function update(Request $request, Style $style)
    {
        $this->authorize('update', $style);
        $data = $request->validate([
            'style_number' => 'required|string|max:50|unique:styles,style_number,' . $style->id,
            'buyer_id' => 'required|exists:buyers,id',
            'order_quantity' => 'required|numeric|min:0',
            'target_date' => 'required|date',
            'status' => 'nullable|in:planning,in_progress,completed,on_hold',
        ]);
        $style->update($data);
        return redirect()->route('admin.styles.index')->with('success', 'Style updated.');
    }

    public function destroy(Style $style)
    {
        $this->authorize('delete', $style);
        $style->delete();
        return redirect()->route('admin.styles.index')->with('success', 'Style deleted.');
    }
}
