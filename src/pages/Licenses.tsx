import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Key, Plus, Pencil, Copy, Search, Loader2, Ban } from 'lucide-react';
import { licensesApi, usersApi, License, User } from '@/lib/api';
import { useToast } from '@/hooks/use-toast';
import { format } from 'date-fns';
import { ptBR } from 'date-fns/locale';

// Mock data
const mockLicenses: License[] = [
  {
    id: 1,
    license_key: 'XXXX-XXXX-XXXX-1234',
    user_id: 2,
    user_email: 'joao@empresa.com',
    status: 'active',
    expires_at: '2025-12-31T23:59:59Z',
    max_devices: 3,
    created_at: '2024-01-15T10:00:00Z',
  },
  {
    id: 2,
    license_key: 'XXXX-XXXX-XXXX-5678',
    user_id: 3,
    user_email: 'maria@empresa.com',
    status: 'active',
    expires_at: '2025-06-30T23:59:59Z',
    max_devices: 2,
    created_at: '2024-02-20T14:30:00Z',
  },
  {
    id: 3,
    license_key: 'XXXX-XXXX-XXXX-9012',
    user_id: 4,
    user_email: 'pedro@empresa.com',
    status: 'expired',
    expires_at: '2024-01-01T23:59:59Z',
    max_devices: 1,
    created_at: '2023-01-01T09:00:00Z',
  },
];

const mockUsers: User[] = [
  { id: 2, email: 'joao@empresa.com', role: 'user', status: 'active', created_at: '' },
  { id: 3, email: 'maria@empresa.com', role: 'user', status: 'active', created_at: '' },
  { id: 4, email: 'pedro@empresa.com', role: 'user', status: 'blocked', created_at: '' },
];

export default function LicensesPage() {
  const [licenses, setLicenses] = useState<License[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingLicense, setEditingLicense] = useState<License | null>(null);
  const [formData, setFormData] = useState({
    user_id: '',
    expires_at: '',
    max_devices: '3',
    status: 'active' as 'active' | 'expired' | 'blocked',
  });
  const [isSaving, setIsSaving] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setIsLoading(true);
    try {
      const [licensesRes, usersRes] = await Promise.all([
        licensesApi.getAll(),
        usersApi.getAll(),
      ]);

      if (licensesRes.status && licensesRes.data) {
        setLicenses(licensesRes.data);
      } else {
        setLicenses(mockLicenses);
      }

      if (usersRes.status && usersRes.data) {
        setUsers(usersRes.data);
      } else {
        setUsers(mockUsers);
      }
    } catch {
      setLicenses(mockLicenses);
      setUsers(mockUsers);
    } finally {
      setIsLoading(false);
    }
  };

  const handleOpenDialog = (license?: License) => {
    if (license) {
      setEditingLicense(license);
      setFormData({
        user_id: license.user_id.toString(),
        expires_at: license.expires_at.split('T')[0],
        max_devices: license.max_devices.toString(),
        status: license.status,
      });
    } else {
      setEditingLicense(null);
      const nextYear = new Date();
      nextYear.setFullYear(nextYear.getFullYear() + 1);
      setFormData({
        user_id: '',
        expires_at: nextYear.toISOString().split('T')[0],
        max_devices: '3',
        status: 'active',
      });
    }
    setIsDialogOpen(true);
  };

  const handleSave = async () => {
    if (!formData.user_id || !formData.expires_at) {
      toast({
        title: 'Erro',
        description: 'Usuário e data de expiração são obrigatórios',
        variant: 'destructive',
      });
      return;
    }

    setIsSaving(true);
    try {
      let result;
      if (editingLicense) {
        result = await licensesApi.update(editingLicense.id, {
          expires_at: formData.expires_at,
          max_devices: parseInt(formData.max_devices),
          status: formData.status,
        });
      } else {
        result = await licensesApi.generate({
          user_id: parseInt(formData.user_id),
          expires_at: formData.expires_at,
          max_devices: parseInt(formData.max_devices),
        });
      }

      if (result.status) {
        toast({
          title: editingLicense ? 'Licença atualizada!' : 'Licença gerada!',
          description: result.data?.license_key
            ? `Chave: ${result.data.license_key}`
            : 'Operação realizada com sucesso',
        });
        setIsDialogOpen(false);
        fetchData();
      } else {
        toast({
          title: 'Erro',
          description: result.message || 'Falha ao salvar licença',
          variant: 'destructive',
        });
      }
    } catch {
      // Demo mode
      const selectedUser = users.find(u => u.id.toString() === formData.user_id);
      const newKey = `XXXX-XXXX-XXXX-${Math.random().toString(36).substring(2, 6).toUpperCase()}`;
      
      if (editingLicense) {
        setLicenses(licenses.map(l => 
          l.id === editingLicense.id 
            ? { ...l, expires_at: formData.expires_at + 'T23:59:59Z', max_devices: parseInt(formData.max_devices), status: formData.status }
            : l
        ));
      } else {
        const newLicense: License = {
          id: licenses.length + 1,
          license_key: newKey,
          user_id: parseInt(formData.user_id),
          user_email: selectedUser?.email,
          status: 'active',
          expires_at: formData.expires_at + 'T23:59:59Z',
          max_devices: parseInt(formData.max_devices),
          created_at: new Date().toISOString(),
        };
        setLicenses([...licenses, newLicense]);
      }
      
      toast({
        title: editingLicense ? 'Licença atualizada!' : 'Licença gerada!',
        description: `Chave: ${editingLicense?.license_key || newKey} (demo)`,
      });
      setIsDialogOpen(false);
    } finally {
      setIsSaving(false);
    }
  };

  const handleCopyKey = (key: string) => {
    navigator.clipboard.writeText(key);
    toast({
      title: 'Copiado!',
      description: 'Chave copiada para a área de transferência',
    });
  };

  const handleToggleStatus = async (license: License) => {
    const newStatus = license.status === 'blocked' ? 'active' : 'blocked';
    try {
      await licensesApi.update(license.id, { status: newStatus });
      fetchData();
    } catch {
      setLicenses(licenses.map(l => l.id === license.id ? { ...l, status: newStatus } : l));
    }
    toast({
      title: 'Status atualizado!',
      description: `Licença ${newStatus === 'active' ? 'ativada' : 'bloqueada'}`,
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge className="bg-success hover:bg-success/90">Ativa</Badge>;
      case 'expired':
        return <Badge variant="secondary" className="bg-warning hover:bg-warning/90">Expirada</Badge>;
      case 'blocked':
        return <Badge variant="destructive">Bloqueada</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const filteredLicenses = licenses.filter((license) => {
    const matchesSearch =
      license.license_key.toLowerCase().includes(searchTerm.toLowerCase()) ||
      license.user_email?.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === 'all' || license.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

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
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-foreground flex items-center gap-2">
            <Key className="w-6 h-6 text-primary" />
            Licenças
          </h1>
          <p className="text-muted-foreground">Gerencie as licenças da extensão</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button onClick={() => handleOpenDialog()}>
              <Plus className="w-4 h-4 mr-2" />
              Nova Licença
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>
                {editingLicense ? 'Editar Licença' : 'Gerar Nova Licença'}
              </DialogTitle>
              <DialogDescription>
                {editingLicense
                  ? 'Atualize as configurações da licença'
                  : 'Configure os parâmetros para gerar uma nova licença'}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 mt-4">
              <div className="space-y-2">
                <Label>Usuário</Label>
                <Select
                  value={formData.user_id}
                  onValueChange={(value) => setFormData({ ...formData, user_id: value })}
                  disabled={!!editingLicense}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Selecione um usuário" />
                  </SelectTrigger>
                  <SelectContent>
                    {users.filter(u => u.role === 'user').map((user) => (
                      <SelectItem key={user.id} value={user.id.toString()}>
                        {user.email}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="expires">Data de Expiração</Label>
                <Input
                  id="expires"
                  type="date"
                  value={formData.expires_at}
                  onChange={(e) => setFormData({ ...formData, expires_at: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="devices">Máximo de Dispositivos</Label>
                <Input
                  id="devices"
                  type="number"
                  min="1"
                  max="10"
                  value={formData.max_devices}
                  onChange={(e) => setFormData({ ...formData, max_devices: e.target.value })}
                />
              </div>
              {editingLicense && (
                <div className="space-y-2">
                  <Label>Status</Label>
                  <Select
                    value={formData.status}
                    onValueChange={(value: 'active' | 'expired' | 'blocked') =>
                      setFormData({ ...formData, status: value })
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">Ativa</SelectItem>
                      <SelectItem value="expired">Expirada</SelectItem>
                      <SelectItem value="blocked">Bloqueada</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              )}
              <Button onClick={handleSave} className="w-full" disabled={isSaving}>
                {isSaving ? (
                  <>
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                    Salvando...
                  </>
                ) : editingLicense ? (
                  'Atualizar'
                ) : (
                  'Gerar Licença'
                )}
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                placeholder="Buscar por chave ou email..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger className="w-full sm:w-40">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos</SelectItem>
                <SelectItem value="active">Ativas</SelectItem>
                <SelectItem value="expired">Expiradas</SelectItem>
                <SelectItem value="blocked">Bloqueadas</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Licenses Table */}
      <Card>
        <CardHeader>
          <CardTitle>
            {filteredLicenses.length} licença{filteredLicenses.length !== 1 ? 's' : ''} encontrada{filteredLicenses.length !== 1 ? 's' : ''}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Chave</TableHead>
                <TableHead>Usuário</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Expira em</TableHead>
                <TableHead>Dispositivos</TableHead>
                <TableHead className="text-right">Ações</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredLicenses.map((license) => (
                <TableRow key={license.id}>
                  <TableCell className="font-mono text-sm">
                    <div className="flex items-center gap-2">
                      {license.license_key}
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6"
                        onClick={() => handleCopyKey(license.license_key)}
                      >
                        <Copy className="w-3 h-3" />
                      </Button>
                    </div>
                  </TableCell>
                  <TableCell>{license.user_email}</TableCell>
                  <TableCell>{getStatusBadge(license.status)}</TableCell>
                  <TableCell>
                    {format(new Date(license.expires_at), "dd/MM/yyyy", { locale: ptBR })}
                  </TableCell>
                  <TableCell>{license.max_devices}</TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-2">
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleOpenDialog(license)}
                      >
                        <Pencil className="w-4 h-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleToggleStatus(license)}
                        className={license.status !== 'blocked' ? 'hover:text-destructive' : 'hover:text-success'}
                      >
                        <Ban className="w-4 h-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
              {filteredLicenses.length === 0 && (
                <TableRow>
                  <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                    Nenhuma licença encontrada
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
