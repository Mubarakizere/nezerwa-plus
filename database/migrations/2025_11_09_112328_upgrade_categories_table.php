<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            // Structure
            $t->enum('kind', ['product','expense','both'])->default('both')->after('description');
            $t->foreignId('parent_id')->nullable()->after('kind')->constrained('categories')->nullOnDelete();
            $t->string('slug', 160)->nullable()->after('parent_id');
            $t->string('code', 40)->nullable()->after('slug');
            $t->string('color', 20)->nullable()->after('code'); // e.g. #22c55e or 'emerald'
            $t->string('icon', 50)->nullable()->after('color');  // e.g. 'package', 'wallet'
            $t->unsignedInteger('sort_order')->default(0)->after('icon');
            $t->boolean('is_active')->default(true)->after('sort_order');

            // Audit + soft deletes
            $t->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $t->softDeletes();

            // Indexes (add uniques AFTER backfill)
            $t->index('kind');
            $t->index('parent_id');
            $t->index('sort_order');
            $t->index('is_active');
        });

        // Backfill: slug + sort_order + kind
        // 1) Set sort_order=id when 0; generate unique slugs per (kind)
        $rows = DB::table('categories')->select('id','name','slug','sort_order','kind')->orderBy('id')->get();

        // Build per-kind slug sets to ensure uniqueness
        $seen = ['product'=>[], 'expense'=>[], 'both'=>[]];

        foreach ($rows as $r) {
            $kind = $r->kind ?? 'both';
            $base = Str::slug((string)($r->name ?? 'category'));
            if ($base === '') $base = 'category';

            $slug = $base;
            $i = 2;
            while (in_array($slug, $seen[$kind], true)) {
                $slug = $base.'-'.$i++;
            }
            $seen[$kind][] = $slug;

            DB::table('categories')
                ->where('id', $r->id)
                ->update([
                    'slug'       => $slug,
                    'sort_order' => $r->sort_order ?: $r->id,
                ]);
        }

        // 2) Infer kind from usage (safe if tables exist)
        // both: referenced by products AND expenses
        if (Schema::hasTable('products')) {
            if (Schema::hasTable('expenses')) {
                DB::statement("
                    UPDATE categories c
                       SET kind='both'
                     WHERE EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id)
                       AND EXISTS (SELECT 1 FROM expenses e WHERE e.category_id = c.id)
                ");
                DB::statement("
                    UPDATE categories c
                       SET kind='product'
                     WHERE EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id)
                       AND NOT EXISTS (SELECT 1 FROM expenses e WHERE e.category_id = c.id)
                ");
                DB::statement("
                    UPDATE categories c
                       SET kind='expense'
                     WHERE EXISTS (SELECT 1 FROM expenses e WHERE e.category_id = c.id)
                       AND NOT EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id)
                ");
            } else {
                DB::statement("
                    UPDATE categories c
                       SET kind='product'
                     WHERE EXISTS (SELECT 1 FROM products p WHERE p.category_id = c.id)
                ");
            }
        } elseif (Schema::hasTable('expenses')) {
            DB::statement("
                UPDATE categories c
                   SET kind='expense'
                 WHERE EXISTS (SELECT 1 FROM expenses e WHERE e.category_id = c.id)
            ");
        }

        // 3) Add uniqueness now that slugs exist
        Schema::table('categories', function (Blueprint $t) {
            $t->unique(['kind','slug'], 'categories_kind_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            $t->dropUnique('categories_kind_slug_unique');

            $t->dropConstrainedForeignId('updated_by');
            $t->dropConstrainedForeignId('created_by');
            $t->dropConstrainedForeignId('parent_id');

            $t->dropColumn([
                'kind','slug','code','color','icon','sort_order','is_active',
                'created_by','updated_by','deleted_at','parent_id'
            ]);
        });
    }
};
