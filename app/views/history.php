<div class="page-history">
    <div class="history-header">
        <h2>Historique des transactions</h2>
        <div class="filter-bar">
            <select id="typeFilter" class="filter-select">
                <option value="">Tous les types</option>
                <option value="send" <?= $filters['type'] === 'send' ? 'selected' : '' ?>>Envois</option>
                <option value="deposit" <?= $filters['type'] === 'deposit' ? 'selected' : '' ?>>Dépôts</option>
                <option value="withdraw" <?= $filters['type'] === 'withdraw' ? 'selected' : '' ?>>Retraits</option>
                <option value="bill" <?= $filters['type'] === 'bill' ? 'selected' : '' ?>>Factures</option>
            </select>
            <select id="statusFilter" class="filter-select">
                <option value="">Tous les statuts</option>
                <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Réussis</option>
                <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                <option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : '' ?>>Échoués</option>
            </select>
        </div>
    </div>
    
    <?php if (empty($transactions)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-receipt"></i>
            <p>Aucune transaction trouvée</p>
            <a href="/transactions/send" class="btn btn-primary">Effectuer un transfert</a>
        </div>
    <?php else: ?>
        <div class="transactions-list">
            <?php foreach ($transactions as $transaction): ?>
                <div class="transaction-item">
                    <div class="transaction-icon">
                        <?php if ($transaction['type'] === 'send'): ?>
                            <i class="fa-solid fa-paper-plane"></i>
                        <?php elseif ($transaction['type'] === 'deposit'): ?>
                            <i class="fa-solid fa-download"></i>
                        <?php elseif ($transaction['type'] === 'withdraw'): ?>
                            <i class="fa-solid fa-upload"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-receipt"></i>
                        <?php endif; ?>
                    </div>
                    <div class="transaction-details">
                        <div class="transaction-title">
                            <?php if ($transaction['type'] === 'send'): ?>
                                Envoi à <?= htmlspecialchars($transaction['recipient_name'] ?? 'Inconnu') ?>
                            <?php elseif ($transaction['type'] === 'deposit'): ?>
                                Dépôt
                            <?php elseif ($transaction['type'] === 'withdraw'): ?>
                                Retrait
                            <?php else: ?>
                                Paiement de facture
                            <?php endif; ?>
                        </div>
                        <div class="transaction-meta">
                            <span class="transaction-date">
                                <?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?>
                            </span>
                            <span class="transaction-ref">
                                Réf: <?= htmlspecialchars($transaction['transaction_reference']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="transaction-amount <?= $transaction['type'] === 'send' ? 'negative' : 'positive' ?>">
                        <?php if ($transaction['type'] === 'send'): ?>
                            -<?= number_format($transaction['amount'], 0, ',', ' ') ?> FC
                        <?php else: ?>
                            +<?= number_format($transaction['amount'], 0, ',', ' ') ?> FC
                        <?php endif; ?>
                    </div>
                    <div class="transaction-status status-<?= $transaction['status'] ?>">
                        <?php if ($transaction['status'] === 'completed'): ?>
                            <i class="fa-solid fa-check-circle"></i> Réussi
                        <?php elseif ($transaction['status'] === 'pending'): ?>
                            <i class="fa-solid fa-clock"></i> En attente
                        <?php else: ?>
                            <i class="fa-solid fa-times-circle"></i> Échoué
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= isset($filters['type']) && $filters['type'] ? '&type=' . $filters['type'] : '' ?><?= isset($filters['status']) && $filters['status'] ? '&status=' . $filters['status'] : '' ?>" 
                       class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    function updateFilters() {
        const params = new URLSearchParams(window.location.search);
        
        if (typeFilter.value) {
            params.set('type', typeFilter.value);
        } else {
            params.delete('type');
        }
        
        if (statusFilter.value) {
            params.set('status', statusFilter.value);
        } else {
            params.delete('status');
        }
        
        params.set('page', '1');
        window.location.search = params.toString();
    }
    
    typeFilter.addEventListener('change', updateFilters);
    statusFilter.addEventListener('change', updateFilters);
});
</script>