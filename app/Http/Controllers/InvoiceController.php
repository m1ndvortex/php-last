<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Invoice index - to be implemented']);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Invoice store - to be implemented']);
    }

    public function show($id): JsonResponse
    {
        return response()->json(['message' => 'Invoice show - to be implemented']);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json(['message' => 'Invoice update - to be implemented']);
    }

    public function destroy($id): JsonResponse
    {
        return response()->json(['message' => 'Invoice destroy - to be implemented']);
    }
}