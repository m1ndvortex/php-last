<?php

return [
    // General errors
    'something_went_wrong' => 'Something went wrong. Please try again.',
    'network_error' => 'Network error. Please check your connection and try again.',
    'server_error' => 'Server error. Please try again later.',
    'validation_failed' => 'The provided data is invalid.',
    'unauthorized' => 'You are not authorized to perform this action.',
    'not_found' => 'The requested resource was not found.',
    
    // Inventory errors
    'inventory' => [
        'insufficient_stock' => 'Insufficient stock for the requested items.',
        'item_not_found' => 'Inventory item not found.',
        'failed_to_create_item' => 'Failed to create inventory item. Please try again.',
        'failed_to_update_item' => 'Failed to update inventory item. Please try again.',
        'failed_to_delete_item' => 'Failed to delete inventory item. Please try again.',
        'failed_to_load_categories' => 'Failed to load categories. Please refresh the page.',
        'failed_to_load_locations' => 'Failed to load locations. Please refresh the page.',
        'failed_to_load_gold_purity_options' => 'Failed to load gold purity options. Please refresh the page.',
        'failed_to_load_form_data' => 'Failed to load form data. Please refresh the page and try again.',
        'stock_reservation_failed' => 'Failed to reserve inventory stock.',
        'stock_restoration_failed' => 'Failed to restore inventory stock.',
        'movement_creation_failed' => 'Failed to create inventory movement record.',
        'invalid_quantity' => 'Invalid quantity specified.',
        'negative_stock_not_allowed' => 'Stock cannot go below zero.',
    ],
    
    // Pricing errors
    'pricing' => [
        'calculation_failed' => 'Failed to calculate item price.',
        'invalid_gold_price' => 'Invalid gold price specified.',
        'invalid_percentage' => 'Invalid percentage value specified.',
        'missing_pricing_data' => 'Required pricing data is missing.',
        'negative_price_not_allowed' => 'Negative prices are not allowed.',
        'zero_weight_not_allowed' => 'Item weight cannot be zero for pricing calculations.',
        'invalid_formula_parameters' => 'Invalid parameters for pricing formula.',
    ],
    
    // Invoice errors
    'invoice' => [
        'creation_failed' => 'Failed to create invoice. Please try again.',
        'update_failed' => 'Failed to update invoice. Please try again.',
        'deletion_failed' => 'Failed to delete invoice. Please try again.',
        'not_found' => 'Invoice not found.',
        'already_paid' => 'Invoice is already paid and cannot be modified.',
        'invalid_status' => 'Invalid invoice status.',
        'no_items' => 'Invoice must contain at least one item.',
        'customer_required' => 'Customer is required for invoice creation.',
        'pdf_generation_failed' => 'Failed to generate PDF. Please try again.',
    ],
    
    // API errors
    'api' => [
        'endpoint_not_found' => 'API endpoint not found.',
        'method_not_allowed' => 'HTTP method not allowed for this endpoint.',
        'rate_limit_exceeded' => 'Too many requests. Please try again later.',
        'invalid_json' => 'Invalid JSON data provided.',
        'missing_required_fields' => 'Required fields are missing.',
        'invalid_data_format' => 'Invalid data format provided.',
    ],
    
    // Database errors
    'database' => [
        'connection_failed' => 'Database connection failed. Please try again later.',
        'query_failed' => 'Database query failed. Please try again.',
        'constraint_violation' => 'Database constraint violation.',
        'duplicate_entry' => 'Duplicate entry detected.',
        'foreign_key_constraint' => 'Cannot delete record due to related data.',
    ],
    
    // Authentication errors
    'auth' => [
        'invalid_credentials' => 'Invalid credentials provided.',
        'token_expired' => 'Authentication token has expired.',
        'token_invalid' => 'Invalid authentication token.',
        'session_expired' => 'Your session has expired. Please log in again.',
        'account_locked' => 'Account is locked. Please contact administrator.',
        'insufficient_permissions' => 'You do not have sufficient permissions.',
    ],
    
    // File upload errors
    'upload' => [
        'file_too_large' => 'File size exceeds the maximum allowed limit.',
        'invalid_file_type' => 'Invalid file type. Please upload a valid file.',
        'upload_failed' => 'File upload failed. Please try again.',
        'file_not_found' => 'Uploaded file not found.',
        'storage_error' => 'File storage error. Please try again.',
    ],
    
    // Report errors
    'reports' => [
        'generation_failed' => 'Failed to generate report. Please try again.',
        'no_data_available' => 'No data available for the selected criteria.',
        'invalid_date_range' => 'Invalid date range specified.',
        'export_failed' => 'Failed to export report. Please try again.',
        'invalid_format' => 'Invalid report format requested.',
    ],
];