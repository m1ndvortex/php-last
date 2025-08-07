# Category Management API Documentation

## Overview

The Category Management API provides comprehensive endpoints for managing hierarchical jewelry categories with gold purity specifications, image support, and bilingual functionality.

## Base URL
```
/api/categories
```

## Authentication
All endpoints require authentication via Bearer token.

## Endpoints

### List Categories
```http
GET /api/categories
```

**Query Parameters:**
- `include_hierarchy` (boolean): Include full hierarchical structure
- `with_images` (boolean): Include category images
- `with_counts` (boolean): Include item counts
- `parent_id` (integer): Filter by parent category
- `search` (string): Search in category names

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Rings",
      "name_persian": "انگشتر",
      "code": "RING",
      "parent_id": null,
      "default_gold_purity": 18.000,
      "image_path": "/storage/categories/ring-image.webp",
      "sort_order": 1,
      "is_active": true,
      "item_count": 25,
      "children": [
        {
          "id": 2,
          "name": "Wedding Rings",
          "name_persian": "حلقه ازدواج",
          "parent_id": 1,
          "item_count": 12
        }
      ]
    }
  ]
}
```

### Get Category Hierarchy
```http
GET /api/categories/hierarchy
```

Returns complete hierarchical tree structure with all categories and subcategories.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Rings",
      "name_persian": "انگشتر",
      "children": [
        {
          "id": 2,
          "name": "Wedding Rings",
          "name_persian": "حلقه ازدواج",
          "children": []
        }
      ]
    }
  ]
}
```

### Create Category
```http
POST /api/categories
```

**Request Body:**
```json
{
  "name": "Necklaces",
  "name_persian": "گردنبند",
  "description": "Beautiful necklaces collection",
  "description_persian": "مجموعه زیبای گردنبندها",
  "code": "NECK",
  "parent_id": null,
  "default_gold_purity": 21.000,
  "is_active": true,
  "specifications": {
    "chain_types": ["box", "rope", "cable"],
    "length_range": "16-24 inches"
  }
}
```

**Validation Rules:**
- `name`: required, string, max:255
- `name_persian`: nullable, string, max:255
- `code`: required, string, max:10, unique
- `parent_id`: nullable, exists:categories,id, no circular reference
- `default_gold_purity`: nullable, numeric, min:0, max:24
- `image`: nullable, image, mimes:jpeg,png,jpg,webp, max:2048KB

### Get Category Details
```http
GET /api/categories/{id}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Rings",
    "name_persian": "انگشتر",
    "description": "Ring collection",
    "description_persian": "مجموعه انگشتر",
    "code": "RING",
    "parent_id": null,
    "default_gold_purity": 18.000,
    "formatted_gold_purity": "18K (750‰)",
    "image_path": "/storage/categories/ring-image.webp",
    "sort_order": 1,
    "is_active": true,
    "item_count": 25,
    "has_children": true,
    "parent": null,
    "children": [...],
    "images": [
      {
        "id": 1,
        "image_path": "/storage/categories/ring-image.webp",
        "alt_text": "Ring category image",
        "alt_text_persian": "تصویر دسته انگشتر",
        "is_primary": true
      }
    ]
  }
}
```

### Update Category
```http
PUT /api/categories/{id}
```

Same request body format as create. Supports partial updates.

### Delete Category
```http
DELETE /api/categories/{id}
```

**Validation:**
- Cannot delete category with subcategories
- Cannot delete category with associated inventory items
- Returns 422 if validation fails

### Upload Category Image
```http
POST /api/categories/{id}/image
```

**Request:** Multipart form data
- `image`: Image file (JPEG, PNG, WebP, max 2MB)
- `alt_text`: Alternative text for accessibility
- `alt_text_persian`: Persian alternative text

**Response:**
```json
{
  "data": {
    "image_path": "/storage/categories/category-123-image.webp",
    "alt_text": "Category image",
    "is_primary": true
  }
}
```

### Remove Category Image
```http
DELETE /api/categories/{id}/image
```

Removes the primary image associated with the category.

### Reorder Categories
```http
POST /api/categories/reorder
```

**Request Body:**
```json
{
  "categories": [
    {"id": 1, "sort_order": 1, "parent_id": null},
    {"id": 2, "sort_order": 2, "parent_id": null},
    {"id": 3, "sort_order": 1, "parent_id": 1}
  ]
}
```

### Get Gold Purity Options
```http
GET /api/categories/gold-purity-options
```

**Response:**
```json
{
  "data": [
    {"value": 10.000, "label": "10K (417‰)", "label_persian": "۱۰ عیار"},
    {"value": 14.000, "label": "14K (585‰)", "label_persian": "۱۴ عیار"},
    {"value": 18.000, "label": "18K (750‰)", "label_persian": "۱۸ عیار"},
    {"value": 21.000, "label": "21K (875‰)", "label_persian": "۲۱ عیار"},
    {"value": 22.000, "label": "22K (916‰)", "label_persian": "۲۲ عیار"},
    {"value": 24.000, "label": "24K (999‰)", "label_persian": "۲۴ عیار"}
  ]
}
```

## Error Responses

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "code": ["The code has already been taken."],
    "parent_id": ["Creating this relationship would create a circular reference."]
  }
}
```

### 404 Not Found
```json
{
  "message": "Category not found."
}
```

### 409 Conflict (Cannot Delete)
```json
{
  "message": "Cannot delete category with associated items or subcategories.",
  "details": {
    "subcategories_count": 3,
    "items_count": 15
  }
}
```

## Rate Limiting
- 60 requests per minute per authenticated user
- Image upload endpoints: 10 requests per minute

## Localization
All text responses support localization based on `Accept-Language` header:
- `en`: English responses
- `fa`: Persian/Farsi responses

## Examples

### Creating a Main Category with Image
```bash
curl -X POST /api/categories \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "name=Bracelets" \
  -F "name_persian=دستبند" \
  -F "code=BRAC" \
  -F "default_gold_purity=18" \
  -F "image=@bracelet-category.jpg"
```

### Getting Category Hierarchy
```bash
curl -X GET /api/categories/hierarchy \
  -H "Authorization: Bearer {token}" \
  -H "Accept-Language: fa"
```

### Filtering Categories by Parent
```bash
curl -X GET "/api/categories?parent_id=1&with_counts=true" \
  -H "Authorization: Bearer {token}"
```