<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Customer index - to be implemented']);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Customer store - to be implemented']);
    }

    public function show($id): JsonResponse
    {
        return response()->json(['message' => 'Customer show - to be implemented']);
    }

    public function update(Request $request, $id): JsonResponse
    {
        return response()->json(['message' => 'Customer update - to be implemented']);
    }

    public function destroy($id): JsonResponse
    {
        return response()->json(['message' => 'Customer destroy - to be implemented']);
    }
}