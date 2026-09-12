import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'core/api_client.dart';
import 'core/auth_state.dart';
import 'payments/opay_checkout_page.dart';

const navy = Color(0xFF17105B);
const navyDark = Color(0xFF0F0A3C);
const teal = Color(0xFF2CB6C5);
const mint = Color(0xFFE5F7F6);
const pageBackground = Color(0xFFF7F8FC);
const ink = Color(0xFF182039);
const muted = Color(0xFF7E879B);
const success = Color(0xFF2EA66F);

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(ChangeNotifierProvider(
      create: (_) => AuthState()..initialize(), child: const DriverApp()));
}

class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    final base = ThemeData(
        useMaterial3: true, colorScheme: ColorScheme.fromSeed(seedColor: navy));
    return MaterialApp(
      title: 'E-RIDE Driver',
      debugShowCheckedModeBanner: false,
      theme: base.copyWith(
        scaffoldBackgroundColor: pageBackground,
        colorScheme: base.colorScheme
            .copyWith(primary: navy, secondary: teal, surface: Colors.white),
        textTheme: GoogleFonts.manropeTextTheme(base.textTheme)
            .apply(bodyColor: ink, displayColor: navy),
        appBarTheme: const AppBarTheme(
            backgroundColor: pageBackground,
            foregroundColor: navy,
            elevation: 0,
            centerTitle: false),
        cardTheme: CardThemeData(
            color: Colors.white,
            elevation: 0,
            margin: EdgeInsets.zero,
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.all(Radius.circular(22)))),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFFF6F7FB),
          labelStyle: const TextStyle(color: muted),
          hintStyle: const TextStyle(color: Color(0xFFADB4C3)),
          prefixIconColor: navy,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 18, vertical: 17),
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: Color(0xFFE8EAF2))),
          focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: teal, width: 2)),
          errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: Colors.redAccent)),
        ),
        filledButtonTheme: FilledButtonThemeData(
            style: FilledButton.styleFrom(
                backgroundColor: navy,
                foregroundColor: Colors.white,
                minimumSize: const Size(0, 52),
                padding: const EdgeInsets.symmetric(horizontal: 22),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(15)),
                textStyle: const TextStyle(fontWeight: FontWeight.w800))),
        outlinedButtonTheme: OutlinedButtonThemeData(
            style: OutlinedButton.styleFrom(
                foregroundColor: navy,
                minimumSize: const Size(0, 50),
                side: const BorderSide(color: Color(0xFFD9DDEA)),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(15)),
                textStyle: const TextStyle(fontWeight: FontWeight.w700))),
        navigationBarTheme: NavigationBarThemeData(
            backgroundColor: Colors.white,
            indicatorColor: mint,
            labelTextStyle: WidgetStateProperty.all(
                const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
            elevation: 8),
      ),
      home: Consumer<AuthState>(
        builder: (context, auth, _) {
          if (auth.initializing) return const SplashView();
          if (auth.isLoggedIn) return const AppShell();
          return const LoginPage();
        },
      ),
    );
  }
}

class SplashView extends StatelessWidget {
  const SplashView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
        Image.asset('assets/images/splash.png', width: 225),
        const SizedBox(height: 28),
        const SizedBox(
            width: 24,
            height: 24,
            child: CircularProgressIndicator(strokeWidth: 2, color: teal))
      ])),
    );
  }
}

class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.size = 42, this.light = false});
  final double size;
  final bool light;

  @override
  Widget build(BuildContext context) => Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
          color: Colors.white, borderRadius: BorderRadius.circular(size * .28)),
      clipBehavior: Clip.antiAlias,
      child: Transform.scale(
          scale: 1.38, child: Image.asset('assets/images/logo.png')));
}

class Wordmark extends StatelessWidget {
  const Wordmark({super.key, this.width = 138, this.light = false});
  final double width;
  final bool light;

  @override
  Widget build(BuildContext context) => SizedBox(
      width: width,
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        BrandMark(size: width * .28, light: true),
        SizedBox(width: width * .07),
        Expanded(
            child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
              FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text('E-RIDE',
                      style: TextStyle(
                          color: light ? Colors.white : navy,
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                          letterSpacing: -.5))),
              Text('DRIVER',
                  style: TextStyle(
                      color: light ? teal : muted,
                      fontSize: 8,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 2)),
            ]))
      ]));
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final formKey = GlobalKey<FormState>();
  final email = TextEditingController();
  final password = TextEditingController();
  bool obscure = true;

  @override
  void dispose() {
    email.dispose();
    password.dispose();
    super.dispose();
  }

  Future<void> submit() async {
    if (!(formKey.currentState?.validate() ?? false)) return;
    final auth = context.auth;
    final success = await auth.login(email.text, password.text);
    if (!success && mounted) _toast(auth.error ?? 'Sign in failed');
  }

  Future<void> biometric() async {
    final auth = context.auth;
    final success = await auth.biometricLogin();
    if (!success && mounted) {
      _toast(auth.error ?? 'Biometric login is not available yet');
    }
  }

  void _toast(String message) => ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), behavior: SnackBarBehavior.floating));

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    return Scaffold(
      body: LayoutBuilder(builder: (context, constraints) {
        final wide = constraints.maxWidth >= 920;
        final card = _LoginCard(
            formKey: formKey,
            email: email,
            password: password,
            obscure: obscure,
            busy: auth.busy,
            hasSavedSession: auth.hasSavedSession,
            onToggleObscure: () => setState(() => obscure = !obscure),
            onSubmit: submit,
            onBiometric: biometric);
        if (!wide) {
          return Stack(children: [
            Positioned(
                top: -100,
                right: -90,
                child: _Glow(size: 250, color: teal.withValues(alpha: .08))),
            SafeArea(
                child: Center(
                    child: SingleChildScrollView(
                        padding: const EdgeInsets.fromLTRB(20, 24, 20, 30),
                        child: ConstrainedBox(
                            constraints: const BoxConstraints(maxWidth: 470),
                            child: Column(children: [
                              const Align(
                                  alignment: Alignment.centerLeft,
                                  child: Wordmark(width: 142)),
                              const SizedBox(height: 28),
                              Container(
                                  padding: const EdgeInsets.all(22),
                                  decoration: BoxDecoration(
                                      color: Colors.white,
                                      borderRadius: BorderRadius.circular(26),
                                      border: Border.all(
                                          color: const Color(0xFFEAECF3)),
                                      boxShadow: [
                                        BoxShadow(
                                            color: navy.withValues(alpha: .07),
                                            blurRadius: 28,
                                            offset: const Offset(0, 14))
                                      ]),
                                  child: card),
                              const SizedBox(height: 20),
                              const Text(
                                  'Need help signing in? Contact your E-RIDE administrator.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                      color: muted, fontSize: 11, height: 1.4)),
                            ])))))
          ]);
        }
        return Row(children: [
          Expanded(flex: 11, child: _LoginBrandPanel()),
          Expanded(
              flex: 9,
              child: Center(
                  child: SingleChildScrollView(
                      padding: const EdgeInsets.all(48),
                      child: ConstrainedBox(
                          constraints: const BoxConstraints(maxWidth: 430),
                          child: Container(
                              padding: const EdgeInsets.all(30),
                              decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(26),
                                  border: Border.all(
                                      color: const Color(0xFFEAECF3))),
                              child: card)))))
        ]);
      }),
    );
  }
}

class _LoginBrandPanel extends StatelessWidget {
  @override
  Widget build(BuildContext context) => Container(
        decoration: const BoxDecoration(
            gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [navyDark, navy, Color(0xFF29218A)])),
        child: Stack(children: [
          Positioned(
              top: -80,
              right: -60,
              child: _Glow(size: 260, color: teal.withValues(alpha: .18))),
          Positioned(
              bottom: -100,
              left: -50,
              child:
                  _Glow(size: 310, color: Colors.white.withValues(alpha: .06))),
          Padding(
              padding: const EdgeInsets.symmetric(horizontal: 70, vertical: 64),
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Wordmark(width: 160, light: true),
                    const SizedBox(height: 76),
                    const Text('Move with confidence.',
                        style: TextStyle(
                            color: Colors.white,
                            fontSize: 46,
                            height: 1.1,
                            fontWeight: FontWeight.w900,
                            letterSpacing: -1.2)),
                    const SizedBox(height: 22),
                    Text(
                        'Everything you need for a smoother driving day, in one focused workspace.',
                        style: TextStyle(
                            color: Colors.white.withValues(alpha: .72),
                            fontSize: 17,
                            height: 1.6)),
                    const SizedBox(height: 42),
                    const _Benefit(
                        icon: Icons.account_balance_wallet_outlined,
                        title: 'Stay on top of your wallet',
                        subtitle: 'See balances and payments at a glance.'),
                    const SizedBox(height: 18),
                    const _Benefit(
                        icon: Icons.directions_car_outlined,
                        title: 'Know your assignment',
                        subtitle:
                            'Keep vehicle details close when you need them.'),
                  ])),
        ]),
      );
}

class _Glow extends StatelessWidget {
  const _Glow({required this.size, required this.color});
  final double size;
  final Color color;
  @override
  Widget build(BuildContext context) => Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: color,
          boxShadow: [
            BoxShadow(color: color, blurRadius: 80, spreadRadius: 25)
          ]));
}

class _Benefit extends StatelessWidget {
  const _Benefit(
      {required this.icon, required this.title, required this.subtitle});
  final IconData icon;
  final String title;
  final String subtitle;
  @override
  Widget build(BuildContext context) => Row(children: [
        Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: .12),
                borderRadius: BorderRadius.circular(13)),
            child: Icon(icon, color: teal)),
        const SizedBox(width: 14),
        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(title,
              style: const TextStyle(
                  color: Colors.white, fontWeight: FontWeight.w800)),
          const SizedBox(height: 3),
          Text(subtitle,
              style: TextStyle(
                  color: Colors.white.withValues(alpha: .62), fontSize: 12))
        ])
      ]);
}

class _LoginCard extends StatelessWidget {
  const _LoginCard(
      {required this.formKey,
      required this.email,
      required this.password,
      required this.obscure,
      required this.busy,
      required this.hasSavedSession,
      required this.onToggleObscure,
      required this.onSubmit,
      required this.onBiometric});
  final GlobalKey<FormState> formKey;
  final TextEditingController email;
  final TextEditingController password;
  final bool obscure;
  final bool busy;
  final bool hasSavedSession;
  final VoidCallback onToggleObscure;
  final VoidCallback onSubmit;
  final VoidCallback onBiometric;

  @override
  Widget build(BuildContext context) =>
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        const Text('SECURE DRIVER ACCESS',
            style: TextStyle(
                color: teal,
                fontSize: 10,
                fontWeight: FontWeight.w900,
                letterSpacing: 1.6)),
        const SizedBox(height: 10),
        const Text('Welcome back',
            style: TextStyle(
                color: navy,
                fontSize: 28,
                fontWeight: FontWeight.w900,
                letterSpacing: -.6)),
        const SizedBox(height: 9),
        const Text('Sign in with the same E-RIDE credentials you already use.',
            style: TextStyle(color: muted, height: 1.5)),
        const SizedBox(height: 26),
        Form(
            key: formKey,
            child: Column(children: [
              TextFormField(
                  controller: email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                      labelText: 'Email address',
                      prefixIcon: Icon(Icons.mail_outline_rounded)),
                  validator: (value) => value == null || !value.contains('@')
                      ? 'Enter a valid email address'
                      : null),
              const SizedBox(height: 14),
              TextFormField(
                  controller: password,
                  obscureText: obscure,
                  decoration: InputDecoration(
                      labelText: 'Password',
                      prefixIcon: const Icon(Icons.lock_outline_rounded),
                      suffixIcon: IconButton(
                          onPressed: onToggleObscure,
                          icon: Icon(obscure
                              ? Icons.visibility_outlined
                              : Icons.visibility_off_outlined))),
                  validator: (value) => value == null || value.length < 6
                      ? 'Enter your password'
                      : null,
                  onFieldSubmitted: (_) => onSubmit),
              const SizedBox(height: 22),
              SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                      onPressed: busy ? null : onSubmit,
                      child: busy
                          ? const SizedBox.square(
                              dimension: 21,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : const Text('Sign in'))),
              if (hasSavedSession && !kIsWeb) ...[
                const SizedBox(height: 12),
                SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                        onPressed: busy ? null : onBiometric,
                        icon: const Icon(Icons.fingerprint_rounded),
                        label: const Text('Unlock with biometrics')))
              ],
            ])),
        const SizedBox(height: 22),
        Container(
            width: double.infinity,
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
                color: mint.withValues(alpha: .65),
                borderRadius: BorderRadius.circular(15)),
            child: const Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.verified_user_outlined, color: navy, size: 19),
                  SizedBox(width: 10),
                  Expanded(
                      child: Text(
                          'Your account is protected by E-RIDE secure sign-in.',
                          style: TextStyle(
                              color: navy,
                              fontSize: 12,
                              height: 1.45,
                              fontWeight: FontWeight.w700)))
                ])),
      ]);
}

class AppShell extends StatefulWidget {
  const AppShell({super.key});
  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int selected = 0;
  final pages = const [
    DriverOverviewPage(),
    DriverPaymentsPage(),
    VehiclePage(),
    DriverProfilePage()
  ];
  final labels = const ['Overview', 'Payments', 'Vehicle', 'Profile'];
  final icons = const [
    Icons.space_dashboard_rounded,
    Icons.account_balance_wallet_rounded,
    Icons.directions_car_filled_rounded,
    Icons.person_rounded
  ];

  @override
  Widget build(BuildContext context) {
    final wide = MediaQuery.sizeOf(context).width >= 980;
    final body = IndexedStack(index: selected, children: pages);
    return Scaffold(
      body: wide
          ? Row(children: [
              Expanded(
                  flex: 2,
                  child: _Sidebar(
                      selected: selected,
                      labels: labels,
                      icons: icons,
                      onSelect: (value) => setState(() => selected = value))),
              Expanded(flex: 7, child: body)
            ])
          : body,
      bottomNavigationBar: wide
          ? null
          : NavigationBar(
              selectedIndex: selected,
              onDestinationSelected: (value) =>
                  setState(() => selected = value),
              destinations: [
                  for (var i = 0; i < labels.length; i++)
                    NavigationDestination(
                        icon: Icon(icons[i]), label: labels[i])
                ]),
    );
  }
}

class _Sidebar extends StatelessWidget {
  const _Sidebar(
      {required this.selected,
      required this.labels,
      required this.icons,
      required this.onSelect});
  final int selected;
  final List<String> labels;
  final List<IconData> icons;
  final ValueChanged<int> onSelect;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    return Container(
        color: Colors.white,
        padding: const EdgeInsets.fromLTRB(22, 30, 22, 24),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Padding(
              padding: EdgeInsets.only(left: 12, bottom: 52),
              child: Wordmark(width: 137)),
          const Padding(
              padding: EdgeInsets.only(left: 12, bottom: 13),
              child: Text('WORKSPACE',
                  style: TextStyle(
                      color: muted,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.6))),
          for (var i = 0; i < labels.length; i++)
            _SideNavItem(
                icon: icons[i],
                label: labels[i],
                selected: selected == i,
                onTap: () => onSelect(i)),
          const Spacer(),
          Container(
              padding: const EdgeInsets.all(13),
              decoration: BoxDecoration(
                  color: pageBackground,
                  borderRadius: BorderRadius.circular(18)),
              child: Row(children: [
                CircleAvatar(
                    radius: 19,
                    backgroundColor: mint,
                    child: Text(_initials(auth.displayName),
                        style: const TextStyle(
                            color: navy,
                            fontSize: 12,
                            fontWeight: FontWeight.w900))),
                const SizedBox(width: 10),
                Expanded(
                    child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                      Text(auth.displayName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                              color: navy,
                              fontSize: 12,
                              fontWeight: FontWeight.w800)),
                      const SizedBox(height: 2),
                      const Text('Driver account',
                          style: TextStyle(color: muted, fontSize: 10))
                    ])),
                IconButton(
                    tooltip: 'Sign out',
                    onPressed: () => context.auth.logout(),
                    icon: const Icon(Icons.logout_rounded,
                        color: muted, size: 18))
              ]))
        ]));
  }
}

class _SideNavItem extends StatelessWidget {
  const _SideNavItem(
      {required this.icon,
      required this.label,
      required this.selected,
      required this.onTap});
  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Material(
          color: selected ? mint : Colors.transparent,
          borderRadius: BorderRadius.circular(14),
          child: InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(14),
              child: Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                  child: Row(children: [
                    Icon(icon, color: selected ? navy : muted, size: 20),
                    const SizedBox(width: 13),
                    Text(label,
                        style: TextStyle(
                            color: selected ? navy : muted,
                            fontWeight:
                                selected ? FontWeight.w800 : FontWeight.w600))
                  ])))));
}

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key});
  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  late Future<Map<String, dynamic>> future;
  @override
  void initState() {
    super.initState();
    future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('driver/dashboard')
      .then((value) => _map(value['data']));
  void refresh() => setState(() => future = _load());

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final firstName = auth.displayName.trim().split(' ').first;
    return AppPage(
        title: 'Good ${_partOfDay()}, $firstName',
        subtitle: 'Here is your driving day at a glance.',
        action: IconButton(
            tooltip: 'Refresh',
            onPressed: refresh,
            icon: const Icon(Icons.refresh_rounded)),
        child: FutureBuilder<Map<String, dynamic>>(
            future: future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const LoadingCards();
              }
              if (snapshot.hasError) {
                return ErrorCard(
                    message: snapshot.error.toString(), onRetry: refresh);
              }
              final data = snapshot.data ?? {};
              final wallet =
                  _num(data['wallet_balance'] ?? data['wallet']?['balance']);
              final due = _num(data['today']?['balance'] ??
                  data['today_ledger']?['balance'] ??
                  data['pending_remittance']);
              final vehicle = data['current_vehicle'] ?? data['vehicle'];
              final activity =
                  _list(data['recent_transactions'] ?? data['transactions']);
              return RefreshIndicator(
                  onRefresh: () async => refresh(),
                  child: ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      shrinkWrap: true,
                      children: [
                        _WalletHero(
                            balance: wallet,
                            onFund: () => _openPayments(context)),
                        const SizedBox(height: 18),
                        LayoutBuilder(
                            builder: (context, constraints) =>
                                Wrap(spacing: 14, runSpacing: 14, children: [
                                  SizedBox(
                                      width: constraints.maxWidth >= 800
                                          ? (constraints.maxWidth - 28) / 3
                                          : constraints.maxWidth,
                                      child: _StatTile(
                                          icon: Icons.event_note_rounded,
                                          label: 'Today\'s due',
                                          value: money(due),
                                          tint: const Color(0xFFFFF3DD),
                                          iconColor: const Color(0xFFE39A22))),
                                  SizedBox(
                                      width: constraints.maxWidth >= 800
                                          ? (constraints.maxWidth - 28) / 3
                                          : constraints.maxWidth,
                                      child: _StatTile(
                                          icon: Icons.verified_rounded,
                                          label: 'Account status',
                                          value: 'Active',
                                          tint: const Color(0xFFE6F7EF),
                                          iconColor: success)),
                                  SizedBox(
                                      width: constraints.maxWidth >= 800
                                          ? (constraints.maxWidth - 28) / 3
                                          : constraints.maxWidth,
                                      child: _StatTile(
                                          icon: Icons
                                              .directions_car_filled_rounded,
                                          label: 'Assignment',
                                          value: vehicle is Map
                                              ? 'Assigned'
                                              : 'Pending',
                                          tint: const Color(0xFFE9E9FF),
                                          iconColor: navy)),
                                ])),
                        const SizedBox(height: 28),
                        const SectionHeading(title: 'Quick actions'),
                        const SizedBox(height: 12),
                        LayoutBuilder(
                            builder: (context, constraints) =>
                                Wrap(spacing: 12, runSpacing: 12, children: [
                                  SizedBox(
                                      width: constraints.maxWidth >= 800
                                          ? 210
                                          : (constraints.maxWidth - 12) / 2,
                                      child: QuickAction(
                                          icon: Icons.add_card_rounded,
                                          title: 'Fund wallet',
                                          subtitle: 'Add money securely',
                                          onTap: () => _openPayments(context))),
                                  SizedBox(
                                      width: constraints.maxWidth >= 800
                                          ? 210
                                          : (constraints.maxWidth - 12) / 2,
                                      child: QuickAction(
                                          icon: Icons.payments_outlined,
                                          title: 'Pay remittance',
                                          subtitle: 'Clear today\'s due',
                                          onTap: () => _openPayments(context))),
                                  SizedBox(
                                      width: constraints.maxWidth >= 800
                                          ? 210
                                          : (constraints.maxWidth - 12) / 2,
                                      child: QuickAction(
                                          icon: Icons.directions_car_outlined,
                                          title: 'View vehicle',
                                          subtitle: 'See assignment details',
                                          onTap: () => _openVehicle(context))),
                                ])),
                        const SizedBox(height: 28),
                        LayoutBuilder(builder: (context, constraints) {
                          final vehicleCard = Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionHeading(title: 'Current vehicle'),
                                const SizedBox(height: 12),
                                CardSurface(
                                    child: vehicle is Map
                                        ? VehicleSummary(
                                            vehicle: Map<String, dynamic>.from(
                                                vehicle))
                                        : const EmptyState(
                                            icon: Icons.directions_car_outlined,
                                            title: 'No vehicle assigned',
                                            message:
                                                'Your current assignment will appear here.'))
                              ]);
                          final activityCard = Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SectionHeading(title: 'Recent activity'),
                                const SizedBox(height: 12),
                                CardSurface(
                                    child: activity.isEmpty
                                        ? const EmptyState(
                                            icon: Icons.receipt_long_outlined,
                                            title: 'No recent activity',
                                            message:
                                                'Your latest updates will appear here.')
                                        : Column(children: [
                                            for (var i = 0;
                                                i < activity.length && i < 3;
                                                i++)
                                              _ActivityRow(
                                                  item: _map(activity[i]),
                                                  last: i ==
                                                          activity.length - 1 ||
                                                      i == 2)
                                          ]))
                              ]);
                          if (constraints.maxWidth < 820) {
                            return Column(children: [
                              vehicleCard,
                              const SizedBox(height: 22),
                              activityCard
                            ]);
                          }
                          return Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(child: vehicleCard),
                                const SizedBox(width: 22),
                                Expanded(child: activityCard)
                              ]);
                        }),
                      ]));
            }));
  }

  void _openPayments(BuildContext context) => Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const PaymentsPage(fullPage: true)));
  void _openVehicle(BuildContext context) => Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const VehiclePage(fullPage: true)));
}

class _WalletHero extends StatelessWidget {
  const _WalletHero({required this.balance, required this.onFund});
  final double balance;
  final VoidCallback onFund;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [navy, Color(0xFF342A9B)]),
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
                color: navy.withValues(alpha: .18),
                blurRadius: 24,
                offset: const Offset(0, 12))
          ],
        ),
        child: Stack(children: [
          Positioned(
              right: -22,
              top: -38,
              child: _Glow(size: 170, color: teal.withValues(alpha: .16))),
          Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Container(
                  width: 38,
                  height: 38,
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: .14),
                      borderRadius: BorderRadius.circular(12)),
                  child: Image.asset('assets/images/logo.png')),
              const SizedBox(width: 10),
              Text('E-RIDE WALLET',
                  style: TextStyle(
                      color: Colors.white.withValues(alpha: .72),
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.4)),
              const Spacer(),
              Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: .13),
                      borderRadius: BorderRadius.circular(20)),
                  child: const Row(mainAxisSize: MainAxisSize.min, children: [
                    Icon(Icons.verified_rounded, color: teal, size: 14),
                    SizedBox(width: 5),
                    Text('Active',
                        style: TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.w700))
                  ])),
            ]),
            const SizedBox(height: 24),
            const Text('Available balance',
                style: TextStyle(color: Colors.white70, fontSize: 13)),
            const SizedBox(height: 5),
            Text(money(balance),
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 31,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -.7)),
            const SizedBox(height: 22),
            Align(
                alignment: Alignment.centerLeft,
                child: FilledButton.icon(
                    onPressed: onFund,
                    icon: const Icon(Icons.add_rounded, size: 18),
                    label: const Text('Fund wallet'),
                    style: FilledButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: navy,
                        minimumSize: const Size(0, 44)))),
          ]),
        ]),
      );
}

class _StatTile extends StatelessWidget {
  const _StatTile(
      {required this.icon,
      required this.label,
      required this.value,
      required this.tint,
      required this.iconColor});
  final IconData icon;
  final String label;
  final String value;
  final Color tint;
  final Color iconColor;
  @override
  Widget build(BuildContext context) => CardSurface(
          child: Row(children: [
        Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
                color: tint, borderRadius: BorderRadius.circular(13)),
            child: Icon(icon, color: iconColor, size: 21)),
        const SizedBox(width: 13),
        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(label, style: const TextStyle(color: muted, fontSize: 12)),
          const SizedBox(height: 4),
          Text(value,
              style: const TextStyle(
                  color: navy, fontSize: 17, fontWeight: FontWeight.w900))
        ])
      ]));
}

class PaymentsPage extends StatefulWidget {
  const PaymentsPage({super.key, this.fullPage = false});
  final bool fullPage;
  @override
  State<PaymentsPage> createState() => _PaymentsPageState();
}

class _PaymentsPageState extends State<PaymentsPage>
    with SingleTickerProviderStateMixin {
  late Future<List<dynamic>> future;
  late TabController tabs;
  @override
  void initState() {
    super.initState();
    tabs = TabController(length: 3, vsync: this);
    future = _load();
  }

  @override
  void dispose() {
    tabs.dispose();
    super.dispose();
  }

  Future<List<dynamic>> _load() async => Future.wait([
        ApiClient.instance.get('driver/wallet'),
        ApiClient.instance.get('driver/remittance/all'),
        ApiClient.instance.get('driver/transactions')
      ]);
  void refresh() => setState(() => future = _load());

  @override
  Widget build(BuildContext context) {
    final content = FutureBuilder<List<dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingCards();
          }
          if (snapshot.hasError) {
            return ErrorCard(
                message: snapshot.error.toString(), onRetry: refresh);
          }
          final responses = snapshot.data!;
          final wallet = _map(responses[0]['data']);
          final remittances = _list(responses[1]['data']);
          final transactions = _list(responses[2]['data']);
          return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _WalletHero(
                    balance:
                        _num(wallet['balance'] ?? wallet['wallet_balance']),
                    onFund: () => _startCheckout('wallet_funding')),
                const SizedBox(height: 24),
                CardSurface(
                    child: Column(children: [
                  TabBar(
                      controller: tabs,
                      isScrollable: true,
                      tabAlignment: TabAlignment.start,
                      labelColor: navy,
                      unselectedLabelColor: muted,
                      indicatorColor: teal,
                      tabs: const [
                        Tab(text: 'Remittance'),
                        Tab(text: 'Transactions'),
                        Tab(text: 'Wallet funding')
                      ]),
                  const SizedBox(height: 16),
                  SizedBox(
                      height: 440,
                      child: TabBarView(controller: tabs, children: [
                        _remittanceList(remittances),
                        _transactionList(transactions),
                        const EmptyState(
                            icon: Icons.receipt_long_outlined,
                            title: 'Wallet funding history',
                            message:
                                'Paid wallet funding requests will appear in your transaction history.')
                      ]))
                ]))
              ]);
        });
    if (widget.fullPage) {
      return Scaffold(
          appBar: AppBar(
              title: const Text('Payments',
                  style: TextStyle(fontWeight: FontWeight.w800))),
          body: AppPageBody(child: content));
    }
    return AppPage(
        title: 'Payments',
        subtitle: 'Fund your wallet and keep payments moving.',
        action: IconButton(
            tooltip: 'Refresh',
            onPressed: refresh,
            icon: const Icon(Icons.refresh_rounded)),
        child: content);
  }

  Widget _remittanceList(List<dynamic> items) {
    if (items.isEmpty) {
      return const EmptyState(
          icon: Icons.check_circle_outline_rounded,
          title: 'You are all caught up',
          message: 'There are no remittances to display.');
    }
    return ListView.separated(
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = _map(items[index]);
          final status = (item['status'] ?? 'pending').toString();
          return _PaymentRow(
              status: status,
              title: item['reference']?.toString() ?? 'Daily remittance',
              date: item['created_date']?.toString() ??
                  item['created_at']?.toString() ??
                  'Pending',
              amount: _num(item['amount']),
              action: status == 'pending' || status == 'due'
                  ? TextButton(
                      onPressed: () => _startCheckout('remittance',
                          amount: _num(item['amount']),
                          transactionId: item['id']),
                      child: const Text('Pay now'))
                  : null);
        });
  }

  Widget _transactionList(List<dynamic> items) {
    if (items.isEmpty) {
      return const EmptyState(
          icon: Icons.receipt_long_outlined,
          title: 'No transactions yet',
          message: 'Your payment activity will appear here.');
    }
    return ListView.separated(
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          final item = _map(items[index]);
          return _PaymentRow(
              status: (item['status'] ?? 'pending').toString(),
              title: _pretty((item['type'] ?? 'transaction').toString()),
              date: item['created_at']?.toString() ?? '',
              amount: _num(item['amount']));
        });
  }

  Future<void> _startCheckout(String purpose,
      {double? amount, dynamic transactionId}) async {
    final chosen = amount ??
        await _amountDialog(context,
            purpose == 'wallet_funding' ? 'Fund wallet' : 'Pay remittance');
    if (chosen == null || chosen <= 0 || !mounted) return;
    try {
      final response =
          await ApiClient.instance.post('driver/payments/opay/checkout', {
        'purpose': purpose,
        'amount': chosen,
        if (transactionId != null) 'transaction_id': transactionId
      });
      final data = _map(response['data']);
      final checkoutUrl = data['checkout_url']?.toString() ?? '';
      final reference = data['reference']?.toString() ?? '';
      final checkoutAmount = _num(data['amount']);
      if (checkoutUrl.isEmpty || reference.isEmpty) {
        throw ApiException('OPay did not return a valid checkout session.');
      }
      if (!mounted) return;
      final result = await Navigator.of(context).push<OpayCheckoutResult>(
        MaterialPageRoute(
          fullscreenDialog: true,
          builder: (_) => OpayCheckoutPage(
            checkoutUrl: checkoutUrl,
            reference: reference,
            amount: checkoutAmount > 0 ? checkoutAmount : chosen,
          ),
        ),
      );
      if (mounted && result != null) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(result.message),
          backgroundColor: result.paid ? success : null,
          behavior: SnackBarBehavior.floating,
        ));
        refresh();
      }
    } on ApiException catch (exception) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(exception.message),
            behavior: SnackBarBehavior.floating));
      }
    }
  }

  Future<double?> _amountDialog(BuildContext context, String title) async {
    final controller = TextEditingController();
    final value = await showModalBottomSheet<double>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (context) => Padding(
        padding: EdgeInsets.fromLTRB(
            22, 4, 22, MediaQuery.viewInsetsOf(context).bottom + 22),
        child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title,
                  style: const TextStyle(
                      color: navy, fontSize: 21, fontWeight: FontWeight.w900)),
              const SizedBox(height: 6),
              const Text('Enter the amount you want to pay securely with OPay.',
                  style: TextStyle(color: muted)),
              const SizedBox(height: 20),
              TextField(
                  controller: controller,
                  autofocus: true,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(
                      prefixText: '₦ ', labelText: 'Amount')),
              const SizedBox(height: 16),
              SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                      onPressed: () => Navigator.pop(
                          context, double.tryParse(controller.text.trim())),
                      child: const Text('Continue to OPay'))),
            ]),
      ),
    );
    controller.dispose();
    return value;
  }
}

class _PaymentRow extends StatelessWidget {
  const _PaymentRow(
      {required this.status,
      required this.title,
      required this.date,
      required this.amount,
      this.action});
  final String status;
  final String title;
  final String date;
  final double amount;
  final Widget? action;
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 11),
        decoration: const BoxDecoration(
            border: Border(bottom: BorderSide(color: Color(0xFFF0F1F5)))),
        child: Row(children: [
          StatusIcon(status: status),
          const SizedBox(width: 12),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Text(title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        color: navy, fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                Text(date,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: muted, fontSize: 11))
              ])),
          Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
            Text(money(amount),
                style:
                    const TextStyle(color: navy, fontWeight: FontWeight.w900)),
            if (action != null) action!
          ]),
        ]),
      );
}

class VehiclePage extends StatefulWidget {
  const VehiclePage({super.key, this.fullPage = false});
  final bool fullPage;
  @override
  State<VehiclePage> createState() => _VehiclePageState();
}

class _VehiclePageState extends State<VehiclePage> {
  late Future<Map<String, dynamic>> future;
  @override
  void initState() {
    super.initState();
    future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('driver/vehicle/current')
      .then((value) => _map(value['data']));
  void refresh() => setState(() => future = _load());
  @override
  Widget build(BuildContext context) {
    final content = FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingCards();
          }
          if (snapshot.hasError) {
            return ErrorCard(
                message: snapshot.error.toString(), onRetry: refresh);
          }
          final assignment = snapshot.data ?? {};
          final vehicle = _map(assignment['vehicle']);
          if (assignment.isEmpty || vehicle.isEmpty) {
            return const EmptyState(
                icon: Icons.directions_car_outlined,
                title: 'No current assignment',
                message:
                    'Your assigned vehicle will appear here when it is available.');
          }
          final displayVehicle = <String, dynamic>{
            ...vehicle,
            'assigned_at': assignment['assigned_at'],
            'assigned_by': assignment['assigned_by'],
          };
          return Column(children: [
            _VehicleHero(vehicle: displayVehicle),
            const SizedBox(height: 18),
            LayoutBuilder(builder: (context, constraints) {
              final width = (constraints.maxWidth - 12) / 2;
              return Wrap(spacing: 12, runSpacing: 12, children: [
                SizedBox(
                    width: width,
                    child: _VehicleDetail(
                        icon: Icons.calendar_today_outlined,
                        label: 'Model year',
                        value: vehicle['year']?.toString() ?? 'Not available')),
                SizedBox(
                    width: width,
                    child: _VehicleDetail(
                        icon: Icons.palette_outlined,
                        label: 'Colour',
                        value:
                            vehicle['color']?.toString() ?? 'Not available')),
                SizedBox(
                    width: width,
                    child: _VehicleDetail(
                        icon: Icons.pin_outlined,
                        label: 'VIN',
                        value: vehicle['vin']?.toString() ?? 'Not available')),
                SizedBox(
                    width: width,
                    child: _VehicleDetail(
                        icon: Icons.person_outline_rounded,
                        label: 'Assigned by',
                        value: assignment['assigned_by']?.toString() ??
                            'E-RIDE admin')),
              ]);
            }),
            const SizedBox(height: 18),
            CardSurface(
                child: ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: Container(
                        width: 42,
                        height: 42,
                        decoration: BoxDecoration(
                            color: mint,
                            borderRadius: BorderRadius.circular(13)),
                        child: const Icon(Icons.history_rounded, color: navy)),
                    title: const Text('Assignment history',
                        style: TextStyle(
                            color: navy, fontWeight: FontWeight.w800)),
                    subtitle: const Text('View previous vehicle assignments',
                        style: TextStyle(color: muted)),
                    trailing:
                        const Icon(Icons.chevron_right_rounded, color: muted),
                    onTap: () => _showHistory(context)))
          ]);
        });
    if (widget.fullPage) {
      return Scaffold(
          appBar: AppBar(
              title: const Text('Vehicle',
                  style: TextStyle(fontWeight: FontWeight.w800))),
          body: AppPageBody(child: content));
    }
    return AppPage(
        title: 'Vehicle',
        subtitle: 'Your current assignment at a glance.',
        action: IconButton(
            tooltip: 'Refresh',
            onPressed: refresh,
            icon: const Icon(Icons.refresh_rounded)),
        child: content);
  }

  Future<void> _showHistory(BuildContext context) async {
    try {
      final response = await ApiClient.instance.get('driver/vehicle/history');
      final list = _list(response['data']);
      if (!context.mounted) return;
      showModalBottomSheet(
          context: context,
          showDragHandle: true,
          builder: (_) => SafeArea(
              child: Padding(
                  padding: const EdgeInsets.all(22),
                  child: list.isEmpty
                      ? const EmptyState(
                          icon: Icons.history,
                          title: 'No history',
                          message: 'No previous assignments were found.')
                      : ListView.separated(
                          itemCount: list.length,
                          separatorBuilder: (_, __) =>
                              const Divider(height: 22),
                          itemBuilder: (_, index) {
                            final assignment = _map(list[index]);
                            final item = _map(assignment['vehicle']);
                            return ListTile(
                                contentPadding: EdgeInsets.zero,
                                leading: const Icon(
                                    Icons.directions_car_outlined,
                                    color: navy),
                                title: Text(
                                    '${item['make'] ?? ''} ${item['model'] ?? ''}',
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w800)),
                                subtitle: Text(
                                    assignment['assigned_at']?.toString() ?? '',
                                    style: const TextStyle(color: muted)),
                                trailing: Text(
                                    item['plate_number']?.toString() ?? '',
                                    style: const TextStyle(
                                        color: navy,
                                        fontWeight: FontWeight.w800)));
                          }))));
    } on ApiException catch (exception) {
      if (context.mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(exception.message)));
      }
    }
  }
}

class _VehicleHero extends StatelessWidget {
  const _VehicleHero({required this.vehicle});
  final Map<String, dynamic> vehicle;
  @override
  Widget build(BuildContext context) => Container(
      padding: const EdgeInsets.all(24),
      decoration:
          BoxDecoration(color: navy, borderRadius: BorderRadius.circular(24)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .13),
                  borderRadius: BorderRadius.circular(16)),
              child: const Icon(Icons.directions_car_filled_rounded,
                  color: teal, size: 28)),
          const Spacer(),
          _PlateBadge(
              value: vehicle['plate_number']?.toString() ??
                  vehicle['plate']?.toString() ??
                  'N/A')
        ]),
        const SizedBox(height: 26),
        Text(
            '${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}'.trim().isEmpty
                ? 'Assigned vehicle'
                : '${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}',
            style: const TextStyle(
                color: Colors.white,
                fontSize: 24,
                fontWeight: FontWeight.w900)),
        const SizedBox(height: 8),
        Text(
            vehicle['assigned_at'] == null
                ? 'Your current E-RIDE assignment'
                : 'Assigned ${vehicle['assigned_at']}',
            style: const TextStyle(color: Colors.white70, fontSize: 13))
      ]));
}

class _PlateBadge extends StatelessWidget {
  const _PlateBadge({required this.value});
  final String value;
  @override
  Widget build(BuildContext context) => Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
          color: Colors.white, borderRadius: BorderRadius.circular(9)),
      child: Text(value,
          style: const TextStyle(
              color: navy, fontWeight: FontWeight.w900, letterSpacing: .7)));
}

class _VehicleDetail extends StatelessWidget {
  const _VehicleDetail({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => CardSurface(
        padding: const EdgeInsets.all(15),
        child: Row(children: [
          Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                  color: mint, borderRadius: BorderRadius.circular(12)),
              child: Icon(icon, color: navy, size: 19)),
          const SizedBox(width: 11),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Text(label, style: const TextStyle(color: muted, fontSize: 9)),
                const SizedBox(height: 3),
                Text(value,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        color: navy,
                        fontSize: 12,
                        fontWeight: FontWeight.w900)),
              ])),
        ]),
      );
}

class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key});
  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    return AppPage(
        title: 'Profile',
        subtitle: 'Keep your account secure and up to date.',
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          CardSurface(
              child: Row(children: [
            CircleAvatar(
                radius: 31,
                backgroundColor: mint,
                child: Text(_initials(auth.displayName),
                    style: const TextStyle(
                        color: navy,
                        fontSize: 22,
                        fontWeight: FontWeight.w900))),
            const SizedBox(width: 16),
            Expanded(
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                  Text(auth.displayName,
                      style: const TextStyle(
                          color: navy,
                          fontSize: 19,
                          fontWeight: FontWeight.w900)),
                  const SizedBox(height: 4),
                  Text(auth.email,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: muted))
                ])),
            const Icon(Icons.verified_rounded, color: success)
          ])),
          const SizedBox(height: 18),
          const SectionHeading(title: 'Security'),
          const SizedBox(height: 12),
          CardSurface(
              child: Column(children: [
            ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const _SettingIcon(icon: Icons.fingerprint_rounded),
                title: const Text('Biometric login',
                    style: TextStyle(color: navy, fontWeight: FontWeight.w800)),
                subtitle: Text(
                    kIsWeb
                        ? 'Available on Android only'
                        : 'Unlock the app with your device biometrics',
                    style: const TextStyle(color: muted, fontSize: 12)),
                trailing: Switch(
                    value: auth.biometricEnabled,
                    onChanged: kIsWeb
                        ? null
                        : (value) async {
                            final ok = await auth.toggleBiometric(value);
                            if (!ok && context.mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                      content: Text(
                                          'Biometrics are not available on this device.')));
                            }
                          })),
            const Divider(height: 16),
            ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const _SettingIcon(icon: Icons.lock_outline_rounded),
                title: const Text('Change password',
                    style: TextStyle(color: navy, fontWeight: FontWeight.w800)),
                subtitle: const Text('Update your account password',
                    style: TextStyle(color: muted, fontSize: 12)),
                trailing: const Icon(Icons.chevron_right_rounded, color: muted),
                onTap: () => _changePassword(context))
          ])),
          const SizedBox(height: 18),
          CardSurface(
              child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading:
                      const Icon(Icons.logout_rounded, color: Colors.redAccent),
                  title: const Text('Sign out',
                      style: TextStyle(
                          color: Colors.redAccent,
                          fontWeight: FontWeight.w800)),
                  subtitle: const Text('End this session on the device',
                      style: TextStyle(color: muted, fontSize: 12)),
                  onTap: () => context.auth.logout())),
        ]));
  }

  Future<void> _changePassword(BuildContext context) async {
    final current = TextEditingController();
    final next = TextEditingController();
    final confirm = TextEditingController();
    final form = GlobalKey<FormState>();
    await showDialog(
        context: context,
        builder: (context) => AlertDialog(
                title: const Text('Change password'),
                content: Form(
                    key: form,
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      TextFormField(
                          controller: current,
                          obscureText: true,
                          decoration: const InputDecoration(
                              labelText: 'Current password'),
                          validator: (v) =>
                              v == null || v.isEmpty ? 'Required' : null),
                      const SizedBox(height: 12),
                      TextFormField(
                          controller: next,
                          obscureText: true,
                          decoration:
                              const InputDecoration(labelText: 'New password'),
                          validator: (v) => v == null || v.length < 6
                              ? 'Use at least 6 characters'
                              : null),
                      const SizedBox(height: 12),
                      TextFormField(
                          controller: confirm,
                          obscureText: true,
                          decoration: const InputDecoration(
                              labelText: 'Confirm password'),
                          validator: (v) =>
                              v != next.text ? 'Passwords do not match' : null)
                    ])),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Cancel')),
                  FilledButton(
                      onPressed: () async {
                        if (!(form.currentState?.validate() ?? false)) return;
                        try {
                          await ApiClient.instance
                              .post('driver/profile/change-password', {
                            'current_password': current.text,
                            'new_password': next.text,
                            'new_password_confirmation': confirm.text
                          });
                          if (context.mounted) {
                            Navigator.pop(context);
                            ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                    content: Text(
                                        'Password changed successfully.')));
                          }
                        } on ApiException catch (e) {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(e.message)));
                          }
                        }
                      },
                      child: const Text('Update'))
                ]));
    current.dispose();
    next.dispose();
    confirm.dispose();
  }
}

class _SettingIcon extends StatelessWidget {
  const _SettingIcon({required this.icon});
  final IconData icon;
  @override
  Widget build(BuildContext context) => Container(
      width: 40,
      height: 40,
      decoration:
          BoxDecoration(color: mint, borderRadius: BorderRadius.circular(12)),
      child: Icon(icon, color: navy, size: 20));
}

class DriverProfilePage extends StatefulWidget {
  const DriverProfilePage({super.key});

  @override
  State<DriverProfilePage> createState() => _DriverProfilePageState();
}

class _DriverProfilePageState extends State<DriverProfilePage> {
  late Future<Map<String, dynamic>> future;

  @override
  void initState() {
    super.initState();
    future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('driver/profile')
      .then((response) => _map(response['data']));

  void refresh() => setState(() => future = _load());

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    return AppPage(
      title: 'Profile',
      subtitle: 'Personal details and account security.',
      action: IconButton(
        tooltip: 'Refresh profile',
        onPressed: refresh,
        icon: const Icon(Icons.refresh_rounded),
      ),
      child: FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingCards();
          }
          if (snapshot.hasError) {
            return ErrorCard(
                message: snapshot.error.toString(), onRetry: refresh);
          }

          final profile = snapshot.data ?? const <String, dynamic>{};
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _ProfileHero(profile: profile, fallbackName: auth.displayName),
              const SizedBox(height: 24),
              const SectionHeading(title: 'Driver information'),
              const SizedBox(height: 12),
              CardSurface(
                child: Column(children: [
                  _ProfileDetailRow(
                    icon: Icons.phone_outlined,
                    label: 'Phone number',
                    value: profile['phone_number']?.toString(),
                  ),
                  const Divider(height: 1),
                  _ProfileDetailRow(
                    icon: Icons.badge_outlined,
                    label: 'Driver licence',
                    value: profile['license_number']?.toString(),
                  ),
                  const Divider(height: 1),
                  _ProfileDetailRow(
                    icon: Icons.event_available_outlined,
                    label: 'Licence expiry',
                    value: profile['license_expiry']?.toString(),
                  ),
                  const Divider(height: 1),
                  _ProfileDetailRow(
                    icon: Icons.location_on_outlined,
                    label: 'Address',
                    value: profile['address']?.toString(),
                  ),
                ]),
              ),
              const SizedBox(height: 24),
              const SectionHeading(title: 'Security'),
              const SizedBox(height: 12),
              CardSurface(
                child: Column(children: [
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading:
                        const _SettingIcon(icon: Icons.fingerprint_rounded),
                    title: const Text('Biometric app lock',
                        style: TextStyle(
                            color: navy, fontWeight: FontWeight.w900)),
                    subtitle: Text(
                      kIsWeb
                          ? 'Available in the Android app'
                          : 'Require your fingerprint or device biometrics at launch',
                      style: const TextStyle(color: muted, fontSize: 11),
                    ),
                    trailing: Switch(
                      value: auth.biometricEnabled,
                      onChanged: kIsWeb
                          ? null
                          : (value) async {
                              final enabled = await auth.toggleBiometric(value);
                              if (!enabled && context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text(
                                        'Biometrics are unavailable or verification was cancelled.'),
                                  ),
                                );
                              }
                            },
                    ),
                  ),
                  const Divider(height: 12),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const _SettingIcon(icon: Icons.password_rounded),
                    title: const Text('Change password',
                        style: TextStyle(
                            color: navy, fontWeight: FontWeight.w900)),
                    subtitle: const Text('Update your E-RIDE login password',
                        style: TextStyle(color: muted, fontSize: 11)),
                    trailing:
                        const Icon(Icons.chevron_right_rounded, color: muted),
                    onTap: _changePassword,
                  ),
                ]),
              ),
              const SizedBox(height: 18),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _confirmSignOut,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.redAccent,
                    side: const BorderSide(color: Color(0xFFFFD7D7)),
                  ),
                  icon: const Icon(Icons.logout_rounded),
                  label: const Text('Sign out of this device'),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _changePassword() async {
    final current = TextEditingController();
    final next = TextEditingController();
    final confirm = TextEditingController();
    final form = GlobalKey<FormState>();
    var submitting = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => Padding(
          padding: EdgeInsets.fromLTRB(
            22,
            2,
            22,
            MediaQuery.viewInsetsOf(context).bottom + 24,
          ),
          child: Form(
            key: form,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Change password',
                    style: TextStyle(
                        color: navy,
                        fontSize: 21,
                        fontWeight: FontWeight.w900)),
                const SizedBox(height: 6),
                const Text('Use at least 8 characters for your new password.',
                    style: TextStyle(color: muted, fontSize: 12)),
                const SizedBox(height: 20),
                TextFormField(
                  controller: current,
                  obscureText: true,
                  decoration:
                      const InputDecoration(labelText: 'Current password'),
                  validator: (value) =>
                      value == null || value.isEmpty ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: next,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'New password'),
                  validator: (value) => value == null || value.length < 8
                      ? 'Use at least 8 characters'
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: confirm,
                  obscureText: true,
                  decoration:
                      const InputDecoration(labelText: 'Confirm new password'),
                  validator: (value) =>
                      value != next.text ? 'Passwords do not match' : null,
                ),
                const SizedBox(height: 18),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: submitting
                        ? null
                        : () async {
                            if (!(form.currentState?.validate() ?? false)) {
                              return;
                            }
                            setSheetState(() => submitting = true);
                            try {
                              await ApiClient.instance.post(
                                'driver/profile/change-password',
                                {
                                  'current_password': current.text,
                                  'new_password': next.text,
                                  'new_password_confirmation': confirm.text,
                                },
                              );
                              if (sheetContext.mounted) {
                                Navigator.pop(sheetContext);
                              }
                              if (mounted) {
                                ScaffoldMessenger.of(this.context).showSnackBar(
                                  const SnackBar(
                                    content:
                                        Text('Password changed successfully.'),
                                    backgroundColor: success,
                                  ),
                                );
                              }
                            } on ApiException catch (exception) {
                              if (sheetContext.mounted) {
                                ScaffoldMessenger.of(sheetContext).showSnackBar(
                                  SnackBar(content: Text(exception.message)),
                                );
                                setSheetState(() => submitting = false);
                              }
                            }
                          },
                    child: submitting
                        ? const SizedBox.square(
                            dimension: 20,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.white))
                        : const Text('Update password'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );

    current.dispose();
    next.dispose();
    confirm.dispose();
  }

  Future<void> _confirmSignOut() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sign out?'),
        content: const Text(
            'You will need your E-RIDE credentials to sign in again.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel')),
          FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Sign out')),
        ],
      ),
    );
    if (confirmed == true && mounted) {
      await context.auth.logout();
    }
  }
}

class _ProfileHero extends StatelessWidget {
  const _ProfileHero({required this.profile, required this.fallbackName});

  final Map<String, dynamic> profile;
  final String fallbackName;

  @override
  Widget build(BuildContext context) {
    final name = profile['full_name']?.toString() ?? fallbackName;
    final photo = profile['profile_photo']?.toString();
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
            colors: [navyDark, Color(0xFF2B2380)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight),
        borderRadius: BorderRadius.circular(25),
      ),
      child: Row(children: [
        CircleAvatar(
          radius: 34,
          backgroundColor: Colors.white,
          backgroundImage:
              photo != null && photo.isNotEmpty ? NetworkImage(photo) : null,
          child: photo == null || photo.isEmpty
              ? Text(_initials(name),
                  style: const TextStyle(
                      color: navy, fontSize: 21, fontWeight: FontWeight.w900))
              : null,
        ),
        const SizedBox(width: 16),
        Expanded(
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 19,
                    fontWeight: FontWeight.w900)),
            const SizedBox(height: 4),
            Text(profile['email']?.toString() ?? '',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Colors.white70, fontSize: 12)),
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .12),
                  borderRadius: BorderRadius.circular(20)),
              child: Text(profile['branch']?.toString() ?? 'E-RIDE Driver',
                  style: const TextStyle(
                      color: teal, fontSize: 10, fontWeight: FontWeight.w900)),
            ),
          ]),
        ),
        const Icon(Icons.verified_rounded, color: teal),
      ]),
    );
  }
}

class _ProfileDetailRow extends StatelessWidget {
  const _ProfileDetailRow({
    required this.icon,
    required this.label,
    this.value,
  });

  final IconData icon;
  final String label;
  final String? value;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 13),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, color: teal, size: 20),
          const SizedBox(width: 13),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Text(label, style: const TextStyle(color: muted, fontSize: 9)),
                const SizedBox(height: 3),
                Text(
                    value == null || value!.trim().isEmpty
                        ? 'Not provided'
                        : value!,
                    style: const TextStyle(
                        color: navy,
                        fontSize: 12,
                        fontWeight: FontWeight.w800)),
              ])),
        ]),
      );
}

class AppPage extends StatelessWidget {
  const AppPage(
      {super.key,
      required this.title,
      required this.subtitle,
      required this.child,
      this.action});
  final String title;
  final String subtitle;
  final Widget child;
  final Widget? action;
  @override
  Widget build(BuildContext context) => Scaffold(
          body: AppPageBody(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
            Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Expanded(
                  child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                    Text(title,
                        style: const TextStyle(
                            color: navy,
                            fontSize: 27,
                            fontWeight: FontWeight.w900,
                            letterSpacing: -.5)),
                    const SizedBox(height: 5),
                    Text(subtitle,
                        style: const TextStyle(color: muted, fontSize: 13))
                  ])),
              if (action != null) action!
            ]),
            const SizedBox(height: 26),
            child
          ])));
}

class AppPageBody extends StatelessWidget {
  const AppPageBody({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
          padding: EdgeInsets.fromLTRB(
              MediaQuery.sizeOf(context).width >= 980 ? 44 : 20,
              MediaQuery.sizeOf(context).width >= 980 ? 38 : 26,
              MediaQuery.sizeOf(context).width >= 980 ? 44 : 20,
              34),
          child: ConstrainedBox(
              constraints: BoxConstraints(
                  maxWidth: 1180,
                  minWidth: constraints.maxWidth > 1210 ? 900 : 0),
              child: child)));
}

class CardSurface extends StatelessWidget {
  const CardSurface(
      {super.key,
      required this.child,
      this.padding = const EdgeInsets.all(20)});
  final Widget child;
  final EdgeInsets padding;
  @override
  Widget build(BuildContext context) => Card(
      shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(22),
          side: const BorderSide(color: Color(0xFFEEF0F5))),
      child: Padding(padding: padding, child: child));
}

class QuickAction extends StatelessWidget {
  const QuickAction(
      {super.key,
      required this.icon,
      required this.title,
      required this.subtitle,
      required this.onTap});
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(18),
          child: Container(
              padding: const EdgeInsets.all(15),
              decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: const Color(0xFFEEF0F5))),
              child: Row(children: [
                Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                        color: mint, borderRadius: BorderRadius.circular(12)),
                    child: Icon(icon, color: navy, size: 19)),
                const SizedBox(width: 11),
                Expanded(
                    child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                      Text(title,
                          style: const TextStyle(
                              color: navy, fontWeight: FontWeight.w800)),
                      const SizedBox(height: 3),
                      Text(subtitle,
                          style: const TextStyle(color: muted, fontSize: 11))
                    ])),
                const Icon(Icons.arrow_forward_rounded, color: muted, size: 17)
              ]))));
}

class SectionHeading extends StatelessWidget {
  const SectionHeading({super.key, required this.title});
  final String title;
  @override
  Widget build(BuildContext context) => Text(title,
      style: const TextStyle(
          color: navy, fontSize: 16, fontWeight: FontWeight.w900));
}

class VehicleSummary extends StatelessWidget {
  const VehicleSummary({super.key, required this.vehicle});
  final Map<String, dynamic> vehicle;
  @override
  Widget build(BuildContext context) => Row(children: [
        Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
                color: mint, borderRadius: BorderRadius.circular(15)),
            child: const Icon(Icons.directions_car_filled_rounded,
                color: navy, size: 26)),
        const SizedBox(width: 14),
        Expanded(
            child:
                Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(
              '${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}'
                      .trim()
                      .isEmpty
                  ? 'Assigned vehicle'
                  : '${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}',
              style: const TextStyle(
                  color: navy, fontSize: 16, fontWeight: FontWeight.w900)),
          const SizedBox(height: 5),
          Text(
              vehicle['plate_number']?.toString() ??
                  vehicle['plate']?.toString() ??
                  'Plate number unavailable',
              style: const TextStyle(color: muted)),
          if (vehicle['assigned_at'] != null)
            Text('Assigned ${vehicle['assigned_at']}',
                style: const TextStyle(color: muted, fontSize: 11))
        ]))
      ]);
}

class _ActivityRow extends StatelessWidget {
  const _ActivityRow({required this.item, required this.last});
  final Map<String, dynamic> item;
  final bool last;
  @override
  Widget build(BuildContext context) => Container(
      padding: const EdgeInsets.symmetric(vertical: 9),
      decoration: BoxDecoration(
          border: last
              ? null
              : const Border(bottom: BorderSide(color: Color(0xFFF0F1F5)))),
      child: Row(children: [
        StatusIcon(status: (item['status'] ?? 'pending').toString()),
        const SizedBox(width: 11),
        Expanded(
            child:
                Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(
              _pretty((item['type'] ?? item['reference'] ?? 'Transaction')
                  .toString()),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: navy, fontWeight: FontWeight.w800)),
          const SizedBox(height: 3),
          Text(item['created_at']?.toString() ?? '',
              style: const TextStyle(color: muted, fontSize: 11))
        ])),
        Text(money(_num(item['amount'])),
            style: const TextStyle(color: navy, fontWeight: FontWeight.w800))
      ]));
}

class StatusIcon extends StatelessWidget {
  const StatusIcon({super.key, required this.status});
  final String status;
  @override
  Widget build(BuildContext context) {
    final normalized = status.toLowerCase();
    final good =
        ['paid', 'successful', 'approved', 'completed'].contains(normalized);
    final rejected = normalized == 'rejected' || normalized == 'failed';
    final color = good
        ? success
        : rejected
            ? Colors.redAccent
            : const Color(0xFFE39A22);
    return Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
            color: color.withValues(alpha: .12), shape: BoxShape.circle),
        child: Icon(
            good
                ? Icons.check_rounded
                : rejected
                    ? Icons.close_rounded
                    : Icons.schedule_rounded,
            color: color,
            size: 18));
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState(
      {super.key,
      required this.icon,
      required this.title,
      required this.message});
  final IconData icon;
  final String title;
  final String message;
  @override
  Widget build(BuildContext context) => Padding(
      padding: const EdgeInsets.all(25),
      child: Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
                color: mint, borderRadius: BorderRadius.circular(18)),
            child: Icon(icon, color: navy, size: 26)),
        const SizedBox(height: 14),
        Text(title,
            textAlign: TextAlign.center,
            style: const TextStyle(
                color: navy, fontSize: 16, fontWeight: FontWeight.w900)),
        const SizedBox(height: 6),
        Text(message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: muted, fontSize: 12, height: 1.45))
      ])));
}

class LoadingCards extends StatelessWidget {
  const LoadingCards({super.key});
  @override
  Widget build(BuildContext context) => Column(children: [
        for (var i = 0; i < 3; i++)
          Padding(
              padding: const EdgeInsets.only(bottom: 14),
              child: Container(
                  height: i == 0 ? 150 : 78,
                  decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(22))))
      ]);
}

class ErrorCard extends StatelessWidget {
  const ErrorCard({super.key, required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => CardSurface(
          child: Column(children: [
        Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
                color: const Color(0xFFFFF3DD),
                borderRadius: BorderRadius.circular(18)),
            child:
                const Icon(Icons.cloud_off_rounded, color: Color(0xFFE39A22))),
        const SizedBox(height: 14),
        const Text('We could not load this page',
            style: TextStyle(color: navy, fontWeight: FontWeight.w900)),
        const SizedBox(height: 6),
        Text(message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: muted, fontSize: 12)),
        const SizedBox(height: 16),
        OutlinedButton(onPressed: onRetry, child: const Text('Try again'))
      ]));
}

class DriverOverviewPage extends StatefulWidget {
  const DriverOverviewPage({super.key});

  @override
  State<DriverOverviewPage> createState() => _DriverOverviewPageState();
}

class _DriverOverviewPageState extends State<DriverOverviewPage> {
  late Future<Map<String, dynamic>> future;

  @override
  void initState() {
    super.initState();
    future = _load();
  }

  Future<Map<String, dynamic>> _load() => ApiClient.instance
      .get('driver/dashboard')
      .then((response) => _map(response['data']));

  void refresh() => setState(() => future = _load());

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final firstName = auth.displayName.trim().split(' ').first;
    return AppPage(
      title: 'Good ${_partOfDay()}, $firstName',
      subtitle: 'Your payments, progress and vehicle in one place.',
      action: IconButton(
        tooltip: 'Refresh dashboard',
        onPressed: refresh,
        icon: const Icon(Icons.refresh_rounded),
      ),
      child: FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const LoadingCards();
          }
          if (snapshot.hasError) {
            return ErrorCard(
              message: snapshot.error.toString(),
              onRetry: refresh,
            );
          }

          final data = snapshot.data ?? const <String, dynamic>{};
          final wallet = _map(data['wallet']);
          final daily = _map(data['daily_balance']);
          final summary = _map(data['remittance_summary']);
          final totals = _map(data['total_counts']);
          final hirePurchase = _map(data['hire_purchase']);
          final nextRemittance = _map(data['next_remittance']);
          final vehicle = _map(data['vehicle']);
          final recent = _list(data['recent_remittances']);
          final minimumDue = _num(nextRemittance['minimum_amount'] ??
              daily['balance'] ??
              daily['required']);

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _OverviewHero(
                balance: _num(wallet['balance']),
                minimumDue: minimumDue,
                onPay: nextRemittance.isEmpty
                    ? null
                    : () => _openPayments(context),
                onFund: () => _openPayments(context),
              ),
              if (hirePurchase['has_active_contract'] == true) ...[
                const SizedBox(height: 18),
                _HirePurchaseProgressCard(data: hirePurchase),
              ],
              const SizedBox(height: 26),
              const SectionHeading(title: 'Payment overview'),
              const SizedBox(height: 12),
              LayoutBuilder(
                builder: (context, constraints) {
                  final columns = constraints.maxWidth >= 850 ? 4 : 2;
                  final width =
                      (constraints.maxWidth - ((columns - 1) * 12)) / columns;
                  return Wrap(
                    spacing: 12,
                    runSpacing: 12,
                    children: [
                      SizedBox(
                        width: width,
                        child: _DashboardMetric(
                          icon: Icons.task_alt_rounded,
                          label: 'Remittances paid',
                          value:
                              '${summary['paid_count'] ?? totals['paid_remittances'] ?? 0}',
                          color: success,
                        ),
                      ),
                      SizedBox(
                        width: width,
                        child: _DashboardMetric(
                          icon: Icons.pending_actions_rounded,
                          label: 'Pending',
                          value: '${summary['pending_count'] ?? 0}',
                          color: const Color(0xFFE59A22),
                        ),
                      ),
                      SizedBox(
                        width: width,
                        child: _DashboardMetric(
                          icon: Icons.payments_outlined,
                          label: 'Total paid',
                          value: money(_num(summary['total_paid'])),
                          color: navy,
                        ),
                      ),
                      SizedBox(
                        width: width,
                        child: _DashboardMetric(
                          icon: Icons.calendar_month_rounded,
                          label: 'Paid this month',
                          value: money(_num(summary['this_month_paid'])),
                          color: teal,
                        ),
                      ),
                    ],
                  );
                },
              ),
              const SizedBox(height: 26),
              LayoutBuilder(
                builder: (context, constraints) {
                  final remittances = _RecentRemittancesCard(
                    items: recent,
                    onViewAll: () => _openPayments(context),
                  );
                  final assignment = _DashboardVehicleCard(vehicle: vehicle);
                  if (constraints.maxWidth < 820) {
                    return Column(
                      children: [
                        remittances,
                        const SizedBox(height: 18),
                        assignment,
                      ],
                    );
                  }
                  return Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(flex: 3, child: remittances),
                      const SizedBox(width: 18),
                      Expanded(flex: 2, child: assignment),
                    ],
                  );
                },
              ),
            ],
          );
        },
      ),
    );
  }

  void _openPayments(BuildContext context) => Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => const DriverPaymentsPage(fullPage: true),
        ),
      );
}

class _OverviewHero extends StatelessWidget {
  const _OverviewHero({
    required this.balance,
    required this.minimumDue,
    required this.onPay,
    required this.onFund,
  });

  final double balance;
  final double minimumDue;
  final VoidCallback? onPay;
  final VoidCallback onFund;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF17105B), Color(0xFF302784)],
          ),
          borderRadius: BorderRadius.circular(26),
          boxShadow: [
            BoxShadow(
              color: navy.withValues(alpha: .18),
              blurRadius: 28,
              offset: const Offset(0, 14),
            ),
          ],
        ),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final compact = constraints.maxWidth < 650;
            final walletBlock = Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const BrandMark(size: 34, light: true),
                    const SizedBox(width: 9),
                    Text(
                      'AVAILABLE WALLET',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: .68),
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 1.3,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                Text(
                  money(balance),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 30,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -.8,
                  ),
                ),
                const SizedBox(height: 14),
                OutlinedButton.icon(
                  onPressed: onFund,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side:
                        BorderSide(color: Colors.white.withValues(alpha: .35)),
                    minimumSize: const Size(0, 42),
                  ),
                  icon: const Icon(Icons.add_rounded, size: 17),
                  label: const Text('Fund wallet'),
                ),
              ],
            );
            final dueBlock = Container(
              padding: const EdgeInsets.all(17),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: .11),
                borderRadius: BorderRadius.circular(19),
                border: Border.all(color: Colors.white.withValues(alpha: .12)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    onPay == null ? 'TODAY' : 'MINIMUM DUE',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: .62),
                      fontSize: 9,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.3,
                    ),
                  ),
                  const SizedBox(height: 7),
                  Text(
                    onPay == null ? 'All caught up' : money(minimumDue),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  if (onPay != null) ...[
                    const SizedBox(height: 5),
                    Text(
                      'You may pay this amount or more',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: .65),
                        fontSize: 10,
                      ),
                    ),
                    const SizedBox(height: 13),
                    FilledButton.icon(
                      onPressed: onPay,
                      style: FilledButton.styleFrom(
                        backgroundColor: teal,
                        foregroundColor: navyDark,
                        minimumSize: const Size(0, 42),
                      ),
                      icon: const Icon(Icons.arrow_forward_rounded, size: 17),
                      label: const Text('Pay now'),
                    ),
                  ],
                ],
              ),
            );
            if (compact) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [walletBlock, const SizedBox(height: 18), dueBlock],
              );
            }
            return Row(
              children: [
                Expanded(child: walletBlock),
                const SizedBox(width: 22),
                Expanded(child: dueBlock),
              ],
            );
          },
        ),
      );
}

class _HirePurchaseProgressCard extends StatelessWidget {
  const _HirePurchaseProgressCard({required this.data});

  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final progress = (_num(data['progress_percentage']) / 100).clamp(0.0, 1.0);
    final days = data['estimated_payment_days_remaining'] ??
        data['payments_remaining'] ??
        0;
    return CardSurface(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: mint,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: const Icon(Icons.route_rounded, color: navy),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Hire-purchase journey',
                        style: TextStyle(
                            color: navy, fontWeight: FontWeight.w900)),
                    SizedBox(height: 2),
                    Text('Track your ownership progress',
                        style: TextStyle(color: muted, fontSize: 11)),
                  ],
                ),
              ),
              Text(
                '${(progress * 100).toStringAsFixed(0)}%',
                style: const TextStyle(
                    color: teal, fontSize: 20, fontWeight: FontWeight.w900),
              ),
            ],
          ),
          const SizedBox(height: 19),
          ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 10,
              backgroundColor: const Color(0xFFE9EBF3),
              color: teal,
            ),
          ),
          const SizedBox(height: 18),
          LayoutBuilder(
            builder: (context, constraints) {
              final width = (constraints.maxWidth - 18) / 3;
              return Wrap(
                spacing: 9,
                runSpacing: 9,
                children: [
                  SizedBox(
                    width: width,
                    child: _MiniValue(
                        label: 'Remaining',
                        value: money(_num(data['total_balance']))),
                  ),
                  SizedBox(
                    width: width,
                    child: _MiniValue(
                        label: 'Paid so far',
                        value: money(_num(data['total_paid']))),
                  ),
                  SizedBox(
                    width: width,
                    child: _MiniValue(label: 'Est. days left', value: '$days'),
                  ),
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}

class _MiniValue extends StatelessWidget {
  const _MiniValue({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: pageBackground,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: muted, fontSize: 9)),
            const SizedBox(height: 5),
            Text(value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                    color: navy, fontSize: 12, fontWeight: FontWeight.w900)),
          ],
        ),
      );
}

class _DashboardMetric extends StatelessWidget {
  const _DashboardMetric({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) => CardSurface(
        padding: const EdgeInsets.all(15),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: color.withValues(alpha: .11),
                borderRadius: BorderRadius.circular(11),
              ),
              child: Icon(icon, color: color, size: 18),
            ),
            const SizedBox(height: 13),
            Text(value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                    color: navy, fontSize: 16, fontWeight: FontWeight.w900)),
            const SizedBox(height: 4),
            Text(label,
                maxLines: 2,
                style:
                    const TextStyle(color: muted, fontSize: 10, height: 1.3)),
          ],
        ),
      );
}

class _RecentRemittancesCard extends StatelessWidget {
  const _RecentRemittancesCard({
    required this.items,
    required this.onViewAll,
  });

  final List<dynamic> items;
  final VoidCallback onViewAll;

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                  child: SectionHeading(title: 'Recent remittances')),
              TextButton(onPressed: onViewAll, child: const Text('View all')),
            ],
          ),
          const SizedBox(height: 8),
          CardSurface(
            child: items.isEmpty
                ? const EmptyState(
                    icon: Icons.receipt_long_outlined,
                    title: 'No remittances yet',
                    message: 'Generated and paid remittances will appear here.',
                  )
                : Column(
                    children: [
                      for (var index = 0; index < items.length; index++)
                        _RemittanceSummaryRow(
                          item: _map(items[index]),
                          last: index == items.length - 1,
                        ),
                    ],
                  ),
          ),
        ],
      );
}

class _RemittanceSummaryRow extends StatelessWidget {
  const _RemittanceSummaryRow({required this.item, required this.last});

  final Map<String, dynamic> item;
  final bool last;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          border: last
              ? null
              : const Border(bottom: BorderSide(color: Color(0xFFF0F1F5))),
        ),
        child: Row(
          children: [
            StatusIcon(status: (item['status'] ?? 'pending').toString()),
            const SizedBox(width: 11),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(item['created_date']?.toString() ?? 'Daily remittance',
                      style: const TextStyle(
                          color: navy, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 3),
                  Text(_pretty((item['status'] ?? 'pending').toString()),
                      style: const TextStyle(color: muted, fontSize: 10)),
                ],
              ),
            ),
            Text(money(_num(item['amount'])),
                style: const TextStyle(
                    color: navy, fontSize: 12, fontWeight: FontWeight.w900)),
          ],
        ),
      );
}

class _DashboardVehicleCard extends StatelessWidget {
  const _DashboardVehicleCard({required this.vehicle});

  final Map<String, dynamic> vehicle;

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SectionHeading(title: 'Current vehicle'),
          const SizedBox(height: 12),
          CardSurface(
            child: vehicle.isEmpty
                ? const EmptyState(
                    icon: Icons.directions_car_outlined,
                    title: 'No vehicle assigned',
                    message: 'Your active assignment will appear here.',
                  )
                : VehicleSummary(vehicle: vehicle),
          ),
        ],
      );
}

class DriverPaymentsPage extends StatefulWidget {
  const DriverPaymentsPage({super.key, this.fullPage = false});

  final bool fullPage;

  @override
  State<DriverPaymentsPage> createState() => _DriverPaymentsPageState();
}

class _DriverPaymentsPageState extends State<DriverPaymentsPage> {
  late Future<List<Map<String, dynamic>>> future;
  int selectedSegment = 0;
  bool startingPayment = false;

  @override
  void initState() {
    super.initState();
    future = _load();
  }

  Future<List<Map<String, dynamic>>> _load() async {
    final responses = await Future.wait([
      ApiClient.instance.get('driver/dashboard'),
      ApiClient.instance.get('driver/remittance/all'),
      ApiClient.instance.get('driver/transactions'),
    ]);
    return responses;
  }

  void refresh() => setState(() => future = _load());

  @override
  Widget build(BuildContext context) {
    final content = FutureBuilder<List<Map<String, dynamic>>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const LoadingCards();
        }
        if (snapshot.hasError) {
          return ErrorCard(
              message: snapshot.error.toString(), onRetry: refresh);
        }

        final dashboard = _map(snapshot.data![0]['data']);
        final wallet = _map(dashboard['wallet']);
        final hirePurchase = _map(dashboard['hire_purchase']);
        final summary = _map(dashboard['remittance_summary']);
        final allRemittances = _list(snapshot.data![1]['data']);
        final transactions = _list(snapshot.data![2]['data']);
        final pending = allRemittances.where((item) {
          final status = (_map(item)['status'] ?? '').toString().toLowerCase();
          return ['pending', 'due', 'submitted'].contains(status);
        }).toList();
        final history = allRemittances.where((item) {
          final status = (_map(item)['status'] ?? '').toString().toLowerCase();
          return !['pending', 'due', 'submitted'].contains(status);
        }).toList();

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _PaymentsHeader(
              walletBalance: _num(wallet['balance']),
              pendingCount: pending.length,
              paidCount: int.tryParse(
                    (summary['paid_count'] ?? history.length).toString(),
                  ) ??
                  history.length,
              onFund: startingPayment ? null : () => _fundWallet(),
            ),
            const SizedBox(height: 22),
            _PaymentSegments(
              selected: selectedSegment,
              pendingCount: pending.length,
              onChanged: (value) => setState(() => selectedSegment = value),
            ),
            const SizedBox(height: 14),
            if (selectedSegment == 0)
              _RemittanceList(
                items: pending,
                emptyTitle: 'No payment due',
                emptyMessage:
                    'You are up to date. New remittances appear here.',
                onPay: startingPayment
                    ? null
                    : (item) => _payRemittance(
                          item,
                          maximum: _num(hirePurchase['total_balance']),
                        ),
              )
            else if (selectedSegment == 1)
              _RemittanceList(
                items: history,
                emptyTitle: 'No payment history',
                emptyMessage: 'Completed remittances will appear here.',
              )
            else
              _TransactionHistory(items: transactions),
          ],
        );
      },
    );

    if (widget.fullPage) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Payments',
              style: TextStyle(fontWeight: FontWeight.w900)),
        ),
        body: AppPageBody(child: content),
      );
    }

    return AppPage(
      title: 'Payments',
      subtitle: 'Pay remittances and review every transaction.',
      action: IconButton(
        tooltip: 'Refresh payments',
        onPressed: refresh,
        icon: const Icon(Icons.refresh_rounded),
      ),
      child: content,
    );
  }

  Future<void> _fundWallet() async {
    final amount = await _paymentAmountSheet(
      title: 'Fund wallet',
      minimum: 100,
      description: 'Choose how much you want to add to your E-RIDE wallet.',
    );
    if (amount != null) {
      await _createCheckout(purpose: 'wallet_funding', amount: amount);
    }
  }

  Future<void> _payRemittance(
    Map<String, dynamic> item, {
    double maximum = 0,
  }) async {
    final minimum = _num(item['minimum_amount'] ?? item['amount']);
    final amount = await _paymentAmountSheet(
      title: 'Pay remittance',
      minimum: minimum,
      maximum: maximum > 0 ? maximum : null,
      description:
          'The generated amount is the minimum. You can increase it before continuing.',
    );
    if (amount != null) {
      await _createCheckout(
        purpose: 'remittance',
        amount: amount,
        transactionId: item['id'],
      );
    }
  }

  Future<double?> _paymentAmountSheet({
    required String title,
    required double minimum,
    required String description,
    double? maximum,
  }) async {
    final formKey = GlobalKey<FormState>();
    final controller = TextEditingController(
      text: minimum
          .toStringAsFixed(minimum.truncateToDouble() == minimum ? 0 : 2),
    );
    final value = await showModalBottomSheet<double>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (context) => Padding(
        padding: EdgeInsets.fromLTRB(
          22,
          4,
          22,
          MediaQuery.viewInsetsOf(context).bottom + 24,
        ),
        child: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: mint,
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child: const Icon(Icons.payments_outlined, color: navy),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(title,
                        style: const TextStyle(
                            color: navy,
                            fontSize: 20,
                            fontWeight: FontWeight.w900)),
                  ),
                ],
              ),
              const SizedBox(height: 13),
              Text(description,
                  style:
                      const TextStyle(color: muted, fontSize: 12, height: 1.5)),
              const SizedBox(height: 18),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(13),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF7E8),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.info_outline_rounded,
                        color: Color(0xFFD68A16), size: 18),
                    const SizedBox(width: 9),
                    Expanded(
                      child: Text(
                        'Minimum allowed: ${money(minimum)}${maximum != null ? '  •  Maximum: ${money(maximum)}' : ''}',
                        style: const TextStyle(
                            color: Color(0xFF8B5A0E),
                            fontSize: 11,
                            fontWeight: FontWeight.w700),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 15),
              TextFormField(
                controller: controller,
                autofocus: true,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Amount to pay',
                  prefixText: '₦ ',
                  prefixIcon: Icon(Icons.account_balance_wallet_outlined),
                ),
                validator: (value) {
                  final amount =
                      double.tryParse((value ?? '').replaceAll(',', ''));
                  if (amount == null) return 'Enter a valid amount';
                  if (amount < minimum) {
                    return 'Amount cannot be below ${money(minimum)}';
                  }
                  if (maximum != null && amount > maximum) {
                    return 'Amount cannot exceed ${money(maximum)}';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    if (!(formKey.currentState?.validate() ?? false)) return;
                    Navigator.pop(
                      context,
                      double.parse(controller.text.replaceAll(',', '')),
                    );
                  },
                  icon: const Icon(Icons.lock_rounded, size: 17),
                  label: const Text('Continue securely with OPay'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
    controller.dispose();
    return value;
  }

  Future<void> _createCheckout({
    required String purpose,
    required double amount,
    dynamic transactionId,
  }) async {
    setState(() => startingPayment = true);
    try {
      final response = await ApiClient.instance.post(
        'driver/payments/opay/checkout',
        {
          'purpose': purpose,
          'amount': amount,
          if (transactionId != null) 'transaction_id': transactionId,
        },
      );
      final data = _map(response['data']);
      final checkoutUrl = data['checkout_url']?.toString() ?? '';
      final reference = data['reference']?.toString() ?? '';
      final checkoutAmount = _num(data['amount']);
      if (checkoutUrl.isEmpty || reference.isEmpty) {
        throw ApiException('OPay did not return a valid checkout session.');
      }
      if (!mounted) return;

      final result = await Navigator.of(context).push<OpayCheckoutResult>(
        MaterialPageRoute(
          fullscreenDialog: true,
          builder: (_) => OpayCheckoutPage(
            checkoutUrl: checkoutUrl,
            reference: reference,
            amount: checkoutAmount > 0 ? checkoutAmount : amount,
          ),
        ),
      );

      if (!mounted) return;
      if (result != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result.message),
            backgroundColor: result.paid ? success : null,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
      refresh();
    } on ApiException catch (exception) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(exception.message),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => startingPayment = false);
    }
  }
}

class _PaymentsHeader extends StatelessWidget {
  const _PaymentsHeader({
    required this.walletBalance,
    required this.pendingCount,
    required this.paidCount,
    required this.onFund,
  });

  final double walletBalance;
  final int pendingCount;
  final int paidCount;
  final VoidCallback? onFund;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: navy,
          borderRadius: BorderRadius.circular(24),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.account_balance_wallet_rounded,
                    color: teal, size: 24),
                const SizedBox(width: 10),
                const Text('Wallet balance',
                    style: TextStyle(color: Colors.white70, fontSize: 12)),
                const Spacer(),
                FilledButton(
                  onPressed: onFund,
                  style: FilledButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: navy,
                    minimumSize: const Size(0, 40),
                  ),
                  child: const Text('Fund wallet'),
                ),
              ],
            ),
            const SizedBox(height: 17),
            Text(money(walletBalance),
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 27,
                    fontWeight: FontWeight.w900)),
            const SizedBox(height: 14),
            Row(
              children: [
                _HeaderStat(label: 'Pending', value: '$pendingCount'),
                const SizedBox(width: 10),
                _HeaderStat(label: 'Paid', value: '$paidCount'),
              ],
            ),
          ],
        ),
      );
}

class _HeaderStat extends StatelessWidget {
  const _HeaderStat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: .1),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text('$label  $value',
            style: const TextStyle(
                color: Colors.white,
                fontSize: 10,
                fontWeight: FontWeight.w700)),
      );
}

class _PaymentSegments extends StatelessWidget {
  const _PaymentSegments({
    required this.selected,
    required this.pendingCount,
    required this.onChanged,
  });

  final int selected;
  final int pendingCount;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(
          color: const Color(0xFFEDEFF5),
          borderRadius: BorderRadius.circular(15),
        ),
        child: Row(
          children: [
            _SegmentButton(
                label: 'Due ($pendingCount)',
                selected: selected == 0,
                onTap: () => onChanged(0)),
            _SegmentButton(
                label: 'Paid',
                selected: selected == 1,
                onTap: () => onChanged(1)),
            _SegmentButton(
                label: 'All activity',
                selected: selected == 2,
                onTap: () => onChanged(2)),
          ],
        ),
      );
}

class _SegmentButton extends StatelessWidget {
  const _SegmentButton({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Expanded(
        child: Material(
          color: selected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(11),
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(11),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 11, horizontal: 4),
              child: Text(
                label,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: selected ? navy : muted,
                  fontSize: 10,
                  fontWeight: selected ? FontWeight.w900 : FontWeight.w600,
                ),
              ),
            ),
          ),
        ),
      );
}

class _RemittanceList extends StatelessWidget {
  const _RemittanceList({
    required this.items,
    required this.emptyTitle,
    required this.emptyMessage,
    this.onPay,
  });

  final List<dynamic> items;
  final String emptyTitle;
  final String emptyMessage;
  final ValueChanged<Map<String, dynamic>>? onPay;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return CardSurface(
        child: EmptyState(
          icon: Icons.task_alt_rounded,
          title: emptyTitle,
          message: emptyMessage,
        ),
      );
    }
    return Column(
      children: [
        for (final raw in items) ...[
          _RemittancePaymentCard(item: _map(raw), onPay: onPay),
          const SizedBox(height: 11),
        ],
      ],
    );
  }
}

class _RemittancePaymentCard extends StatelessWidget {
  const _RemittancePaymentCard({required this.item, this.onPay});

  final Map<String, dynamic> item;
  final ValueChanged<Map<String, dynamic>>? onPay;

  @override
  Widget build(BuildContext context) {
    final status = (item['status'] ?? 'pending').toString();
    final canPay =
        onPay != null && ['pending', 'due'].contains(status.toLowerCase());
    return CardSurface(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          StatusIcon(status: status),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item['created_date']?.toString() ?? 'Daily remittance',
                    style: const TextStyle(
                        color: navy, fontWeight: FontWeight.w900)),
                const SizedBox(height: 4),
                Text(
                  canPay ? 'Minimum generated amount' : _pretty(status),
                  style: const TextStyle(color: muted, fontSize: 10),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(money(_num(item['amount'])),
                  style: const TextStyle(
                      color: navy, fontWeight: FontWeight.w900)),
              if (canPay)
                TextButton(
                  onPressed: () => onPay!(item),
                  child: const Text('Choose amount'),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _TransactionHistory extends StatelessWidget {
  const _TransactionHistory({required this.items});

  final List<dynamic> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const CardSurface(
        child: EmptyState(
          icon: Icons.receipt_long_outlined,
          title: 'No transaction activity',
          message: 'Wallet and remittance activity will appear here.',
        ),
      );
    }
    return CardSurface(
      child: Column(
        children: [
          for (var index = 0; index < items.length; index++)
            _ActivityRow(
              item: _map(items[index]),
              last: index == items.length - 1,
            ),
        ],
      ),
    );
  }
}

Map<String, dynamic> _map(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};
List<dynamic> _list(dynamic value) => value is List
    ? value
    : value is Map && value['data'] is List
        ? List<dynamic>.from(value['data'])
        : <dynamic>[];
double _num(dynamic value) => value is num
    ? value.toDouble()
    : double.tryParse(value?.toString() ?? '') ?? 0;
String money(double value) =>
    NumberFormat.currency(locale: 'en_NG', symbol: '₦', decimalDigits: 2)
        .format(value);
String _pretty(String value) => value
    .replaceAll('_', ' ')
    .split(' ')
    .map((word) =>
        word.isEmpty ? word : '${word[0].toUpperCase()}${word.substring(1)}')
    .join(' ');
String _initials(String value) {
  final parts = value
      .trim()
      .split(RegExp(r'\s+'))
      .where((part) => part.isNotEmpty)
      .toList();
  if (parts.isEmpty) return 'D';
  return parts.length == 1
      ? parts.first[0].toUpperCase()
      : '${parts.first[0]}${parts.last[0]}'.toUpperCase();
}

String _partOfDay() {
  final hour = DateTime.now().hour;
  if (hour < 12) return 'morning';
  if (hour < 17) return 'afternoon';
  return 'evening';
}
