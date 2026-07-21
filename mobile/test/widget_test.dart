import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:fithub_app/main.dart';

void main() {
  testWidgets('shows login screen when logged out', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: FitHubApp()));
    await tester.pump();

    expect(find.text('FITHUB'), findsOneWidget);
    expect(find.text('LOG IN'), findsOneWidget);
  });
}
