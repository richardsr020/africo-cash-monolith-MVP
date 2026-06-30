<?php require __DIR__ . '/../partials/app_shell_start.php';

$userId = (int) $currentUser['id'];

$accountsStmt = $db->prepare("SELECT currency, balance FROM accounts WHERE user_id = :uid");
$accountsStmt->execute([':uid' => $userId]);
$accounts = $accountsStmt->fetchAll();
$balances = [];
foreach ($accounts as $acc) {
    $balances[$acc['currency']] = (int) $acc['balance'];
}
$balanceCdf = $balances['CDF'] ?? 0;
$balanceUsd = $balances['USD'] ?? 0;

$activeTab = $_GET['tab'] ?? 'send';

$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$filters = ['type' => $typeFilter, 'status' => $statusFilter];

$txnWhere = 'WHERE user_id = :uid';
$txnParams = [':uid' => $userId];
if ($typeFilter !== '') {
    $txnWhere .= ' AND type = :type';
    $txnParams[':type'] = $typeFilter;
}
if ($statusFilter !== '') {
    $txnWhere .= ' AND status = :status';
    $txnParams[':status'] = $statusFilter;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions {$txnWhere}");
$countStmt->execute($txnParams);
$totalTransactions = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalTransactions / $limit));

$txnStmt = $db->prepare("SELECT * FROM transactions {$txnWhere} ORDER BY created_at DESC LIMIT :lim OFFSET :off");
$txnStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$txnStmt->bindValue(':off', $offset, PDO::PARAM_INT);
foreach ($txnParams as $k => $v) {
    $txnStmt->bindValue($k, $v);
}
$txnStmt->execute();
$transactions = $txnStmt->fetchAll();

$errors = $_SESSION['errors'] ?? [];
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['error'], $_SESSION['success']);
?>

<div class="transactions-module">
    <div class="transactions-header">
        <h1>Transactions</h1>
        <p>Gérez vos transferts d'argent et consultez votre historique</p>
    </div>

    <!-- Tabs Navigation -->
    <div class="transactions-tabs">
        <button class="tab-btn <?= $activeTab === 'send' ? 'active' : '' ?>" data-tab="send">
            <i class="fa-solid fa-paper-plane"></i>
            <span>Envoyer</span>
        </button>
        <button class="tab-btn <?= $activeTab === 'deposit' ? 'active' : '' ?>" data-tab="deposit">
            <i class="fa-solid fa-download"></i>
            <span>Dépôt</span>
        </button>
        <button class="tab-btn <?= $activeTab === 'withdraw' ? 'active' : '' ?>" data-tab="withdraw">
            <i class="fa-solid fa-upload"></i>
            <span>Retrait</span>
        </button>
        <button class="tab-btn <?= $activeTab === 'history' ? 'active' : '' ?>" data-tab="history">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Historique</span>
        </button>
        <?php if (isset($_SESSION['receipt']) || $activeTab === 'receipt'): ?>
        <button class="tab-btn <?= $activeTab === 'receipt' ? 'active' : '' ?>" data-tab="receipt">
            <i class="fa-solid fa-receipt"></i>
            <span>Reçu</span>
        </button>
        <?php endif; ?>
    </div>

    <!-- Tab Content: Send -->
    <div class="tab-content <?= $activeTab === 'send' ? 'active' : '' ?>" id="tab-send">
        <div class="transaction-card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/transactions" class="transaction-form" id="sendForm">
                <div class="form-group">
                    <label>Solde disponible</label>
                    <div class="balance-display">
                        <span class="balance-amount"><?= number_format($balanceCdf / 100, 2, ',', ' ') ?> FC</span>
                        <?php if ($balanceUsd > 0): ?>
                        <span class="balance-amount" style="margin-left:0.5rem;color:var(--text-muted)"><?= number_format($balanceUsd / 100, 2, ',', ' ') ?> USD</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="recipient">Numéro Africo du destinataire</label>
                    <input type="text" id="recipient" name="recipient" required placeholder="Ex: 12345678" pattern="[0-9]{8}" autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="sendCurrency">Devise</label>
                    <select id="sendCurrency" name="currency" class="form-input">
                        <option value="CDF">FC (Franc Congolais)</option>
                        <option value="USD">USD (Dollar Américain)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sendAmount">Montant à envoyer</label>
                    <input type="number" id="sendAmount" name="amount" required min="100" max="1000000" step="100" placeholder="0">
                </div>

                <div class="form-group">
                    <label for="description">Description (optionnel)</label>
                    <textarea id="description" name="description" rows="2" placeholder="Motif du transfert"></textarea>
                </div>

                <div class="form-group">
                    <label for="sendPin">PIN de sécurité</label>
                    <input type="password" id="sendPin" name="pin" required placeholder="Votre PIN à 4 chiffres" pattern="[0-9]{4}" inputmode="numeric" maxlength="4" autocomplete="off">
                </div>

                <input type="hidden" name="action" value="send">

                <div class="fee-breakdown" style="display:none"></div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-paper-plane"></i>
                    Effectuer le transfert
                </button>
            </form>
        </div>
    </div>

    <!-- Tab Content: Deposit -->
    <div class="tab-content <?= $activeTab === 'deposit' ? 'active' : '' ?>" id="tab-deposit">
        <div class="transaction-card">
            <div class="deposit-info">
                <h3>Recevoir de l'argent sur votre compte Africo Cash</h3>
                <p>Utilisez les informations ci-dessous pour recevoir un virement depuis Airtel Money, Orange Money, M-Pesa, Afrimoney ou un transfert bancaire.</p>

                <div class="account-detail-card">
                    <label>Numéro Africo Cash</label>
                    <div class="detail-value">
                        <span><?= htmlspecialchars($currentUser['africo_number']) ?></span>
                        <button class="btn btn-soft btn-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($currentUser['africo_number']) ?>'); this.innerHTML='Copié ✓'; setTimeout(()=>this.innerHTML='<i class=\'fa-solid fa-copy\'></i>', 2000)" type="button">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="account-detail-card">
                    <label>Intitulé du compte</label>
                    <div class="detail-value">
                        <?php
                        $accountName = strtoupper($currentUser['full_name']);
                        ?>
                        <span><?= htmlspecialchars($accountName) ?></span>
                        <button class="btn btn-soft btn-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($accountName) ?>'); this.innerHTML='Copié ✓'; setTimeout(()=>this.innerHTML='<i class=\'fa-solid fa-copy\'></i>', 2000)" type="button">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="deposit-providers">
                    <span><i class="fa-solid fa-mobile-screen-button"></i> Airtel Money</span>
                    <span><i class="fa-solid fa-mobile-screen-button"></i> Orange Money</span>
                    <span><i class="fa-solid fa-mobile-screen-button"></i> M-Pesa</span>
                    <span><i class="fa-solid fa-mobile-screen-button"></i> Afrimoney</span>
                    <span><i class="fa-solid fa-building-columns"></i> Virement bancaire</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content: Withdraw -->
    <div class="tab-content <?= $activeTab === 'withdraw' ? 'active' : '' ?>" id="tab-withdraw">
        <div class="transaction-card">
            <div class="withdraw-module" data-withdraw-module>

                <!-- Step 1: Choose method -->
                <div class="withdraw-step" data-step="1">
                    <div class="step-header">
                        <span class="step-badge">1</span>
                        <h3>Choisissez le moyen de retrait</h3>
                    </div>
                    <div class="method-grid">
                        <button class="method-card" data-method="atm" type="button">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                            <span class="method-label">Distributeur ATM</span>
                            <span class="method-desc">Retrait sans carte via un code temporaire</span>
                        </button>
                        <button class="method-card" data-method="agent" type="button">
                            <i class="fa-solid fa-store"></i>
                            <span class="method-label">Agent Africo Cash</span>
                            <span class="method-desc">Retrait auprès d'un agent agréé</span>
                        </button>
                    </div>
                    <div class="step-actions">
                        <button class="btn btn-primary btn-next-step" data-step-next type="button" disabled>
                            Suivant <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2a: ATM - Currency & Amount -->
                <div class="withdraw-step" data-step="2a" style="display:none">
                    <div class="step-header">
                        <span class="step-badge">2</span>
                        <h3>Paramètres du retrait ATM</h3>
                    </div>
                    <div class="form-group">
                        <label for="atmCurrency">Devise</label>
                        <select id="atmCurrency" class="form-input">
                            <option value="CDF">FC (Franc Congolais)</option>
                            <option value="USD">USD (Dollar Américain)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="atmAmount">Montant</label>
                        <input type="number" id="atmAmount" class="form-input" min="1000" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Solde disponible</label>
                        <div class="balance-display" data-balance-container="atm">
                            <span class="balance-amount" data-atm-balance><?= number_format($balanceCdf / 100, 2, ',', ' ') ?> FC</span>
                            <?php if ($balanceUsd > 0): ?>
                            <span class="balance-amount" style="margin-left:0.5rem;color:var(--text-muted)" data-atm-balance-usd><?= number_format($balanceUsd / 100, 2, ',', ' ') ?> USD</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button class="btn btn-secondary" data-step-back type="button">Retour</button>
                        <button class="btn btn-primary" data-step-next type="button">Suivant <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 2b: Agent - Info -->
                <div class="withdraw-step" data-step="2b" style="display:none">
                    <div class="step-header">
                        <span class="step-badge">2</span>
                        <h3>Informations de l'agent</h3>
                    </div>
                    <div class="form-group">
                        <label for="agentCode">Numéro ou code agent</label>
                        <input type="text" id="agentCode" class="form-input" placeholder="Ex: AG-12345">
                    </div>
                    <div class="form-group">
                        <label for="agentCurrency">Devise</label>
                        <select id="agentCurrency" class="form-input">
                            <option value="CDF">FC (Franc Congolais)</option>
                            <option value="USD">USD (Dollar Américain)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="agentAmount">Montant</label>
                        <input type="number" id="agentAmount" class="form-input" min="100" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Solde disponible</label>
                        <div class="balance-display" data-balance-container="agent">
                            <span class="balance-amount" data-agent-balance><?= number_format($balanceCdf / 100, 2, ',', ' ') ?> FC</span>
                            <?php if ($balanceUsd > 0): ?>
                            <span class="balance-amount" style="margin-left:0.5rem;color:var(--text-muted)" data-agent-balance-usd><?= number_format($balanceUsd / 100, 2, ',', ' ') ?> USD</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button class="btn btn-secondary" data-step-back type="button">Retour</button>
                        <button class="btn btn-primary" data-step-next type="button">Suivant <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 3: PIN Validation -->
                <div class="withdraw-step" data-step="3" style="display:none">
                    <div class="step-header">
                        <span class="step-badge">3</span>
                        <h3>Confirmation et validation</h3>
                    </div>

                    <div class="withdraw-summary">
                        <div class="summary-row">
                            <span>Moyen</span>
                            <strong data-summary-method>--</strong>
                        </div>
                        <div class="summary-row">
                            <span>Devise</span>
                            <strong data-summary-currency>--</strong>
                        </div>
                        <div class="summary-row">
                            <span>Montant</span>
                            <strong data-summary-amount>--</strong>
                        </div>
                        <div class="summary-row" data-summary-agent-row style="display:none">
                            <span>Agent</span>
                            <strong data-summary-agent>--</strong>
                        </div>
                        <div class="summary-row total">
                            <span>Frais</span>
                            <strong data-summary-fees>--</strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="withdrawPin">Entrez votre code PIN de sécurité</label>
                        <input type="password" id="withdrawPin" class="form-input" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" placeholder="4 chiffres" autocomplete="off">
                    </div>

                    <div class="step-actions">
                        <button class="btn btn-secondary" data-step-back type="button">Retour</button>
                        <button class="btn btn-primary" data-submit-withdraw type="button">
                            <i class="fa-solid fa-check-circle"></i> Valider le retrait
                        </button>
                    </div>
                </div>

                <!-- Step 4: Result -->
                <div class="withdraw-step" data-step="4" style="display:none">
                    <div class="step-header">
                        <span class="step-badge"><i class="fa-solid fa-check"></i></span>
                        <h3 data-result-title>Retrait effectué avec succès</h3>
                    </div>
                    <div class="withdraw-result" data-withdraw-result>

                        <!-- ATM result -->
                        <div data-result-atm>
                            <p>Utilisez les informations ci-dessous dans le distributeur ATM partenaire :</p>
                            <div class="account-detail-card">
                                <label>Code de retrait</label>
                                <div class="detail-value detail-value--large">
                                    <span data-result-code>--</span>
                                    <button class="btn btn-soft btn-sm" onclick="navigator.clipboard.writeText(this.parentElement.querySelector('span').textContent); this.innerHTML='Copié ✓'; setTimeout(()=>this.innerHTML='<i class=\'fa-solid fa-copy\'></i>', 2000)" type="button">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="account-detail-card">
                                <label>PIN ATM</label>
                                <div class="detail-value detail-value--large">
                                    <span data-result-pin>--</span>
                                    <button class="btn btn-soft btn-sm" onclick="navigator.clipboard.writeText(this.parentElement.querySelector('span').textContent); this.innerHTML='Copié ✓'; setTimeout(()=>this.innerHTML='<i class=\'fa-solid fa-copy\'></i>', 2000)" type="button">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="atm-expiry">
                                <i class="fa-solid fa-clock"></i>
                                Ce code expire dans <strong data-result-expiry>10 minutes</strong>
                            </div>
                        </div>

                        <!-- Agent result -->
                        <div data-result-agent style="display:none">
                            <div class="receipt-card" style="box-shadow:none;margin:0">
                                <div class="receipt-header" style="border-radius:var(--radius-md)">
                                    <i class="fa-solid fa-check-circle" style="font-size:3rem;margin-bottom:0.75rem"></i>
                                    <h2 style="margin-bottom:0.25rem">Retrait Agent effectué</h2>
                                    <p>Votre retrait a été traité avec succès</p>
                                </div>
                                <div class="receipt-body">
                                    <div class="receipt-row">
                                        <span class="receipt-label">Agent</span>
                                        <span class="receipt-value" data-result-agent-name>--</span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="receipt-label">Montant</span>
                                        <span class="receipt-value amount" data-result-agent-amount>--</span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="receipt-label">Frais</span>
                                        <span class="receipt-value" data-result-agent-fees>--</span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="receipt-label">Total débité</span>
                                        <span class="receipt-value" data-result-agent-total>--</span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="receipt-label">Date</span>
                                        <span class="receipt-value" data-result-agent-date>--</span>
                                    </div>
                                    <div class="receipt-row">
                                        <span class="receipt-label">Référence</span>
                                        <span class="receipt-value" data-result-agent-ref>--</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="step-actions">
                        <button class="btn btn-secondary" data-tab="withdraw">
                            <i class="fa-solid fa-arrow-left"></i> Nouveau retrait
                        </button>
                        <button class="btn btn-primary" data-tab="send">
                            <i class="fa-solid fa-paper-plane"></i> Envoyer de l'argent
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Tab Content: History -->
    <div class="tab-content <?= $activeTab === 'history' ? 'active' : '' ?>" id="tab-history">
        <div class="history-header">
            <div class="filter-bar">
                <select id="typeFilter" class="filter-select">
                    <option value="">Tous les types</option>
                    <option value="send" <?= ($filters['type'] ?? '') === 'send' ? 'selected' : '' ?>>Envois</option>
                    <option value="deposit" <?= ($filters['type'] ?? '') === 'deposit' ? 'selected' : '' ?>>Dépôts</option>
                    <option value="withdraw" <?= ($filters['type'] ?? '') === 'withdraw' ? 'selected' : '' ?>>Retraits</option>
                    <option value="bill" <?= ($filters['type'] ?? '') === 'bill' ? 'selected' : '' ?>>Factures</option>
                </select>
                <select id="statusFilter" class="filter-select">
                    <option value="">Tous les statuts</option>
                    <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Réussis</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>En attente</option>
                    <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Échoués</option>
                </select>
            </div>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <p>Aucune transaction trouvée</p>
                <button class="btn btn-primary" data-tab="send">Effectuer un transfert</button>
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
                                <?php if ($transaction['type'] === 'send' || $transaction['type'] === 'send_mobile_money'): ?>
                                <button class="btn btn-soft btn-xs rate-btn" data-rate-txn="<?= htmlspecialchars($transaction['transaction_reference']) ?>" data-rate-recipient="<?= htmlspecialchars($currentUser['africo_number']) ?>" title="Évaluer">
                                    <i class="fa-solid fa-star"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="transaction-amount <?= $transaction['type'] === 'send' ? 'negative' : 'positive' ?>">
                            <?php $txnAmt = $transaction['amount'] / 100; ?>
                            <?php if ($transaction['type'] === 'send'): ?>
                                -<?= number_format($txnAmt, 2, ',', ' ') ?> <?= htmlspecialchars($transaction['currency']) ?>
                            <?php else: ?>
                                +<?= number_format($txnAmt, 2, ',', ' ') ?> <?= htmlspecialchars($transaction['currency']) ?>
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
                        <a href="?tab=history&page=<?= $i ?><?= isset($filters['type']) && $filters['type'] ? '&type=' . $filters['type'] : '' ?><?= isset($filters['status']) && $filters['status'] ? '&status=' . $filters['status'] : '' ?>"
                           class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Tab Content: Receipt -->
    <div class="tab-content <?= $activeTab === 'receipt' ? 'active' : '' ?>" id="tab-receipt">
        <?php if (isset($_SESSION['receipt'])):
            $transaction = $_SESSION['receipt'];
            unset($_SESSION['receipt']);
        ?>
            <div class="receipt-card">
                <div class="receipt-header">
                    <i class="fa-solid fa-check-circle success-icon"></i>
                    <h2>Transaction réussie !</h2>
                    <p>Votre transfert a été effectué avec succès</p>
                </div>

                <div class="receipt-body">
                    <div class="receipt-row">
                        <span class="receipt-label">Référence</span>
                        <span class="receipt-value"><?= htmlspecialchars($transaction['transaction_reference']) ?></span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Type</span>
                        <span class="receipt-value">
                            <?php if ($transaction['type'] === 'send'): ?>
                                Envoi d'argent
                            <?php elseif ($transaction['type'] === 'deposit'): ?>
                                Dépôt
                            <?php else: ?>
                                Paiement de facture
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Date</span>
                        <span class="receipt-value"><?= date('d/m/Y à H:i', strtotime($transaction['created_at'])) ?></span>
                    </div>

                    <?php if ($transaction['type'] === 'send'): ?>
                        <div class="receipt-row">
                            <span class="receipt-label">Destinataire</span>
                            <span class="receipt-value"><?= htmlspecialchars($transaction['recipient_name'] ?? 'Inconnu') ?></span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Compte destinataire</span>
                            <span class="receipt-value"><?= htmlspecialchars($transaction['recipient_account']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="receipt-divider"></div>

                    <div class="receipt-row">
                        <span class="receipt-label">Montant</span>
                        <span class="receipt-value amount"><?= number_format($transaction['amount'] / 100, 2, ',', ' ') ?> <?= htmlspecialchars($transaction['currency']) ?></span>
                    </div>

                    <?php if ($transaction['fees'] > 0): ?>
                        <div class="receipt-row">
                            <span class="receipt-label">Frais</span>
                            <span class="receipt-value"><?= number_format($transaction['fees'] / 100, 2, ',', ' ') ?> <?= htmlspecialchars($transaction['currency']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="receipt-row total">
                        <span class="receipt-label">Total débité</span>
                        <span class="receipt-value"><?= number_format($transaction['total_amount'] / 100, 2, ',', ' ') ?> <?= htmlspecialchars($transaction['currency']) ?></span>
                    </div>

                    <?php if (!empty($transaction['metadata']['description'])): ?>
                        <div class="receipt-row">
                            <span class="receipt-label">Description</span>
                            <span class="receipt-value"><?= htmlspecialchars($transaction['metadata']['description']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="receipt-footer">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fa-solid fa-print"></i> Imprimer
                    </button>
                    <button class="btn btn-primary" data-tab="send">
                        <i class="fa-solid fa-paper-plane"></i> Nouveau transfert
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <p>Aucun reçu à afficher</p>
                <button class="btn btn-primary" data-tab="send">Effectuer un transfert</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Rate modal -->
<dialog class="modal" data-rate-modal aria-labelledby="rate-modal-title">
  <div class="modal-card">
    <button class="modal-close" type="button" data-rate-close aria-label="Fermer">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="rate-modal-content">
      <i class="fa-solid fa-star rate-modal-icon"></i>
      <h2 id="rate-modal-title">Évaluer le destinataire</h2>
      <p>Notez votre expérience avec cette transaction</p>
      <div class="star-rating" data-star-rating>
        <button class="star-btn" data-star="1" type="button"><i class="fa-solid fa-star"></i></button>
        <button class="star-btn" data-star="2" type="button"><i class="fa-solid fa-star"></i></button>
        <button class="star-btn" data-star="3" type="button"><i class="fa-solid fa-star"></i></button>
        <button class="star-btn" data-star="4" type="button"><i class="fa-solid fa-star"></i></button>
        <button class="star-btn" data-star="5" type="button"><i class="fa-solid fa-star"></i></button>
      </div>
      <textarea class="rate-comment" data-rate-comment rows="2" placeholder="Commentaire (optionnel)"></textarea>
      <div class="mm-modal-actions" style="justify-content:center">
        <button class="btn btn-soft" type="button" data-rate-close>Annuler</button>
        <button class="btn btn-primary" type="button" data-rate-submit disabled>
          <i class="fa-solid fa-check"></i> Noter
        </button>
      </div>
    </div>
  </div>
</dialog>

<link rel="stylesheet" href="/assets/css/transactions.css">
<link rel="stylesheet" href="/assets/css/views/trust_score.css?v=1">
<script>window.balances = {cdf:<?= $balanceCdf / 100 ?>,usd:<?= $balanceUsd / 100 ?>,cdf_formatted:'<?= number_format($balanceCdf / 100, 2, ',', ' ') ?>',usd_formatted:'<?= number_format($balanceUsd / 100, 2, ',', ' ') ?>'};</script>
<script src="/assets/js/transactions.js" defer></script>

<?php require __DIR__ . '/../partials/app_shell_end.php'; ?>
