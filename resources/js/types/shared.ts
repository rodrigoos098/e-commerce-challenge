// Types compartilhados entre Admin e Public
export interface Address {
    name?: string;
    street: string;
    city: string;
    state: string;
    zip_code: string;
    country: string;
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
    children?: Category[];
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
}

export type OrderStatus = 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';

export interface Order {
    id: number;
    user_id: number;
    status: OrderStatus;
    total: number;
    subtotal: number;
    tax: number;
    shipping_cost: number;
    items: OrderItem[];
    shipping_address?: Address | null;
    billing_address?: Address | null;
    notes?: string;
    created_at: string;
}

export interface OrderItem {
    id: number;
    product: Product;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface User {
    id: number;
    name: string;
    email: string;
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
