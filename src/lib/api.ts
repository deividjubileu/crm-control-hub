// API Configuration - Point to your PHP backend
const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://painelcrm.fwh.is/api';

// Types
export interface User {
  id: number;
  email: string;
  role: 'admin' | 'user';
  status: 'active' | 'blocked';
  created_at: string;
}

export interface License {
  id: number;
  license_key: string;
  user_id: number;
  user_email?: string;
  status: 'active' | 'expired' | 'blocked';
  expires_at: string;
  max_devices: number;
  created_at: string;
}

export interface Token {
  id: number;
  user_id: number;
  token: string;
  device_hash: string;
  ip_address: string;
  last_seen: string;
  expires_at: string;
}

export interface Feature {
  id: number;
  license_id: number;
  feature_key: string;
  enabled: boolean;
  value?: number;
}

export interface Log {
  id: number;
  user_id: number;
  user_email?: string;
  action: string;
  ip: string;
  created_at: string;
}

export interface DashboardStats {
  total_users: number;
  active_licenses: number;
  expired_licenses: number;
  blocked_licenses: number;
  recent_activity: Log[];
}

export interface ApiResponse<T = unknown> {
  status: boolean;
  message?: string;
  data?: T;
}

export interface LoginResponse {
  status: boolean;
  token?: string;
  expires_in?: number;
  message?: string;
  user?: User;
}

// Helper to get auth headers
function getAuthHeaders(): HeadersInit {
  const token = localStorage.getItem('admin_token');
  return {
    'Content-Type': 'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
  };
}

// Generic API request handler
async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers: {
        ...getAuthHeaders(),
        ...options.headers,
      },
    });

    const data = await response.json();
    
    if (!response.ok) {
      return { status: false, message: data.message || 'Request failed' };
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    return { status: false, message: 'Network error' };
  }
}

// Auth API
export const authApi = {
  login: async (email: string, password: string): Promise<LoginResponse> => {
    const response = await apiRequest<LoginResponse>('/admin/login.php', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    return response as LoginResponse;
  },

  logout: async (): Promise<ApiResponse> => {
    return apiRequest('/admin/logout.php', { method: 'POST' });
  },

  validateToken: async (): Promise<ApiResponse<User>> => {
    return apiRequest('/admin/validate-token.php');
  },
};

// Users API
export const usersApi = {
  getAll: async (): Promise<ApiResponse<User[]>> => {
    return apiRequest('/admin/users.php');
  },

  getById: async (id: number): Promise<ApiResponse<User>> => {
    return apiRequest(`/admin/users.php?id=${id}`);
  },

  create: async (data: Partial<User> & { password: string }): Promise<ApiResponse<User>> => {
    return apiRequest('/admin/users.php', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  update: async (id: number, data: Partial<User>): Promise<ApiResponse<User>> => {
    return apiRequest(`/admin/users.php?id=${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  delete: async (id: number): Promise<ApiResponse> => {
    return apiRequest(`/admin/users.php?id=${id}`, {
      method: 'DELETE',
    });
  },
};

// Licenses API
export const licensesApi = {
  getAll: async (): Promise<ApiResponse<License[]>> => {
    return apiRequest('/admin/licenses.php');
  },

  getById: async (id: number): Promise<ApiResponse<License>> => {
    return apiRequest(`/admin/licenses.php?id=${id}`);
  },

  create: async (data: {
    user_id: number;
    expires_at: string;
    max_devices: number;
  }): Promise<ApiResponse<License>> => {
    return apiRequest('/admin/licenses.php', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  update: async (id: number, data: Partial<License>): Promise<ApiResponse<License>> => {
    return apiRequest(`/admin/licenses.php?id=${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  delete: async (id: number): Promise<ApiResponse> => {
    return apiRequest(`/admin/licenses.php?id=${id}`, {
      method: 'DELETE',
    });
  },

  generate: async (data: {
    user_id: number;
    expires_at: string;
    max_devices: number;
  }): Promise<ApiResponse<License>> => {
    return apiRequest('/admin/licenses.php?action=generate', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },
};

// Features API
export const featuresApi = {
  getByLicense: async (licenseId: number): Promise<ApiResponse<Feature[]>> => {
    return apiRequest(`/admin/features.php?license_id=${licenseId}`);
  },

  update: async (licenseId: number, features: Feature[]): Promise<ApiResponse> => {
    return apiRequest('/admin/features.php', {
      method: 'PUT',
      body: JSON.stringify({ license_id: licenseId, features }),
    });
  },

  getDefaults: async (): Promise<ApiResponse<Feature[]>> => {
    return apiRequest('/admin/features.php?defaults=true');
  },
};

// Logs API
export const logsApi = {
  getAll: async (filters?: {
    user_id?: number;
    action?: string;
    from?: string;
    to?: string;
  }): Promise<ApiResponse<Log[]>> => {
    const params = new URLSearchParams();
    if (filters?.user_id) params.append('user_id', filters.user_id.toString());
    if (filters?.action) params.append('action', filters.action);
    if (filters?.from) params.append('from', filters.from);
    if (filters?.to) params.append('to', filters.to);
    
    const query = params.toString() ? `?${params.toString()}` : '';
    return apiRequest(`/admin/logs.php${query}`);
  },
};

// Dashboard API
export const dashboardApi = {
  getStats: async (): Promise<ApiResponse<DashboardStats>> => {
    return apiRequest('/admin/dashboard.php');
  },
};

// Tokens API (for managing active sessions)
export const tokensApi = {
  getByUser: async (userId: number): Promise<ApiResponse<Token[]>> => {
    return apiRequest(`/admin/tokens.php?user_id=${userId}`);
  },

  revoke: async (tokenId: number): Promise<ApiResponse> => {
    return apiRequest(`/admin/tokens.php?id=${tokenId}`, {
      method: 'DELETE',
    });
  },

  revokeAll: async (userId: number): Promise<ApiResponse> => {
    return apiRequest(`/admin/tokens.php?user_id=${userId}`, {
      method: 'DELETE',
    });
  },
};
