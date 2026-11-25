import { useCallback, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, errorMessage } from '../api/client';
import { theme, num, rupiah } from '../theme';
import { Card, Kpi, Title, s } from '../ui';

export default function DashboardScreen({ navigation }) {
  const [d, setD] = useState(null);
  const [err, setErr] = useState(null);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    try { setErr(null); setD((await api.get('/dashboard')).data); }
    catch (e) { setErr(errorMessage(e)); }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  if (err) return <View style={[s.screen, s.pad]}><Text style={{ color: theme.danger }}>{err}</Text></View>;
  if (!d) return <View style={[s.screen, { justifyContent: 'center' }]}><ActivityIndicator color={theme.primary} /></View>;

  const k = d.kpi;
  return (
    <ScrollView style={s.screen} contentContainerStyle={s.pad}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={async () => { setRefreshing(true); await load(); setRefreshing(false); }} />}>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 12 }}>
        <Kpi label="Total ayam hidup" value={`${num(k.live_population)} ekor`} sub={`${k.commodity_count} komoditas`} />
        <Kpi label="Batch aktif" value={`${k.active_batches} batch`} bg={theme.sky} />
        <Kpi label="Pendapatan bulan ini" value={rupiah(k.income)} />
        <Kpi label="Pengeluaran bulan ini" value={rupiah(k.expense)} bg={theme.peach} />
      </View>

      <Card>
        <Title>Populasi per komoditas</Title>
        {d.population_by_commodity.map((p) => (
          <View key={p.name} style={{ marginBottom: 10 }}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
              <Text style={{ color: theme.text }}>{p.name}</Text><Text style={{ color: theme.muted }}>{num(p.total)} • {p.pct}%</Text>
            </View>
            <View style={{ height: 8, borderRadius: 4, backgroundColor: theme.mint, marginTop: 4 }}>
              <View style={{ width: `${p.pct}%`, height: 8, borderRadius: 4, backgroundColor: theme.primary }} />
            </View>
          </View>
        ))}
      </Card>

      {k.low_stock > 0 && (
        <Card style={{ backgroundColor: theme.peach, borderColor: '#f6d9a8' }}>
          <Text style={{ fontWeight: '700', color: theme.warn }}>Perlu diperhatikan</Text>
          <Text style={{ color: theme.text, marginTop: 4 }}>{k.low_stock} barang mendekati batas stok minimum.</Text>
        </Card>
      )}

      <Card>
        <Title>Batch aktif</Title>
        {d.batches.map((b) => (
          <Text key={b.id} onPress={() => navigation.navigate('BatchDetail', { id: b.id })} style={{ paddingVertical: 8, color: theme.text }}>
            <Text style={{ fontWeight: '700' }}>{b.code}</Text>  {b.commodity} • {b.coop} • {num(b.population)} ekor • {b.age_days} hari
          </Text>
        ))}
      </Card>

      <Card>
        <Title>Jadwal mendatang</Title>
        {d.schedules.length === 0 && <Text style={{ color: theme.muted }}>Tidak ada jadwal mendatang.</Text>}
        {d.schedules.map((x, i) => (
          <View key={i} style={{ paddingVertical: 6 }}>
            <Text style={{ fontWeight: '600', color: theme.text }}>{x.title} – {x.sub}</Text>
            <Text style={{ color: theme.muted, fontSize: 12 }}>{x.due}</Text>
          </View>
        ))}
      </Card>
      <Text style={{ color: theme.muted, fontSize: 11, textAlign: 'center' }}>Angka berasal dari transaksi yang tercatat.</Text>
    </ScrollView>
  );
}
