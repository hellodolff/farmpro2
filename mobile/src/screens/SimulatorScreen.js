import { useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { api, errorMessage } from '../api/client';
import { theme, num, rupiah } from '../theme';
import { Card, Row, Title, s } from '../ui';

// Nilai awal hanya contoh ilustrasi — ganti sesuai harga dan kondisi Anda.
const DEFAULTS = {
  population: '1000', doc_price: '7000', health_per_head: '1000', feed_kg_per_head: '3.2', feed_price: '8000',
  mortality_pct: '5', output_per_head: '2', sell_price: '21000', capex: '40000000', labor: '3000000', utilities: '1000000',
};
const LABELS = {
  population: 'Target populasi (ekor)', doc_price: 'Harga DOC/ekor (Rp)', health_per_head: 'Vaksin & obat/ekor (Rp)',
  feed_kg_per_head: 'Pakan/ekor (kg)', feed_price: 'Harga pakan/kg (Rp)', mortality_pct: 'Mortalitas asumsi (%)',
  output_per_head: 'Output panen/ekor (kg)', sell_price: 'Harga jual/kg (Rp)', capex: 'Investasi kandang & alat (Rp)',
  labor: 'Tenaga kerja/siklus (Rp)', utilities: 'Listrik & air/siklus (Rp)',
};
const NAMES = { conservative: 'Konservatif', base: 'Dasar', optimistic: 'Optimistis' };

export default function SimulatorScreen() {
  const [form, setForm] = useState(DEFAULTS);
  const [res, setRes] = useState(null);
  const [busy, setBusy] = useState(false);

  const run = async () => {
    setBusy(true);
    try {
      const body = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, Number(v)]));
      setRes((await api.post('/simulations/calculate', body)).data);
    } catch (e) { Alert.alert('Perhitungan gagal', errorMessage(e)); }
    finally { setBusy(false); }
  };

  return (
    <ScrollView style={s.screen} contentContainerStyle={s.pad} keyboardShouldPersistTaps="handled">
      <Text style={{ color: theme.muted, marginBottom: 12 }}>Estimasi berdasarkan asumsi Anda — bukan jaminan keuntungan.</Text>
      {Object.keys(DEFAULTS).map((k) => (
        <View key={k}>
          <Text style={s.label}>{LABELS[k]}</Text>
          <TextInput style={s.input} keyboardType="decimal-pad" value={form[k]} onChangeText={(v) => setForm((f) => ({ ...f, [k]: v }))} />
        </View>
      ))}
      <TouchableOpacity style={s.btn} onPress={run} disabled={busy}>
        {busy ? <ActivityIndicator color="#fff" /> : <Text style={s.btnText}>Hitung</Text>}
      </TouchableOpacity>

      {res && Object.entries(res.scenarios).map(([key, r]) => (
        <Card key={key} style={{ marginTop: 12 }}>
          <Title>Skenario {NAMES[key]}</Title>
          <Row label="Volume panen" value={`${num(r.volume)} kg`} />
          <Row label="Pendapatan" value={rupiah(r.revenue)} />
          <Row label="Biaya operasional" value={rupiah(r.opex)} />
          <Row label="Laba/rugi per siklus" value={rupiah(r.profit)} bold />
          <Row label="HPP per kg" value={r.hpp ? rupiah(r.hpp) : '-'} />
          <Row label="BEP" value={r.bep_volume ? `${num(r.bep_volume)} kg` : '-'} />
          <Row label="ROI" value={r.roi_pct != null ? `${r.roi_pct}%` : '-'} />
        </Card>
      ))}
    </ScrollView>
  );
}
