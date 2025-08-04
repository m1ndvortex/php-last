// Business domain types

// Customer types
export interface Customer {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  preferred_language: "en" | "fa";
  customer_type: "individual" | "business";
  credit_limit?: number;
  payment_terms?: number;
  notes?: string;
  created_at: string;
  updated_at: string;
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
  description: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  created_at: string;
  updated_at: string;
}

// Inventory types
export interface InventoryItem {
  id: number;
  name: string;
  description?: string;
  sku: string;
  category_id?: number;
  location_id?: number;
  quantity: number;
  unit_price: number;
  cost_price: number;
  gold_purity?: number;
  weight?: number;
  serial_number?: string;
  batch_number?: string;
  expiry_date?: string;
  created_at: string;
  updated_at: string;
  category?: Category;
  location?: Location;
}

export interface Category {
  id: number;
  name: string;
  description?: string;
  parent_id?: number;
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
  type: "whatsapp" | "sms" | "email";
  subject?: string;
  message: string;
  status: "pending" | "sent" | "delivered" | "failed";
  sent_at?: string;
  created_at: string;
  updated_at: string;
  customer?: Customer;
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
    custom_fields: string[];
  };
  created_at: string;
  updated_at: string;
}
