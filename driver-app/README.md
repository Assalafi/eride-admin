# E-RIDE Driver

The new Android/web driver app for E-RIDE Nigeria. The default API is the live API at `https://admin.eride.ng/api`.

```text
flutter pub get
flutter run -d chrome --dart-define=API_BASE_URL=https://admin.eride.ng/api
flutter build apk --dart-define=API_BASE_URL=https://admin.eride.ng/api
flutter build web --dart-define=API_BASE_URL=https://admin.eride.ng/api
```

The app intentionally excludes charging and mechanic features. Android uses `local_auth` for biometric unlock. Web restores a normal authenticated browser session because browser biometrics require a separate WebAuthn/passkey flow.

It uses the existing `/api/login` credentials and the new `/api/driver/payments/opay/*` endpoints. OPay secrets remain on the Laravel server.
