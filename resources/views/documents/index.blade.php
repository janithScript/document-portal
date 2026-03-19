@extends('layouts.app')
@section('title','My Documents')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>All Documents</h3>
  <a href="{{ route('documents.create') }}" class="btn btn-primary">
    <i class="fas fa-upload me-2"></i>Upload PDF
  </a>
</div>
<div class="row">
  @forelse($documents as $doc)
  <div class="col-md-4 mb-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title"><i class="fas fa-file-pdf text-danger me-2"></i>{{ $doc->title }}</h5>
        <p class="text-muted small">By: {{ $doc->uploader_name }} | {{ $doc->created_at->diffForHumans() }}</p>
        <span class="badge bg-{{ $doc->status==='signed'?'success':($doc->status==='signing'?'warning':'secondary') }}">
          {{ ucfirst($doc->status) }}</span>
        <div class="mt-2">
          <a href="{{ route('documents.sign', $doc->id) }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-pen me-1"></i>Sign/Edit</a>
          <a href="{{ route('documents.download', $doc->id) }}" class="btn btn-sm btn-outline-success">
            <i class="fas fa-download me-1"></i>Download</a>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col-12"><p class="text-muted">No documents yet. Upload your first PDF!</p></div>
  @endforelse
</div>
{{ $documents->links() }}
@endsection
