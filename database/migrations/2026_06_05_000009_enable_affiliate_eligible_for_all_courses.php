<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('courses')->update(['affiliate_eligible' => true]);
    }

    public function down(): void
    {
        DB::table('courses')->update(['affiliate_eligible' => false]);
    }
};
