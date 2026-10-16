<?php

namespace App\Http\Controllers\Admin\Cutting;

use App\Http\Controllers\Controller;
use App\Models\CuttingBundle;
use Illuminate\Http\Request;

class CuttingBundleController extends Controller
{
    public function print(Request $request, CuttingBundle $bundle)
    {
        $this->authorize('view', $bundle->audit);
        $bundle->load('audit.cuttingTable', 'audit.buyer');

        return view('admin.cutting.bundles.print', compact('bundle'));
    }
}
