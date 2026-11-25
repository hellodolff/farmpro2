import { useCallback, useState } from 'react';
import { FlatList, RefreshControl, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, errorMessage } from '../api/client';
import { theme, num } from '../theme';
import { s } from '../ui';

const STATUS = { active: 'Aktif', completed: 'Selesai', planned: 'Direncanakan', cancelled: 'Dibatalkan' };

export default function BatchListScreen({ navigation }) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try { setErr(null); setItems((await api.get('/batches')).data); }
    catch (e) { setErr(errorMessage(e)); }
    finally { setLoading(false); }
  }, []);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  return (
    <View style={s.screen}>
      {err && <Text style={{ color: theme.danger, padding: 16 }}>{err}</Text>}
      <FlatList
        data={items}
        keyExtractor={(b) => String(b.id)}
        contentContainerStyle={s.pad}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}
        ListEmptyComponent={!loading && <Text style={{ color: theme.muted, textAlign: 'center', marginTop: 40 }}>Belum ada batch.</Text>}
        renderItem={({ item: b }) => (
          <TouchableOpacity style={s.card} onPress={() => navigation.navigate('BatchDetail', { id: b.id })}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
              <Text style={{ fontWeight: '700', color: theme.text, fontSize: 16 }}>{b.code}</Text>
              <Text style={{ color: b.status === 'active' ? theme.primary : theme.muted, fontWeight: '600' }}>{STATUS[b.status]}</Text>
            </View>
            <Text style={{ color: theme.muted, marginTop: 4 }}>{b.commodity?.name} • {b.coop?.name} • umur {b.age_days} hari</Text>
            <Text style={{ color: theme.text, marginTop: 4 }}>{num(b.current_population)} / {num(b.initial_population)} ekor</Text>
          </TouchableOpacity>
        )}
      />
    </View>
  );
}
