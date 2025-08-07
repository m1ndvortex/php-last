# Category Management User Guide

## Overview

The Category Management system allows you to organize your jewelry inventory using a hierarchical structure with main categories and subcategories. Each category can have specific gold purity settings, images, and bilingual names to support both English and Persian languages.

## Getting Started

### Accessing Category Management

1. Navigate to the **Inventory** section from the main menu
2. Click on the **Categories** tab
3. You'll see the category management interface with a tree view of your existing categories

### Understanding the Interface

The category management interface consists of:
- **Category Tree**: Hierarchical view of all categories and subcategories
- **Action Buttons**: Create, edit, delete, and reorder categories
- **Search Bar**: Find categories quickly by name
- **Category Details Panel**: View category information and statistics

## Managing Categories

### Creating a Main Category

1. Click the **"Add Category"** button
2. Fill in the category information:
   - **Name** (English): Required field for the category name
   - **Name (Persian)**: Optional Persian translation
   - **Category Code**: Unique identifier (e.g., "RING", "NECK")
   - **Description**: Optional detailed description
   - **Default Gold Purity**: Set default gold purity for items in this category
   - **Category Image**: Upload an image to represent this category

3. Click **"Create"** to save the category

**Example:**
- Name: "Rings"
- Name (Persian): "انگشتر"
- Code: "RING"
- Default Gold Purity: 18K
- Description: "Collection of all ring types"

### Creating Subcategories

1. Select an existing category from the tree
2. Click **"Add Subcategory"** or use the dropdown menu next to the category
3. Fill in the subcategory details (same fields as main category)
4. The **Parent Category** field will be automatically set
5. Click **"Create"** to save

**Example Subcategories for "Rings":**
- Wedding Rings (حلقه ازدواج)
- Engagement Rings (انگشتر نامزدی)
- Fashion Rings (انگشتر مد)

### Editing Categories

1. Click the **edit icon** (pencil) next to any category
2. Modify the desired fields
3. Click **"Update"** to save changes

**Note:** You can change the parent category to move a category to a different location in the hierarchy.

### Deleting Categories

1. Click the **delete icon** (trash) next to the category
2. Confirm the deletion in the popup dialog

**Important Restrictions:**
- Cannot delete categories that have subcategories
- Cannot delete categories that have inventory items assigned
- Move or reassign items/subcategories before deletion

### Reordering Categories

You can change the order of categories using drag-and-drop:

1. Click and hold on a category name
2. Drag it to the desired position
3. Release to drop it in the new location
4. The system automatically saves the new order

## Gold Purity System

### Understanding Gold Purity

Gold purity is measured in karats (K) and represents the proportion of pure gold in an alloy:
- **24K**: Pure gold (999‰)
- **22K**: 91.6% gold (916‰)
- **21K**: 87.5% gold (875‰)
- **18K**: 75% gold (750‰)
- **14K**: 58.5% gold (585‰)
- **10K**: 41.7% gold (417‰)

### Setting Default Gold Purity

1. When creating or editing a category, set the **Default Gold Purity**
2. This value will automatically populate when adding new inventory items to this category
3. Users can still override this value for individual items

### Persian Gold Purity Display

In Persian interface:
- Gold purity shows as "عیار طلا"
- Numbers display in Persian numerals (۱۸ عیار)
- Tooltips show both karat and per-mille values

## Image Management

### Uploading Category Images

1. In the category form, click **"Choose Image"**
2. Select an image file (JPEG, PNG, WebP)
3. The system will automatically:
   - Resize the image to optimal dimensions
   - Compress for web performance
   - Generate thumbnails for different uses

**Image Requirements:**
- Maximum file size: 2MB
- Supported formats: JPEG, PNG, WebP
- Recommended dimensions: 400x400 pixels
- Images are automatically optimized

### Managing Images

- **Replace Image**: Upload a new image to replace the existing one
- **Remove Image**: Click the "X" button to remove the current image
- **Alt Text**: Add descriptive text for accessibility (both English and Persian)

### Image Display

Category images appear in:
- Category tree view (small thumbnails)
- Category selection dropdowns
- Inventory item forms
- Reports and invoices (optional)

## Bilingual Support

### Language Settings

The system supports both English and Persian languages:

1. **Interface Language**: Switch using the language selector in the header
2. **Category Names**: Enter names in both languages for complete bilingual support
3. **RTL Support**: Persian text automatically displays right-to-left

### Best Practices for Bilingual Categories

1. **Always provide both English and Persian names** for better user experience
2. **Use consistent terminology** across similar categories
3. **Consider cultural context** when naming categories in Persian
4. **Test both language interfaces** to ensure proper display

### Common Persian Jewelry Terms

- انگشتر (Ring)
- گردنبند (Necklace)
- دستبند (Bracelet)
- گوشواره (Earring)
- زنجیر (Chain)
- آویز (Pendant)
- عیار طلا (Gold Purity)

## Using Categories in Inventory Management

### Adding Items to Categories

1. When creating a new inventory item:
   - Select **Main Category** from the dropdown
   - Choose **Subcategory** (if applicable)
   - Gold purity will auto-populate from category settings
   - Category-specific fields may appear

2. **Dual Category Selection**:
   - Main Category: Broad classification (e.g., "Rings")
   - Subcategory: Specific type (e.g., "Wedding Rings")

### Category-Based Filtering

Use categories to filter inventory:
- Filter by main category or subcategory
- Combine with other filters (gold purity, price range)
- Export filtered results for reports

### Bulk Category Operations

1. **Move Items Between Categories**:
   - Select multiple items in inventory list
   - Use "Change Category" bulk action
   - Choose new category and confirm

2. **Update Category Settings**:
   - Changes to default gold purity affect new items only
   - Existing items retain their individual settings

## Reporting and Analytics

### Category-Based Reports

Access category reports from the Reports section:

1. **Inventory by Category**: Stock levels grouped by category
2. **Sales Performance**: Revenue and profit by category
3. **Gold Purity Analysis**: Distribution of gold purity across categories
4. **Category Movement**: Stock movements filtered by category

### Dashboard Widgets

Add category-related widgets to your dashboard:
- **Category Performance**: Top-performing categories
- **Stock Alerts by Category**: Low stock items grouped by category
- **Gold Purity Distribution**: Visual breakdown of inventory by purity

## Troubleshooting

### Common Issues

**Problem**: Cannot delete a category
**Solution**: Check if the category has subcategories or inventory items. Move or delete these first.

**Problem**: Category images not displaying
**Solution**: 
1. Check file permissions in storage/app/public/categories
2. Ensure the image file exists
3. Verify nginx configuration for serving static files

**Problem**: Persian text not displaying correctly
**Solution**:
1. Ensure browser supports Persian fonts
2. Check that RTL CSS is loaded
3. Verify database charset supports UTF-8

**Problem**: Gold purity not auto-populating
**Solution**:
1. Verify the category has a default gold purity set
2. Check that the item form is properly loading category data
3. Clear browser cache and reload

### Performance Tips

1. **Optimize Images**: Use WebP format for better compression
2. **Limit Category Depth**: Keep hierarchy to 2-3 levels maximum
3. **Regular Cleanup**: Remove unused categories and images
4. **Index Usage**: Categories are indexed for fast searching

### Data Backup

Category data is included in regular system backups:
- Database tables: `categories`, `category_images`
- File storage: `storage/app/public/categories/`
- Backup frequency: Daily (configurable)

## Advanced Features

### Category Specifications

Use the specifications field for category-specific attributes:

```json
{
  "chain_types": ["box", "rope", "cable"],
  "length_range": "16-24 inches",
  "clasp_types": ["lobster", "spring", "toggle"]
}
```

### API Integration

For developers integrating with external systems:
- RESTful API endpoints available
- JSON responses with full category hierarchy
- Webhook support for category changes
- Rate limiting: 60 requests/minute

### Custom Category Fields

Contact system administrator to add custom fields:
- Additional specifications
- Custom validation rules
- Integration with external systems
- Specialized reporting requirements

## Support

For additional help:
- Check the API documentation for technical details
- Contact system administrator for configuration issues
- Refer to troubleshooting guide for common problems
- Submit feature requests through the feedback system