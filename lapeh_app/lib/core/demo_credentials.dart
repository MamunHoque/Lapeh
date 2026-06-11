class DemoAccount {
  final String label;
  final String phone;
  final String password;
  final bool isSender;

  const DemoAccount({
    required this.label,
    required this.phone,
    required this.password,
    required this.isSender,
  });
}

/// Seeded accounts from TEST_CREDENTIALS.md — for local / debug builds only.
const demoAccounts = [
  DemoAccount(
    label: 'Sender · Mariam',
    phone: '+971501111111',
    password: 'sender1234',
    isSender: true,
  ),
  DemoAccount(
    label: 'Sender · Omar (business)',
    phone: '+971501111112',
    password: 'sender1234',
    isSender: true,
  ),
  DemoAccount(
    label: 'Driver · Bilal (online)',
    phone: '+971502222222',
    password: 'driver1234',
    isSender: false,
  ),
  DemoAccount(
    label: 'Driver · Karim (offline)',
    phone: '+971503333333',
    password: 'driver1234',
    isSender: false,
  ),
];
