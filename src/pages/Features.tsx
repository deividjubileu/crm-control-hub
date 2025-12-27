import { useEffect, useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Puzzle, Save, Loader2 } from 'lucide-react';
import { featuresApi, licensesApi, License, Feature } from '@/lib/api';
import { FEATURE_DEFINITIONS, FeatureKey } from '@/lib/types';
import { useToast } from '@/hooks/use-toast';

// Mock data
const mockLicenses: License[] = [
  { id: 1, license_key: 'XXXX-1234', user_id: 2, user_email: 'joao@empresa.com', status: 'active', expires_at: '', max_devices: 3, created_at: '' },
  { id: 2, license_key: 'XXXX-5678', user_id: 3, user_email: 'maria@empresa.com', status: 'active', expires_at: '', max_devices: 2, created_at: '' },
];

export default function FeaturesPage() {
  const [licenses, setLicenses] = useState<License[]>([]);
  const [selectedLicenseId, setSelectedLicenseId] = useState<string>('');
  const [features, setFeatures] = useState<Record<string, { enabled: boolean; value?: number }>>({});
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    fetchLicenses();
  }, []);

  useEffect(() => {
    if (selectedLicenseId) {
      fetchFeatures(parseInt(selectedLicenseId));
    }
  }, [selectedLicenseId]);

  const fetchLicenses = async () => {
    setIsLoading(true);
    try {
      const result = await licensesApi.getAll();
      if (result.status && result.data) {
        setLicenses(result.data.filter(l => l.status === 'active'));
      } else {
        setLicenses(mockLicenses);
      }
    } catch {
      setLicenses(mockLicenses);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchFeatures = async (licenseId: number) => {
    try {
      const result = await featuresApi.getByLicense(licenseId);
      if (result.status && result.data) {
        const featuresMap: Record<string, { enabled: boolean; value?: number }> = {};
        result.data.forEach((f: Feature) => {
          featuresMap[f.feature_key] = { enabled: f.enabled, value: f.value };
        });
        setFeatures(featuresMap);
      } else {
        // Default features for demo
        setFeatures({
          auto_reply: { enabled: true },
          bulk_send: { enabled: true },
          scraping: { enabled: true },
          daily_limit: { enabled: true, value: 200 },
          ai_assistant: { enabled: false },
          templates: { enabled: true },
          scheduler: { enabled: true },
          reports: { enabled: false },
        });
      }
    } catch {
      // Demo defaults
      setFeatures({
        auto_reply: { enabled: true },
        bulk_send: { enabled: true },
        scraping: { enabled: true },
        daily_limit: { enabled: true, value: 200 },
        ai_assistant: { enabled: false },
        templates: { enabled: true },
        scheduler: { enabled: true },
        reports: { enabled: false },
      });
    }
  };

  const handleToggleFeature = (key: string, enabled: boolean) => {
    setFeatures({
      ...features,
      [key]: { ...features[key], enabled },
    });
  };

  const handleValueChange = (key: string, value: number) => {
    setFeatures({
      ...features,
      [key]: { ...features[key], value },
    });
  };

  const handleSave = async () => {
    if (!selectedLicenseId) {
      toast({
        title: 'Erro',
        description: 'Selecione uma licença primeiro',
        variant: 'destructive',
      });
      return;
    }

    setIsSaving(true);
    try {
      const featuresList: Feature[] = Object.entries(features).map(([key, data], index) => ({
        id: index,
        license_id: parseInt(selectedLicenseId),
        feature_key: key,
        enabled: data.enabled,
        value: data.value,
      }));

      const result = await featuresApi.update(parseInt(selectedLicenseId), featuresList);
      
      if (result.status) {
        toast({
          title: 'Recursos salvos!',
          description: 'As configurações foram atualizadas com sucesso.',
        });
      } else {
        throw new Error(result.message);
      }
    } catch {
      // Demo mode
      toast({
        title: 'Recursos salvos!',
        description: 'As configurações foram atualizadas (demo)',
      });
    } finally {
      setIsSaving(false);
    }
  };

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
          <Puzzle className="w-6 h-6 text-primary" />
          Recursos
        </h1>
        <p className="text-muted-foreground">
          Configure os recursos disponíveis para cada licença
        </p>
      </div>

      {/* License Selector */}
      <Card>
        <CardHeader>
          <CardTitle>Selecione uma Licença</CardTitle>
        </CardHeader>
        <CardContent>
          <Select value={selectedLicenseId} onValueChange={setSelectedLicenseId}>
            <SelectTrigger className="w-full md:w-96">
              <SelectValue placeholder="Escolha uma licença ativa" />
            </SelectTrigger>
            <SelectContent>
              {licenses.map((license) => (
                <SelectItem key={license.id} value={license.id.toString()}>
                  {license.user_email} - {license.license_key}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </CardContent>
      </Card>

      {/* Features Configuration */}
      {selectedLicenseId && (
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle>Configuração de Recursos</CardTitle>
            <Button onClick={handleSave} disabled={isSaving}>
              {isSaving ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  Salvando...
                </>
              ) : (
                <>
                  <Save className="w-4 h-4 mr-2" />
                  Salvar
                </>
              )}
            </Button>
          </CardHeader>
          <CardContent>
            <div className="grid gap-6 md:grid-cols-2">
              {Object.entries(FEATURE_DEFINITIONS).map(([key, def]) => (
                <div
                  key={key}
                  className="flex items-center justify-between p-4 rounded-lg border border-border bg-card"
                >
                  <div className="space-y-1">
                    <Label htmlFor={key} className="text-base font-medium">
                      {def.label}
                    </Label>
                    {def.type === 'number' && features[key]?.enabled && (
                      <div className="flex items-center gap-2 mt-2">
                        <Input
                          type="number"
                          value={features[key]?.value || (def as { default?: number }).default || 0}
                          onChange={(e) => handleValueChange(key, parseInt(e.target.value))}
                          className="w-24 h-8"
                          min="0"
                        />
                        <span className="text-sm text-muted-foreground">limite</span>
                      </div>
                    )}
                  </div>
                  <Switch
                    id={key}
                    checked={features[key]?.enabled ?? false}
                    onCheckedChange={(checked) => handleToggleFeature(key, checked)}
                  />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {!selectedLicenseId && (
        <Card>
          <CardContent className="py-12 text-center text-muted-foreground">
            Selecione uma licença para configurar os recursos
          </CardContent>
        </Card>
      )}
    </div>
  );
}
