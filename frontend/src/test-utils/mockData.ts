import type { Category } from "@/types";

export const mockCategories: Category[] = [
  {
    id: 1,
    name: "Jewelry",
    code: "JEWELRY",
    localized_name: "Jewelry",
    parent_id: undefined,
    sort_order: 1,
    is_active: true,
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
  {
    id: 2,
    name: "Necklaces",
    code: "NECKLACES",
    localized_name: "Necklaces",
    parent_id: 1,
    sort_order: 1,
    is_active: true,
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
  {
    id: 3,
    name: "Rings",
    code: "RINGS",
    localized_name: "Rings",
    parent_id: 1,
    sort_order: 2,
    is_active: true,
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
];

export const mockGoldPurityOptions = [
  { value: 10, label: "10K Gold", karat: 10, percentage: 41.7 },
  { value: 14, label: "14K Gold", karat: 14, percentage: 58.3 },
  { value: 18, label: "18K Gold", karat: 18, percentage: 75.0 },
  { value: 22, label: "22K Gold", karat: 22, percentage: 91.7 },
  { value: 24, label: "24K Gold", karat: 24, percentage: 99.9 },
];

export const mockApiResponse = {
  data: {
    success: true,
    data: mockCategories,
  },
};
