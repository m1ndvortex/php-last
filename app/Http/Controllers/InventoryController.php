<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Inventory index - to be implemented']);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Inventory store - to be implemented']);
    }

    public function show($id): JsonResponse
    {
        return response()->json(['message' => 'Inventory show - to be implemented']);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json(['message' => 'Inventory update - to be implemented']);
    }

    public function destroy($id): JsonResponse
    {
        return response()->json(['message' => 'Inventory destroy - to be implemented']);
    }
}