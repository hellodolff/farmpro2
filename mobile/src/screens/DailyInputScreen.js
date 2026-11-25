import { Fragment, useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Platform, ScrollView, Text, TextInput, TouchableOpacity } from 'react-native';
import { api, errorMessage } from '../api/client';
import { s } from '../ui';

const today = () => new Date().toISOString().slice(0, 10);

const FIELDS = [
  ['deaths', 'Kematian (ekor)'], ['culls', 'Afkir (ekor)'], ['feed_kg', 'Pakan terpakai (kg)'],
  ['avg_weight_g', 'Bobot rata-rata sampel (gram)'], ['sample_count', 'Jumlah sampel'],
];
const EGG_FIELDS = [
  ['total_eggs', 'Telur total (butir)'], ['sellable_eggs', 'Telur layak jual'], ['cracked_eggs', 'Retak/pecah'],
  ['dirty_eggs', 'Kotor'], ['reject_eggs', 'Afkir'], ['egg_weight_kg', 'Berat telur (kg)'],
];

export default function DailyInputScreen({ route, navigation }) {
  const { id, isLayer } = route.params;
  const [form, setForm] = useState({ record_date: today() });
  const [busy, setBusy] = useState(false);
  const set = (k) => (v) => setForm((f) => ({ ...f, [k]: v }));

  const submit = async () => {
    setBusy(true);
    try {
      // Kirim hanya kolom yang diisi
      const body = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== '' && v != null));
      await api.post(`/batches/${id}/daily-records`, body);
      Alert.alert('Tersimpan', 'Catatan harian berhasil disimpan.');
      navigation.goBack();
    } catch (e) {
      Alert.alert('Tidak dapat menyimpan', errorMessage(e));
    } finally { setBusy(false); }
  };

  const field = ([k, label]) => (
    <Fragment key={k}>
      <Text style={s.label}>{label}</Text>
      <TextInput style={s.input} keyboardType="decimal-pad" value={form[k] ?? ''} onChangeText={set(k)} />
    </Fragment>
  );

  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView style={s.screen} contentContainerStyle={s.pad} keyboardShouldPersistTaps="handled">
        <Text style={s.label}>Tanggal (YYYY-MM-DD)</Text>
        <TextInput style={s.input} value={form.record_date} onChangeText={set('record_date')} />
        {FIELDS.map(field)}
        {isLayer && EGG_FIELDS.map(field)}
        <Text style={s.label}>Catatan</Text>
        <TextInput style={[s.input, { height: 80 }]} multiline value={form.notes ?? ''} onChangeText={set('notes')} />
        <TouchableOpacity style={s.btn} onPress={submit} disabled={busy}>
          {busy ? <ActivityIndicator color="#fff" /> : <Text style={s.btnText}>Simpan</Text>}
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
