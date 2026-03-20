@extends('layouts.app')
@section('title','Upload Document')
@push('styles')
<style>
 .upload-card {
       border: 1px solid rgba(255, 255, 255, 0.65);
       background: rgba(255, 255, 255, 0.78);
       backdrop-filter: blur(10px);
       -webkit-backdrop-filter: blur(10px);
 }

 .upload-header {
       background: linear-gradient(120deg, rgba(17, 111, 149, 0.94), rgba(30, 161, 160, 0.9));
       color: #fff;
       border-bottom: 1px solid rgba(255, 255, 255, 0.35);
 }

 .upload-header h5 {
       margin: 0;
       color: #fff;
 }

 .form-control {
       border-radius: 12px;
       border-color: rgba(23, 80, 120, 0.22);
       background: rgba(255, 255, 255, 0.9);
 }

 .form-control:focus {
       border-color: rgba(24, 141, 169, 0.5);
       box-shadow: 0 0 0 0.2rem rgba(24, 141, 169, 0.16);
 }

 .form-label {
       font-weight: 600;
 }

 .upload-btn {
       position: relative;
       min-height: 44px;
 }

 .upload-btn.is-loading {
       opacity: 0.95;
 }

 .upload-progress {
       margin-top: 10px;
       height: 6px;
       border-radius: 999px;
       overflow: hidden;
       background: rgba(20, 96, 130, 0.12);
 }

 .upload-progress-bar {
       height: 100%;
       width: 45%;
       border-radius: 999px;
       background: linear-gradient(90deg, rgba(21, 137, 166, 0.9), rgba(20, 110, 145, 0.95));
       animation: uploadSlide 1s ease-in-out infinite;
 }

 .file-drop-zone {
       border: 2px dashed rgba(24, 141, 169, 0.35);
       border-radius: 12px;
       padding: 10px;
       background: rgba(255, 255, 255, 0.65);
       transition: border-color 0.2s ease, background-color 0.2s ease;
 }

 .file-drop-zone.is-dragging {
       border-color: rgba(21, 137, 166, 0.95);
       background: rgba(219, 245, 250, 0.75);
 }

 .file-drop-hint {
       margin-top: 8px;
       margin-bottom: 0;
       font-size: 0.875rem;
       color: rgba(17, 70, 100, 0.85);
 }

 @keyframes uploadSlide {
       0% { transform: translateX(-120%); }
       100% { transform: translateX(260%); }
 }
</style>
@endpush
@section('content')
<div class="row justify-content-center">
 <div class="col-md-6">
       <div class="card shadow upload-card">
        <div class="card-header upload-header"><h5>Upload PDF Document</h5></div>
   <div class="card-body">
    <form id="uploadForm" method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
     @csrf
     <div class="mb-3">
      <label class="form-label">Document Title *</label>
      <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
             value="{{ old('title') }}" required>
      @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
     </div>
     <div class="mb-3">
      <label class="form-label">Your Name *</label>
      <input type="text" name="uploader_name" class="form-control" required>
     </div>
     <div class="mb-3">
      <label class="form-label">PDF File * (max 20MB)</label>
      <div id="pdfDropZone" class="file-drop-zone">
      <input id="pdfFileInput" type="file" name="pdf_file" class="form-control @error('pdf_file') is-invalid @enderror"
            accept=".pdf,application/pdf" required>
      <p id="pdfDropHint" class="file-drop-hint">Drag and drop a PDF here, or click to browse.</p>
      </div>
      @error('pdf_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
     </div>
             <button id="uploadBtn" type="submit" class="btn btn-primary w-100 upload-btn">
                  <span class="btn-label"><i class="fas fa-upload me-2"></i>Upload & Continue to Sign</span>
                  <span class="btn-loading d-none">
                   <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                   Uploading document...
                  </span>
     </button>
             <div id="uploadProgress" class="upload-progress d-none" aria-hidden="true">
                  <div class="upload-progress-bar"></div>
             </div>
    </form>
   </div>
  </div>
 </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('uploadForm');
      const btn = document.getElementById('uploadBtn');
      const progress = document.getElementById('uploadProgress');
      const dropZone = document.getElementById('pdfDropZone');
      const fileInput = document.getElementById('pdfFileInput');
      const dropHint = document.getElementById('pdfDropHint');
      if (!form || !btn || !progress) {
            return;
      }

      if (dropZone && fileInput) {
            const isPdfFile = (file) => {
                  if (!file) {
                        return false;
                  }

                  const mimeType = (file.type || '').toLowerCase();
                  const fileName = (file.name || '').toLowerCase();
                  return mimeType === 'application/pdf' || fileName.endsWith('.pdf');
            };

            const setInputFile = (file) => {
                  const transfer = new DataTransfer();
                  transfer.items.add(file);
                  fileInput.files = transfer.files;
                  fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const updateHint = (message) => {
                  if (dropHint) {
                        dropHint.textContent = message;
                  }
            };

            ['dragenter', 'dragover'].forEach((eventName) => {
                  dropZone.addEventListener(eventName, (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        dropZone.classList.add('is-dragging');
                  });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                  dropZone.addEventListener(eventName, (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        dropZone.classList.remove('is-dragging');
                  });
            });

            dropZone.addEventListener('drop', (event) => {
                  const droppedFiles = event.dataTransfer ? event.dataTransfer.files : null;
                  const droppedFile = droppedFiles && droppedFiles.length ? droppedFiles[0] : null;
                  if (!droppedFile) {
                        return;
                  }

                  if (!isPdfFile(droppedFile)) {
                        fileInput.setCustomValidity('Please upload a valid PDF file.');
                        form.reportValidity();
                        updateHint('Only PDF files are allowed.');
                        return;
                  }

                  fileInput.setCustomValidity('');
                  setInputFile(droppedFile);
                  updateHint(`Selected: ${droppedFile.name}`);
            });

            fileInput.addEventListener('change', () => {
                  const selectedFile = fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
                  if (!selectedFile) {
                        fileInput.setCustomValidity('');
                        updateHint('Drag and drop a PDF here, or click to browse.');
                        return;
                  }

                  if (!isPdfFile(selectedFile)) {
                        fileInput.setCustomValidity('Please upload a valid PDF file.');
                        updateHint('Only PDF files are allowed.');
                        return;
                  }

                  fileInput.setCustomValidity('');
                  updateHint(`Selected: ${selectedFile.name}`);
            });
      }

      form.addEventListener('submit', () => {
            if (!form.checkValidity()) {
                  return;
            }

            btn.disabled = true;
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');

            const label = btn.querySelector('.btn-label');
            const loading = btn.querySelector('.btn-loading');
            if (label) {
                  label.classList.add('d-none');
            }
            if (loading) {
                  loading.classList.remove('d-none');
            }

            progress.classList.remove('d-none');
            progress.setAttribute('aria-hidden', 'false');
      });
});
</script>
@endpush
