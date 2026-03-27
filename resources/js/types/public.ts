import type {
  Product,
  Category,
  Tag,
  Order,
  OrderStatus,
  PaymentStatus,
  OrderItem,
  User,
  PaginatedResponse,
} from '@/types/shared';

// Re-export para conveniência
export type {
  Product,
  Category,
  Tag,
  Order,
  OrderStatus,
  PaymentStatus,
  OrderItem,
  User,
  PaginatedResponse,
};

// Types específicos do frontend público
export interface CartItem {
  id: number;
  product_id?: number;
  product: Product;
  quantity: number;
  subtotal?: number;
}

export interface Cart {
  id: number;
  user_id?: number;
  items: CartItem[];
  total: number;
  subtotal: number;
  tax: number;
  shipping_cost: number;
  shipping_zip_code?: string | null;
  shipping_rule_label: string;
  shipping_rule_description: string;
  shipping_is_free: boolean;
  item_count: number;
  items_count?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface SavedAddress {
  id: number;
  label: string;
  recipient_name: string;
  street: string;
  city: string;
  state: string;
  zip_code: string;
  country: string;
  is_default_shipping: boolean;
  is_default_billing: boolean;
}

export interface AddressSummary {
  count: number;
  default_shipping_label: string | null;
  default_billing_label: string | null;
}

export interface CheckoutFormData {
  shipping_mode: 'saved' | 'new';
  shipping_address_id?: number;
  shipping_name: string;
  shipping_street: string;
  shipping_city: string;
  shipping_state: string;
  shipping_zip: string;
  shipping_country: string;
  same_billing: boolean;
  billing_mode?: 'saved' | 'new';
  billing_address_id?: number;
  billing_name: string;
  billing_street: string;
  billing_city: string;
  billing_state: string;
  billing_zip: string;
  billing_country: string;
  notes?: string;
  payment_simulated?: boolean;
}

export interface ProductFilters {
  search?: string;
  category_id?: number | string;
  price_min?: number | string;
  price_max?: number | string;
  page?: number | string;
}

export interface HomePageProps {
  featured_products: Product[];
  categories: Category[];
  stats?: {
    product_count: number;
    category_count: number;
  };
}

export interface ProductsPageProps {
  products: PaginatedResponse<Product>;
  categories: Category[];
  filters: ProductFilters;
}

export interface ProductShowPageProps {
  product: Product;
  related_products?: Product[];
}

export interface CartPageProps {
  cart: Cart;
}

export interface CheckoutPageProps {
  cart: Cart;
  addresses: SavedAddress[];
}

export interface OrdersPageProps {
  orders: PaginatedResponse<
    Pick<
      Order,
      | 'id'
      | 'status'
      | 'payment_status'
      | 'payment_method'
      | 'paid_at'
      | 'can_cancel'
      | 'subtotal'
      | 'tax'
      | 'shipping_cost'
      | 'total'
      | 'created_at'
      | 'updated_at'
      | 'items_count'
    >
  >;
}

export interface OrderShowPageProps {
  order: Order;
}

export interface ProfilePageProps {
  user: User;
  address_summary: AddressSummary;
}

export interface CustomerAddressesPageProps {
  addresses: SavedAddress[];
}
