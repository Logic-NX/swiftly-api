<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function search($document_id)
    {
        // Busca el cliente que coincida con la cédula/RIF y que pertenezca a la misma empresa
        $customer = Customer::where('document_id', $document_id)
            ->where('company_id', request()->user()->company_id)
            ->first();

        if ($customer) {
            return response()->json($customer);
        }

        return response()->json(['message' => 'Cliente no encontrado'], 404);
    }
}