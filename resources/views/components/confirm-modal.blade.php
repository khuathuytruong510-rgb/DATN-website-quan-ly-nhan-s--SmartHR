<div class="modal fade" id="appConfirmModal" tabindex="-1" aria-labelledby="appConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content app-confirm-content">
            <div class="modal-header">
                <h5 class="modal-title" id="appConfirmTitle">Xác nhận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="appConfirmBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn primary" id="appConfirmOk">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<style>
    .app-confirm-content {
        border: 1px solid var(--line, #e2e8f0);
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
    }
    #appConfirmModal .modal-header,
    #appConfirmModal .modal-footer {
        border-color: var(--line, #e2e8f0);
        padding: 14px 18px;
    }
    #appConfirmModal .modal-title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    #appConfirmBody {
        white-space: pre-line;
        color: #334155;
        line-height: 1.6;
        padding: 16px 18px;
    }
    #appConfirmOk.danger,
    #appConfirmOk.btn-danger {
        background: #b91c1c;
        color: #fff;
        border-color: transparent;
    }
    #appConfirmOk.danger:hover,
    #appConfirmOk.btn-danger:hover {
        background: #991b1b;
        color: #fff;
    }
</style>
