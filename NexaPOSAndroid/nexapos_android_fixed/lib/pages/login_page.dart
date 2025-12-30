import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import 'dashboard_page.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final userC = TextEditingController(text: 'admin');
  final passC = TextEditingController(text: 'admin');
  bool loading = false;
  String? error;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Card(
          margin: const EdgeInsets.all(24),
          color: const Color(0xFF111827),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 360),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('NexaPOS Android', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  TextField(
                    controller: userC,
                    decoration: const InputDecoration(labelText: 'Username'),
                  ),
                  TextField(
                    controller: passC,
                    decoration: const InputDecoration(labelText: 'Password'),
                    obscureText: true,
                  ),
                  if (error != null) Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(error!, style: const TextStyle(color: Colors.red)),
                  ),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: loading ? null : () async {
                      setState(() { loading = true; error = null; });
                      final svc = AuthService();
                      final user = await svc.login(userC.text, passC.text);
                      if (!mounted) return;
                      if (user == null) {
                        setState(() { error = 'Login gagal'; loading = false; });
                      } else {
                        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const DashboardPage()));
                      }
                    },
                    child: loading ? const CircularProgressIndicator() : const Text('Login'),
                  )
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
