import 'package:intl/intl.dart';

/// Formats [value] as Nigerian Naira, e.g. `₦1,250.00`.
String money(double value) => NumberFormat.currency(
      locale: 'en_NG',
      symbol: '₦',
      decimalDigits: 2,
    ).format(value);

/// Turns a snake_case / raw API token into a readable label.
String prettyLabel(String value) => value
    .replaceAll('_', ' ')
    .split(' ')
    .map((word) =>
        word.isEmpty ? word : '${word[0].toUpperCase()}${word.substring(1)}')
    .join(' ');

/// Up to two uppercase initials for [value].
String initialsOf(String value) {
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

/// Greeting suffix based on the current local hour.
String partOfDay() {
  final hour = DateTime.now().hour;
  if (hour < 12) return 'morning';
  if (hour < 17) return 'afternoon';
  return 'evening';
}

/// Today's date, e.g. `Saturday, 12 September`.
String todayLabel() => DateFormat('EEEE, d MMMM').format(DateTime.now());
