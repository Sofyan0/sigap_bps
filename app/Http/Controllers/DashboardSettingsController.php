<?php

namespace App\Http\Controllers;

use App\Models\DashboardSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DashboardSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'institution' => ['required', 'string', 'max:255'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email_ews_enabled' => ['required', 'boolean'],
            'weekly_iph_enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $oldPhotoPath = $user->profile_photo_path;
        $profile = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'institution' => $validated['institution'],
            'employee_id' => $validated['employee_id'] ?? null,
        ];

        if ($request->hasFile('profile_photo')) {
            $storedPhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
            if ($storedPhotoPath === false) {
                throw new \RuntimeException('Foto profil gagal disimpan.');
            }

            $profile['profile_photo_path'] = $storedPhotoPath;
        }

        $user->update($profile);

        if (isset($profile['profile_photo_path']) && $oldPhotoPath !== null) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        $settings = DashboardSetting::firstOrCreate(
            ['id' => 1],
            [
                'admin_name' => 'Admin TPID Situbondo',
                'institution' => 'Bagian Perekonomian Setda',
            ]
        );
        $settings->update([
            'email_ews_enabled' => $validated['email_ews_enabled'],
            'weekly_iph_enabled' => $validated['weekly_iph_enabled'],
        ]);

        return redirect()
            ->route('dashboard', ['tab' => 'pengaturan'])
            ->with('success', 'Pengaturan profil berhasil disimpan.');
    }
}
