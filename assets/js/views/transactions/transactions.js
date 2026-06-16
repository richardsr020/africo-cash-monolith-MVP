/**
 * Transactions Module JavaScript
 * Gère les onglets, calculs des frais et filtres
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 1. GESTION DES ONGLETS
    // ============================================
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    function switchTab(tabId) {
        // Mettre à jour l'URL sans recharger la page
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabId);
        window.history.pushState({}, '', url);
        
        // Activer le bon contenu
        tabContents.forEach(content => {
            content.classList.remove('active');
            if (content.id === `tab-${tabId}`) {
                content.classList.add('active');
            }
        });
        
        // Activer le bon bouton
        tabBtns.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabId) {
                btn.classList.add('active');
            }
        });
        
        // Scroll en haut de la page
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Écouter les clics sur les boutons d'onglets
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab;
            if (tabId) switchTab(tabId);
        });
    });
    
    // Gestion des boutons qui changent d'onglet (data-tab attribute)
    const tabTriggers = document.querySelectorAll('[data-tab]');
    tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            const tabId = trigger.dataset.tab;
            if (tabId) switchTab(tabId);
        });
    });
    
    // ============================================
    // 2. VALIDATION DU FORMULAIRE D'ENVOI
    // ============================================
    const sendForm = document.getElementById('sendForm');
    
    if (sendForm) {
        sendForm.addEventListener('submit', function(e) {
            const recipient = document.getElementById('recipient');
            const amount = document.getElementById('sendAmount');
            
            if (recipient && !recipient.value.match(/^\d{8}$/)) {
                alert('Le numéro Africo doit contenir 8 chiffres');
                recipient.focus();
                e.preventDefault();
                return;
            }
            
            if (amount) {
                const amountValue = parseInt(amount.value);
                if (isNaN(amountValue) || amountValue < 100) {
                    alert('Le montant minimum est de 100 FC');
                    amount.focus();
                    e.preventDefault();
                    return;
                }
                if (amountValue > 1000000) {
                    alert('Le montant maximum est de 1.000.000 FC');
                    amount.focus();
                    e.preventDefault();
                    return;
                }
            }
        });
    }
    
    // ============================================
    // 3. FILTRES POUR L'HISTORIQUE
    // ============================================
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (typeFilter || statusFilter) {
        /**
         * Met à jour les filtres et recharge la page
         */
        function updateFilters() {
            const params = new URLSearchParams(window.location.search);
            params.set('tab', 'history');
            
            if (typeFilter && typeFilter.value) {
                params.set('type', typeFilter.value);
            } else {
                params.delete('type');
            }
            
            if (statusFilter && statusFilter.value) {
                params.set('status', statusFilter.value);
            } else {
                params.delete('status');
            }
            
            params.set('page', '1');
            window.location.search = params.toString();
        }
        
        // Écouter les changements de filtres
        if (typeFilter) {
            typeFilter.addEventListener('change', updateFilters);
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', updateFilters);
        }
    }
    
    // ============================================
    // 4. FILTRES POUR L'HISTORIQUE (suite)
    // ============================================
    
    // ============================================
    // 5. FERMETURE AUTOMATIQUE DES ALERTES
    // ============================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.display = 'none';
                }
            }, 300);
        }, 5000);
    });
    
    // ============================================
    // 6. GESTION DU BOUTON RETOUR (HISTORY API)
    // ============================================
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'send';
        switchTab(tab);
    });
    
    // ============================================
    // 7. MISE À JOUR DU TITRE DE LA PAGE
    // ============================================
    function updatePageTitle() {
        const activeTab = document.querySelector('.tab-btn.active');
        if (activeTab) {
            const tabText = activeTab.querySelector('span')?.textContent || 'Transactions';
            document.title = `${tabText} - Africo Cash`;
        }
    }
    
    // Observer les changements d'onglets
    const observer = new MutationObserver(updatePageTitle);
    observer.observe(document.querySelector('.transactions-tabs'), { 
        attributes: true, 
        subtree: true,
        attributeFilter: ['class'] 
    });
    
    // Initialiser le titre
    updatePageTitle();
});


// Ajoutez ceci à votre fichier transactions.js

// ============================================
// 8. GESTION AMÉLIORÉE DU COPY POUR LA SECTION DÉPÔT
// ============================================

function initDepositSection() {
    const copyButtons = document.querySelectorAll('#tab-deposit .btn-sm');
    
    copyButtons.forEach(button => {
        // Sauvegarder le contenu original
        const originalContent = button.innerHTML;
        
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Trouver le texte à copier (le span précédent ou le parent)
            const detailValue = this.closest('.detail-value');
            const textToCopy = detailValue.querySelector('span').textContent.trim();
            
            try {
                // Copier dans le presse-papier
                await navigator.clipboard.writeText(textToCopy);
                
                // Changer le bouton pour indiquer le succès
                this.innerHTML = 'Copié ✓';
                this.style.background = '#10b981';
                this.style.borderColor = '#10b981';
                this.style.color = 'white';
                
                // Afficher une notification flottante
                showCopyNotification(`"${textToCopy}" copié !`);
                
                // Restaurer le bouton après 2 secondes
                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.style.background = '';
                    this.style.borderColor = '';
                    this.style.color = '';
                }, 2000);
                
            } catch (err) {
                console.error('Erreur de copie:', err);
                
                // Fallback pour les navigateurs anciens
                const textArea = document.createElement('textarea');
                textArea.value = textToCopy;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                this.innerHTML = 'Copié ✓';
                setTimeout(() => {
                    this.innerHTML = originalContent;
                }, 2000);
            }
        });
    });
}

// Afficher une notification temporaire
function showCopyNotification(message) {
    // Supprimer les notifications existantes
    const existingNotification = document.querySelector('.copy-feedback');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = 'copy-feedback';
    notification.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${message}`;
    document.body.appendChild(notification);
    
    // Supprimer après 2 secondes
    setTimeout(() => {
        notification.remove();
    }, 2000);
}

// Initialiser la section dépôt quand l'onglet est activé
function observeDepositTab() {
    const depositTab = document.getElementById('tab-deposit');
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class' && depositTab.classList.contains('active')) {
                initDepositSection();
            }
        });
    });
    
    if (depositTab) {
        observer.observe(depositTab, { attributes: true });
    }
}

// Exécuter au chargement
if (document.getElementById('tab-deposit')) {
    initDepositSection();
    observeDepositTab();
}

// ============================================
// 8. GESTION DU RETRAIT MULTI-ÉTAPES
// ============================================
(function() {
    const module = document.querySelector('[data-withdraw-module]');
    if (!module) return;

    let currentStep = '1';
    let selectedMethod = null;
    let withdrawData = {};

    function showStep(stepId) {
        module.querySelectorAll('.withdraw-step').forEach(s => s.style.display = 'none');
        const step = module.querySelector(`[data-step="${stepId}"]`);
        if (step) step.style.display = 'block';
        currentStep = stepId;
        window.scrollTo({ top: module.offsetTop - 20, behavior: 'smooth' });
    }

    function updateBalanceDisplay(prefix, currency) {
        const container = document.querySelector(`[data-balance-container="${prefix}"]`);
        if (!container) return;
        const cdfEl = container.querySelector('[data-' + prefix + '-balance]');
        const usdEl = container.querySelector('[data-' + prefix + '-balance-usd]');
        if (currency === 'USD' && usdEl) {
            usdEl.style.display = 'inline';
            if (cdfEl) cdfEl.style.display = 'none';
        } else {
            if (cdfEl) cdfEl.style.display = 'inline';
            if (usdEl) usdEl.style.display = 'none';
        }
    }

    // Currency switch → update balance
    ['sendCurrency', 'atmCurrency', 'agentCurrency'].forEach(id => {
        const sel = document.getElementById(id);
        if (sel) {
            sel.addEventListener('change', function () {
                if (id === 'sendCurrency') {
                    const display = document.querySelector('#tab-send .balance-amount');
                    if (display && window.balances) {
                        display.textContent = this.value === 'USD'
                            ? window.balances.usd_formatted + ' USD'
                            : window.balances.cdf_formatted + ' FC';
                    }
                } else {
                    const prefix = id === 'atmCurrency' ? 'atm' : 'agent';
                    updateBalanceDisplay(prefix, this.value);
                }
            });
        }
    });

    // Step 1: Method selection
    const stepNextBtn = module.querySelector('[data-step-next]');
    module.querySelectorAll('[data-method]').forEach(btn => {
        btn.addEventListener('click', () => {
            module.querySelectorAll('[data-method]').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedMethod = btn.dataset.method;
            withdrawData.method = selectedMethod;
            if (stepNextBtn) stepNextBtn.disabled = false;
        });
    });

    // Step 1 → Step 2 (Suivant)
    const nextBtns = module.querySelectorAll('[data-step-next]');
    nextBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep === '1') {
                if (!selectedMethod) {
                    alert('Veuillez choisir un moyen de retrait.');
                    return;
                }
                showStep(selectedMethod === 'atm' ? '2a' : '2b');
            } else if (currentStep === '2a' || currentStep === '2b') {
                const prefix = currentStep === '2a' ? 'atm' : 'agent';
                const currency = document.getElementById(prefix + 'Currency');
                const amount = document.getElementById(prefix + 'Amount');

                if (!amount || !amount.value || parseInt(amount.value) <= 0) {
                    alert('Veuillez entrer un montant valide.');
                    amount?.focus();
                    return;
                }

                withdrawData.currency = currency ? currency.value : 'CDF';
                withdrawData.amount = parseInt(amount.value);
                if (prefix === 'agent') {
                    const agentCode = document.getElementById('agentCode');
                    if (!agentCode || !agentCode.value.trim()) {
                        alert('Veuillez entrer le numéro ou code de l\'agent.');
                        agentCode?.focus();
                        return;
                    }
                    withdrawData.agent_code = agentCode.value.trim();
                }

                // Fill summary
                const methodLabel = module.querySelector('[data-summary-method]');
                if (methodLabel) {
                    methodLabel.textContent = selectedMethod === 'atm' ? 'Distributeur ATM' : 'Agent Africo Cash';
                }
                const curLabel = module.querySelector('[data-summary-currency]');
                if (curLabel) curLabel.textContent = withdrawData.currency;
                const amtLabel = module.querySelector('[data-summary-amount]');
                if (amtLabel) amtLabel.textContent = withdrawData.amount.toLocaleString() + ' FC';

                if (selectedMethod === 'agent') {
                    const agentRow = module.querySelector('[data-summary-agent-row]');
                    const agentLabel = module.querySelector('[data-summary-agent]');
                    if (agentRow) agentRow.style.display = 'flex';
                    if (agentLabel) agentLabel.textContent = withdrawData.agent_code;
                }

                showStep('3');
            }
        });
    });

    // Step back buttons
    module.querySelectorAll('[data-step-back]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep === '2a' || currentStep === '2b') {
                showStep('1');
            } else if (currentStep === '3') {
                showStep(selectedMethod === 'atm' ? '2a' : '2b');
            }
        });
    });

    // Step 3: Submit
    const submitBtn = module.querySelector('[data-submit-withdraw]');
    if (submitBtn) {
        submitBtn.addEventListener('click', async function () {
            const pin = document.getElementById('withdrawPin');
            if (!pin || !pin.value || pin.value.length !== 4) {
                alert('Veuillez entrer votre code PIN à 4 chiffres.');
                pin?.focus();
                return;
            }
            withdrawData.pin = pin.value;

            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';

            const titleEl = module.querySelector('[data-result-title]');

            if (withdrawData.method === 'atm') {
                try {
                    const response = await fetch('/api/app/atm/withdraw', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            amount: withdrawData.amount,
                            currency: withdrawData.currency,
                            pin: withdrawData.pin,
                        }),
                    });

                    const result = await response.json();

                    pin.value = '';
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-check-circle"></i> Valider le retrait';

                    if (!result.success) {
                        alert(result.error?.message || 'Erreur lors du retrait ATM.');
                        return;
                    }

                    const data = result.data;

                    module.querySelector('[data-result-atm]').style.display = 'block';
                    module.querySelector('[data-result-agent]').style.display = 'none';
                    if (titleEl) titleEl.textContent = 'Retrait ATM autorisé';

                    const codeEl = module.querySelector('[data-result-code]');
                    const pinResultEl = module.querySelector('[data-result-pin]');
                    const expiryEl = module.querySelector('[data-result-expiry]');
                    if (codeEl) codeEl.textContent = data.atm_code;
                    if (pinResultEl) pinResultEl.textContent = data.atm_pin;
                    if (expiryEl) expiryEl.textContent = '10 minutes';

                    showStep('4');
                } catch (err) {
                    pin.value = '';
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-check-circle"></i> Valider le retrait';
                    alert('Erreur de connexion. Veuillez réessayer.');
                }
            } else {
                try {
                    const response = await fetch('/api/app/agent/withdraw', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            agent_code: withdrawData.agent_code,
                            amount: withdrawData.amount,
                            currency: withdrawData.currency,
                            pin: withdrawData.pin,
                        }),
                    });

                    const result = await response.json();

                    pin.value = '';
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-check-circle"></i> Valider le retrait';

                    if (!result.success) {
                        alert(result.error?.message || 'Erreur lors du retrait agent.');
                        return;
                    }

                    const data = result.data;

                    module.querySelector('[data-result-atm]').style.display = 'none';
                    module.querySelector('[data-result-agent]').style.display = 'block';
                    if (titleEl) titleEl.textContent = 'Retrait Agent effectué';

                    const now = new Date();
                    const dateStr = now.toLocaleDateString('fr-FR') + ' à ' + now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

                    const nameEl = module.querySelector('[data-result-agent-name]');
                    const amtEl = module.querySelector('[data-result-agent-amount]');
                    const feesEl = module.querySelector('[data-result-agent-fees]');
                    const totalEl = module.querySelector('[data-result-agent-total]');
                    const dateEl = module.querySelector('[data-result-agent-date]');
                    const refEl = module.querySelector('[data-result-agent-ref]');
                    if (nameEl) nameEl.textContent = data.agent_name || withdrawData.agent_code;
                    if (amtEl) amtEl.textContent = data.amount.toLocaleString() + ' FC';
                    if (feesEl) feesEl.textContent = data.fees.toLocaleString() + ' FC';
                    if (totalEl) totalEl.textContent = data.total_amount.toLocaleString() + ' FC';
                    if (dateEl) dateEl.textContent = dateStr;
                    if (refEl) refEl.textContent = data.reference;

                    showStep('4');
                } catch (err) {
                    pin.value = '';
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-check-circle"></i> Valider le retrait';
                    alert('Erreur de connexion. Veuillez réessayer.');
                }
            }
        });
    }
})();