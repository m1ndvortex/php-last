# Requirements Document

## Introduction

This specification addresses critical production issues in the jewelry platform based on observed console errors and user feedback. The main issues include: non-functional "Create Item" button, mandatory pricing fields that should be optional for dynamic gold pricing, missing invoice-inventory relationship management, non-functional reports with mock data, and the need for dynamic gold pricing calculations using the Persian jewelry industry formula: Weight × (Gold Price + Labor Cost + Profit + Tax) = Final Price.

## Requirements

### Requirement 1: Fix Inventory Item Creation Console Errors

**User Story:** As a jewelry store manager, I want to be able to create inventory items without encountering console errors like "Failed to load resource: net::ERR_FAILED" and "Network error: XMLHttpRequest", so that I can efficiently manage my inventory.

#### Acceptance Criteria

1. WHEN I click "Create Item" button THEN the system SHALL open the modal without console errors
2. WHEN the form loads THEN all API calls for categories, locations, and gold purity options SHALL succeed
3. WHEN there are network connectivity issues THEN the system SHALL display user-friendly error messages instead of console errors
4. WHEN the form is submitted THEN the system SHALL validate all fields and create the item successfully
5. WHEN the item creation is successful THEN the system SHALL close the modal and refresh the inventory list

### Requirement 2: Make Unit Price and Cost Price Optional for Dynamic Pricing

**User Story:** As a jewelry store manager, I want unit price and cost price to be optional fields when creating inventory items, so that I can add items without fixed pricing since gold prices change dynamically and I will calculate prices during invoice creation.

#### Acceptance Criteria

1. WHEN creating an inventory item THEN unit price field SHALL NOT be marked as required (remove the * indicator)
2. WHEN creating an inventory item THEN cost price field SHALL NOT be marked as required (remove the * indicator)
3. WHEN submitting the form without prices THEN the system SHALL accept the submission successfully
4. WHEN prices are left empty THEN the system SHALL store NULL values in the database
5. WHEN viewing inventory items THEN items without prices SHALL display as "Price on Request" or similar indicator

### Requirement 3: Implement Dynamic Gold Pricing System with Persian Formula

**User Story:** As a jewelry store manager, I want to calculate item prices dynamically based on current gold rates during invoice creation using the Persian jewelry formula, so that I can provide accurate pricing that reflects market conditions.

#### Acceptance Criteria

1. WHEN creating an invoice THEN the system SHALL allow me to enter the current gold price per gram
2. WHEN calculating item prices THEN the system SHALL use the Persian formula: Weight × (Gold Price per gram + Labor Cost + Profit + Tax) = Final Price
3. WHEN labor cost is specified THEN the system SHALL calculate it as a percentage of the base gold value
4. WHEN profit is specified THEN the system SHALL calculate it as a percentage of the total cost
5. WHEN tax is specified THEN the system SHALL calculate it as a percentage of the subtotal
6. WHEN the gold price changes THEN the system SHALL recalculate all affected item prices in the invoice automatically
7. WHEN saving an invoice THEN the system SHALL store the gold price, labor percentage, profit percentage, and tax percentage used for that specific invoice

### Requirement 4: Implement Invoice-Inventory Relationship Management

**User Story:** As a jewelry store manager, I want the system to automatically manage inventory levels when creating, updating, or deleting invoices, so that my inventory counts remain accurate.

#### Acceptance Criteria

1. WHEN creating an invoice THEN the system SHALL check if sufficient inventory exists for each item
2. WHEN an invoice item quantity exceeds available inventory THEN the system SHALL prevent invoice creation and show an error
3. WHEN an invoice is successfully created THEN the system SHALL reduce inventory quantities by the invoiced amounts
4. WHEN an invoice is deleted or cancelled THEN the system SHALL restore the inventory quantities
5. WHEN an invoice is updated THEN the system SHALL adjust inventory quantities based on the changes

### Requirement 5: Enable Dynamic Tax and Profit Settings in Invoices

**User Story:** As a jewelry store manager, I want to be able to modify tax and profit percentages while creating invoices, so that I can adjust pricing based on specific customer or transaction requirements while maintaining the default settings from the Settings page.

#### Acceptance Criteria

1. WHEN creating an invoice THEN the system SHALL display default tax and profit percentages from the Settings page
2. WHEN creating an invoice THEN the system SHALL allow me to modify tax and profit percentages for that specific invoice
3. WHEN I change tax or profit percentages THEN the system SHALL recalculate all item prices immediately using the Persian formula
4. WHEN saving the invoice THEN the system SHALL store the specific tax and profit percentages used for that invoice
5. WHEN the Settings page has default values THEN they SHALL be used as starting values for new invoices

### Requirement 6: Fix and Enhance Report System with Real Data

**User Story:** As a jewelry store manager, I want all four report types (Sales Reports, Inventory Reports, Financial Reports, Customer Reports) to work with actual database data and perform accurate calculations, so that I can make informed business decisions based on real information.

#### Acceptance Criteria

1. WHEN accessing the Reports tab THEN I SHALL see four working report categories: Sales, Inventory, Financial, and Customer
2. WHEN generating a Sales Report THEN the system SHALL display actual sales data from invoices with correct calculations for totals, averages, and trends
3. WHEN generating an Inventory Report THEN the system SHALL show current stock levels, values, and movement history with accurate calculations from the inventory database
4. WHEN generating a Financial Report THEN the system SHALL calculate profit margins, costs, and revenue accurately based on actual transaction data
5. WHEN generating a Customer Report THEN the system SHALL display customer purchase history, preferences, and analytics with correct calculations from real customer data
6. WHEN any report is generated THEN all mathematical calculations SHALL be verified for accuracy and match the actual database values
7. WHEN clicking "Generate Report" THEN the system SHALL process real data instead of showing mock or placeholder information

### Requirement 7: Implement Gold Price Formula Integration

**User Story:** As a jewelry store manager, I want the system to use the standard gold pricing formula for calculating final prices, so that my pricing is consistent with industry standards.

#### Acceptance Criteria

1. WHEN calculating item prices THEN the system SHALL use the formula: Weight × (Gold Price per gram + Labor Cost + Profit + Tax) = Final Price
2. WHEN labor cost is specified THEN the system SHALL calculate it as a percentage of the base gold value
3. WHEN profit is specified THEN the system SHALL calculate it as a percentage of the total cost
4. WHEN tax is specified THEN the system SHALL calculate it as a percentage of the subtotal
5. WHEN displaying price breakdowns THEN the system SHALL show each component clearly

### Requirement 8: Fix Console Errors and Network Issues

**User Story:** As a system administrator, I want all console errors and network connectivity issues to be resolved, so that the application runs smoothly without technical problems.

#### Acceptance Criteria

1. WHEN accessing any page THEN the browser console SHALL NOT display JavaScript errors
2. WHEN making API calls THEN the system SHALL handle network failures gracefully
3. WHEN resources are missing THEN the system SHALL provide fallback options or clear error messages
4. WHEN authentication fails THEN the system SHALL redirect appropriately without console errors
5. WHEN the backend is unavailable THEN the system SHALL display user-friendly offline messages