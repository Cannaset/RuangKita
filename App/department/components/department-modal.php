<div class="admin-modal" id="departmentPostModal" hidden>
    <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="departmentModalTitle">
        <button class="modal-close" id="departmentModalClose" type="button" aria-label="Tutup detail">&times;</button>
        <div class="modal-body" id="departmentModalBody"></div>
    </div>
</div>

<script type="application/json" id="departmentPostData"><?= json_encode(
    $departmentPostDetails,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
); ?></script>
