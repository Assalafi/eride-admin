import 'package:flutter/foundation.dart';

/// Coordinates navigation inside the authenticated [AppShell] so any screen can
/// switch tabs (and target a payments segment) without pushing a new route.
class ShellController extends ChangeNotifier {
  static const int homeIndex = 0;
  static const int paymentsIndex = 1;
  static const int vehicleIndex = 2;
  static const int profileIndex = 3;

  int _index = homeIndex;
  int _paymentsSegment = 0;

  int get index => _index;
  int get paymentsSegment => _paymentsSegment;

  void selectTab(int index) {
    if (_index == index) return;
    _index = index;
    notifyListeners();
  }

  void openPayments({int segment = 0}) {
    _paymentsSegment = segment;
    _index = paymentsIndex;
    notifyListeners();
  }

  void openVehicle() => selectTab(vehicleIndex);

  void setPaymentsSegment(int segment) {
    if (_paymentsSegment == segment) return;
    _paymentsSegment = segment;
    notifyListeners();
  }
}
