<?php

namespace App\Models;

use App\Models\Book;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'name'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function subCategory()
    {
        return $this->hasMany(Subcategory::class);
    }
}
