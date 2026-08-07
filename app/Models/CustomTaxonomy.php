<?php

namespace App\Models;

use App\Enums\TaxonomyStructure;
use Database\Factories\CustomTaxonomyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'structure_type',
])]
class CustomTaxonomy extends Model
{
    /** @use HasFactory<CustomTaxonomyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'structure_type' => TaxonomyStructure::class,
        ];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(CustomTaxonomyTerm::class)->orderBy('name');
    }

    public function postTypeAssociations(): HasMany
    {
        return $this->hasMany(CustomTaxonomyPostType::class);
    }

    /**
     * @return list<string>
     */
    public function postTypeKeys(): array
    {
        return $this->postTypeAssociations()->pluck('post_type_key')->all();
    }

    public function isHierarchical(): bool
    {
        return $this->structure_type === TaxonomyStructure::Hierarchical;
    }

    public function isFlat(): bool
    {
        return $this->structure_type === TaxonomyStructure::Flat;
    }
}
