<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            [
                'key'   => 'security_2fa_enabled',
                'value' => '1',
                'group' => 'security',
                'label' => 'Habilitar autenticación de dos factores (2FA)',
                'type'  => 'boolean',
            ],
            [
                'key'   => 'security_2fa_required_roles',
                'value' => 'Admin,Aprobador',
                'group' => 'security',
                'label' => 'Roles que requieren 2FA',
                'type'  => 'text',
            ],
            [
                'key'   => 'security_2fa_enforcement',
                'value' => 'required',
                'group' => 'security',
                'label' => 'Modo de aplicación del 2FA',
                'type'  => 'select',
            ],
            [
                'key'   => 'security_2fa_grace_days',
                'value' => '0',
                'group' => 'security',
                'label' => 'Días de gracia para configurar 2FA',
                'type'  => 'number',
            ],
        ];

        foreach ($defaults as $row) {
            \DB::table('settings')->insertOrIgnore(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        \DB::table('settings')->whereIn('key', [
            'security_2fa_enabled',
            'security_2fa_required_roles',
            'security_2fa_enforcement',
            'security_2fa_grace_days',
        ])->delete();
    }
};
