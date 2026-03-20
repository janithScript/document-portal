<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model {
    protected $fillable = ['document_id','page_number','x_position','y_position','width','height','rotation','signature_data'];

    public function document() {
        return $this->belongsTo(Document::class);
    }
}
