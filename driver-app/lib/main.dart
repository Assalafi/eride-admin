import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import 'core/api_client.dart';
import 'core/auth_state.dart';

const navy = Color(0xFF190A62);
const teal = Color(0xFF39B6C4);
const pageBackground = Color(0xFFF6F8FC);

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(ChangeNotifierProvider(create: (_) => AuthState()..initialize(), child: const DriverApp()));
}

class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'E-RIDE Driver',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: pageBackground,
        colorScheme: ColorScheme.fromSeed(seedColor: navy, primary: navy, secondary: teal),
        textTheme: GoogleFonts.manropeTextTheme(),
        appBarTheme: const AppBarTheme(backgroundColor: pageBackground, foregroundColor: navy, elevation: 0),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: Colors.grey.shade200)),
          focusedBorder: const OutlineInputBorder(borderRadius: BorderRadius.all(Radius.circular(16)), borderSide: BorderSide(color: teal, width: 2)),
          contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
        ),
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
    return const Scaffold(
      backgroundColor: Colors.white,
      body: Center(child: Image(image: AssetImage('assets/images/splash.png'), width: 235)),
    );
  }
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
    if (!success && mounted) _toast(auth.error ?? 'Biometric login is not available yet');
  }

  void _toast(String message) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message), behavior: SnackBarBehavior.floating));

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final wide = MediaQuery.sizeOf(context).width >= 900;
    final form = Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 440),
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Form(
            key: formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Image.asset('assets/images/logo.png', width: 64, height: 64),
                const SizedBox(height: 30),
                Text('Welcome back', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800, color: navy)),
                const SizedBox(height: 8),
                Text('Sign in to manage your E-RIDE driver account.', style: TextStyle(color: Colors.grey.shade600)),
                const SizedBox(height: 34),
                TextFormField(
                  controller: email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'Email address', prefixIcon: Icon(Icons.mail_outline_rounded)),
                  validator: (value) => value == null || !value.contains('@') ? 'Enter a valid email address' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: password,
                  obscureText: obscure,
                  decoration: InputDecoration(labelText: 'Password', prefixIcon: const Icon(Icons.lock_outline_rounded), suffixIcon: IconButton(onPressed: () => setState(() => obscure = !obscure), icon: Icon(obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined))),
                  validator: (value) => value == null || value.length < 6 ? 'Enter your password' : null,
                  onFieldSubmitted: (_) => submit(),
                ),
                const SizedBox(height: 22),
                SizedBox(
                  width: double.infinity,
                  height: 54,
                  child: FilledButton(
                    onPressed: auth.busy ? null : submit,
                    style: FilledButton.styleFrom(backgroundColor: navy, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16))),
                    child: auth.busy ? const SizedBox.square(dimension: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Sign in', style: TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ),
                if (auth.hasSavedSession && !kIsWeb) ...[
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: OutlinedButton.icon(onPressed: auth.busy ? null : biometric, icon: const Icon(Icons.fingerprint_rounded), label: const Text('Unlock with biometrics'), style: OutlinedButton.styleFrom(foregroundColor: navy, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)))),
                  ),
                ],
                const SizedBox(height: 30),
                Center(child: Text('Your existing E-RIDE credentials work here.', style: TextStyle(color: Colors.grey.shade600, fontSize: 12))),
              ],
            ),
          ),
        ),
      ),
    );

    if (!wide) return Scaffold(body: SafeArea(child: SingleChildScrollView(child: form)));
    return Scaffold(
      body: Row(
        children: [
          Expanded(
            child: Container(
              color: navy,
              padding: const EdgeInsets.all(64),
              child: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.start, children: [
                Image.asset('assets/images/splash.png', width: 250),
                const SizedBox(height: 52),
                const Text('Every trip starts\nwith a better day.', style: TextStyle(color: Colors.white, fontSize: 42, height: 1.1, fontWeight: FontWeight.w800)),
                const SizedBox(height: 18),
                Text('Stay on top of your wallet, remittances and vehicle assignment in one calm, focused workspace.', style: TextStyle(color: Colors.white.withValues(alpha: .75), fontSize: 16, height: 1.6)),
              ]),
            ),
          ),
          Expanded(child: form),
        ],
      ),
    );
  }
}

class AppShell extends StatefulWidget {
  const AppShell({super.key});

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int selected = 0;
  final pages = const [DashboardPage(), PaymentsPage(), VehiclePage(), ProfilePage()];
  final labels = const ['Home', 'Payments', 'Vehicle', 'Profile'];
  final icons = const [Icons.grid_view_rounded, Icons.account_balance_wallet_rounded, Icons.directions_car_filled_rounded, Icons.person_rounded];

  @override
  Widget build(BuildContext context) {
    final wide = MediaQuery.sizeOf(context).width >= 900;
    final body = IndexedStack(index: selected, children: pages);
    return Scaffold(
      body: wide
          ? Row(children: [
              NavigationRail(
                selectedIndex: selected,
                onDestinationSelected: (index) => setState(() => selected = index),
                labelType: NavigationRailLabelType.all,
                backgroundColor: Colors.white,
                leading: Padding(padding: const EdgeInsets.only(top: 28, bottom: 40), child: Image.asset('assets/images/logo.png', width: 48, height: 48)),
                destinations: [for (var i = 0; i < labels.length; i++) NavigationRailDestination(icon: Icon(icons[i]), selectedIcon: Icon(icons[i]), label: Text(labels[i]))],
              ),
              const VerticalDivider(width: 1),
              Expanded(child: body),
            ])
          : body,
      bottomNavigationBar: wide
          ? null
          : NavigationBar(
              selectedIndex: selected,
              onDestinationSelected: (index) => setState(() => selected = index),
              destinations: [for (var i = 0; i < labels.length; i++) NavigationDestination(icon: Icon(icons[i]), label: labels[i])],
            ),
    );
  }
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
    future = ApiClient.instance.get('driver/dashboard').then((value) => _map(value['data']));
  }

  void refresh() => setState(() => future = ApiClient.instance.get('driver/dashboard').then((value) => _map(value['data'])));

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    return AppPage(
      title: 'Good day, ${auth.displayName.split(' ').first}',
      subtitle: 'Here is your driver overview.',
      action: IconButton(onPressed: refresh, icon: const Icon(Icons.refresh_rounded)),
      child: FutureBuilder<Map<String, dynamic>>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) return const LoadingCards();
          if (snapshot.hasError) return ErrorCard(message: snapshot.error.toString(), onRetry: refresh);
          final data = snapshot.data ?? {};
          final wallet = _num(data['wallet_balance'] ?? data['wallet']?['balance']);
          final due = _num(data['today']?['balance'] ?? data['today_ledger']?['balance'] ?? data['pending_remittance']);
          final vehicle = data['current_vehicle'] ?? data['vehicle'];
          return RefreshIndicator(
            onRefresh: () async => refresh(),
            child: ListView(shrinkWrap: true, physics: const AlwaysScrollableScrollPhysics(), children: [
              Wrap(spacing: 16, runSpacing: 16, children: [
                MetricCard(title: 'Wallet balance', value: money(wallet), icon: Icons.account_balance_wallet_rounded, color: teal),
                MetricCard(title: 'Today’s due', value: money(due), icon: Icons.event_note_rounded, color: navy),
                MetricCard(title: 'Account status', value: 'Active', icon: Icons.verified_rounded, color: const Color(0xFF3DAE74)),
              ]),
              const SizedBox(height: 24),
              SectionHeading(title: 'Quick actions', action: null),
              const SizedBox(height: 12),
              Wrap(spacing: 12, runSpacing: 12, children: [
                QuickAction(icon: Icons.payments_outlined, title: 'Pay remittance', onTap: () => _openPayments(context)),
                QuickAction(icon: Icons.add_card_rounded, title: 'Fund wallet', onTap: () => _openPayments(context)),
                QuickAction(icon: Icons.directions_car_filled_outlined, title: 'View vehicle', onTap: () => _openVehicle(context)),
              ]),
              const SizedBox(height: 24),
              SectionHeading(title: 'Current vehicle', action: null),
              const SizedBox(height: 12),
              CardSurface(child: vehicle is Map ? VehicleSummary(vehicle: Map<String, dynamic>.from(vehicle)) : const EmptyState(icon: Icons.directions_car_outlined, title: 'No vehicle assigned', message: 'Your current assignment will appear here.')),
            ]),
          );
        },
      ),
    );
  }

  void _openPayments(BuildContext context) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PaymentsPage(fullPage: true)));
  void _openVehicle(BuildContext context) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const VehiclePage(fullPage: true)));
}

class PaymentsPage extends StatefulWidget {
  const PaymentsPage({super.key, this.fullPage = false});
  final bool fullPage;

  @override
  State<PaymentsPage> createState() => _PaymentsPageState();
}

class _PaymentsPageState extends State<PaymentsPage> with SingleTickerProviderStateMixin {
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

  Future<List<dynamic>> _load() async {
    final responses = await Future.wait([
      ApiClient.instance.get('driver/wallet'),
      ApiClient.instance.get('driver/remittance/all'),
      ApiClient.instance.get('driver/transactions'),
    ]);
    return responses;
  }

  void refresh() => setState(() => future = _load());

  @override
  Widget build(BuildContext context) {
    final content = FutureBuilder<List<dynamic>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) return const LoadingCards();
        if (snapshot.hasError) return ErrorCard(message: snapshot.error.toString(), onRetry: refresh);
        final responses = snapshot.data!;
        final wallet = _map(responses[0]['data']);
        final remittances = _list(responses[1]['data']);
        final transactions = _list(responses[2]['data']);
        return Column(children: [
          CardSurface(child: Row(children: [
            Container(width: 48, height: 48, decoration: BoxDecoration(color: teal.withValues(alpha: .14), borderRadius: BorderRadius.circular(15)), child: const Icon(Icons.account_balance_wallet_rounded, color: teal)),
            const SizedBox(width: 14),
            Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Available wallet balance', style: TextStyle(color: Colors.grey.shade600)), const SizedBox(height: 3), Text(money(_num(wallet['balance'] ?? wallet['wallet_balance'])), style: const TextStyle(color: navy, fontSize: 25, fontWeight: FontWeight.w800))]),
            const Spacer(),
            FilledButton(onPressed: () => _startCheckout('wallet_funding'), child: const Text('Fund wallet')),
          ])),
          const SizedBox(height: 20),
          TabBar(controller: tabs, isScrollable: true, tabAlignment: TabAlignment.start, labelColor: navy, tabs: const [Tab(text: 'Remittance'), Tab(text: 'Transactions'), Tab(text: 'Wallet funding')]),
          const SizedBox(height: 16),
          SizedBox(height: 410, child: TabBarView(controller: tabs, children: [
            _remittanceList(remittances),
            _transactionList(transactions),
            const EmptyState(icon: Icons.receipt_long_outlined, title: 'Wallet funding history', message: 'Paid wallet funding requests will appear in your transaction history.'),
          ])),
        ]);
      },
    );

    if (widget.fullPage) return Scaffold(appBar: AppBar(title: const Text('Payments')), body: AppPageBody(child: content));
    return AppPage(title: 'Payments', subtitle: 'Your wallet and payment activity.', action: IconButton(onPressed: refresh, icon: const Icon(Icons.refresh_rounded)), child: content);
  }

  Widget _remittanceList(List<dynamic> items) {
    if (items.isEmpty) return const EmptyState(icon: Icons.check_circle_outline_rounded, title: 'You are all caught up', message: 'There are no remittances to display.');
    return ListView.separated(itemCount: items.length, separatorBuilder: (_, __) => const SizedBox(height: 10), itemBuilder: (context, index) {
      final item = _map(items[index]);
      final status = (item['status'] ?? 'pending').toString();
      return CardSurface(child: ListTile(contentPadding: EdgeInsets.zero, leading: StatusIcon(status: status), title: Text(item['reference']?.toString() ?? 'Daily remittance', style: const TextStyle(fontWeight: FontWeight.w700)), subtitle: Text(item['created_date']?.toString() ?? item['created_at']?.toString() ?? 'Pending'), trailing: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.end, children: [Text(money(_num(item['amount'])), style: const TextStyle(fontWeight: FontWeight.w800, color: navy)), if (status == 'pending' || status == 'due') TextButton(onPressed: () => _startCheckout('remittance', amount: _num(item['amount']), transactionId: item['id']), child: const Text('Pay now'))])));
    });
  }

  Widget _transactionList(List<dynamic> items) {
    if (items.isEmpty) return const EmptyState(icon: Icons.receipt_long_outlined, title: 'No transactions yet', message: 'Your payment activity will appear here.');
    return ListView.separated(itemCount: items.length, separatorBuilder: (_, __) => const SizedBox(height: 10), itemBuilder: (context, index) {
      final item = _map(items[index]);
      final status = (item['status'] ?? 'pending').toString();
      return CardSurface(child: ListTile(contentPadding: EdgeInsets.zero, leading: StatusIcon(status: status), title: Text(_pretty((item['type'] ?? 'transaction').toString()), style: const TextStyle(fontWeight: FontWeight.w700)), subtitle: Text(item['created_at']?.toString() ?? ''), trailing: Text(money(_num(item['amount'])), style: const TextStyle(fontWeight: FontWeight.w800, color: navy))));
    });
  }

  Future<void> _startCheckout(String purpose, {double? amount, dynamic transactionId}) async {
    final chosen = amount ?? await _amountDialog(context, purpose == 'wallet_funding' ? 'Fund wallet' : 'Pay remittance');
    if (chosen == null || chosen <= 0 || !mounted) return;
    try {
      final response = await ApiClient.instance.post('driver/payments/opay/checkout', {'purpose': purpose, 'amount': chosen, if (transactionId != null) 'transaction_id': transactionId});
      final data = _map(response['data']);
      final url = Uri.tryParse(data['checkout_url']?.toString() ?? '');
      if (url == null || !await launchUrl(url, mode: LaunchMode.externalApplication)) throw ApiException('Could not open OPay checkout.');
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('OPay checkout opened. Return here and refresh after payment.'), behavior: SnackBarBehavior.floating));
    } on ApiException catch (exception) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(exception.message), behavior: SnackBarBehavior.floating));
    }
  }

  Future<double?> _amountDialog(BuildContext context, String title) async {
    final controller = TextEditingController();
    return showDialog<double>(context: context, builder: (context) => AlertDialog(title: Text(title), content: TextField(controller: controller, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(prefixText: '₦ ', labelText: 'Amount')), actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')), FilledButton(onPressed: () => Navigator.pop(context, double.tryParse(controller.text.trim())), child: const Text('Continue'))]));
  }
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
    future = ApiClient.instance.get('driver/vehicle/current').then((value) => _map(value['data']));
  }

  @override
  Widget build(BuildContext context) {
    final content = FutureBuilder<Map<String, dynamic>>(future: future, builder: (context, snapshot) {
      if (snapshot.connectionState == ConnectionState.waiting) return const LoadingCards();
      if (snapshot.hasError) return ErrorCard(message: snapshot.error.toString(), onRetry: () => setState(() => future = ApiClient.instance.get('driver/vehicle/current').then((value) => _map(value['data']))));
      final data = snapshot.data ?? {};
      if (data.isEmpty) return const EmptyState(icon: Icons.directions_car_outlined, title: 'No current assignment', message: 'Your assigned vehicle will appear here when it is available.');
      return Column(children: [
        CardSurface(child: VehicleSummary(vehicle: data)),
        const SizedBox(height: 22),
        CardSurface(child: ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.history_rounded, color: navy), title: const Text('Assignment history', style: TextStyle(fontWeight: FontWeight.w700)), subtitle: const Text('View previous vehicle assignments'), trailing: const Icon(Icons.chevron_right_rounded), onTap: () => _showHistory(context))),
      ]);
    });
    if (widget.fullPage) return Scaffold(appBar: AppBar(title: const Text('Vehicle')), body: AppPageBody(child: content));
    return AppPage(title: 'Vehicle', subtitle: 'Your current assignment at a glance.', child: content);
  }

  Future<void> _showHistory(BuildContext context) async {
    try {
      final response = await ApiClient.instance.get('driver/vehicle/history');
      final list = _list(response['data']);
      if (!context.mounted) return;
      showModalBottomSheet(context: context, showDragHandle: true, builder: (_) => SafeArea(child: Padding(padding: const EdgeInsets.all(22), child: list.isEmpty ? const EmptyState(icon: Icons.history, title: 'No history', message: 'No previous assignments were found.') : ListView.separated(itemCount: list.length, separatorBuilder: (_, __) => const Divider(), itemBuilder: (_, index) { final item = _map(list[index]); return ListTile(title: Text('${item['make'] ?? ''} ${item['model'] ?? ''}'), subtitle: Text(item['assigned_at']?.toString() ?? ''), trailing: Text(item['plate_number']?.toString() ?? '')); }))));
    } on ApiException catch (exception) {
      if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(exception.message)));
    }
  }
}

class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    return AppPage(title: 'Profile', subtitle: 'Keep your account secure and up to date.', child: Column(children: [
      CardSurface(child: Row(children: [CircleAvatar(radius: 29, backgroundColor: teal.withValues(alpha: .18), child: Text(auth.displayName.isEmpty ? 'D' : auth.displayName[0].toUpperCase(), style: const TextStyle(color: navy, fontSize: 24, fontWeight: FontWeight.w800))), const SizedBox(width: 16), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(auth.displayName, style: const TextStyle(color: navy, fontSize: 18, fontWeight: FontWeight.w800)), const SizedBox(height: 4), Text(auth.email, style: TextStyle(color: Colors.grey.shade600))]))])),
      const SizedBox(height: 18),
      CardSurface(child: Column(children: [
        ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.shield_outlined, color: navy), title: const Text('Biometric login', style: TextStyle(fontWeight: FontWeight.w700)), subtitle: Text(kIsWeb ? 'Available on Android only' : 'Unlock the app with your device biometrics'), trailing: Switch(value: auth.biometricEnabled, onChanged: kIsWeb ? null : (value) async { final ok = await auth.toggleBiometric(value); if (!ok && context.mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Biometrics are not available on this device.'))); })),
        const Divider(height: 18),
        ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.lock_outline_rounded, color: navy), title: const Text('Change password', style: TextStyle(fontWeight: FontWeight.w700)), trailing: const Icon(Icons.chevron_right_rounded), onTap: () => _changePassword(context)),
      ])),
      const SizedBox(height: 18),
      CardSurface(child: ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.logout_rounded, color: Colors.redAccent), title: const Text('Sign out', style: TextStyle(color: Colors.redAccent, fontWeight: FontWeight.w700)), onTap: () => context.auth.logout())),
    ]));
  }

  Future<void> _changePassword(BuildContext context) async {
    final current = TextEditingController();
    final next = TextEditingController();
    final confirm = TextEditingController();
    final form = GlobalKey<FormState>();
    await showDialog(context: context, builder: (context) => AlertDialog(title: const Text('Change password'), content: Form(key: form, child: Column(mainAxisSize: MainAxisSize.min, children: [TextFormField(controller: current, obscureText: true, decoration: const InputDecoration(labelText: 'Current password'), validator: (v) => v == null || v.isEmpty ? 'Required' : null), const SizedBox(height: 12), TextFormField(controller: next, obscureText: true, decoration: const InputDecoration(labelText: 'New password'), validator: (v) => v == null || v.length < 6 ? 'Use at least 6 characters' : null), const SizedBox(height: 12), TextFormField(controller: confirm, obscureText: true, decoration: const InputDecoration(labelText: 'Confirm password'), validator: (v) => v != next.text ? 'Passwords do not match' : null)])), actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')), FilledButton(onPressed: () async { if (!(form.currentState?.validate() ?? false)) return; try { await ApiClient.instance.post('driver/profile/change-password', {'current_password': current.text, 'password': next.text, 'password_confirmation': confirm.text}); if (context.mounted) { Navigator.pop(context); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password changed successfully.'))); } } on ApiException catch (e) { if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message))); } }, child: const Text('Update'))]));
    current.dispose(); next.dispose(); confirm.dispose();
  }
}

class AppPage extends StatelessWidget {
  const AppPage({super.key, required this.title, required this.subtitle, required this.child, this.action});
  final String title;
  final String subtitle;
  final Widget child;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: action == null ? null : Row(children: [const Spacer(), action!]), automaticallyImplyLeading: false), body: AppPageBody(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: Theme.of(context).textTheme.headlineSmall?.copyWith(color: navy, fontWeight: FontWeight.w800)), const SizedBox(height: 5), Text(subtitle, style: TextStyle(color: Colors.grey.shade600)), const SizedBox(height: 24), child])));
}

class AppPageBody extends StatelessWidget {
  const AppPageBody({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => LayoutBuilder(builder: (context, constraints) => SingleChildScrollView(padding: EdgeInsets.fromLTRB(MediaQuery.sizeOf(context).width >= 900 ? 42 : 20, 8, MediaQuery.sizeOf(context).width >= 900 ? 42 : 20, 32), child: ConstrainedBox(constraints: BoxConstraints(maxWidth: 1180, minWidth: constraints.maxWidth < 1200 ? 0 : 900), child: child)));
}

class CardSurface extends StatelessWidget {
  const CardSurface({super.key, required this.child});
  final Widget child;
  @override
  Widget build(BuildContext context) => Card(margin: EdgeInsets.zero, elevation: 0, color: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22), side: BorderSide(color: Colors.grey.shade100)), child: Padding(padding: const EdgeInsets.all(20), child: child));
}

class MetricCard extends StatelessWidget {
  const MetricCard({super.key, required this.title, required this.value, required this.icon, required this.color});
  final String title, value;
  final IconData icon;
  final Color color;
  @override
  Widget build(BuildContext context) => SizedBox(width: 260, child: CardSurface(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Container(width: 42, height: 42, decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(13)), child: Icon(icon, color: color)), const SizedBox(height: 18), Text(title, style: TextStyle(color: Colors.grey.shade600)), const SizedBox(height: 5), Text(value, style: const TextStyle(fontSize: 22, color: navy, fontWeight: FontWeight.w800))])));
}

class QuickAction extends StatelessWidget {
  const QuickAction({super.key, required this.icon, required this.title, required this.onTap});
  final IconData icon;
  final String title;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => InkWell(onTap: onTap, borderRadius: BorderRadius.circular(18), child: Container(width: 180, padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), border: Border.all(color: Colors.grey.shade100)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Icon(icon, color: navy), const SizedBox(height: 20), Text(title, style: const TextStyle(color: navy, fontWeight: FontWeight.w700))])));
}

class SectionHeading extends StatelessWidget {
  const SectionHeading({super.key, required this.title, this.action});
  final String title;
  final Widget? action;
  @override
  Widget build(BuildContext context) => Row(children: [Text(title, style: const TextStyle(color: navy, fontSize: 17, fontWeight: FontWeight.w800)), const Spacer(), if (action != null) action!]);
}

class VehicleSummary extends StatelessWidget {
  const VehicleSummary({super.key, required this.vehicle});
  final Map<String, dynamic> vehicle;
  @override
  Widget build(BuildContext context) => Row(children: [Container(width: 58, height: 58, decoration: BoxDecoration(color: navy.withValues(alpha: .08), borderRadius: BorderRadius.circular(17)), child: const Icon(Icons.directions_car_filled_rounded, color: navy, size: 29)), const SizedBox(width: 16), Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}'.trim().isEmpty ? 'Assigned vehicle' : '${vehicle['make'] ?? ''} ${vehicle['model'] ?? ''}', style: const TextStyle(color: navy, fontSize: 17, fontWeight: FontWeight.w800)), const SizedBox(height: 5), Text(vehicle['plate_number']?.toString() ?? vehicle['plate']?.toString() ?? 'Plate number unavailable', style: TextStyle(color: Colors.grey.shade600)), if (vehicle['assigned_at'] != null) Text('Assigned ${vehicle['assigned_at']}', style: TextStyle(color: Colors.grey.shade500, fontSize: 12))]))]);
}

class StatusIcon extends StatelessWidget {
  const StatusIcon({super.key, required this.status});
  final String status;
  @override
  Widget build(BuildContext context) { final good = ['paid', 'successful', 'approved', 'completed'].contains(status.toLowerCase()); final color = good ? const Color(0xFF3DAE74) : status.toLowerCase() == 'rejected' ? Colors.redAccent : teal; return CircleAvatar(backgroundColor: color.withValues(alpha: .12), child: Icon(good ? Icons.check_rounded : Icons.schedule_rounded, color: color)); }
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.icon, required this.title, required this.message});
  final IconData icon;
  final String title, message;
  @override
  Widget build(BuildContext context) => Padding(padding: const EdgeInsets.all(30), child: Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(icon, color: teal, size: 42), const SizedBox(height: 14), Text(title, style: const TextStyle(color: navy, fontSize: 17, fontWeight: FontWeight.w800)), const SizedBox(height: 6), Text(message, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600))])));
}

class LoadingCards extends StatelessWidget {
  const LoadingCards({super.key});
  @override
  Widget build(BuildContext context) => const Center(child: Padding(padding: EdgeInsets.all(55), child: CircularProgressIndicator(color: teal)));
}

class ErrorCard extends StatelessWidget {
  const ErrorCard({super.key, required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) => CardSurface(child: Column(children: [const Icon(Icons.cloud_off_rounded, color: Colors.orange, size: 38), const SizedBox(height: 12), const Text('We could not load this page', style: TextStyle(color: navy, fontWeight: FontWeight.w800)), const SizedBox(height: 6), Text(message, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600)), const SizedBox(height: 16), OutlinedButton(onPressed: onRetry, child: const Text('Try again'))]));
}

Map<String, dynamic> _map(dynamic value) => value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};
List<dynamic> _list(dynamic value) => value is List ? value : value is Map && value['data'] is List ? List<dynamic>.from(value['data']) : <dynamic>[];
double _num(dynamic value) => value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '') ?? 0;
String money(double value) => NumberFormat.currency(locale: 'en_NG', symbol: '₦', decimalDigits: 2).format(value);
String _pretty(String value) => value.replaceAll('_', ' ').split(' ').map((word) => word.isEmpty ? word : '${word[0].toUpperCase()}${word.substring(1)}').join(' ');
