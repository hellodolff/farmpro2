import { useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { useAuth } from '../auth';
import { errorMessage } from '../api/client';
import { theme } from '../theme';
import { s } from '../ui';

export default function LoginScreen() {
  const { signIn } = useAuth();
  const [email, setEmail] = useState('admin@farmpro.test');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    setBusy(true);
    try { await signIn(email.trim(), password); }
    catch (e) { Alert.alert('Gagal masuk', errorMessage(e)); }
    finally { setBusy(false); }
  };

  return (
    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1, backgroundColor: theme.dark, justifyContent: 'center', padding: 24 }}>
      <Text style={{ fontSize: 48, textAlign: 'center' }}>🐔</Text>
      <Text style={{ color: '#fff', fontSize: 32, fontWeight: '700', textAlign: 'center' }}>FarmPro</Text>
      <Text style={{ color: '#bfe5d0', textAlign: 'center', marginBottom: 28 }}>Poultry Management</Text>
      <View style={{ backgroundColor: '#fff', borderRadius: 20, padding: 20 }}>
        <Text style={s.label}>Email</Text>
        <TextInput style={s.input} value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
        <Text style={s.label}>Kata sandi</Text>
        <TextInput style={s.input} value={password} onChangeText={setPassword} secureTextEntry />
        <TouchableOpacity style={s.btn} onPress={submit} disabled={busy}>
          {busy ? <ActivityIndicator color="#fff" /> : <Text style={s.btnText}>Masuk</Text>}
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
}
