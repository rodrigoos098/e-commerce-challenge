import type {
    Product,
    Category,
    Order,
    OrderStatus,
    OrderItem,
    PaginatedResponse,
    User,
    Tag,
} from '@/types/shared';

// Re-export para conveniência
export type { Product, Category, Order, OrderStatus, OrderItem, PaginatedResponse, User, Tag };

// Types específicos do admin
export type StockMovementType = 'entrada' | 'saida' | 'ajuste' | 'venda' | 'devolucao';

export interface StockMovement {
    id: number;
    product_id: number;
    product?: Product;
    type: StockMovementType;
    quantity: number;
    notes?: string;
    created_at: string;
}

export interface DashboardStats {
    total_products: number;
    total_orders: number;
    total_revenue: number;
    low_stock_count: number;
    orders_by_day: Array<{
        date: string;
        orders: number;
        revenue: number;
    }>;
    recent_orders: Order[];
    low_stock_products: Product[];
}

export interface AdminNavItem {
    label: string;
    href: string;
    icon: 'dashboard' | 'products' | 'categories' | 'orders' | 'stock';
    active?: boolean;
}

export interface ProductFormData {
    name: string;
    description: string;
    price: number | string;
    cost_price: number | string;
    quantity: number | string;
    min_quantity: number | string;
    category_id: number | string;
    tags: number[];
    active: boolean;
}

export interface CategoryFormData {
    name: string;
    description: string;
    parent_id: number | null;
    active: boolean;
}

export interface AdminPageProps {
    auth: {
        user: User;
    };
    flash?: {
        success?: string;
        error?: string;
    };
}
