import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../data/db.dart';
import '../../state/providers.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _u = TextEditingController(text: 'admin');
  final _p = TextEditingController(text: 'admin');
  bool _loading = false;
  String? _error;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 360),
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('Masuk', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  TextField(controller: _u, decoration: const InputDecoration(labelText: 'Username')),
                  TextField(controller: _p, decoration: const InputDecoration(labelText: 'Password'), obscureText: true),
                  const SizedBox(height: 12),
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  const SizedBox(height: 8),
                  FilledButton(
                    onPressed: _loading
                        ? null
                        : () async {
                            setState(() => _loading = true);
                            final ok = await _login(_u.text.trim(), _p.text);
                            setState(() => _loading = false);
                            if (ok) {
                              ref.read(authProvider.notifier).setUser(_u.text.trim());
                              if (context.mounted) context.go('/pos');
                            } else {
                              setState(() => _error = 'Login gagal');
                            }
                          },
                    child: _loading ? const CircularProgressIndicator() : const Text('Masuk'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<bool> _login(String u, String p) async {
    final db = await AppDatabase.instance();
    final rows = await db.query('users', where: 'username=? AND password_hash=? AND active=1', whereArgs: [u, p], limit: 1);
    return rows.isNotEmpty;
  }
}
