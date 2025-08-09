# Implementation Plan

- [x] 1. Fix inventory item creation console errors and API endpoints





  - Fix backend API routes for categories, locations, and gold purity options
  - Update InventoryController to handle missing endpoints and proper error responses
  - Add proper error handling for network failures in frontend ItemFormModal
  - Test API endpoints to ensure they return proper data without 404 errors
  - Verify form submission works without console errors
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 2. Make unit price and cost price optional in inventory items





  - Update StoreInventoryItemRequest validation rules to make unit_price and cost_price nullable
  - Modify ItemFormModal.vue to remove required indicators (*) from price fields
  - Update database migration to allow NULL values for unit_price and cost_price columns
  - Add placeholder text "Price on Request" for empty price fields
  - Update inventory display to show appropriate text when prices are null
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 3. Create GoldPricingService with Persian jewelry formula





  - Implement GoldPricingService class with calculateItemPrice method
  - Code the Persian formula: Weight × (Gold Price + Labor Cost + Profit + Tax) = Final Price
  - Add percentage calculations for labor, profit, and tax components
  - Create getDefaultPricingSettings method to load from business configuration
  - Write unit tests to verify formula calculations are accurate
  - _Requirements: 3.2, 3.3, 3.4, 3.7_

- [x] 4. Implement dynamic gold pricing in invoice creation





  - Update InvoiceFormModal.vue to include gold pricing section
  - Add form fields for current gold price per gram and percentage settings
  - Implement real-time price recalculation when values change
  - Load default settings from business configuration
  - Display price breakdown showing each component (base, labor, profit, tax)
  - Store gold pricing parameters in invoice record
  - _Requirements: 3.1, 3.5, 3.6, 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 5. Create InventoryManagementService for stock control
  - Implement checkInventoryAvailability method to validate stock before invoice creation
  - Create reserveInventory method to reduce stock when invoice is created
  - Add restoreInventory method to return stock when invoice is cancelled
  - Implement InventoryMovement tracking for all stock changes
  - Add InsufficientInventoryException for stock validation errors
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 6. Enhance InvoiceService with inventory integration
  - Update createInvoice method to check inventory availability first
  - Integrate GoldPricingService for dynamic price calculations
  - Add inventory reservation within database transaction
  - Implement cancelInvoice method with inventory restoration
  - Store complete price breakdown in invoice items
  - Add proper error handling for inventory and pricing issues
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 3.1, 3.5, 3.6_

- [ ] 7. Build ReportService with real data calculations
  - Create generateSalesReport method using actual invoice data
  - Implement generateInventoryReport with real stock levels and values
  - Add generateFinancialReport with profit/loss calculations from real transactions
  - Create generateCustomerReport with actual purchase history and statistics
  - Calculate all metrics from database queries instead of mock data
  - Add proper date filtering and customer/category filtering
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

- [ ] 8. Update ReportsInterface.vue to use real API calls
  - Replace mock data calls with actual API requests to ReportService
  - Implement proper loading states and error handling
  - Add report filters for date ranges, customers, and categories
  - Create individual report components (SalesReport, InventoryReport, etc.)
  - Add export functionality for PDF generation
  - Test all four report types with actual database data
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.7_

- [ ] 9. Fix business settings integration for default percentages
  - Update BusinessConfiguration model to include default pricing percentages
  - Add database migration for default_labor_percentage, default_profit_percentage, default_tax_percentage
  - Update BusinessSettings.vue to include pricing configuration fields
  - Ensure settings are loaded properly in invoice creation
  - Test that default values populate correctly in invoice form
  - _Requirements: 5.1, 5.5_

- [ ] 10. Create comprehensive error handling system
  - Implement InsufficientInventoryException with proper JSON responses
  - Update global exception handler to handle inventory and pricing errors
  - Add user-friendly error messages for all failure scenarios
  - Implement proper API error responses with consistent structure
  - Add frontend error handling for all API calls
  - Test error scenarios and ensure proper user feedback
  - _Requirements: 1.3, 4.2, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7_

- [ ] 11. Add database migrations for new fields and relationships
  - Create migration to add gold pricing fields to invoices table
  - Add price breakdown fields to invoice_items table
  - Create inventory_movements table for stock tracking
  - Add default pricing percentage fields to business_configurations table
  - Update existing tables to allow NULL values for optional price fields
  - _Requirements: 2.4, 3.7, 4.4, 5.5_

- [ ] 12. Write comprehensive tests for all new functionality
  - Create unit tests for GoldPricingService formula calculations
  - Add integration tests for inventory management and stock control
  - Write feature tests for invoice creation with dynamic pricing
  - Test report generation with real data scenarios
  - Add tests for error handling and edge cases
  - Verify all console errors are resolved
  - _Requirements: All requirements validation_

- [ ] 13. Update API routes and controllers
  - Add missing routes for inventory form data (categories, locations, gold purity)
  - Update ReportController with real data endpoints
  - Fix any remaining 404 errors in API calls
  - Add proper middleware and validation to all routes
  - Test all API endpoints return correct data structures
  - _Requirements: 1.1, 1.2, 6.7, 8.1_

- [ ] 14. Final integration testing and validation
  - Test complete workflow: create item → create invoice → check inventory reduction
  - Verify all four report types work with actual data
  - Test invoice cancellation restores inventory correctly
  - Validate Persian gold pricing formula produces correct results
  - Ensure no console errors remain in browser
  - Test with realistic data volumes and scenarios
  - _Requirements: All requirements final validation_