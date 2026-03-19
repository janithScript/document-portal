<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\DocumentSignature;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class SignatureController extends Controller
{
    // Save signature data + merge into PDF
    public function store(Request $request, Document $document) {
        $request->validate([
            'signature_data' => 'required|string',  // base64 PNG
            'page_number'    => 'required|integer|min:1',
            'x_position'     => 'required|numeric',
            'y_position'     => 'required|numeric',
            'width'          => 'required|numeric',
            'height'         => 'required|numeric',
        ]);

        // Save signature record to DB
        DocumentSignature::create([
            'document_id'    => $document->id,
            'page_number'    => $request->page_number,
            'x_position'     => $request->x_position,
            'y_position'     => $request->y_position,
            'width'          => $request->width,
            'height'         => $request->height,
            'signature_data' => $request->signature_data,
        ]);

        // Merge all signatures into PDF
        $signedPath = $this->mergePdf($document);
        $document->update(['signed_path' => $signedPath, 'status' => 'signed']);

        return response()->json([
            'success'    => true,
            'signed_url' => asset('storage/' . $signedPath),
            'message'    => 'Signature applied successfully!',
        ]);
    }

    // Core PDF merging using FPDI
    private function mergePdf(Document $document): string {
        $sourcePath = Storage::disk('public')->path($document->original_path);
        $outputName = 'signed/' . pathinfo($document->original_path, PATHINFO_FILENAME) . '_signed.pdf';
        $outputPath = Storage::disk('public')->path($outputName);

        // Ensure signed/ directory exists
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            // Get signatures for this page
            $sigs = $document->signatures()->where('page_number', $i)->get();
            foreach ($sigs as $sig) {
                // Decode base64 signature PNG
                $imgData = base64_decode(preg_replace('/^data:image\/png;base64,/', '', $sig->signature_data));
                $tmpFile = tempnam(sys_get_temp_dir(), 'sig') . '.png';
                file_put_contents($tmpFile, $imgData);

                // Position: convert % to mm
                $x = ($sig->x_position / 100) * $size['width'];
                $y = ($sig->y_position / 100) * $size['height'];
                $w = ($sig->width / 100) * $size['width'];

                $pdf->Image($tmpFile, $x, $y, $w, 0, 'PNG');
                unlink($tmpFile);
            }
        }

        $pdf->Output('F', $outputPath);
        return $outputName;
    }

    // Clear all signatures from a document
    public function clear(Document $document) {
        $document->signatures()->delete();
        $document->update(['status' => 'uploaded', 'signed_path' => null]);
        return response()->json(['success' => true, 'message' => 'Signatures cleared.']);
    }
}
