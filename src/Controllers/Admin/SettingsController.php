<?php

namespace App\Controllers\Admin;

use App\Auth;
use App\AuditLog;
use App\Csrf;
use App\Setting;
use App\View;

class SettingsController
{
    private const KEYS = ['support_phone', 'support_note', 'chat_enabled'];

    public function edit(): void
    {
        echo View::renderAdmin('admin/settings/edit', [
            'settings' => Setting::many(self::KEYS),
        ], 'Instellingen');
    }

    public function update(): void
    {
        if (! Csrf::verify()) {
            http_response_code(419);
            exit('Page Expired');
        }

        Setting::set('support_phone', trim((string) ($_POST['support_phone'] ?? '')) ?: null);
        Setting::set('support_note', trim((string) ($_POST['support_note'] ?? '')) ?: null);
        Setting::set('chat_enabled', ($_POST['chat_enabled'] ?? '0') === '1' ? '1' : '0');

        $admin = Auth::admin();
        AuditLog::log((int) $admin['id'], 'settings.update', 'Setting', null);

        View::flash('Instellingen opgeslagen.');
        header('Location: /admin/settings');
    }
}
