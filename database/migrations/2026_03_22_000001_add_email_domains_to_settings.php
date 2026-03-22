<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \App\Models\Setting::set('user_email_domains', 'axvos.local');

        \DB::table('settings')
            ->where('key', 'user_email_domains')
            ->update([
                'group' => 'users',
                'label' => 'Dominios de correo permitidos (separados por coma)',
                'type'  => 'text',
            ]);
    }

    public function down(): void
    {
        \DB::table('settings')->where('key', 'user_email_domains')->delete();
    }
};
