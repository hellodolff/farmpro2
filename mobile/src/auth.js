import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import * as SecureStore from 'expo-secure-store';
import { api } from './api/client';

const AuthContext = createContext(null);
export const useAuth = () => useContext(AuthContext);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    SecureStore.getItemAsync('token').then((t) => { setToken(t); setReady(true); });
  }, []);

  const value = useMemo(() => ({
    token,
    signIn: async (email, password) => {
      const { data } = await api.post('/login', { email, password });
      await SecureStore.setItemAsync('token', data.token);
      setToken(data.token);
    },
    signOut: async () => {
      try { await api.post('/logout'); } catch (e) { /* token mungkin sudah kedaluwarsa */ }
      await SecureStore.deleteItemAsync('token');
      setToken(null);
    },
  }), [token]);

  if (!ready) return null;
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
