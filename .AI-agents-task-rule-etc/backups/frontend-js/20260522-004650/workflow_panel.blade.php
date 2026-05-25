<!-- Workflow Timeline Modal -->
<div class="popup popup-lg" id="workflowTimelineModal">
    <div class="popup-header">
        <div class="popup-title-group">
            <h3><i class="fas fa-stream"></i> Riwayat Workflow</h3>
            <p class="popup-kicker">Timeline lengkap setiap langkah dalam proses service order</p>
        </div>
        <button class="popup-close" onclick="closePopup('workflowTimelineModal')">&times;</button>
    </div>
    <div class="popup-body" id="workflowTimelineBody">
        <div class="timeline-loading" style="text-align:center;padding:2rem;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i>
            <p style="margin-top:1rem;">Memuat timeline...</p>
        </div>
    </div>
</div>

<!-- Workflow Panel Partial -->
<div id="workflowPanel" class="workflow-panel" style="display:none">
    <div class="workflow-header">
        <h4>Workflow <span id="workflowOrderNum"></span></h4>
        <button class="btn-close" onclick="closeWorkflowPanel()">&times;</button>
    </div>
    <div class="workflow-body">
        <p>Workflow panel will be populated dynamically.</p>
    </div>
</div>

<script>
window.closeWorkflowPanel = function() {
    document.getElementById('workflowPanel')?.classList.remove('active');
};
</script>