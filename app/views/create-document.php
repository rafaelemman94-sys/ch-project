<?php
$document = $document ?? null;
$authors = $authors ?? [];
$sponsors = $sponsors ?? [];
$selectedAuthorId = (string)($document['author_id'] ?? '');
$selectedSponsorId = (string)($document['sponsor_id'] ?? '');
$selectedAuthorName = $document['author_name'] ?? '';
$selectedSponsorName = $document['sponsor_name'] ?? '';
$documentStatus = $document['status'] ?? 'draft';
?>
<div class="document-shell">
  <div class="document-header">
    <div>
      <div class="document-kicker">Document workspace</div>
      <h1 class="document-heading">Create New Document</h1>
      <p class="document-subtitle">Set up the document details, write the content, then publish when ready.</p>
    </div>
    <div class="document-steps" aria-label="Document progress">
      <span class="document-step is-active"><span class="document-step-number">1</span> Setup</span>
      <span class="document-step-line"></span>
      <span class="document-step"><span class="document-step-number">2</span> Content</span>
      <span class="document-step-line"></span>
      <span class="document-step"><span class="document-step-number">3</span> Review</span>
    </div>
  </div>

  <form class="document-grid" method="post" action="index.php?page=create-document" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_document">
    <input type="hidden" name="id" value="<?= esc($document['id'] ?? '') ?>">
    <input type="hidden" name="author_name" id="author-name" value="<?= esc($selectedAuthorName) ?>">
    <input type="hidden" name="sponsor_name" id="sponsor-name" value="<?= esc($selectedSponsorName) ?>">
    <main class="document-card">
      <div class="document-card-body document-form">
        <div>
          <label class="document-label" for="document-title">Document Title</label>
          <input class="document-input" id="document-title" name="title" required placeholder="Enter Document Title, e.g., Annual Report 2026" value="<?= esc($document['title'] ?? '') ?>">
        </div>
        <div class="document-field-row">
          <div>
            <label class="document-label" for="author-id">Document Author</label>
            <div class="document-picker">
              <select class="document-select" id="author-id" name="author_id">
                <option value="">Select an author</option>
                <?php foreach ($authors as $author): ?>
                  <option value="<?= esc($author['id']) ?>" data-name="<?= esc($author['name']) ?>"<?= $selectedAuthorId === (string)$author['id'] ? ' selected' : '' ?>><?= esc($author['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="document-quick" type="button" onclick="addDocumentPerson('author')">+ Add New Author</button>
            </div>
            <span class="document-pill" id="author-pill"<?= $selectedAuthorName === '' ? ' hidden' : '' ?>>● <span><?= esc($selectedAuthorName) ?></span></span>
          </div>
          <div>
            <label class="document-label" for="sponsor-id">Document Sponsor</label>
            <div class="document-picker">
              <select class="document-select" id="sponsor-id" name="sponsor_id">
                <option value="">Select a sponsor</option>
                <?php foreach ($sponsors as $sponsor): ?>
                  <option value="<?= esc($sponsor['id']) ?>" data-name="<?= esc($sponsor['name']) ?>"<?= $selectedSponsorId === (string)$sponsor['id'] ? ' selected' : '' ?>><?= esc($sponsor['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="document-quick" type="button" onclick="addDocumentPerson('sponsor')">+ Add New Sponsor</button>
            </div>
            <span class="document-pill" id="sponsor-pill"<?= $selectedSponsorName === '' ? ' hidden' : '' ?>>● <span><?= esc($selectedSponsorName) ?></span></span>
          </div>
        </div>
        <div>
          <label class="document-label" for="document-content">Document Content</label>
          <div class="document-toolbar" aria-label="Formatting toolbar">
            <button class="document-tool" type="button" onclick="formatDocument('bold')"><strong>B</strong></button>
            <button class="document-tool" type="button" onclick="formatDocument('italic')"><em>I</em></button>
            <button class="document-tool" type="button" onclick="formatDocument('underline')"><u>U</u></button>
            <button class="document-tool" type="button" onclick="formatDocument('insertUnorderedList')">&#8226;</button>
            <button class="document-tool" type="button" onclick="formatDocument('insertOrderedList')">1.</button>
            <button class="document-tool" type="button" onclick="formatDocument('formatBlock', 'blockquote')">&ldquo;</button>
          </div>
          <textarea class="document-textarea" id="document-content" name="content" required placeholder="Start writing the document introduction or main body..."><?= esc($document['content'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="document-label" for="document-file">Attachment <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
          <div class="document-attachment"><input id="document-file" type="file" name="document_file" accept=".pdf,.doc,.docx,.txt"> <span>PDF, Word, or TXT up to 10 MB</span></div>
        </div>
      </div>
    </main>

    <aside class="document-card document-sidebar">
      <div class="document-meta">
        <div class="document-meta-row"><span>Status</span><span class="document-status<?= $documentStatus === 'published' ? ' published' : '' ?>"><?= esc(ucfirst($documentStatus)) ?></span></div>
        <div class="document-meta-row"><span>Last Saved</span><strong><?= esc($document['updated_at'] ?? 'Not saved yet') ?></strong></div>
        <div class="document-meta-row"><span>Version</span><strong><?= esc($document['version'] ?? '1.0') ?></strong></div>
      </div>
      <div class="document-actions">
        <button class="btn btn-secondary" type="submit" name="status" value="draft">Save Draft</button>
        <button class="btn btn-primary" type="submit" name="status" value="published">Publish Document</button>
        <a class="btn btn-ghost" href="?page=dashboard" style="justify-content:center">Cancel</a>
      </div>
    </aside>
  </form>
</div>
