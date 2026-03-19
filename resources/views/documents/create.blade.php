@extends('layouts.app')
@section('title','Upload Document')
@section('content')
<div class="row justify-content-center">
 <div class="col-md-6">
  <div class="card shadow">
   <div class="card-header" style="background:#6c3483;color:white"><h5>Upload PDF Document</h5></div>
   <div class="card-body">
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
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
      <input type="file" name="pdf_file" class="form-control @error('pdf_file') is-invalid @enderror"
             accept=".pdf" required>
      @error('pdf_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
     </div>
     <button type="submit" class="btn btn-primary w-100">
      <i class="fas fa-upload me-2"></i>Upload & Continue to Sign
     </button>
    </form>
   </div>
  </div>
 </div>
</div>
@endsection
