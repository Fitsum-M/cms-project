<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'custom_taxonomy_id',
    'post_type_key',
])]
class CustomTaxonomyPostType extends Model
{
    protected $table = 'custom_taxonomy_post_type';

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(CustomTaxonomy::class, 'custom_taxonomy_id');
    }
}
