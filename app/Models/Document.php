<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Document extends Model {
    protected $fillable = ['title','uploader_name','original_path','signed_path','page_count','status'];

    public function signatures() {
        return $this->hasMany(DocumentSignature::class);
    }

    public function getOriginalUrlAttribute() {
        return asset('storage/' . $this->original_path);
    }

    public function getSignedUrlAttribute() {
        return $this->signed_path ? asset('storage/' . $this->signed_path) : null;
    }
}
