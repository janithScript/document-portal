@extends('layouts.app')
@section('title','Sign Document')
@push('styles')
<style>
  #pdfContainer {
    position: relative;
    display: inline-block;
    border: 1px solid rgba(255, 255, 255, 0.75);
    background: rgba(255, 255, 255, 0.7);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 14px 32px rgba(15, 43, 78, 0.16);
  }

  #pdfCanvas { display:block; }

  #sigCanvas {
    position: absolute;
    top: 0;
    left: 0;
    cursor: crosshair;
  }

  .toolbar {
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(255, 255, 255, 0.68);
    border-radius: 16px;
    padding: 12px;
    box-shadow: 0 10px 24px rgba(18, 47, 81, 0.12);
  }

  #sigPad {
    border: 2px dashed rgba(21, 129, 145, 0.7);
    border-radius: 12px;
    cursor: crosshair;
    background: rgba(255, 255, 255, 0.95);
    width: 100%;
    max-width: 280px;
  }

  .doc-title {
    font-weight: 700;
    margin-bottom: 0.9rem;
  }

  .page-controls {
    background: rgba(255, 255, 255, 0.62);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 12px;
    padding: 8px 10px;
    width: fit-content;
  }

  .field-label {
    font-weight: 600;
    color: #254361;
  }

  #penColor,
  #penSize {
    margin-top: 4px;
  }
</style>
@endpush
@section('content')
<div class="row">
 <!-- Left: PDF viewer -->
 <div class="col-md-8">
  <h5 class="doc-title"><i class="fas fa-file-pdf text-danger me-2"></i>{{ $document->title }}</h5>
  <!-- Page controls -->
  <div class="d-flex align-items-center mb-2 gap-2 page-controls">
   <button class="btn btn-sm btn-outline-secondary" onclick="prevPage()"><i class="fas fa-chevron-left"></i></button>
   <span>Page <span id="pageNum">1</span> of <span id="pageCount">?</span></span>
   <button class="btn btn-sm btn-outline-secondary" onclick="nextPage()"><i class="fas fa-chevron-right"></i></button>
   <button class="btn btn-sm btn-outline-danger ms-2" onclick="clearPageSigs()">
    <i class="fas fa-eraser me-1"></i>Clear Page Signs</button>
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
    <button class="btn btn-sm btn-primary" onclick="applySignature()">
     Apply to Page</button>
   </div>
  </div>
  <div class="toolbar mb-3">
   <h6>Color & Size</h6>
   <label class="field-label">Pen Color: <input type="color" id="penColor" value="#000000" style="width:50px"></label><br>
   <label class="field-label">Pen Size: <input type="range" id="penSize" min="1" max="10" value="2" style="width:100%"></label>
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
let selectedSigIndex = -1;
let activeSigIndex = -1;
let activeAction = null; // drag | resize | rotate
let dragOffsetX = 0;
let dragOffsetY = 0;

const HANDLE_RADIUS = 9;
const ROTATE_HANDLE_OFFSET = 24;
const MIN_SIG_SIZE = 30;

function getPageSignatures(pageNum = currentPage) {
  return placedSignatures.filter(s => s.page_number === pageNum);
}

function getSigCenter(sig) {
  return {
    cx: sig._xPx + sig._wPx / 2,
    cy: sig._yPx + sig._hPx / 2,
  };
}

function getRotatedPoint(cx, cy, lx, ly, rotation) {
  const cos = Math.cos(rotation);
  const sin = Math.sin(rotation);
  return {
    x: cx + (lx * cos - ly * sin),
    y: cy + (lx * sin + ly * cos),
  };
}

function pointToSignatureLocal(x, y, sig) {
  const { cx, cy } = getSigCenter(sig);
  const dx = x - cx;
  const dy = y - cy;
  const cos = Math.cos(-sig._rotation);
  const sin = Math.sin(-sig._rotation);
  return {
    x: dx * cos - dy * sin,
    y: dx * sin + dy * cos,
  };
}

function getSignatureHandles(sig) {
  const { cx, cy } = getSigCenter(sig);
  return {
    rotate: getRotatedPoint(cx, cy, 0, -sig._hPx / 2 - ROTATE_HANDLE_OFFSET, sig._rotation),
    resize: getRotatedPoint(cx, cy, sig._wPx / 2, sig._hPx / 2, sig._rotation),
  };
}

function distanceSq(x1, y1, x2, y2) {
  const dx = x1 - x2;
  const dy = y1 - y2;
  return dx * dx + dy * dy;
}

function findInteractionAt(x, y) {
  const hitRadiusSq = HANDLE_RADIUS * HANDLE_RADIUS;

  for (let i = placedSignatures.length - 1; i >= 0; i--) {
    const sig = placedSignatures[i];
    if (sig.page_number !== currentPage) {
      continue;
    }

    const handles = getSignatureHandles(sig);
    if (distanceSq(x, y, handles.rotate.x, handles.rotate.y) <= hitRadiusSq) {
      return { index: i, action: 'rotate' };
    }

    if (distanceSq(x, y, handles.resize.x, handles.resize.y) <= hitRadiusSq) {
      return { index: i, action: 'resize' };
    }

    const local = pointToSignatureLocal(x, y, sig);
    const withinX = Math.abs(local.x) <= sig._wPx / 2;
    const withinY = Math.abs(local.y) <= sig._hPx / 2;
    if (withinX && withinY) {
      return { index: i, action: 'drag' };
    }
  }

  return { index: -1, action: null };
}

function redrawOverlay() {
  const overlay = document.getElementById('sigCanvas');
  const octx = overlay.getContext('2d');
  octx.clearRect(0, 0, overlay.width, overlay.height);

  for (let i = 0; i < placedSignatures.length; i++) {
    const sig = placedSignatures[i];
    if (sig.page_number !== currentPage) {
      continue;
    }

    if (sig._img && sig._img.complete) {
      const { cx, cy } = getSigCenter(sig);
      octx.save();
      octx.translate(cx, cy);
      octx.rotate(sig._rotation);
      octx.drawImage(sig._img, -sig._wPx / 2, -sig._hPx / 2, sig._wPx, sig._hPx);

      if (i === selectedSigIndex) {
        octx.strokeStyle = '#158191';
        octx.lineWidth = 1.5;
        octx.setLineDash([6, 4]);
        octx.strokeRect(-sig._wPx / 2, -sig._hPx / 2, sig._wPx, sig._hPx);
        octx.setLineDash([]);

        const localRotateY = -sig._hPx / 2 - ROTATE_HANDLE_OFFSET;
        octx.beginPath();
        octx.moveTo(0, -sig._hPx / 2);
        octx.lineTo(0, localRotateY);
        octx.strokeStyle = '#158191';
        octx.stroke();

        octx.fillStyle = '#ffffff';
        octx.strokeStyle = '#158191';
        octx.lineWidth = 2;

        octx.beginPath();
        octx.arc(sig._wPx / 2, sig._hPx / 2, HANDLE_RADIUS, 0, Math.PI * 2);
        octx.fill();
        octx.stroke();

        octx.beginPath();
        octx.arc(0, localRotateY, HANDLE_RADIUS, 0, Math.PI * 2);
        octx.fill();
        octx.stroke();
      }

      octx.restore();
    }
  }
}

function keepSignatureInBounds(sig, overlay) {
  const halfW = sig._wPx / 2;
  const halfH = sig._hPx / 2;
  const cos = Math.cos(sig._rotation);
  const sin = Math.sin(sig._rotation);
  const halfBBoxW = Math.abs(halfW * cos) + Math.abs(halfH * sin);
  const halfBBoxH = Math.abs(halfW * sin) + Math.abs(halfH * cos);

  const center = getSigCenter(sig);
  const minCx = halfBBoxW;
  const maxCx = Math.max(halfBBoxW, overlay.width - halfBBoxW);
  const minCy = halfBBoxH;
  const maxCy = Math.max(halfBBoxH, overlay.height - halfBBoxH);

  const clampedCx = Math.min(Math.max(center.cx, minCx), maxCx);
  const clampedCy = Math.min(Math.max(center.cy, minCy), maxCy);
  sig._xPx = clampedCx - halfW;
  sig._yPx = clampedCy - halfH;
}

function updateSignaturePercent(sig, overlay) {
  sig.x_position = ((sig._xPx / overlay.width) * 100).toFixed(2);
  sig.y_position = ((sig._yPx / overlay.height) * 100).toFixed(2);
  sig.width = ((sig._wPx / overlay.width) * 100).toFixed(2);
  sig.height = ((sig._hPx / overlay.height) * 100).toFixed(2);
  sig.rotation = ((((sig._rotation * 180) / Math.PI) % 360) + 360).toFixed(2);
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

function updateOverlayCursor(event) {
  const overlay = document.getElementById('sigCanvas');
  const point = getPointerOnOverlay(event, overlay);
  const hit = findInteractionAt(point.x, point.y);

  if (hit.action === 'rotate') {
    overlay.style.cursor = 'grab';
  } else if (hit.action === 'resize') {
    overlay.style.cursor = 'nwse-resize';
  } else if (hit.action === 'drag') {
    overlay.style.cursor = 'move';
  } else {
    overlay.style.cursor = 'crosshair';
  }
}

function beginDrag(event) {
  const overlay = document.getElementById('sigCanvas');
  const point = getPointerOnOverlay(event, overlay);
  const hit = findInteractionAt(point.x, point.y);

  if (hit.index === -1) {
    selectedSigIndex = -1;
    redrawOverlay();
    updateOverlayCursor(event);
    return;
  }

  activeSigIndex = hit.index;
  activeAction = hit.action;
  selectedSigIndex = hit.index;

  const sig = placedSignatures[hit.index];
  if (activeAction === 'drag') {
    dragOffsetX = point.x - sig._xPx;
    dragOffsetY = point.y - sig._yPx;
    overlay.style.cursor = 'grabbing';
  } else if (activeAction === 'resize') {
    overlay.style.cursor = 'nwse-resize';
  } else if (activeAction === 'rotate') {
    overlay.style.cursor = 'grabbing';
  }

  redrawOverlay();
  event.preventDefault();
}

function moveDrag(event) {
  if (activeSigIndex === -1 || !activeAction) {
    if (event.type === 'mousemove') {
      updateOverlayCursor(event);
    }
    return;
  }

  const overlay = document.getElementById('sigCanvas');
  const point = getPointerOnOverlay(event, overlay);
  const sig = placedSignatures[activeSigIndex];

  if (activeAction === 'drag') {
    sig._xPx = point.x - dragOffsetX;
    sig._yPx = point.y - dragOffsetY;
  } else if (activeAction === 'resize') {
    const center = getSigCenter(sig);
    const local = pointToSignatureLocal(point.x, point.y, sig);
    sig._wPx = Math.max(MIN_SIG_SIZE, Math.abs(local.x) * 2);
    sig._hPx = Math.max(MIN_SIG_SIZE * 0.6, Math.abs(local.y) * 2);
    sig._xPx = center.cx - sig._wPx / 2;
    sig._yPx = center.cy - sig._hPx / 2;
  } else if (activeAction === 'rotate') {
    const center = getSigCenter(sig);
    sig._rotation = Math.atan2(point.y - center.cy, point.x - center.cx) + Math.PI / 2;
  }

  keepSignatureInBounds(sig, overlay);
  updateSignaturePercent(sig, overlay);
  redrawOverlay();
  event.preventDefault();
}

function endDrag() {
  if (activeSigIndex === -1) {
    document.getElementById('sigCanvas').style.cursor = 'crosshair';
    return;
  }
  activeSigIndex = -1;
  activeAction = null;
  document.getElementById('sigCanvas').style.cursor = 'crosshair';
}

function bindOverlayDragEvents() {
  const overlay = document.getElementById('sigCanvas');
  overlay.addEventListener('mousedown', beginDrag);
  overlay.addEventListener('mousemove', updateOverlayCursor);
  window.addEventListener('mousemove', moveDrag);
  window.addEventListener('mouseup', endDrag);

  overlay.addEventListener('touchstart', beginDrag, { passive: false });
  window.addEventListener('touchmove', moveDrag, { passive: false });
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
    _rotation: 0,
    _img: img,
  };

  keepSignatureInBounds(sig, overlay);
  updateSignaturePercent(sig, overlay);

  img.onload = () => {
    redrawOverlay();
  };
  img.src = sigData;

  // Store for saving
  placedSignatures.push(sig);
  selectedSigIndex = placedSignatures.length - 1;
  showStatus('Signature placed! Drag, resize (corner), or rotate (top handle), then click Save.', 'info');
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
