<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\DocumentSignature;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class RotatableFpdi extends Fpdi
{
    protected $angle = 0;

    public function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x === -1) {
            $x = $this->x;
        }
        if ($y === -1) {
            $y = $this->y;
        }

        if ($this->angle != 0) {
            $this->_out('Q');
        }

        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    public function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}

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
            'rotation'       => 'nullable|numeric',
        ]);

        // Save signature record to DB
        DocumentSignature::create([
            'document_id'    => $document->id,
            'page_number'    => $request->page_number,
            'x_position'     => $request->x_position,
            'y_position'     => $request->y_position,
            'width'          => $request->width,
            'height'         => $request->height,
            'rotation'       => $request->rotation ?? 0,
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

        $pdf = new RotatableFpdi();
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
                $h = ($sig->height / 100) * $size['height'];
                $angle = (float) ($sig->rotation ?? 0);

                if (abs($angle) > 0.01) {
                    $pdf->Rotate($angle, $x + ($w / 2), $y + ($h / 2));
                }

                $pdf->Image($tmpFile, $x, $y, $w, $h, 'PNG');

                if (abs($angle) > 0.01) {
                    $pdf->Rotate(0);
                }

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
