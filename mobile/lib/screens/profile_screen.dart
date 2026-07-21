import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/logout_action.dart';
import 'home_screen.dart' show membershipProvider;

final memberProfileProvider = FutureProvider.autoDispose((ref) async {
  final client = ref.watch(apiClientProvider);
  return client.fetchProfile();
});

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileAsync = ref.watch(memberProfileProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('MY PROFILE'), actions: const [LogoutAction()]),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(memberProfileProvider);
          ref.invalidate(membershipProvider);
        },
        color: AppColors.gold,
        backgroundColor: AppColors.ink2,
        child: profileAsync.when(
          data: (member) => ListView(
            padding: const EdgeInsets.all(20),
            children: [
              _AvatarHeader(member: member),
              const SizedBox(height: 24),
              _MembershipSummary(),
              const SizedBox(height: 24),
              _EditDetailsCard(member: member),
              const SizedBox(height: 24),
              const _ChangePasswordCard(),
            ],
          ),
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (err, _) => Center(
            child: Text('Could not load profile: $err', style: const TextStyle(color: AppColors.tape)),
          ),
        ),
      ),
    );
  }
}

class _AvatarHeader extends ConsumerStatefulWidget {
  const _AvatarHeader({required this.member});

  final Map<String, dynamic> member;

  @override
  ConsumerState<_AvatarHeader> createState() => _AvatarHeaderState();
}

class _AvatarHeaderState extends ConsumerState<_AvatarHeader> {
  bool _uploading = false;

  Future<void> _pickPhoto() async {
    final picked = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: 800, imageQuality: 85);
    if (picked == null) return;

    setState(() => _uploading = true);
    try {
      final client = ref.read(apiClientProvider);
      await client.uploadPhoto(File(picked.path));
      ref.invalidate(memberProfileProvider);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
        );
      }
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final client = ref.watch(apiClientProvider);
    final photoUrl = widget.member['photo_url'] as String?;
    final name = (widget.member['name'] as String?) ?? '';

    return Center(
      child: Column(
        children: [
          GestureDetector(
            onTap: _uploading ? null : _pickPhoto,
            child: Stack(
              children: [
                Container(
                  width: 92,
                  height: 92,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: AppColors.gold, width: 2),
                    color: AppColors.ink2,
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: photoUrl != null
                      ? Image.network(
                          photoUrl,
                          headers: {'Authorization': 'Bearer ${client.authToken}'},
                          fit: BoxFit.cover,
                        )
                      : Center(
                          child: Text(
                            name.isNotEmpty ? name[0].toUpperCase() : '?',
                            style: AppTheme.display(fontSize: 34, color: AppColors.gold),
                          ),
                        ),
                ),
                if (_uploading)
                  Positioned.fill(
                    child: DecoratedBox(
                      decoration: const BoxDecoration(shape: BoxShape.circle, color: Colors.black45),
                      child: const Center(
                        child: SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.gold),
                        ),
                      ),
                    ),
                  ),
                Positioned(
                  right: 0,
                  bottom: 0,
                  child: Container(
                    padding: const EdgeInsets.all(5),
                    decoration: const BoxDecoration(shape: BoxShape.circle, color: AppColors.gold),
                    child: const Icon(Icons.camera_alt, size: 14, color: AppColors.ink),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Text(name, style: AppTheme.display(fontSize: 18, fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text(
            '${widget.member['display_code'] ?? ''}  ·  ${widget.member['email'] ?? ''}',
            style: AppTheme.mono(fontSize: 12, color: AppColors.steel2),
          ),
        ],
      ),
    );
  }
}

class _MembershipSummary extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final membershipAsync = ref.watch(membershipProvider);

    return membershipAsync.when(
      data: (data) {
        final membership = data['membership'] as Map<String, dynamic>?;
        final trainer = data['trainer'] as Map<String, dynamic>?;
        if (membership == null) return const SizedBox.shrink();

        final plan = membership['plan'] as Map<String, dynamic>;

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: AppColors.inkLine),
            color: AppColors.ink2,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('MEMBERSHIP', style: AppTheme.display(fontSize: 11, color: AppColors.steel2, letterSpacing: 1.5)),
              const SizedBox(height: 8),
              _row('Plan', plan['name'] as String),
              _row('Ends', membership['end_date'].toString().split('T').first),
              if (trainer != null) _row('Trainer', trainer['name'] as String),
            ],
          ),
        );
      },
      loading: () => const SizedBox.shrink(),
      error: (_, _) => const SizedBox.shrink(),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: AppTheme.mono(fontSize: 12, color: AppColors.steel2)),
          Text(value, style: AppTheme.mono(fontSize: 12, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

class _EditDetailsCard extends ConsumerStatefulWidget {
  const _EditDetailsCard({required this.member});

  final Map<String, dynamic> member;

  @override
  ConsumerState<_EditDetailsCard> createState() => _EditDetailsCardState();
}

class _EditDetailsCardState extends ConsumerState<_EditDetailsCard> {
  late final _nameController = TextEditingController(text: widget.member['name'] as String? ?? '');
  late final _phoneController = TextEditingController(text: widget.member['phone'] as String? ?? '');
  bool _saving = false;
  String? _error;

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final client = ref.read(apiClientProvider);
      await client.updateProfile(name: _nameController.text.trim(), phone: _phoneController.text.trim());
      ref.invalidate(memberProfileProvider);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated.')));
      }
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('DETAILS', style: AppTheme.display(fontSize: 13, fontWeight: FontWeight.w600, letterSpacing: 1)),
            const SizedBox(height: 14),
            TextField(
              controller: _nameController,
              decoration: const InputDecoration(labelText: 'Name'),
              style: const TextStyle(color: AppColors.chalk),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'Phone'),
              style: const TextStyle(color: AppColors.chalk),
            ),
            const SizedBox(height: 10),
            TextField(
              enabled: false,
              controller: TextEditingController(text: widget.member['email'] as String? ?? ''),
              decoration: const InputDecoration(labelText: 'Email (contact your gym to change)'),
              style: const TextStyle(color: AppColors.steel2),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: AppColors.tape)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.ink),
                    )
                  : const Text('SAVE CHANGES'),
            ),
          ],
        ),
      ),
    );
  }
}

class _ChangePasswordCard extends ConsumerStatefulWidget {
  const _ChangePasswordCard();

  @override
  ConsumerState<_ChangePasswordCard> createState() => _ChangePasswordCardState();
}

class _ChangePasswordCardState extends ConsumerState<_ChangePasswordCard> {
  final _currentController = TextEditingController();
  final _newController = TextEditingController();
  final _confirmController = TextEditingController();
  bool _saving = false;
  String? _error;

  Future<void> _save() async {
    if (_newController.text != _confirmController.text) {
      setState(() => _error = 'New passwords do not match.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final client = ref.read(apiClientProvider);
      await client.changePassword(
        currentPassword: _currentController.text,
        newPassword: _newController.text,
      );
      _currentController.clear();
      _newController.clear();
      _confirmController.clear();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password updated.')));
      }
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  void dispose() {
    _currentController.dispose();
    _newController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('CHANGE PASSWORD', style: AppTheme.display(fontSize: 13, fontWeight: FontWeight.w600, letterSpacing: 1)),
            const SizedBox(height: 14),
            TextField(
              controller: _currentController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Current password'),
              style: const TextStyle(color: AppColors.chalk),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _newController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'New password'),
              style: const TextStyle(color: AppColors.chalk),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _confirmController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Confirm new password'),
              style: const TextStyle(color: AppColors.chalk),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: AppColors.tape)),
            ],
            const SizedBox(height: 16),
            OutlinedButton(
              onPressed: _saving ? null : _save,
              child: _saving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.chalk),
                    )
                  : const Text('UPDATE PASSWORD'),
            ),
          ],
        ),
      ),
    );
  }
}
