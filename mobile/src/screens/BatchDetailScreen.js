import { useCallback, useState } from 'react';
import { ActivityIndicator, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, errorMessage } from '../api/client';
import { theme, num } from '../theme';
import { Card, Row, Title, s } from '../ui';

export default function BatchDetailScreen({ route, navigation }) {
  const { id } = route.params;
  const [data, setData] = useState(null);
  const [err, setErr] = useState(null);

  useFocusEffect(useCallback(() => {
    api.get(`/batches/${id}`).then((r) => setData(r.data)).catch((e) => setErr(errorMessage(e)));
  }, [id]));

  if (err) return <View style={[s.screen, s.pad]}><Text style={{ color: theme.danger }}>{err}</Text></View>;
  if (!data) return <View style={[s.screen, { justifyContent: 'center' }]}><ActivityIndicator color={theme.primary} /></View>;

  const { batch: b, performance: p } = data;
  const isLayer = b.commodity?.code === 'layer';

  return (
    <ScrollView style={s.screen} contentContainerStyle={s.pad}>
      <Card>
        <Title>{b.code} • {b.commodity?.name}</Title>
        <Row label="Kandang" value={b.coop?.name} />
        <Row label="Umur" value={`${b.age_days} hari`} />
        <Row label="Populasi" value={`${num(b.current_population)} dari ${num(b.initial_population)} ekor`} />
      </Card>

      <Card>
        <Title>Performa</Title>
        <Row label="Mortalitas" value={`${p.mortality_pct}%`} />
        <Row label="Livability" value={`${p.livability_pct}%`} />
        <Row label="Total pakan" value={`${num(p.feed_kg)} kg`} />
        <Row label="FCR" value={p.fcr} bold />
        {isLayer ? <Row label="HDP terakhir" value={p.hdp_pct != null ? `${p.hdp_pct}%` : '-'} bold />
                 : <><Row label="Bobot rata-rata" value={p.avg_weight_g ? `${p.avg_weight_g} g` : '-'} /><Row label="IP" value={p.ip} bold /></>}
      </Card>

      {b.status === 'active' && (
        <TouchableOpacity style={s.btn} onPress={() => navigation.navigate('DailyInput', { id: b.id, code: b.code, isLayer })}>
          <Text style={s.btnText}>Input harian</Text>
        </TouchableOpacity>
      )}
    </ScrollView>
  );
}
