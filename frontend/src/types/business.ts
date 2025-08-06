// Business domain types

// Customer types
export interface Customer {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  preferred_language: "en" | "fa";
  customer_type: "retail" | "wholesale" | "vip";
  credit_limit?: number;
  payment_terms?: number;
  notes?: string;
  birthday?: string;
  anniversary?: string;
  preferred_communication_method?: "email" | "sms" | "whatsapp" | "phone";
  is_active: boolean;
  crm_stage: "lead" | "prospect" | "customer" | "inactive";
  lead_source?:
    | "referral"
    | "website"
    | "social_media"
    | "walk_in"
    | "advertisement"
    | "other";
  tags?: string[];
  created_at: string;
  updated_at: string;
  // Computed fields from backend
  display_name?: string;
  age?: number;
  total_invoice_amount?: number;
  outstanding_balance?: number;
  last_invoice_date?: string;
}

// Invoice types
export interface Invoice {
  id: number;
  customer_id: number;
  invoice_number: string;
  issue_date: string;
  due_date: string;
  subtotal: number;
  tax_amount: number;
  total_amount: number;
  status: "draft" | "sent" | "paid" | "overdue" | "cancelled";
  language: "en" | "fa";
  template_id?: number;
  notes?: string;
  created_at: string;
  updated_at: string;
  customer?: Customer;
  items?: InvoiceItem[];
}

export interface InvoiceItem {
  id: number;
  invoice_id: number;
  inventory_item_id?: number;
  name?: string;
  description: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  gold_purity?: number;
  weight?: number;
  serial_number?: string;
  category_id?: number;
  main_category_id?: number;
  category_path?: string;
  main_category_name?: string;
  category_name?: string;
  category_image_url?: string;
  gold_purity_from_category?: number;
  created_at: string;
  updated_at: string;
}

// Inventory types
export interface InventoryItem {
  id: number;
  name: string;
  name_persian?: string;
  description?: string;
  description_persian?: string;
  sku: string;
  category_id?: number;
  main_category_id?: number;
  category_path?: string;
  location_id?: number;
  quantity: number;
  unit_price: number;
  cost_price: number;
  gold_purity?: number;
  weight?: number;
  serial_number?: string;
  batch_number?: string;
  expiry_date?: string;
  minimum_stock?: number;
  maximum_stock?: number;
  is_active: boolean;
  track_serial: boolean;
  track_batch: boolean;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
  category?: Category;
  main_category?: Category;
  location?: Location;
  // Computed fields
  localized_name?: string;
  localized_description?: string;
  total_value?: number;
  total_cost?: number;
  is_low_stock?: boolean;
  is_expiring?: boolean;
  is_expired?: boolean;
  formatted_gold_purity?: string;
}

export interface InventoryMovement {
  id: number;
  inventory_item_id: number;
  from_location_id?: number;
  to_location_id?: number;
  type: "in" | "out" | "transfer" | "adjustment" | "wastage" | "production";
  quantity: number;
  unit_cost?: number;
  reference_type?: string;
  reference_id?: number;
  batch_number?: string;
  notes?: string;
  user_id?: number;
  movement_date: string;
  created_at: string;
  updated_at: string;
  inventory_item?: InventoryItem;
  from_location?: Location;
  to_location?: Location;
  user?: any;
  // Computed fields
  total_value?: number;
  is_inbound?: boolean;
  is_outbound?: boolean;
  is_transfer?: boolean;
}

export interface StockAudit {
  id: number;
  audit_number: string;
  location_id?: number;
  status: "pending" | "in_progress" | "completed" | "cancelled";
  audit_date: string;
  auditor_id?: number;
  notes?: string;
  started_at?: string;
  completed_at?: string;
  created_at: string;
  updated_at: string;
  location?: Location;
  auditor?: any;
  audit_items?: StockAuditItem[];
  // Computed fields
  total_variance_value?: number;
  items_with_variance_count?: number;
  completion_percentage?: number;
  is_completed?: boolean;
  is_in_progress?: boolean;
}

export interface StockAuditItem {
  id: number;
  stock_audit_id: number;
  inventory_item_id: number;
  expected_quantity: number;
  actual_quantity?: number;
  variance?: number;
  variance_value?: number;
  is_counted: boolean;
  notes?: string;
  created_at: string;
  updated_at: string;
  inventory_item?: InventoryItem;
}

export interface BillOfMaterial {
  id: number;
  finished_item_id: number;
  component_item_id: number;
  quantity_required: number;
  wastage_percentage: number;
  is_active: boolean;
  notes?: string;
  created_at: string;
  updated_at: string;
  finished_item?: InventoryItem;
  component_item?: InventoryItem;
  // Computed fields
  total_quantity_required?: number;
  wastage_quantity?: number;
  total_cost?: number;
}

export interface Category {
  id: number;
  name: string;
  name_persian?: string;
  description?: string;
  description_persian?: string;
  code?: string;
  parent_id?: number;
  default_gold_purity?: number;
  image_path?: string;
  sort_order?: number;
  is_active?: boolean;
  specifications?: Record<string, any>;
  item_count?: number;
  subcategory_count?: number;
  has_children?: boolean;
  localized_name?: string;
  localized_description?: string;
  formatted_gold_purity?: string;
  created_at: string;
  updated_at: string;
}

export interface Location {
  id: number;
  name: string;
  description?: string;
  address?: string;
  created_at: string;
  updated_at: string;
}

// Accounting types
export interface Account {
  id: number;
  name: string;
  code: string;
  type: "asset" | "liability" | "equity" | "revenue" | "expense";
  parent_id?: number;
  balance: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Transaction {
  id: number;
  account_id: number;
  reference_number: string;
  description: string;
  debit_amount: number;
  credit_amount: number;
  transaction_date: string;
  type: string;
  is_locked: boolean;
  created_at: string;
  updated_at: string;
  account?: Account;
}

// Dashboard types
export interface BusinessKPI {
  gold_sold: {
    value: number;
    unit: "grams" | "ounces";
    change: number;
  };
  total_profit: {
    value: number;
    currency: string;
    change: number;
  };
  average_price: {
    value: number;
    currency: string;
    change: number;
  };
  returns: {
    count: number;
    value: number;
    change: number;
  };
  gross_margin: {
    percentage: number;
    change: number;
  };
  net_margin: {
    percentage: number;
    change: number;
  };
}

export interface Alert {
  id: string;
  type: "pending_cheque" | "low_stock" | "expiring_item" | "overdue_invoice";
  title: string;
  message: string;
  severity: "low" | "medium" | "high";
  created_at: string;
  read: boolean;
  data?: any;
}

// Communication types
export interface Communication {
  id: number;
  customer_id: number;
  user_id?: number;
  type: "email" | "sms" | "whatsapp" | "phone" | "meeting" | "note";
  subject?: string;
  message: string;
  status: "draft" | "sent" | "delivered" | "read" | "failed";
  sent_at?: string;
  delivered_at?: string;
  read_at?: string;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
  customer?: Customer;
}

// CRM types
export interface CRMPipelineStage {
  stage: "lead" | "prospect" | "customer" | "inactive";
  name: string;
  count: number;
  customers: Customer[];
}

export interface CRMPipelineData {
  stages: CRMPipelineStage[];
  total_customers: number;
  conversion_rates: {
    lead_to_prospect: number;
    prospect_to_customer: number;
    overall: number;
  };
}

// Customer aging report types
export interface CustomerAgingBucket {
  range: string;
  customers: Customer[];
  total_amount: number;
  count: number;
}

export interface CustomerAgingReport {
  buckets: CustomerAgingBucket[];
  total_outstanding: number;
  total_customers: number;
  generated_at: string;
}

// Report types
export interface ReportFilter {
  date_from?: string;
  date_to?: string;
  customer_id?: number;
  category_id?: number;
  location_id?: number;
  status?: string;
}

export interface SalesReport {
  period: string;
  total_sales: number;
  total_items: number;
  average_order_value: number;
  top_customers: {
    customer: Customer;
    total_sales: number;
  }[];
  top_products: {
    item: InventoryItem;
    quantity_sold: number;
    revenue: number;
  }[];
}

// Template types
export interface InvoiceTemplate {
  id: number;
  name: string;
  language: "en" | "fa";
  layout: "standard" | "modern" | "classic";
  fields: {
    logo: boolean;
    qr_code: boolean;
    category_hierarchy: boolean;
    category_images: boolean;
    gold_purity: boolean;
    custom_fields: string[];
  };
  created_at: string;
  updated_at: string;
}
