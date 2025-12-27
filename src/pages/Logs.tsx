import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { FileText, Search, Calendar } from 'lucide-react';
import { logsApi, Log } from '@/lib/api';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

// Mock data
const mockLogs: Log[] = [
  { id: 1, user_id: 1, user_email: 'admin@painelcrm.com', action: 'admin_login', ip: '192.168.1.100', created_at: new Date().toISOString() },
  { id: 2, user_id: 2, user_email: 'joao@empresa.com', action: 'login', ip: '189.45.123.45', created_at: new Date(Date.now() - 1800000).toISOString() },
  { id: 3, user_id: 2, user_email: 'joao@empresa.com', action: 'license_validated', ip: '189.45.123.45', created_at: new Date(Date.now() - 3600000).toISOString() },
  { id: 4, user_id: 3, user_email: 'maria@empresa.com', action: 'login', ip: '201.23.45.67', created_at: new Date(Date.now() - 7200000).toISOString() },
  { id: 5, user_id: 3, user_email: 'maria@empresa.com', action: 'feature_used', ip: '201.23.45.67', created_at: new Date(Date.now() - 10800000).toISOString() },
  { id: 6, user_id: 4, user_email: 'pedro@empresa.com', action: 'login_failed', ip: '177.89.12.34', created_at: new Date(Date.now() - 14400000).toISOString() },
  { id: 7, user_id: 1, user_email: 'admin@painelcrm.com', action: 'license_blocked', ip: '192.168.1.100', created_at: new Date(Date.now() - 86400000).toISOString() },
  { id: 8, user_id: 2, user_email: 'joao@empresa.com', action: 'logout', ip: '189.45.123.45', created_at: new Date(Date.now() - 172800000).toISOString() },
];

const actionLabels: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
  login: { label: 'Login', variant: 'default' },
  logout: { label: 'Logout', variant: 'secondary' },
  admin_login: { label: 'Login Admin', variant: 'default' },
  login_failed: { label: 'Login Falhou', variant: 'destructive' },
  license_validated: { label: 'Licença Validada', variant: 'outline' },
  license_blocked: { label: 'Licença Bloqueada', variant: 'destructive' },
  feature_used: { label: 'Recurso Usado', variant: 'secondary' },
  user_created: { label: 'Usuário Criado', variant: 'default' },
  user_blocked: { label: 'Usuário Bloqueado', variant: 'destructive' },
};

export default function LogsPage() {
  const [logs, setLogs] = useState<Log[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterAction, setFilterAction] = useState<string>('all');
  const [filterDate, setFilterDate] = useState<string>('');

  useEffect(() => {
    fetchLogs();
  }, []);

  const fetchLogs = async () => {
    setIsLoading(true);
    try {
      const result = await logsApi.getAll();
      if (result.status && result.data) {
        setLogs(result.data);
      } else {
        setLogs(mockLogs);
      }
    } catch {
      setLogs(mockLogs);
    } finally {
      setIsLoading(false);
    }
  };

  const getActionBadge = (action: string) => {
    const config = actionLabels[action] || { label: action, variant: 'secondary' as const };
    return <Badge variant={config.variant}>{config.label}</Badge>;
  };

  const filteredLogs = logs.filter((log) => {
    const matchesSearch =
      log.user_email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      log.ip.includes(searchTerm);
    const matchesAction = filterAction === 'all' || log.action === filterAction;
    const matchesDate = !filterDate || log.created_at.startsWith(filterDate);
    return matchesSearch && matchesAction && matchesDate;
  });

  const uniqueActions = [...new Set(logs.map((log) => log.action))];

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
        <h1 className="text-2xl font-bold text-foreground flex items-center gap-2">
          <FileText className="w-6 h-6 text-primary" />
          Logs
        </h1>
        <p className="text-muted-foreground">Histórico de ações do sistema</p>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                placeholder="Buscar por email ou IP..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={filterAction} onValueChange={setFilterAction}>
              <SelectTrigger className="w-full md:w-48">
                <SelectValue placeholder="Ação" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todas as ações</SelectItem>
                {uniqueActions.map((action) => (
                  <SelectItem key={action} value={action}>
                    {actionLabels[action]?.label || action}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <div className="relative">
              <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                type="date"
                value={filterDate}
                onChange={(e) => setFilterDate(e.target.value)}
                className="pl-10 w-full md:w-48"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Logs Table */}
      <Card>
        <CardHeader>
          <CardTitle>
            {filteredLogs.length} registro{filteredLogs.length !== 1 ? 's' : ''} encontrado{filteredLogs.length !== 1 ? 's' : ''}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Data/Hora</TableHead>
                <TableHead>Usuário</TableHead>
                <TableHead>Ação</TableHead>
                <TableHead>IP</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredLogs.map((log) => (
                <TableRow key={log.id}>
                  <TableCell className="whitespace-nowrap">
                    {format(new Date(log.created_at), "dd/MM/yyyy HH:mm:ss", { locale: ptBR })}
                  </TableCell>
                  <TableCell>{log.user_email}</TableCell>
                  <TableCell>{getActionBadge(log.action)}</TableCell>
                  <TableCell className="font-mono text-sm">{log.ip}</TableCell>
                </TableRow>
              ))}
              {filteredLogs.length === 0 && (
                <TableRow>
                  <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                    Nenhum log encontrado
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
