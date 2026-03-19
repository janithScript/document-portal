<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Show document list
    public function index() {
        $documents = Document::latest()->paginate(10);
        return view('documents.index', compact('documents'));
    }

    // Show upload form
    public function create() {
        return view('documents.create');
    }

    // Handle PDF upload
    public function store(Request $request) {
        $request->validate([
            'title'         => 'required|string|max:200',
            'uploader_name' => 'required|string|max:100',
            'pdf_file'      => 'required|file|mimes:pdf|max:20480', // 20MB max
        ]);

        // Store the file in storage/app/public/documents/
        $path = $request->file('pdf_file')->store('documents', 'public');

        $doc = Document::create([
            'title'         => $request->title,
            'uploader_name' => $request->uploader_name,
            'original_path' => $path,
            'status'        => 'uploaded',
        ]);

        return redirect()->route('documents.sign', $doc->id)
            ->with('success', 'Document uploaded! You can now add signatures.');
    }

    // Show the signing/editing page
    public function sign(Document $document) {
        return view('documents.sign', compact('document'));
    }

    // Download signed document
    public function download(Document $document) {
        $path = $document->signed_path ?? $document->original_path;
        return Storage::disk('public')->download($path, $document->title . '.pdf');
    }
}
