// Types compartilhados entre Admin e Public
export interface Address {
  id?: number;
  label?: string | null;
  name?: string | null;
  recipient_name?: string | null;
  street: string;
  city: string;
  state: string;
  zip_code: string;
  country: string;
  is_default_shipping?: boolean;
  is_default_billing?: boolean;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: number;
  cost_price?: number;
  quantity: number;
  min_quantity: number;
  active: boolean;
  image_url: string | null;
  in_stock?: boolean;
  low_stock?: boolean;
  category: Category | null;
  tags: Tag[];
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  parent_id: number | null;
  active: boolean;
  parent?: Category | null;
  children?: Category[];
  products_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface Tag {
  id: number;
  name: string;
  slug: string;
  products_count?: number;
}

export type OrderStatus = 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';

export type PaymentStatus = 'pending' | 'paid';

export interface Order {
  id: number;
  user_id: number;
  user?: User;
  status: OrderStatus;
  payment_status: PaymentStatus;
  payment_method?: string | null;
  paid_at?: string | null;
  can_cancel?: boolean;
  total: number;
  subtotal: number;
  tax: number;
  shipping_cost: number;
  items: OrderItem[];
  items_count?: number;
  shipping_address?: Address | null;
  billing_address?: Address | null;
  notes?: string | null;
  created_at: string;
  updated_at?: string;
}

export interface OrderItem {
  id: number;
  product_id?: number;
  product: Product;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface User {
  id: number;
  name: string;
  email: string;
  roles?: string[];
  permissions?: string[];
  email_verified?: boolean;
  email_verified_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
}
