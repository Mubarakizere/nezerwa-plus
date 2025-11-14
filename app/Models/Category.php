<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

   protected $fillable = ['name','description','kind','parent_id','slug','code','color','icon','sort_order','is_active','created_by','updated_by'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /* ===== Relations ===== */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /* ===== Scopes ===== */
    public function scopeActive($q)      { return $q->where('is_active', true); }
    public function scopeOrdered($q)     { return $q->orderBy('sort_order')->orderBy('name'); }
    public function scopeRoots($q)       { return $q->whereNull('parent_id'); }
    public function scopeChildrenOf($q, $parentId) { return $q->where('parent_id', $parentId); }

    public function scopeForProducts($q)
    {
        return $q->whereIn('kind', ['product','both']);
    }

    public function scopeForExpenses($q)
    {
        return $q->whereIn('kind', ['expense','both']);
    }


    /* ===== Accessors/Mutators ===== */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => $v ? strtoupper(trim($v)) : null,
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Category $c) {
            // Audit
            if (auth()->check()) {
                $c->created_by = $c->created_by ?: auth()->id();
                $c->updated_by = $c->updated_by ?: auth()->id();
            }
            // Defaults
            $c->kind = $c->kind ?: 'both';
            $c->sort_order = $c->sort_order ?: 0;

            // Slug (unique per kind)
            $c->slug = static::uniqueSlug($c->name, $c->kind);
        });

        static::updating(function (Category $c) {
            if (auth()->check()) {
                $c->updated_by = auth()->id();
            }
            if ($c->isDirty('name') || ($c->isDirty('kind') && $c->slug)) {
                $c->slug = static::uniqueSlug($c->name, $c->kind, $c->id);
            }
        });
    }

    public static function uniqueSlug(string $name, string $kind = 'both', ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->where('kind', $kind)
            ->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
