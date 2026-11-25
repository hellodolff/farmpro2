import { StyleSheet, Text, View } from 'react-native';
import { theme } from './theme';

export function Card({ children, style }) {
  return <View style={[s.card, style]}>{children}</View>;
}

export function Kpi({ label, value, sub, bg = theme.mint }) {
  return (
    <View style={[s.kpi, { backgroundColor: bg }]}>
      <Text style={s.kpiLabel}>{label}</Text>
      <Text style={s.kpiValue} numberOfLines={1} adjustsFontSizeToFit>{value}</Text>
      {sub ? <Text style={s.kpiSub}>{sub}</Text> : null}
    </View>
  );
}

export function Title({ children }) {
  return <Text style={s.title}>{children}</Text>;
}

export function Row({ label, value, bold }) {
  return (
    <View style={s.row}>
      <Text style={s.rowLabel}>{label}</Text>
      <Text style={[s.rowValue, bold && { fontWeight: '700' }]}>{value ?? '-'}</Text>
    </View>
  );
}

export const s = StyleSheet.create({
  card: { backgroundColor: theme.card, borderRadius: 16, padding: 16, marginBottom: 12, borderWidth: 1, borderColor: theme.border },
  kpi: { flexBasis: '48%', flexGrow: 1, borderRadius: 16, padding: 14 },
  kpiLabel: { color: theme.muted, fontSize: 12 },
  kpiValue: { color: theme.text, fontSize: 20, fontWeight: '700', marginTop: 4 },
  kpiSub: { color: theme.muted, fontSize: 12, marginTop: 2 },
  title: { fontSize: 16, fontWeight: '700', color: theme.text, marginBottom: 10 },
  row: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6 },
  rowLabel: { color: theme.muted },
  rowValue: { color: theme.text },
  input: { borderWidth: 1, borderColor: theme.border, borderRadius: 10, paddingHorizontal: 12, paddingVertical: 10, backgroundColor: '#fff', color: theme.text, marginBottom: 10 },
  label: { fontSize: 12, color: theme.muted, marginBottom: 4 },
  btn: { backgroundColor: theme.primary, borderRadius: 12, paddingVertical: 14, alignItems: 'center', marginTop: 4 },
  btnText: { color: '#fff', fontWeight: '700' },
  screen: { flex: 1, backgroundColor: theme.bg },
  pad: { padding: 16 },
});
