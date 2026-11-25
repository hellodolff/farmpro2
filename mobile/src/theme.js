export const theme = {
  primary: '#237a4f',
  dark: '#103a27',
  bg: '#f4f8f5',
  card: '#ffffff',
  text: '#0f172a',
  muted: '#64748b',
  border: '#e2e8f0',
  mint: '#e8f6ef',
  sky: '#e6f3fb',
  peach: '#fdf1e0',
  danger: '#dc2626',
  warn: '#b45309',
};

export const rupiah = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
export const num = (v) => Number(v || 0).toLocaleString('id-ID');
