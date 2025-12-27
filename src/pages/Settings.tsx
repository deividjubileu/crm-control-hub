import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { Settings as SettingsIcon, Globe, Shield, Bell, Loader2 } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

export default function SettingsPage() {
  const [settings, setSettings] = useState({
    apiUrl: 'https://painelcrm.fwh.is/api',
    tokenExpiry: '3600',
    maxDevices: '3',
    enableLogs: true,
    enableRateLimit: true,
    rateLimitRequests: '100',
    rateLimitWindow: '60',
    enableNotifications: true,
    notifyOnLogin: true,
    notifyOnBlock: true,
  });
  const [isSaving, setIsSaving] = useState(false);
  const { toast } = useToast();

  const handleSave = async () => {
    setIsSaving(true);
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000));
    toast({
      title: 'Configurações salvas!',
      description: 'As alterações foram aplicadas com sucesso.',
    });
    setIsSaving(false);
  };

  return (
    <div className="p-6 space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-foreground flex items-center gap-2">
          <SettingsIcon className="w-6 h-6 text-primary" />
          Configurações
        </h1>
        <p className="text-muted-foreground">Configure o comportamento do sistema</p>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {/* API Settings */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Globe className="w-5 h-5 text-primary" />
              API
            </CardTitle>
            <CardDescription>Configurações da API REST</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="apiUrl">URL Base da API</Label>
              <Input
                id="apiUrl"
                value={settings.apiUrl}
                onChange={(e) => setSettings({ ...settings, apiUrl: e.target.value })}
                placeholder="https://api.exemplo.com"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="tokenExpiry">Expiração do Token (segundos)</Label>
              <Input
                id="tokenExpiry"
                type="number"
                value={settings.tokenExpiry}
                onChange={(e) => setSettings({ ...settings, tokenExpiry: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="maxDevices">Máx. Dispositivos Padrão</Label>
              <Input
                id="maxDevices"
                type="number"
                value={settings.maxDevices}
                onChange={(e) => setSettings({ ...settings, maxDevices: e.target.value })}
              />
            </div>
          </CardContent>
        </Card>

        {/* Security Settings */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Shield className="w-5 h-5 text-primary" />
              Segurança
            </CardTitle>
            <CardDescription>Configurações de segurança e rate limit</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Habilitar Logs</Label>
                <p className="text-sm text-muted-foreground">
                  Registrar todas as ações do sistema
                </p>
              </div>
              <Switch
                checked={settings.enableLogs}
                onCheckedChange={(checked) => setSettings({ ...settings, enableLogs: checked })}
              />
            </div>
            <Separator />
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Rate Limit</Label>
                <p className="text-sm text-muted-foreground">
                  Limitar requisições por IP
                </p>
              </div>
              <Switch
                checked={settings.enableRateLimit}
                onCheckedChange={(checked) => setSettings({ ...settings, enableRateLimit: checked })}
              />
            </div>
            {settings.enableRateLimit && (
              <div className="grid grid-cols-2 gap-4 pt-2">
                <div className="space-y-2">
                  <Label htmlFor="rateLimitRequests">Requisições</Label>
                  <Input
                    id="rateLimitRequests"
                    type="number"
                    value={settings.rateLimitRequests}
                    onChange={(e) => setSettings({ ...settings, rateLimitRequests: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="rateLimitWindow">Janela (seg)</Label>
                  <Input
                    id="rateLimitWindow"
                    type="number"
                    value={settings.rateLimitWindow}
                    onChange={(e) => setSettings({ ...settings, rateLimitWindow: e.target.value })}
                  />
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Notification Settings */}
        <Card className="md:col-span-2">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Bell className="w-5 h-5 text-primary" />
              Notificações
            </CardTitle>
            <CardDescription>Configure alertas e notificações</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-3">
              <div className="flex items-center justify-between p-4 rounded-lg border border-border">
                <Label htmlFor="enableNotifications">Ativar Notificações</Label>
                <Switch
                  id="enableNotifications"
                  checked={settings.enableNotifications}
                  onCheckedChange={(checked) =>
                    setSettings({ ...settings, enableNotifications: checked })
                  }
                />
              </div>
              <div className="flex items-center justify-between p-4 rounded-lg border border-border">
                <Label htmlFor="notifyOnLogin">Alertar em Login</Label>
                <Switch
                  id="notifyOnLogin"
                  checked={settings.notifyOnLogin}
                  onCheckedChange={(checked) =>
                    setSettings({ ...settings, notifyOnLogin: checked })
                  }
                  disabled={!settings.enableNotifications}
                />
              </div>
              <div className="flex items-center justify-between p-4 rounded-lg border border-border">
                <Label htmlFor="notifyOnBlock">Alertar em Bloqueio</Label>
                <Switch
                  id="notifyOnBlock"
                  checked={settings.notifyOnBlock}
                  onCheckedChange={(checked) =>
                    setSettings({ ...settings, notifyOnBlock: checked })
                  }
                  disabled={!settings.enableNotifications}
                />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Save Button */}
      <div className="flex justify-end">
        <Button onClick={handleSave} disabled={isSaving} size="lg">
          {isSaving ? (
            <>
              <Loader2 className="w-4 h-4 mr-2 animate-spin" />
              Salvando...
            </>
          ) : (
            'Salvar Configurações'
          )}
        </Button>
      </div>
    </div>
  );
}
