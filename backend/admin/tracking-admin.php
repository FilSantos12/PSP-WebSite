<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

$labels = ['Em Preparação', 'Embalado', 'Enviado', 'Código de Rastreio'];
$labelColors = ['secondary', 'primary', 'warning', 'success'];

// Busca todos os registros com dados do pedido
$rows = $pdo->query("
    SELECT ot.order_id, ot.status, ot.tracking_code, ot.carrier, ot.notes, ot.updated_at,
           p.nome_comprador, p.email_comprador
    FROM order_tracking ot
    LEFT JOIN pedidos p ON p.id = CAST(ot.order_id AS INTEGER)
    ORDER BY ot.updated_at DESC
")->fetchAll();

layout_head('Rastreamento de Pedidos');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Gerencie o status de envio dos pedidos.</p>
    <button class="btn btn-primary btn-sm" onclick="abrirModal(null)">
        <i class="fas fa-plus me-1"></i> Novo Registro
    </button>
</div>

<?php if (empty($rows)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>Nenhum registro de rastreamento. Clique em "Novo Registro" para começar.
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Pedido</th>
                    <th>Comprador</th>
                    <th>Status</th>
                    <th>Transportadora</th>
                    <th>Código</th>
                    <th>Atualizado em</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody id="trackingTableBody">
            <?php foreach ($rows as $r):
                $s   = (int) $r['status'];
                $lbl = $labels[$s] ?? '?';
                $clr = $labelColors[$s] ?? 'secondary';
                $upd = $r['updated_at'] ? date('d/m/Y H:i', strtotime($r['updated_at'])) : '—';
            ?>
            <tr data-orderid="<?= htmlspecialchars($r['order_id']) ?>">
                <td><strong>#<?= htmlspecialchars($r['order_id']) ?></strong></td>
                <td>
                    <div class="small"><?= htmlspecialchars($r['nome_comprador'] ?? '—') ?></div>
                    <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($r['email_comprador'] ?? '') ?></div>
                </td>
                <td>
                    <span class="badge bg-<?= $clr ?> td-status"><?= htmlspecialchars($lbl) ?></span>
                </td>
                <td class="td-carrier"><?= htmlspecialchars($r['carrier'] ?? '—') ?></td>
                <td class="td-code" style="font-family:monospace;"><?= htmlspecialchars($r['tracking_code'] ?? '—') ?></td>
                <td class="td-updated small text-muted"><?= $upd ?></td>
                <td class="text-center">
                    <button class="btn btn-outline-primary btn-sm"
                            onclick='abrirModal(<?= json_encode([
                                "order_id"      => $r["order_id"],
                                "status"        => $s,
                                "tracking_code" => $r["tracking_code"] ?? "",
                                "carrier"       => $r["carrier"] ?? "",
                                "notes"         => $r["notes"] ?? "",
                            ]) ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal Editar / Novo -->
<div class="modal fade" id="trackingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trackingModalTitle">Rastreamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalAlert" class="alert d-none mb-3"></div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Pedido #</label>
                    <input type="text" id="mOrderId" class="form-control" placeholder="Ex: 42">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="mStatus" class="form-select">
                        <option value="0">0 — Em Preparação</option>
                        <option value="1">1 — Embalado</option>
                        <option value="2">2 — Enviado</option>
                        <option value="3">3 — Código de Rastreio</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Código de Rastreio</label>
                    <input type="text" id="mCode" class="form-control" placeholder="Ex: AA123456789BR" style="font-family:monospace; text-transform:uppercase;">
                    <div class="form-text">Formato Correios: 2 letras + 9 dígitos + BR</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Transportadora</label>
                    <input type="text" id="mCarrier" class="form-control" placeholder="Ex: Correios, Jadlog, Total Express">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Observações internas</label>
                    <textarea id="mNotes" class="form-control" rows="2" placeholder="Anotações para controle interno (não visível ao cliente)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvar" onclick="salvar()">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast de feedback -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
    <div id="feedbackToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
const LABELS       = ['Em Preparação', 'Embalado', 'Enviado', 'Código de Rastreio'];
const LABEL_COLORS = ['secondary', 'primary', 'warning', 'success'];
let   modalBS      = null;
let   isEditing    = false;

function abrirModal(data) {
    const title  = document.getElementById('trackingModalTitle');
    const oId    = document.getElementById('mOrderId');
    const alert  = document.getElementById('modalAlert');

    alert.className = 'alert d-none mb-3';
    alert.textContent = '';

    if (data) {
        isEditing         = true;
        title.textContent = 'Editar Rastreamento — Pedido #' + data.order_id;
        oId.value         = data.order_id;
        oId.readOnly      = true;
        document.getElementById('mStatus').value  = data.status;
        document.getElementById('mCode').value    = data.tracking_code || '';
        document.getElementById('mCarrier').value = data.carrier || '';
        document.getElementById('mNotes').value   = data.notes || '';
    } else {
        isEditing         = false;
        title.textContent = 'Novo Registro de Rastreamento';
        oId.value         = '';
        oId.readOnly      = false;
        document.getElementById('mStatus').value  = '0';
        document.getElementById('mCode').value    = '';
        document.getElementById('mCarrier').value = '';
        document.getElementById('mNotes').value   = '';
    }

    if (!modalBS) modalBS = new bootstrap.Modal(document.getElementById('trackingModal'));
    modalBS.show();
}

async function salvar() {
    const btn     = document.getElementById('btnSalvar');
    const alertEl = document.getElementById('modalAlert');
    const orderId = document.getElementById('mOrderId').value.trim();

    if (!orderId) {
        mostrarAlertaModal('Informe o número do pedido.', 'danger');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Salvando...';

    try {
        const resp = await fetch('/backend/api/tracking.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id:      orderId,
                status:        parseInt(document.getElementById('mStatus').value),
                tracking_code: document.getElementById('mCode').value.trim().toUpperCase() || null,
                carrier:       document.getElementById('mCarrier').value.trim() || null,
                notes:         document.getElementById('mNotes').value.trim() || null,
            }),
        });
        const json = await resp.json();

        if (!resp.ok) {
            mostrarAlertaModal(json.erro || 'Erro ao salvar.', 'danger');
            return;
        }

        modalBS.hide();
        atualizarLinha(json);
        mostrarToast('Rastreamento salvo com sucesso!', 'success');

    } catch (_) {
        mostrarAlertaModal('Erro de conexão. Tente novamente.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Salvar';
    }
}

function atualizarLinha(json) {
    const tbody  = document.getElementById('trackingTableBody');
    const s      = json.status;
    const lbl    = LABELS[s] || '?';
    const clr    = LABEL_COLORS[s] || 'secondary';
    const now    = new Date().toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
    const code   = document.getElementById('mCode').value.trim().toUpperCase() || '—';
    const carrier= document.getElementById('mCarrier').value.trim() || '—';

    let row = tbody.querySelector(`tr[data-orderid="${json.order_id}"]`);
    if (row) {
        row.querySelector('.td-status').className   = `badge bg-${clr} td-status`;
        row.querySelector('.td-status').textContent = lbl;
        row.querySelector('.td-carrier').textContent = carrier;
        row.querySelector('.td-code').textContent    = code;
        row.querySelector('.td-updated').textContent = now;

        // Atualiza o onclick do botão de edição
        const editBtn = row.querySelector('button');
        editBtn.setAttribute('onclick', `abrirModal(${JSON.stringify({
            order_id: json.order_id,
            status: s,
            tracking_code: code === '—' ? '' : code,
            carrier: carrier === '—' ? '' : carrier,
            notes: document.getElementById('mNotes').value.trim(),
        })})`);
    } else {
        // Nova linha no topo
        const tr = document.createElement('tr');
        tr.dataset.orderid = json.order_id;
        tr.innerHTML = `
            <td><strong>#${json.order_id}</strong></td>
            <td><div class="small">—</div></td>
            <td><span class="badge bg-${clr} td-status">${lbl}</span></td>
            <td class="td-carrier">${carrier}</td>
            <td class="td-code" style="font-family:monospace;">${code}</td>
            <td class="td-updated small text-muted">${now}</td>
            <td class="text-center">
                <button class="btn btn-outline-primary btn-sm"
                        onclick='abrirModal(${JSON.stringify({
                            order_id: json.order_id, status: s,
                            tracking_code: code === '—' ? '' : code,
                            carrier: carrier === '—' ? '' : carrier, notes: ''
                        })})'>
                    <i class="fas fa-edit"></i>
                </button>
            </td>`;
        tbody.prepend(tr);

        // Remove aviso "nenhum registro" se existir
        document.querySelector('.alert-info')?.remove();
    }
}

function mostrarAlertaModal(msg, tipo) {
    const el = document.getElementById('modalAlert');
    el.className = `alert alert-${tipo} mb-3`;
    el.textContent = msg;
}

function mostrarToast(msg, tipo) {
    const toast = document.getElementById('feedbackToast');
    const body  = document.getElementById('toastMsg');
    toast.className = `toast align-items-center text-white border-0 bg-${tipo}`;
    body.textContent = msg;
    new bootstrap.Toast(toast, { delay: 3500 }).show();
}

// Uppercase automático no campo de código
document.getElementById('mCode').addEventListener('input', function () {
    this.value = this.value.toUpperCase();
});
</script>

<?php layout_foot(); ?>
