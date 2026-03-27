export const appRoutes = {
  home: '/',
  products: {
    index: '/products',
    show: (slug: string): string => `/products/${slug}`,
  },
  cart: {
    index: '/cart',
    items: '/cart/items',
    item: (itemId: number): string => `/cart/items/${itemId}`,
  },
  auth: {
    login: '/login',
    register: '/register',
    logout: '/logout',
  },
  customer: {
    checkout: '/customer/checkout',
    orders: {
      index: '/customer/orders',
      show: (orderId: number): string => `/customer/orders/${orderId}`,
      store: '/customer/orders',
      cancel: (orderId: number): string => `/customer/orders/${orderId}/cancel`,
    },
    addresses: {
      index: '/customer/addresses',
      store: '/customer/addresses',
      update: (addressId: number): string => `/customer/addresses/${addressId}`,
      destroy: (addressId: number): string => `/customer/addresses/${addressId}`,
      setDefault: (addressId: number, type: 'shipping' | 'billing'): string =>
        `/customer/addresses/${addressId}/default/${type}`,
    },
    profile: '/customer/profile',
    profilePassword: '/customer/profile/password',
  },
  admin: {
    root: '/admin',
    dashboard: '/admin/dashboard',
    products: {
      index: '/admin/products',
      create: '/admin/products/create',
      show: (productId: number): string => `/admin/products/${productId}`,
      edit: (productId: number): string => `/admin/products/${productId}/edit`,
    },
    categories: {
      index: '/admin/categories',
      create: '/admin/categories/create',
      show: (categoryId: number): string => `/admin/categories/${categoryId}`,
      edit: (categoryId: number): string => `/admin/categories/${categoryId}/edit`,
    },
    tags: {
      index: '/admin/tags',
      show: (tagId: number): string => `/admin/tags/${tagId}`,
    },
    orders: {
      index: '/admin/orders',
      show: (orderId: number): string => `/admin/orders/${orderId}`,
      updateStatus: (orderId: number): string => `/admin/orders/${orderId}/status`,
    },
    stock: {
      root: '/admin/stock',
      low: '/admin/stock/low',
    },
  },
} as const;
