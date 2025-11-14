<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('categories', function (Blueprint $table) {
    $table->unique(['name','kind','deleted_at'], 'categories_name_kind_deleted_unique');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
