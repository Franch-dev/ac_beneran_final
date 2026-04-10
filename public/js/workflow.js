// workflow.js - Add this to public/js/
// This handles the workflow timeline, assign modal, and progress updates

// Global workflow modals
let assignModal = null;
let timelineModal = null;
const workflowSafeText = window.escapeHtml || ((value) => String(value ?? ''));

const ASSIGNMENT_STATUS_LABELS = {
    assigned: 'Ditugaskan',
    in_progress: 'Sedang Dikerjakan',
    done: 'Selesai',
};

// Open technician assignment modal
function openAssignTech(orderId, orderNumber, masjidName) {
    if (!assignModal) {
        assignModal = createAssignModal();
    }
    assignModal.show(orderId, orderNumber, masjidName);
}

// Show workflow timeline modal
function showWorkflowTimeline(orderId, orderNumber, masjidName) {
    if (!timelineModal) {
        timelineModal = createTimelineModal();
    }
    timelineModal.loadData(orderId, orderNumber, masjidName);
}

// Create technician assignment modal
function createAssignModal() {
    const modal = {
        element: null,
        async show(orderId, orderNumber, masjidName) {
            await this.loadTechnicians(orderId, orderNumber, masjidName);
        },
        async loadTechnicians(orderId, orderNumber, masjidName) {
            try {
                const techs = await apiFetch('/workflow/technicians');
                this.renderModal(orderId, orderNumber, masjidName, techs);
            } catch (error) {
                showToast(error.message || 'Gagal memuat daftar teknisi', 'error');
            }
        },
        renderModal(orderId, orderNumber, masjidName, technicians) {
            const safeOrderNumber = workflowSafeText(orderNumber);
            const safeMasjidName = workflowSafeText(masjidName);
            const html = `
                <div class="popup popup-md popup-workflow" id="assignTechModal" data-temporary-popup="true">
                    <div class="popup-header">
                        <h3><i class="fas fa-user-hard-hat"></i> Tugas Teknisi</h3>
                        <button class="popup-close" type="button" onclick="closeWorkflowPopup('assignTechModal')">&times;</button>
                    </div>
                    <div class="popup-body">
                        <div class="order-meta">
                            <div class="popup-title-group">
                                <span class="popup-kicker">Assignment Queue</span>
                                <div class="order-num">${safeOrderNumber}</div>
                                <div class="text-muted">${safeMasjidName}</div>
                            </div>
                            <span class="notification-badge notification-badge--soft">
                                <i class="fas fa-user-hard-hat"></i> Menunggu penugasan
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pilih Teknisi <span style="color:var(--danger);font-weight:500">*</span></label>
                            <select id="techSelect" class="form-select" required>
                                <option value="">-- Pilih Teknisi --</option>
                                ${technicians.map((technician) => `<option value="${technician.id}">${workflowSafeText(technician.name)} (${workflowSafeText(technician.email)})</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan (opsional)</label>
                            <textarea id="assignNotes" class="form-textarea" rows="3" placeholder="Instruksi khusus untuk teknisi..."></textarea>
                        </div>
                        <div class="popup-actions">
                            <button class="btn btn-secondary" type="button" onclick="closeWorkflowPopup('assignTechModal')">Batal</button>
                            <button class="btn btn-accent" type="button" onclick="submitAssignment(${orderId})">
                                <i class="fas fa-paper-plane"></i> Tugaskan Teknisi
                            </button>
                        </div>
                    </div>
                </div>
            `;
            showCustomPopup(html, 'assignTechModal');
        }
    };
    return modal;
}

// Submit technician assignment
async function submitAssignment(orderId) {
    const techId = document.getElementById('techSelect').value;
    const notes = document.getElementById('assignNotes').value;

    if (!techId) {
        showToast('Pilih teknisi terlebih dahulu', 'error');
        return;
    }

    try {
        const data = await apiFetch(`/workflow/${orderId}/assign`, 'POST', {
            technician_id: techId,
            notes,
        });

        closeWorkflowPopup('assignTechModal');
        if (data.success) {
            showToast(data.message, 'success');
            await refreshMonitoringSurface();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast(error.message || 'Gagal menugaskan teknisi', 'error');
    }
}

// Create timeline modal
function createTimelineModal() {
    const modal = {
        element: null,
        async loadData(orderId, orderNumber, masjidName) {
            try {
                const data = await apiFetch(`/workflow/${orderId}/timeline`);
                this.renderTimeline(orderId, orderNumber, masjidName, data);
            } catch (error) {
                showToast(error.message || 'Gagal memuat timeline workflow', 'error');
            }
        },
        renderTimeline(orderId, orderNumber, masjidName, data) {
            const safeOrderNumber = workflowSafeText(orderNumber);
            const safeMasjidName = workflowSafeText(masjidName);
            const stepsHtml = data.steps.map(step => `
                <div class="timeline-item">
                    <div class="timeline-icon" style="background:${step.color}">
                        <i class="${step.icon}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-label">${workflowSafeText(step.label)}</div>
                        <div class="timeline-actor">${workflowSafeText(step.actor_name)} <span class="role-badge">${workflowSafeText(step.actor_role)}</span></div>
                        ${step.notes ? `<div class="timeline-notes">${workflowSafeText(step.notes)}</div>` : ''}
                        <div class="timeline-time">${workflowSafeText(step.time)}</div>
                    </div>
                </div>
            `).join('');

            const assignmentHtml = data.assignment ? `
                <div class="assignment-section">
                    <div style="font-weight:600;margin-bottom:0.75rem;color:var(--primary);border-bottom:1px solid var(--border);padding-bottom:0.5rem">
                        <i class="fas fa-user-hard-hat"></i> Teknisi Ditugaskan
                    </div>
                    <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem;background:var(--primary-soft);border-radius:var(--radius);margin-bottom:1rem">
                        <div style="font-weight:600">${workflowSafeText(data.assignment.technician_name)}</div>
                        <span class="status-badge status-${data.assignment.status}">${labelForAssignmentStatus(data.assignment.status)}</span>
                    </div>
                    ${data.assignment.notes ? `<div class="timeline-notes">${workflowSafeText(data.assignment.notes)}</div>` : ''}
                    ${data.assignment.started_at ? `<div class="timeline-time">Mulai: ${workflowSafeText(data.assignment.started_at)}</div>` : ''}
                    ${data.assignment.completed_at ? `<div class="timeline-time">Selesai: ${workflowSafeText(data.assignment.completed_at)}</div>` : ''}
                </div>
            ` : '';

            const html = `
                <div class="popup popup-lg popup-workflow" id="timelineModal" data-temporary-popup="true">
                    <div class="popup-header">
                        <h3><i class="fas fa-stream"></i> Riwayat Workflow</h3>
                        <button class="popup-close" type="button" onclick="closeWorkflowPopup('timelineModal')">&times;</button>
                    </div>
                    <div class="popup-body">
                        <div class="order-meta">
                            <div class="popup-title-group">
                                <span class="popup-kicker">Workflow Timeline</span>
                                <div class="order-num">${safeOrderNumber}</div>
                                <div class="text-muted">${safeMasjidName}</div>
                            </div>
                            <span class="notification-badge notification-badge--accent">
                                <i class="fas fa-stream"></i> ${data.steps.length} langkah tercatat
                            </span>
                        </div>
                        <div class="timeline-container">
                            ${stepsHtml}
                            ${assignmentHtml}
                            ${!data.steps.length && !data.assignment ? '<div class="empty-state"><i class="fas fa-stream"></i><p>Belum ada aktivitas workflow</p></div>' : ''}
                        </div>
                    </div>
                </div>
            `;
            showCustomPopup(html, 'timelineModal');
        }
    };
    return modal;
}

function labelForAssignmentStatus(status) {
    return ASSIGNMENT_STATUS_LABELS[status] || String(status || '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
}

// Utility: show custom popup html
function showCustomPopup(html, popupId) {
    document.getElementById(popupId)?.remove();

    const popup = document.createElement('div');
    popup.innerHTML = html.trim();

    const element = popup.firstElementChild;
    if (!element) {
        return;
    }

    element.dataset.temporaryPopup = 'true';
    document.body.appendChild(element);

    if (typeof openPopup === 'function') {
        openPopup(popupId);
        return;
    }

    element.classList.add('active');
}

function closeWorkflowPopup(id) {
    if (typeof closePopup === 'function') {
        closePopup(id);
    } else {
        document.getElementById(id)?.classList.remove('active');
    }

    document.getElementById(id)?.remove();
}

