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
          <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" class="d-inline js-delete-form">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-sm btn-outline-danger js-delete-trigger"
                    data-doc-title="{{ $doc->title }}">
              <i class="fas fa-trash me-1"></i>Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col-12"><p class="text-muted">No documents yet. Upload your first PDF!</p></div>
  @endforelse
</div>
{{ $documents->links() }}

<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-labelledby="deleteDocModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteDocModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0" id="deleteDocMessage">Delete this document? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-outline-danger" id="confirmDeleteDocBtn">
          <i class="fas fa-trash me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('deleteDocModal');
  const confirmBtn = document.getElementById('confirmDeleteDocBtn');
  const messageEl = document.getElementById('deleteDocMessage');
  let targetForm = null;

  // Move modal to body so Bootstrap z-index/backdrop layering works reliably.
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }

  const deleteModal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;

  document.querySelectorAll('.js-delete-trigger').forEach((btn) => {
    btn.addEventListener('click', () => {
      targetForm = btn.closest('form');
      const title = btn.getAttribute('data-doc-title') || 'this document';
      messageEl.textContent = 'Delete "' + title + '"? This action cannot be undone.';

      if (deleteModal) {
        deleteModal.show();
      } else {
        const ok = confirm(messageEl.textContent);
        if (ok && targetForm) {
          targetForm.submit();
        }
      }
    });
  });

  confirmBtn.addEventListener('click', () => {
    if (!targetForm) {
      return;
    }
    confirmBtn.disabled = true;
    targetForm.submit();
  });

  modalEl.addEventListener('hidden.bs.modal', () => {
    targetForm = null;
    confirmBtn.disabled = false;
  });
});
</script>
@endpush
