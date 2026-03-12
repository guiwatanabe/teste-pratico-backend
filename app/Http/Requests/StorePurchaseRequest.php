<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'buyer.name' => ['required', 'string', 'max:255'],
            'buyer.email' => ['required', 'email', 'max:255'],
            'card.number' => ['required', 'digits:16'],
            'card.expiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'card.cvv' => ['required', 'digits:3'],
        ];
    }

    /**
     * after hook - validate product stock availability after initial validation
     *
     * @return array<\Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $expiry = $this->input('card.expiry');
                if ($expiry && preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $matches)) {
                    $month = (int) $matches[1];
                    $year = 2000 + (int) $matches[2];
                    $expiryDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

                    if ($expiryDate->isPast()) {
                        $validator->errors()->add('card.expiry', 'The card has expired.');
                    }
                }

                foreach ($this->input('products', []) as $index => $item) {
                    $product = Product::find($item['id'] ?? null);

                    if ($product && $product->amount < ($item['quantity'] ?? 0)) {
                        $validator->errors()->add(
                            "products.{$index}.quantity",
                            "Insufficient stock for product '{$product->name}'. Available: {$product->amount}."
                        );
                    }
                }
            },
        ];
    }
}
