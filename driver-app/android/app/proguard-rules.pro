## Flutter
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }


## Google Fonts
-keep class com.google.android.gms.** { *; }

## Play Core (deferred components) - referenced by Flutter embedding but optional
-dontwarn com.google.android.play.core.**
-keep class com.google.android.play.core.** { *; }

## Keep annotations
-keepattributes *Annotation*
