// Centralized types for the admin panel

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
  features?: Feature[];
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

// Feature definitions for the extension
export const FEATURE_DEFINITIONS = {
  auto_reply: { key: 'auto_reply', label: 'Resposta Automática', type: 'boolean' },
  bulk_send: { key: 'bulk_send', label: 'Envio em Massa', type: 'boolean' },
  scraping: { key: 'scraping', label: 'Extração de Dados', type: 'boolean' },
  daily_limit: { key: 'daily_limit', label: 'Limite Diário', type: 'number', default: 200 },
  ai_assistant: { key: 'ai_assistant', label: 'Assistente IA', type: 'boolean' },
  templates: { key: 'templates', label: 'Templates', type: 'boolean' },
  scheduler: { key: 'scheduler', label: 'Agendamento', type: 'boolean' },
  reports: { key: 'reports', label: 'Relatórios', type: 'boolean' },
} as const;

export type FeatureKey = keyof typeof FEATURE_DEFINITIONS;
