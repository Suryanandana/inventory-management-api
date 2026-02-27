<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => $this->whenLoaded('variant'),
            'external_id' => $this->external_id,
            'invoice_id' => $this->invoice_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
