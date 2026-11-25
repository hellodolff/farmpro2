import { Ionicons } from '@expo/vector-icons';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Text } from 'react-native';
import { useAuth } from '../auth';
import { theme } from '../theme';
import BatchDetailScreen from '../screens/BatchDetailScreen';
import BatchListScreen from '../screens/BatchListScreen';
import DailyInputScreen from '../screens/DailyInputScreen';
import DashboardScreen from '../screens/DashboardScreen';
import InventoryScreen from '../screens/InventoryScreen';
import LoginScreen from '../screens/LoginScreen';
import SimulatorScreen from '../screens/SimulatorScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();
const header = { headerStyle: { backgroundColor: theme.dark }, headerTintColor: '#fff' };
const ICONS = { Dashboard: 'home', Batch: 'layers', Stok: 'cube', Simulasi: 'calculator' };

function Tabs() {
  const { signOut } = useAuth();
  return (
    <Tab.Navigator screenOptions={({ route }) => ({
      ...header,
      tabBarActiveTintColor: theme.primary,
      tabBarIcon: ({ color, size }) => <Ionicons name={ICONS[route.name]} size={size} color={color} />,
      headerRight: () => <Text onPress={signOut} style={{ color: '#fff', marginRight: 16 }}>Keluar</Text>,
    })}>
      <Tab.Screen name="Dashboard" component={DashboardScreen} />
      <Tab.Screen name="Batch" component={BatchListScreen} options={{ title: 'Batch Ternak' }} />
      <Tab.Screen name="Stok" component={InventoryScreen} options={{ title: 'Inventaris' }} />
      <Tab.Screen name="Simulasi" component={SimulatorScreen} options={{ title: 'Kalkulator Anggaran' }} />
    </Tab.Navigator>
  );
}

export default function RootNavigator() {
  const { token } = useAuth();
  return (
    <Stack.Navigator screenOptions={header}>
      {token ? (
        <>
          <Stack.Screen name="Home" component={Tabs} options={{ headerShown: false }} />
          <Stack.Screen name="BatchDetail" component={BatchDetailScreen} options={{ title: 'Detail Batch' }} />
          <Stack.Screen name="DailyInput" component={DailyInputScreen} options={{ title: 'Input Harian' }} />
        </>
      ) : (
        <Stack.Screen name="Login" component={LoginScreen} options={{ headerShown: false }} />
      )}
    </Stack.Navigator>
  );
}
