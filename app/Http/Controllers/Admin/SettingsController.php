<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canManageSystemSettings = $user->hasRole('Super Admin');
        $settings = $canManageSystemSettings
            ? SystemSetting::all()->groupBy('type')
            : collect();

        return view('admin.settings.index', [
            'settings' => $settings,
            'canManageSystemSettings' => $canManageSystemSettings,
            'userPreferences' => [
                'theme' => $user->getPreference('theme', 'system'),
                'pagination_per_page' => (string) $user->getPreference('pagination_per_page', 20),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'nullable|array',
            'preferences' => 'nullable|array',
            'preferences.theme' => 'nullable|in:light,dark,system',
            'preferences.pagination_per_page' => 'nullable|in:10,20,25,50,100,all',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,ico|max:1024',
        ]);

        $user = auth()->user();
        $preferences = $request->input('preferences', []);
        if ($preferences) {
            $user->setPreferences([
                'theme' => $preferences['theme'] ?? $user->getPreference('theme', 'system'),
                'pagination_per_page' => $preferences['pagination_per_page'] ?? $user->getPreference('pagination_per_page', 20),
            ]);
        }

        // System-wide settings and branding are restricted to Super Admins.
        if (!$user->hasRole('Super Admin')) {
            return redirect()->route('admin.settings.index')
                ->with('success', 'Your workspace preferences were updated successfully.');
        }

        // Update regular settings
        if ($request->has('settings')) {
            foreach ($request->settings as $key => $value) {
                $setting = SystemSetting::where('key', $key)->first();
                
                if ($setting) {
                    // Secrets are intentionally write-only in the admin UI.
                    // An empty secret field means "keep the current value".
                    if (str_ends_with($setting->key, '_secret_key') && blank($value)) {
                        continue;
                    }
                    $setting->update(['value' => $value ?? '']);
                }
            }
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            $oldLogo = SystemSetting::get('system_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $logoPath = $request->file('logo')->store('logos', 'public');
            SystemSetting::set('system_logo', $logoPath, 'file', 'System logo image');
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            // Delete old favicon
            $oldFavicon = SystemSetting::get('system_favicon');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }

            $faviconPath = $request->file('favicon')->store('logos', 'public');
            SystemSetting::set('system_favicon', $faviconPath, 'file', 'System favicon');
        }

        // Clear cache
        SystemSetting::clearCache();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully!');
    }
}
