# Implementation Plan

- [x] 1. Database Schema and Model Enhancements





  - Create database migration to add new fields to categories table (default_gold_purity, image_path, sort_order, specifications)
  - Create category_images table migration for multiple image support
  - Update Category model with new fillable fields, casts, and relationships
  - Create CategoryImage model with proper relationships and localization methods
  - Add main_category_id field to inventory_items table for dual category support
  - _Requirements: 1.1, 1.2, 2.1, 3.1, 4.1_
- [x] 2. Backend Services and Controllers Enhancement








- [x] 2. Backend Services and Controllers Enhancement

  - Create CategoryService class with hierarchy management methods
  - Create CategoryImageService for image upload, optimization, and storage
  - Create GoldPurityService for formatting and validation
  - Enhance CategoryController with new endpoints for hierarchy, images, and reordering
  - Add validation rules for gold purity and image uploads
  - _Requirements: 1.3, 2.2, 3.2, 3.4, 6.1_

- [x] 3. API Endpoints Implementation





  - Implement GET /api/categories/hierarchy endpoint for tree structure
  - Implement POST /api/categories/{id}/image for image upload
  - Implement DELETE /api/categories/{id}/image for image removal
  - Implement POST /api/categories/reorder for drag-and-drop functionality
  - Implement GET /api/categories/gold-purity-options endpoint
  - Update existing category CRUD endpoints to handle new fields
  - _Requirements: 1.4, 2.3, 3.3, 5.2_

- [x] 4. Frontend Category Management Interface






  - Add "Categories" tab to InventoryView component
  - Create CategoryManagement component with tree view and actions
  - Create CategoryTree component for hierarchical display
  - Create CategoryTreeNode component for individual category items
  - Implement drag-and-drop reordering functionality
  - Add category search and filtering capabilities
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 5. Category Form and Modal Components





  - Create CategoryFormModal component for create/edit operations
  - Implement CategorySelector component for parent category selection
  - Create GoldPuritySelector component with standard options
  - Create CategoryImageUpload component with preview and validation
  - Add form validation for category hierarchy and circular reference prevention
  - Implement bilingual form fields with RTL support for Persian
  - _Requirements: 1.5, 2.4, 3.5, 7.1, 7.2_

- [x] 6. Enhanced Item Form with Dual Category Selection





  - Update ItemFormModal to include main category and subcategory dropdowns
  - Implement cascading category selection (main → sub)
  - Add auto-population of gold purity from selected category
  - Update category display with images in dropdown options
  - Add category path display in item details
  - Update InventoryItem model relationships for dual categories
  - _Requirements: 4.2, 4.3, 4.4, 4.5, 4.6_

- [x] 7. Image Management and Storage





  - Implement Docker-compatible image processing with GD library
  - Create image optimization and thumbnail generation
  - Set up proper file storage volumes in Docker configuration
  - Implement image validation and security checks
  - Add image alt text support for accessibility
  - Create image cleanup for deleted categories
  - _Requirements: 3.1, 3.2, 3.4, 3.5, 9.2_

- [x] 8. Gold Purity System Implementation













  - Create gold purity formatting for Persian/English display
  - Implement karat to purity conversion utilities
  - Add gold purity validation and range checking
  - Create standard purity options (18K, 21K, 24K, etc.)
  - Implement Persian numeral display for gold purity
  - Add gold purity filtering in inventory lists
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 9. Localization and Bilingual Support








  - Add category-related translations to en.json and fa.json
  - Implement RTL layout support for category management
  - Add Persian gold purity terminology (عیار طلا)
  - Create localized category name display methods
  - Implement language switching for category interface
  - Add Persian numeral formatting for category data
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 10. Invoice Integration with Categories





















  - Update invoice line items to display category hierarchy
  - Add category information to invoice PDF templates
  - Include gold purity in invoice item details
  - Update invoice template designer with category fields
  - Implement category-based invoice filtering
  - Add category images to invoice layouts (optional)
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [ ] 11. Reporting and Analytics Enhancement
  - Update inventory reports to group by category hierarchy
  - Add category-based sales performance metrics
  - Implement category stock level reporting
  - Create gold purity analysis reports
  - Add category filtering to existing reports
  - Update dashboard widgets with category performance
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

- [ ] 12. Testing Implementation
  - Write unit tests for Category and CategoryImage models
  - Create feature tests for category CRUD operations
  - Test category hierarchy validation and circular reference prevention
  - Write tests for image upload and processing
  - Test gold purity validation and formatting
  - Create frontend component tests for category management
  - _Requirements: All requirements validation_

- [ ] 13. Database Seeders and Sample Data
  - Create CategorySeeder with sample jewelry categories
  - Add sample subcategories (rings, necklaces, bracelets, etc.)
  - Include sample gold purity values and images
  - Update InventoryTestSeeder to use new category structure
  - Create sample category images for testing
  - Add bilingual category names in seeder data
  - _Requirements: 1.6, 2.6, 7.6_

- [ ] 14. Docker Environment Integration
  - Update docker-compose.yml with category image storage volumes
  - Configure nginx for category image serving
  - Test image processing in Docker containers
  - Ensure database migrations run properly in containers
  - Validate file permissions for image uploads
  - Test backup procedures include category images
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

- [ ] 15. Documentation and Deployment
  - Update API documentation with new category endpoints
  - Create user guide for category management features
  - Document gold purity system usage
  - Add deployment notes for image storage setup
  - Create troubleshooting guide for common issues
  - Update system requirements documentation
  - _Requirements: All requirements completion_