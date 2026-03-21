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
        $settings = SystemSetting::all()->groupBy('type');
        
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'nullable|array',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,ico|max:1024',
        ]);

        // Update regular settings
        if ($request->has('settings')) {
            foreach ($request->settings as $key => $value) {
                $setting = SystemSetting::where('key', $key)->first();
                
                if ($setting) {
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
