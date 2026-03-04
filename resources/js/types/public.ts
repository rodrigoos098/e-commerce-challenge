import type { Address, Product, Category, Tag, Order, OrderStatus, OrderItem, User, PaginatedResponse } from '@/types/shared';

// Re-export para conveniência
export type { Product, Category, Tag, Order, OrderStatus, OrderItem, User, PaginatedResponse };

// Types específicos do frontend público
export interface CartItem {
    id: number;
    product: Product;
    quantity: number;
}

export interface Cart {
    id: number;
    items: CartItem[];
    total: number;
    subtotal: number;
    tax: number;
    shipping_cost: number;
    item_count: number;
}

export type CheckoutAddress = Address;

export interface CheckoutFormData {
    shipping_address: CheckoutAddress;
    billing_address: CheckoutAddress;
    same_as_shipping: boolean;
    notes: string;
}

export interface ProductFilters {
    search?: string;
    category_id?: number | string;
    price_min?: number | string;
    price_max?: number | string;
    page?: number;
}

export interface HomePageProps {
    featured_products: Product[];
    categories: Category[];
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
}

export interface OrdersPageProps {
    orders: PaginatedResponse<Order>;
}

export interface OrderShowPageProps {
    order: Order;
}

export interface ProfilePageProps {
    user: User;
}
