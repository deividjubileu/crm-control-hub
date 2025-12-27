import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import {
  Users,
  Key,
  AlertTriangle,
  Ban,
  Plus,
  Activity,
  Clock,
} from 'lucide-react';
import { dashboardApi, usersApi, licensesApi, DashboardStats, User } from '@/lib/api';
import { useToast } from '@/hooks/use-toast';
import { FEATURE_DEFINITIONS } from '@/lib/types';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';

// Mock data for demo (remove when API is connected)
const mockStats: DashboardStats = {
  total_users: 156,
  active_licenses: 89,
  expired_licenses: 23,
  blocked_licenses: 8,
  recent_activity: [
    { id: 1, user_id: 1, user_email: 'user1@example.com', action: 'login', ip: '192.168.1.1', created_at: new Date().toISOString() },
    { id: 2, user_id: 2, user_email: 'user2@example.com', action: 'license_validated', ip: '192.168.1.2', created_at: new Date(Date.now() - 3600000).toISOString() },
    { id: 3, user_id: 3, user_email: 'user3@example.com', action: 'feature_used', ip: '192.168.1.3', created_at: new Date(Date.now() - 7200000).toISOString() },
  ],
};

const mockUsers: User[] = [
  { id: 1, email: 'user1@example.com', role: 'user', status: 'active', created_at: new Date().toISOString() },
  { id: 2, email: 'user2@example.com', role: 'user', status: 'active', created_at: new Date().toISOString() },
];

export default function Dashboard() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [users, setUsers] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const { toast } = useToast();

  // New license form
  const [newLicense, setNewLicense] = useState({
    user_id: '',
    expires_at: '',
    max_devices: '3',
  });

  // Features state
  const [features, setFeatures] = useState<Record<string, boolean>>({
    auto_reply: true,
    bulk_send: true,
    scraping: true,
    ai_assistant: false,
    templates: true,
    scheduler: true,
    reports: false,
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setIsLoading(true);
    try {
      const [statsRes, usersRes] = await Promise.all([
        dashboardApi.getStats(),
        usersApi.getAll(),
      ]);

      if (statsRes.status && statsRes.data) {
        setStats(statsRes.data);
      } else {
        // Use mock data for demo
        setStats(mockStats);
      }

      if (usersRes.status && usersRes.data) {
        setUsers(usersRes.data);
      } else {
        setUsers(mockUsers);
      }
    } catch {
      // Use mock data for demo
      setStats(mockStats);
      setUsers(mockUsers);
    } finally {
      setIsLoading(false);
    }
  };

  const handleGenerateLicense = async () => {
    if (!newLicense.user_id || !newLicense.expires_at) {
      toast({
        title: 'Campos obrigatórios',
        description: 'Selecione um usuário e data de expiração.',
        variant: 'destructive',
      });
      return;
    }

    const result = await licensesApi.generate({
      user_id: parseInt(newLicense.user_id),
      expires_at: newLicense.expires_at,
      max_devices: parseInt(newLicense.max_devices),
    });

    if (result.status) {
      toast({
        title: 'Licença gerada!',
        description: `Chave: ${result.data?.license_key}`,
      });
      setNewLicense({ user_id: '', expires_at: '', max_devices: '3' });
      fetchData();
    } else {
      toast({
        title: 'Erro',
        description: result.message || 'Falha ao gerar licença',
        variant: 'destructive',
      });
    }
  };

  const pieData = stats ? [
    { name: 'Ativas', value: stats.active_licenses, color: 'hsl(142, 76%, 36%)' },
    { name: 'Expiradas', value: stats.expired_licenses, color: 'hsl(38, 92%, 50%)' },
    { name: 'Bloqueadas', value: stats.blocked_licenses, color: 'hsl(0, 84%, 60%)' },
  ] : [];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-foreground">Dashboard</h1>
        <p className="text-muted-foreground">Visão geral do sistema de licenciamento</p>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total Usuários
            </CardTitle>
            <Users className="w-4 h-4 text-primary" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_users || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Licenças Ativas
            </CardTitle>
            <Key className="w-4 h-4 text-success" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-success">{stats?.active_licenses || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Expiradas
            </CardTitle>
            <AlertTriangle className="w-4 h-4 text-warning" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-warning">{stats?.expired_licenses || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Bloqueadas
            </CardTitle>
            <Ban className="w-4 h-4 text-destructive" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-destructive">{stats?.blocked_licenses || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Grid */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* License Stats Chart */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Activity className="w-5 h-5 text-primary" />
              Estatísticas de Licenças
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={pieData}
                    cx="50%"
                    cy="50%"
                    innerRadius={60}
                    outerRadius={80}
                    paddingAngle={5}
                    dataKey="value"
                  >
                    {pieData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Generate License Form */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Plus className="w-5 h-5 text-primary" />
              Gerar Nova Licença
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>Usuário</Label>
              <Select
                value={newLicense.user_id}
                onValueChange={(value) => setNewLicense({ ...newLicense, user_id: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Selecione um usuário" />
                </SelectTrigger>
                <SelectContent>
                  {users.map((user) => (
                    <SelectItem key={user.id} value={user.id.toString()}>
                      {user.email}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Data de Expiração</Label>
              <Input
                type="date"
                value={newLicense.expires_at}
                onChange={(e) => setNewLicense({ ...newLicense, expires_at: e.target.value })}
              />
            </div>

            <div className="space-y-2">
              <Label>Máx. Dispositivos</Label>
              <Input
                type="number"
                min="1"
                max="10"
                value={newLicense.max_devices}
                onChange={(e) => setNewLicense({ ...newLicense, max_devices: e.target.value })}
              />
            </div>

            <Button onClick={handleGenerateLicense} className="w-full">
              <Key className="w-4 h-4 mr-2" />
              Gerar Licença
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* Bottom Grid */}
      <div className="grid gap-6 lg:grid-cols-2">
        {/* Recent Activity */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Clock className="w-5 h-5 text-primary" />
              Atividade Recente
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {stats?.recent_activity?.slice(0, 5).map((log) => (
                <div
                  key={log.id}
                  className="flex items-center justify-between py-2 border-b border-border last:border-0"
                >
                  <div>
                    <p className="text-sm font-medium">{log.user_email}</p>
                    <p className="text-xs text-muted-foreground">{log.action}</p>
                  </div>
                  <div className="text-right">
                    <Badge variant="secondary">{log.ip}</Badge>
                    <p className="text-xs text-muted-foreground mt-1">
                      {format(new Date(log.created_at), "dd/MM HH:mm", { locale: ptBR })}
                    </p>
                  </div>
                </div>
              ))}
              {(!stats?.recent_activity || stats.recent_activity.length === 0) && (
                <p className="text-center text-muted-foreground py-4">
                  Nenhuma atividade recente
                </p>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Extension Features */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Activity className="w-5 h-5 text-primary" />
              Recursos da Extensão
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {Object.entries(FEATURE_DEFINITIONS)
                .filter(([, def]) => def.type === 'boolean')
                .map(([key, def]) => (
                  <div
                    key={key}
                    className="flex items-center justify-between py-2"
                  >
                    <Label htmlFor={key} className="cursor-pointer">
                      {def.label}
                    </Label>
                    <Switch
                      id={key}
                      checked={features[key] ?? false}
                      onCheckedChange={(checked) =>
                        setFeatures({ ...features, [key]: checked })
                      }
                    />
                  </div>
                ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
