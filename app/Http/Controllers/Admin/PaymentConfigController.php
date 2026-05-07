<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentConfigController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('sort_order')->get();
        return view('admin.payment.index', compact('methods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gateway' => 'required|string',
            'account_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'instructions' => 'nullable|string',
            'api_url' => 'nullable|string|url',
            'api_key' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        PaymentMethod::create($request->only([
            'name', 'gateway', 'account_name', 'account_number',
            'bank_name', 'instructions', 'api_url', 'api_key', 'sort_order',
        ]));

        return redirect()->route('admin.payment.index')->with('success', 'Thêm phương thức thanh toán thành công');
    }

    public function update(Request $request, PaymentMethod $method)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'instructions' => 'nullable|string',
            'api_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name', 'account_name', 'account_number',
            'bank_name', 'instructions', 'api_url', 'api_key', 'sort_order',
        ]);

        // Handle is_active checkbox
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        // Don't overwrite api_key if left blank (keep existing)
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $method->update($data);

        return redirect()->route('admin.payment.index')->with('success', 'Cập nhật thành công');
    }

    public function destroy(PaymentMethod $method)
    {
        $method->delete();
        return redirect()->route('admin.payment.index')->with('success', 'Xóa thành công');
    }
}
