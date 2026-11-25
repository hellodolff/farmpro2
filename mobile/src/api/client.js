import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

export const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://10.0.2.2:8000/api';

export const api = axios.create({ baseURL: API_URL, timeout: 15000, headers: { Accept: 'application/json' } });

api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

/** Ubah error Laravel (422/401/jaringan) menjadi satu pesan yang bisa dibaca pengguna. */
export function errorMessage(e) {
  const data = e?.response?.data;
  if (data?.errors) return Object.values(data.errors).flat().join('\n');
  if (data?.message) return data.message;
  if (!e?.response) return 'Tidak dapat terhubung ke server. Periksa alamat API dan koneksi Anda.';
  return 'Terjadi kesalahan. Coba lagi.';
}
