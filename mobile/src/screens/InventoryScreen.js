import { useCallback, useState } from 'react';
import { FlatList, RefreshControl, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, errorMessage } from '../api/client';
import { theme, num } from '../theme';
import { s } from '../ui';

const CAT = { feed: 'Pakan', vaccine: 'Vaksin', medicine: 'Obat', vitamin: 'Vitamin', sanitation: 'Sanitasi', equipment: 'Peralatan' };

export default function InventoryScreen() {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try { setErr(null); setItems((await api.get('/inventory/items')).data); }
    catch (e) { setErr(errorMessage(e)); }
    finally { setLoading(false); }
  }, []);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  return (
    <View style={s.screen}>
      {err && <Text style={{ color: theme.danger, padding: 16 }}>{err}</Text>}
      <FlatList
        data={items}
        keyExtractor={(i) => String(i.id)}
        contentContainerStyle={s.pad}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}
        renderItem={({ item: i }) => (
          <View style={[s.card, i.is_low && { borderColor: '#f6d9a8', backgroundColor: theme.peach }]}>
            <Text style={{ fontWeight: '700', color: theme.text }}>{i.name}</Text>
            <Text style={{ color: theme.muted }}>{CAT[i.category]} • minimum {num(i.min_stock)} {i.base_unit}</Text>
            <Text style={{ marginTop: 4, color: i.is_low ? theme.warn : theme.text, fontWeight: '600' }}>
              Stok {num(i.stock)} {i.base_unit}{i.is_low ? '  • mendekati batas minimum' : ''}
            </Text>
          </View>
        )}
      />
    </View>
  );
}
