@extends('layouts.app')
@section('title','Sign Document')
@push('styles')
<style>
  #pdfContainer { position:relative; display:inline-block; border:2px solid #6c3483; }
  #pdfCanvas { display:block; }
  #sigCanvas { position:absolute;top:0;left:0;cursor:crosshair; }
  .toolbar { background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:10px; }
  #sigPad { border:2px dashed #6c3483;border-radius:8px;cursor:crosshair;background:#fff; }
  .mode-btn.active { background:#6c3483!important;color:white!important; }
</style>
@endpush
@section('content')
<div class="row">
 <!-- Left: PDF viewer -->
 <div class="col-md-8">
  <h5><i class="fas fa-file-pdf text-danger me-2"></i>{{ $document->title }}</h5>
  <!-- Page controls -->
  <div class="d-flex align-items-center mb-2 gap-2">
   <button class="btn btn-sm btn-outline-secondary" onclick="prevPage()"><i class="fas fa-chevron-left"></i></button>
   <span>Page <span id="pageNum">1</span> of <span id="pageCount">?</span></span>
   <button class="btn btn-sm btn-outline-secondary" onclick="nextPage()"><i class="fas fa-chevron-right"></i></button>
   <button class="btn btn-sm btn-outline-danger ms-2" onclick="clearPageSigs()">
    <i class="fas fa-eraser me-1"></i>Clear Page Sigs</button>
  </div>
  <!-- PDF canvas -->
  <div id="pdfContainer">
   <canvas id="pdfCanvas"></canvas>
   <canvas id="sigCanvas"></canvas>
  </div>
 </div>
 <!-- Right: Signature pad + tools -->
 <div class="col-md-4">
  <div class="toolbar mb-3">
   <h6><i class="fas fa-pen me-2"></i>Draw Signature</h6>
   <canvas id="sigPad" width="280" height="120"></canvas>
   <div class="mt-2 d-flex gap-2">
    <button class="btn btn-sm btn-danger" onclick="clearSigPad()">Clear</button>
    <button class="btn btn-sm" style="background:#6c3483;color:white" onclick="applySignature()">
     Apply to Page</button>
   </div>
  </div>
  <div class="toolbar mb-3">
   <h6>Color & Size</h6>
   <label>Pen Color: <input type="color" id="penColor" value="#000000" style="width:50px"></label><br>
   <label>Pen Size: <input type="range" id="penSize" min="1" max="10" value="2" style="width:100%"></label>
  </div>
  <div class="d-grid gap-2">
   <button class="btn btn-success" onclick="saveSignature()">
    <i class="fas fa-save me-2"></i>Save Signature to PDF</button>
   <a href="{{ route('documents.download', $document->id) }}" class="btn btn-outline-primary"
      id="downloadBtn" style="display:{{ $document->signed_path ? 'block' : 'none' }}">
    <i class="fas fa-download me-2"></i>Download Signed PDF</a>
  </div>
  <div id="statusMsg" class="mt-3"></div>
 </div>
</div>
@endsection
@push('scripts')
<!-- Load PDF.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const DOC_ID = {{ $document->id }};
const PDF_URL = '{{ $document->original_url }}'

// ── PDF.js setup
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
let pdfDoc = null, currentPage = 1, scale = 1.5;
let sigDrawing = false, sigCtx, lastX, lastY;

// ── Load PDF
async function loadPdf() {
  pdfDoc = await pdfjsLib.getDocument(PDF_URL).promise;
  document.getElementById('pageCount').textContent = pdfDoc.numPages;
  renderPage(currentPage);
}

async function renderPage(num) {
  const page = await pdfDoc.getPage(num);
  const viewport = page.getViewport({ scale });
  const canvas = document.getElementById('pdfCanvas');
  const ctx = canvas.getContext('2d');
  canvas.width = viewport.width; canvas.height = viewport.height;
  await page.render({ canvasContext: ctx, viewport }).promise;
  // Resize overlay canvas to match
  const overlay = document.getElementById('sigCanvas');
  overlay.width = viewport.width; overlay.height = viewport.height;
  document.getElementById('pageNum').textContent = num;
}

function prevPage() { if (currentPage > 1) { currentPage--; renderPage(currentPage); } }
function nextPage() { if (pdfDoc && currentPage < pdfDoc.numPages) { currentPage++; renderPage(currentPage); } }

// ── Signature Pad (draw your signature)
const sigPad = document.getElementById('sigPad');
const padCtx = sigPad.getContext('2d');
let drawing = false;
padCtx.strokeStyle = '#000'; padCtx.lineWidth = 2; padCtx.lineCap = 'round';

sigPad.addEventListener('mousedown', e => { drawing=true; padCtx.beginPath(); padCtx.moveTo(e.offsetX,e.offsetY); });
sigPad.addEventListener('mousemove', e => { if(!drawing)return; padCtx.lineTo(e.offsetX,e.offsetY); padCtx.stroke(); });
sigPad.addEventListener('mouseup', () => drawing = false);
sigPad.addEventListener('mouseleave', () => drawing = false);
// Touch support for mobile
sigPad.addEventListener('touchstart', e => { e.preventDefault(); const t=e.touches[0]; drawing=true; padCtx.beginPath(); padCtx.moveTo(t.clientX-sigPad.getBoundingClientRect().left, t.clientY-sigPad.getBoundingClientRect().top); });
sigPad.addEventListener('touchmove', e => { e.preventDefault(); if(!drawing)return; const t=e.touches[0]; padCtx.lineTo(t.clientX-sigPad.getBoundingClientRect().left, t.clientY-sigPad.getBoundingClientRect().top); padCtx.stroke(); });
sigPad.addEventListener('touchend', () => drawing=false);

function clearSigPad() { padCtx.clearRect(0,0,sigPad.width,sigPad.height); }

// Color/size controls
document.getElementById('penColor').addEventListener('input', e => padCtx.strokeStyle = e.target.value);
document.getElementById('penSize').addEventListener('input', e => padCtx.lineWidth = e.target.value);

// ── Apply drawn signature onto the PDF overlay
let placedSignatures = []; // local state for current session
let draggingSigIndex = -1;
let dragOffsetX = 0;
let dragOffsetY = 0;

function getPageSignatures(pageNum = currentPage) {
  return placedSignatures.filter(s => s.page_number === pageNum);
}

function redrawOverlay() {
  const overlay = document.getElementById('sigCanvas');
  const octx = overlay.getContext('2d');
  octx.clearRect(0, 0, overlay.width, overlay.height);

  for (const sig of getPageSignatures()) {
    if (sig._img && sig._img.complete) {
      octx.drawImage(sig._img, sig._xPx, sig._yPx, sig._wPx, sig._hPx);
    }
  }
}

function clampPosition(x, y, sig, overlay) {
  const maxX = Math.max(0, overlay.width - sig._wPx);
  const maxY = Math.max(0, overlay.height - sig._hPx);
  return {
    x: Math.min(Math.max(0, x), maxX),
    y: Math.min(Math.max(0, y), maxY),
  };
}

function updateSignaturePercent(sig, overlay) {
  sig.x_position = ((sig._xPx / overlay.width) * 100).toFixed(2);
  sig.y_position = ((sig._yPx / overlay.height) * 100).toFixed(2);
  sig.width = ((sig._wPx / overlay.width) * 100).toFixed(2);
  sig.height = ((sig._hPx / overlay.height) * 100).toFixed(2);
}

function getPointerOnOverlay(event, overlay) {
  const rect = overlay.getBoundingClientRect();
  if (event.touches && event.touches[0]) {
    return {
      x: event.touches[0].clientX - rect.left,
      y: event.touches[0].clientY - rect.top,
    };
  }
  return {
    x: event.clientX - rect.left,
    y: event.clientY - rect.top,
  };
}

function findTopSignatureAt(x, y) {
  for (let i = placedSignatures.length - 1; i >= 0; i--) {
    const sig = placedSignatures[i];
    if (sig.page_number !== currentPage) {
      continue;
    }

    const withinX = x >= sig._xPx && x <= sig._xPx + sig._wPx;
    const withinY = y >= sig._yPx && y <= sig._yPx + sig._hPx;
    if (withinX && withinY) {
      return i;
    }
  }
  return -1;
}

function beginDrag(event) {
  const overlay = document.getElementById('sigCanvas');
  const point = getPointerOnOverlay(event, overlay);
  const idx = findTopSignatureAt(point.x, point.y);
  if (idx === -1) {
    return;
  }

  const sig = placedSignatures[idx];
  draggingSigIndex = idx;
  dragOffsetX = point.x - sig._xPx;
  dragOffsetY = point.y - sig._yPx;
  overlay.style.cursor = 'grabbing';
  event.preventDefault();
}

function moveDrag(event) {
  if (draggingSigIndex === -1) {
    return;
  }

  const overlay = document.getElementById('sigCanvas');
  const point = getPointerOnOverlay(event, overlay);
  const sig = placedSignatures[draggingSigIndex];
  const pos = clampPosition(point.x - dragOffsetX, point.y - dragOffsetY, sig, overlay);
  sig._xPx = pos.x;
  sig._yPx = pos.y;
  updateSignaturePercent(sig, overlay);
  redrawOverlay();
  event.preventDefault();
}

function endDrag() {
  if (draggingSigIndex === -1) {
    return;
  }
  draggingSigIndex = -1;
  document.getElementById('sigCanvas').style.cursor = 'crosshair';
}

function bindOverlayDragEvents() {
  const overlay = document.getElementById('sigCanvas');
  overlay.addEventListener('mousedown', beginDrag);
  overlay.addEventListener('mousemove', moveDrag);
  window.addEventListener('mouseup', endDrag);

  overlay.addEventListener('touchstart', beginDrag, { passive: false });
  overlay.addEventListener('touchmove', moveDrag, { passive: false });
  window.addEventListener('touchend', endDrag);
}

function applySignature() {
  const sigData = sigPad.toDataURL('image/png');
  const overlay = document.getElementById('sigCanvas');
  const img = new Image();
  // Place signature at bottom-right of current view as default
  const x = overlay.width * 0.6, y = overlay.height * 0.85;
  const w = 150, h = 60;

  const sig = {
    signature_data: sigData,
    page_number: currentPage,
    _xPx: x,
    _yPx: y,
    _wPx: w,
    _hPx: h,
    _img: img,
  };

  updateSignaturePercent(sig, overlay);

  img.onload = () => {
    redrawOverlay();
  };
  img.src = sigData;

  // Store for saving
  placedSignatures.push(sig);
  showStatus('Signature placed! Drag it to any position, then click Save.', 'info');
}

// ── Save signature to server (merge into PDF)
async function saveSignature() {
  if (placedSignatures.length === 0) {
    showStatus('Please draw and apply a signature first!', 'warning');
    return;
  }
  showStatus('Saving...', 'info');
  // Save each placed signature
  for (const sig of placedSignatures) {
    const res = await fetch('/documents/' + DOC_ID + '/signature', {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF },
      body: JSON.stringify(sig)
    });
    const data = await res.json();
    if (data.success) {
      showStatus('Saved! <a href="' + data.signed_url + '" target="_blank">View signed PDF</a>', 'success');
      document.getElementById('downloadBtn').style.display = 'block';
      placedSignatures = [];
    } else { showStatus('Error saving signature.', 'danger'); }
  }
}

async function clearPageSigs() {
  const overlay = document.getElementById('sigCanvas');
  overlay.getContext('2d').clearRect(0,0,overlay.width,overlay.height);
  placedSignatures = placedSignatures.filter(s => s.page_number !== currentPage);
  await fetch('/documents/' + DOC_ID + '/signature', {
    method: 'DELETE',
    headers: {'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json'},
    body: JSON.stringify({page: currentPage})
  });
  showStatus('Page signatures cleared.', 'info');
}

function showStatus(msg, type) {
  document.getElementById('statusMsg').innerHTML = '<div class="alert alert-'+type+' py-2">'+msg+'</div>';
}

const originalRenderPage = renderPage;
renderPage = async function(num) {
  await originalRenderPage(num);
  redrawOverlay();
};

document.addEventListener('DOMContentLoaded', () => {
  bindOverlayDragEvents();
  loadPdf();
});
</script>
@endpush
