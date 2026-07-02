(function () {
  var dom = window.dom;
  var api = window.apiClient;

  var currentUserPage = 1;
  var currentTxPage = 1;
  var currentUserSearch = "";

  function q(sel) { return dom.query(sel); }
  function qa(sel) { return dom.queryAll(sel); }

  function showToast(msg, type) {
    var t = q("[data-toast]");
    if (!t) return;
    t.textContent = msg;
    t.className = "toast-notify " + (type || "info");
    clearTimeout(t._hide);
    t._hide = setTimeout(function () { t.className = "toast-notify hidden"; }, 3000);
  }

  // ── TABS ──
  var tabs = qa("[data-tab]");
  tabs.forEach(function (tab) {
    dom.on(tab, "click", function () {
      var name = tab.getAttribute("data-tab");
      tabs.forEach(function (t) {
        var isActive = t.getAttribute("data-tab") === name;
        t.classList.toggle("active", isActive);
        t.setAttribute("aria-selected", String(isActive));
      });
      qa("[data-panel]").forEach(function (p) {
        p.classList.toggle("active", p.getAttribute("data-panel") === name);
      });
      if (name === "overview") loadOverview();
      if (name === "users") loadUsers();
      if (name === "agents") loadAgents();
      if (name === "transactions") loadTransactions();
      if (name === "rates") loadRates();
      if (name === "settings") loadSettings();
      if (name === "badges") loadBadges();
      if (name === "logs") loadLogs();
    });
  });

  // ── OVERVIEW ──
  function loadOverview() {
    api.get("/api/app/admin/overview").then(function (resp) {
      var d = resp.data.data;
      q("[data-admin-users]").textContent = (d.users || 0).toLocaleString("fr-FR");
      q("[data-admin-agents]").textContent = (d.agents || 0).toLocaleString("fr-FR");
      q("[data-admin-transactions]").textContent = (d.transactions || 0).toLocaleString("fr-FR");
      q("[data-admin-volume]").textContent = ((d.volume || 0) / 100).toLocaleString("fr-FR", { minimumFractionDigits: 2 });
      q("[data-admin-silver]").textContent = (d.silverCount || 0).toLocaleString("fr-FR");
      q("[data-admin-gold]").textContent = (d.goldCount || 0).toLocaleString("fr-FR");
    }).catch(function () {
      showToast("Erreur chargement overview.", "error");
    });
    loadVolumeChart();
  }

  function loadVolumeChart() {
    api.get("/api/app/admin/volume-chart").then(function (resp) {
      var data = resp.data.data;
      var container = q("[data-volume-chart]");
      var days = Object.keys(data).sort();
      if (!days.length) {
        container.innerHTML = '<p class="text-muted">Aucune donnée sur les 30 derniers jours.</p>';
        return;
      }
      var html = '<div class="chart-bars">';
      var maxVal = 1;
      days.forEach(function (day) {
        var totals = { CDF: 0, USD: 0 };
        if (data[day]) {
          if (data[day].CDF) totals.CDF = data[day].CDF.income + data[day].CDF.outcome;
          if (data[day].USD) totals.USD = data[day].USD.income + data[day].USD.outcome;
        }
        var total = totals.CDF + totals.USD * 230000;
        if (total > maxVal) maxVal = total;
      });
      days.slice(-14).forEach(function (day) {
        var label = day.slice(5);
        var totals = { CDF: 0, USD: 0 };
        if (data[day]) {
          if (data[day].CDF) totals.CDF = data[day].CDF.income + data[day].CDF.outcome;
          if (data[day].USD) totals.USD = data[day].USD.income + data[day].USD.outcome;
        }
        var total = (totals.CDF + totals.USD * 230000) / 100;
        var pct = Math.max(2, (total / (maxVal / 100)) * 100);
        html += '<div class="chart-bar-item">';
        html += '  <div class="chart-bar" style="height:' + pct + '%" title="' + label + ': ' + total.toLocaleString("fr-FR") + ' CDF"></div>';
        html += '  <span class="chart-label">' + label + "</span>";
        html += "</div>";
      });
      html += "</div>";
      container.innerHTML = html;
    }).catch(function () {
      // silent
    });
  }

  // ── USERS ──
  function loadUsers(page) {
    if (page !== undefined) currentUserPage = page;
    var search = currentUserSearch;
    var url = "/api/app/admin/users?page=" + currentUserPage + "&per_page=20";
    if (search) url += "&search=" + encodeURIComponent(search);
    api.get(url).then(function (resp) {
      var d = resp.data.data;
      var tbody = q("[data-user-rows]");
      if (!d.users || !d.users.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="table-empty">Aucun utilisateur trouvé.</td></tr>';
        return;
      }
      tbody.innerHTML = d.users.map(function (u) {
        var badgeIcon = "";
        if (u.badge === "gold") badgeIcon = '<i class="fa-solid fa-star" style="color:gold" title="Doré"></i>';
        else if (u.badge === "silver") badgeIcon = '<i class="fa-solid fa-star" style="color:silver" title="Argenté"></i>';
        else badgeIcon = '<span style="color:var(--muted-color)">—</span>';
        var statusClass = u.is_active ? "status-active" : "status-inactive";
        var statusText = u.is_active ? "Actif" : "Inactif";
        var roleOptions = ["customer", "agent", "admin"].map(function (r) {
          return '<option value="' + r + '"' + (u.role === r ? " selected" : "") + ">" + r + "</option>";
        }).join("");
        return '<tr>' +
          '<td>' + u.id + '</td>' +
          '<td>' + escapeHtml(u.full_name) + '</td>' +
          '<td>' + escapeHtml(u.afric_number) + '</td>' +
          '<td>' + escapeHtml(u.email || "") + '</td>' +
          '<td><select class="role-select" data-user-id="' + u.id + '" data-role-select>' + roleOptions + '</select></td>' +
          '<td><span class="badge ' + statusClass + '">' + statusText + '</span></td>' +
          '<td>' + badgeIcon + ' ' + u.trust_score + '</td>' +
          '<td class="actions-cell">' +
          '  <button class="btn btn-xs ' + (u.is_active ? "btn-danger" : "btn-success") + '" data-toggle-user="' + u.id + '">' + (u.is_active ? "Désactiver" : "Activer") + '</button>' +
          "</td>" +
          "</tr>";
      }).join("");
      // Pagination
      var totalPages = Math.ceil(d.total / d.per_page);
      renderPagination(q("[data-user-pagination]"), currentUserPage, totalPages, function (p) { loadUsers(p); });
      // Wire role changes
      qa("[data-role-select]").forEach(function (sel) {
        dom.on(sel, "change", function () {
          var uid = parseInt(sel.getAttribute("data-user-id"), 10);
          var role = sel.value;
          api.post("/api/app/admin/users/" + uid + "/role", { role: role }).then(function () {
            showToast("Rôle mis à jour.", "success");
          }).catch(function () {
            showToast("Erreur mise à jour rôle.", "error");
          });
        });
      });
      // Wire toggle
      qa("[data-toggle-user]").forEach(function (btn) {
        dom.on(btn, "click", function () {
          var uid = parseInt(btn.getAttribute("data-toggle-user"), 10);
          api.post("/api/app/admin/users/" + uid + "/toggle-status").then(function () {
            showToast("Statut mis à jour.", "success");
            loadUsers(currentUserPage);
          }).catch(function () {
            showToast("Erreur mise à jour statut.", "error");
          });
        });
      });
    }).catch(function () {
      q("[data-user-rows]").innerHTML = '<tr><td colspan="8" class="table-empty">Erreur de chargement.</td></tr>';
    });
  }

  var searchInput = q("[data-user-search]");
  if (searchInput) {
    var searchTimer;
    dom.on(searchInput, "input", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        currentUserSearch = searchInput.value;
        currentUserPage = 1;
        loadUsers();
      }, 300);
    });
  }

  // ── AGENTS ──
  function loadAgents() {
    api.get("/api/app/admin/agents").then(function (resp) {
      var agents = resp.data.data;
      var tbody = q("[data-agent-rows]");
      if (!agents || !agents.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="table-empty">Aucun agent.</td></tr>';
        return;
      }
      tbody.innerHTML = agents.map(function (a) {
        var statusClass = a.is_active ? "status-active" : "status-inactive";
        var statusText = a.is_active ? "Actif" : "Inactif";
        return '<tr>' +
          '<td><strong>' + escapeHtml(a.agent_code) + '</strong></td>' +
          '<td>' + escapeHtml(a.full_name) + '</td>' +
          '<td>' + escapeHtml(a.afric_number) + '</td>' +
          '<td>' + escapeHtml(a.agent_phone || "") + '</td>' +
          '<td><input type="number" class="commission-input" data-agent-id="' + a.id + '" value="' + a.commission_rate + '" min="0" max="100000"></td>' +
          '<td><span class="badge ' + statusClass + '">' + statusText + '</span></td>' +
          '<td><button class="btn btn-xs btn-primary" data-save-commission="' + a.id + '">Sauver</button></td>' +
          "</tr>";
      }).join("");
      qa("[data-save-commission]").forEach(function (btn) {
        dom.on(btn, "click", function () {
          var aid = parseInt(btn.getAttribute("data-save-commission"), 10);
          var input = q("[data-agent-id='" + aid + "']");
          var rate = parseInt(input.value, 10);
          if (isNaN(rate)) { showToast("Taux invalide.", "error"); return; }
          api.post("/api/app/admin/agents/" + aid + "/commission", { commission_rate: rate }).then(function () {
            showToast("Commission mise à jour.", "success");
          }).catch(function () {
            showToast("Erreur mise à jour.", "error");
          });
        });
      });
    }).catch(function () {
      q("[data-agent-rows]").innerHTML = '<tr><td colspan="7" class="table-empty">Erreur de chargement.</td></tr>';
    });
  }

  // ── TRANSACTIONS ──
  function loadTransactions(page) {
    if (page !== undefined) currentTxPage = page;
    api.get("/api/app/admin/transactions?page=" + currentTxPage).then(function (resp) {
      var d = resp.data.data;
      var tbody = q("[data-transaction-rows]");
      if (!d.transactions || !d.transactions.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="table-empty">Aucune transaction.</td></tr>';
        return;
      }
      tbody.innerHTML = d.transactions.map(function (t) {
        var date = t.created_at ? new Date(t.created_at.replace(" ", "T")).toLocaleString("fr-CD") : "";
        var statusClass = "status-" + t.status;
        return '<tr>' +
          '<td class="mono">' + escapeHtml(t.transaction_reference || "").slice(0, 16) + '</td>' +
          '<td>' + escapeHtml(t.type) + '</td>' +
          '<td>' + escapeHtml(t.user_name || "") + '<br><small>' + escapeHtml(t.user_afric || "") + '</small></td>' +
          '<td class="mono">' + ((t.amount || 0) / 100).toLocaleString("fr-CD") + " " + t.currency + '</td>' +
          '<td class="mono">' + ((t.fees || 0) / 100).toLocaleString("fr-CD") + '</td>' +
          '<td class="mono">' + ((t.total_amount || 0) / 100).toLocaleString("fr-CD") + '</td>' +
          '<td><span class="badge ' + statusClass + '">' + t.status + '</span></td>' +
          '<td class="small">' + date + "</td>" +
          "</tr>";
      }).join("");
      var totalPages = Math.ceil(d.total / d.per_page);
      renderPagination(q("[data-transaction-pagination]"), currentTxPage, totalPages, function (p) { loadTransactions(p); });
    }).catch(function () {
      q("[data-transaction-rows]").innerHTML = '<tr><td colspan="8" class="table-empty">Erreur.</td></tr>';
    });
  }

  // ── EXCHANGE RATES ──
  function loadRates() {
    api.get("/api/app/admin/exchange-rates").then(function (resp) {
      var rates = resp.data.data;
      var tbody = q("[data-rate-rows]");
      if (!rates || !rates.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="table-empty">Aucun taux.</td></tr>';
        return;
      }
      tbody.innerHTML = rates.map(function (r) {
        return '<tr>' +
          '<td>' + escapeHtml(r.from_currency) + '</td>' +
          '<td>' + escapeHtml(r.to_currency) + '</td>' +
          '<td><input type="number" class="rate-input" data-rate-id="' + r.id + '" value="' + r.rate + '" min="1"></td>' +
          '<td>' + (r.effective_date || "") + '</td>' +
          '<td><button class="btn btn-xs btn-primary" data-save-rate="' + r.id + '">Sauver</button></td>' +
          "</tr>";
      }).join("");
      qa("[data-save-rate]").forEach(function (btn) {
        dom.on(btn, "click", function () {
          var rid = parseInt(btn.getAttribute("data-save-rate"), 10);
          var input = q("[data-rate-id='" + rid + "']");
          var rate = parseInt(input.value, 10);
          if (isNaN(rate) || rate <= 0) { showToast("Taux invalide.", "error"); return; }
          api.post("/api/app/admin/exchange-rates/" + rid, { rate: rate }).then(function () {
            showToast("Taux mis à jour.", "success");
          }).catch(function () {
            showToast("Erreur mise à jour.", "error");
          });
        });
      });
    }).catch(function () {
      q("[data-rate-rows]").innerHTML = '<tr><td colspan="5" class="table-empty">Erreur.</td></tr>';
    });
  }

  // ── SETTINGS ──
  function loadSettings() {
    api.get("/api/app/admin/settings").then(function (resp) {
      var settings = resp.data.data;
      var container = q("[data-settings-container]");
      if (!settings || !settings.length) {
        container.innerHTML = '<p class="text-muted">Aucun paramètre.</p>';
        return;
      }
      container.innerHTML = settings.map(function (s) {
        var isBool = s.setting_value === "0" || s.setting_value === "1";
        if (isBool) {
          var checked = s.setting_value === "1" ? "checked" : "";
          return '<div class="setting-card">' +
            '  <div class="setting-info">' +
            '    <strong>' + escapeHtml(s.setting_key) + '</strong>' +
            '    <small>' + escapeHtml(s.description || "") + "</small>" +
            "  </div>" +
            '  <label class="toggle-switch">' +
            '    <input type="checkbox" ' + checked + ' data-toggle-setting="' + s.setting_key + '">' +
            '    <span class="toggle-slider"></span>' +
            "  </label>" +
            "</div>";
        }
        var isPct = s.setting_key.indexOf("percent") !== -1 || s.setting_key.indexOf("bps") !== -1 || s.setting_key.indexOf("markup") !== -1;
        var step = isPct ? "0.01" : "1";
        var suffix = "";
        if (s.setting_key.indexOf("bps") !== -1) suffix = " bps";
        else if (s.setting_key.indexOf("percent") !== -1 || s.setting_key.indexOf("markup") !== -1) suffix = " %";
        else if (s.setting_key.indexOf("flat") !== -1 || s.setting_key.indexOf("min_") !== -1 || s.setting_key.indexOf("max_") !== -1) suffix = " centimes";
        return '<div class="setting-card">' +
          '  <div class="setting-info">' +
          '    <strong>' + escapeHtml(s.setting_key) + '</strong>' +
          '    <small>' + escapeHtml(s.description || "") + "</small>" +
          "  </div>" +
          '  <div class="setting-input">' +
          '    <input type="number" class="form-control" step="' + step + '" data-setting-key="' + s.setting_key + '" value="' + s.setting_value + '">' +
          '    <span class="setting-suffix">' + suffix + '</span>' +
          '    <button class="btn btn-xs btn-primary" data-save-setting="' + s.setting_key + '">Sauver</button>' +
          "  </div>" +
          "</div>";
      }).join("");
      // Wire toggle settings
      qa("[data-toggle-setting]").forEach(function (cb) {
        dom.on(cb, "change", function () {
          var key = cb.getAttribute("data-toggle-setting");
          var val = cb.checked ? "1" : "0";
          api.post("/api/app/admin/settings/" + key, { value: val }).then(function () {
            showToast(key + " mis à jour.", "success");
          }).catch(function () {
            showToast("Erreur.", "error");
          });
        });
      });
      // Wire numeric settings
      qa("[data-save-setting]").forEach(function (btn) {
        dom.on(btn, "click", function () {
          var key = btn.getAttribute("data-save-setting");
          var input = q("[data-setting-key='" + key + "']");
          var val = input.value;
          api.post("/api/app/admin/settings/" + key, { value: val }).then(function () {
            showToast(key + " sauvegardé.", "success");
          }).catch(function () {
            showToast("Erreur.", "error");
          });
        });
      });
    }).catch(function () {
      q("[data-settings-container]").innerHTML = '<p class="text-muted">Erreur de chargement.</p>';
    });
  }

  // ── BADGES ──
  function loadBadges() {
    api.get("/api/app/admin/overview").then(function (resp) {
      var data = resp.data.data;
      var tbody = q("[data-badge-rows]");
      if (!data.badgeData || !data.badgeData.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="table-empty">Aucun badge attribué.</td></tr>';
        return;
      }
      tbody.innerHTML = data.badgeData.map(function (u) {
        var icon = "";
        if (u.badge === "gold") icon = '<i class="fa-solid fa-star" style="color:gold" title="Doré"></i> Doré';
        else if (u.badge === "silver") icon = '<i class="fa-solid fa-star" style="color:silver" title="Argenté"></i> Argenté';
        else icon = '<span style="color:var(--muted-color)">—</span>';
        return '<tr>' +
          '<td>' + escapeHtml(u.full_name) + '</td>' +
          '<td>' + escapeHtml(u.afric_number) + '</td>' +
          '<td>' + icon + '</td>' +
          '<td><strong>' + u.trust_score + '</strong>/1000</td>' +
          '<td>' + (u.volume_6m_cdf || 0).toLocaleString("fr-CD") + '</td>' +
          '<td>' + (u.volume_6m_usd || 0).toLocaleString("fr-CD") + '</td>' +
          '<td>' + (u.tx_count_6m || 0) + '</td>' +
          '<td>' + (u.rating_avg || "0.00") + ' (' + (u.rating_count || 0) + ')</td>' +
          "</tr>";
      }).join("");
    }).catch(function () {
      q("[data-badge-rows]").innerHTML = '<tr><td colspan="8" class="table-empty">Erreur.</td></tr>';
    });
  }

  // ── AUDIT LOGS ──
  function loadLogs() {
    api.get("/api/app/admin/audit-logs").then(function (resp) {
      var logs = resp.data.data;
      var tbody = q("[data-log-rows]");
      if (!logs || !logs.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="table-empty">Aucune entrée.</td></tr>';
        return;
      }
      tbody.innerHTML = logs.map(function (l) {
        var date = l.created_at ? new Date(l.created_at.replace(" ", "T")).toLocaleString("fr-CD") : "";
        return '<tr>' +
          '<td class="small">' + date + '</td>' +
          '<td>' + escapeHtml(l.action || "") + '</td>' +
          '<td>' + escapeHtml(l.entity_type || "") + '</td>' +
          '<td>' + (l.entity_id || "") + '</td>' +
          '<td>' + escapeHtml(l.user_name || "") + '</td>' +
          '<td class="mono">' + escapeHtml(l.ip_address || "") + '</td>' +
          "</tr>";
      }).join("");
    }).catch(function () {
      q("[data-log-rows]").innerHTML = '<tr><td colspan="6" class="table-empty">Erreur.</td></tr>';
    });
  }

  // ── PAGINATION ──
  function renderPagination(container, current, total, callback) {
    if (!container) return;
    if (total <= 1) { container.innerHTML = ""; return; }
    var html = "";
    var start = Math.max(1, current - 2);
    var end = Math.min(total, current + 2);
    if (start > 1) { html += '<button class="page-btn" data-page="1">1</button>'; if (start > 2) html += '<span class="page-dots">...</span>'; }
    for (var i = start; i <= end; i++) {
      html += '<button class="page-btn' + (i === current ? " active" : "") + '" data-page="' + i + '">' + i + "</button>";
    }
    if (end < total) { if (end < total - 1) html += '<span class="page-dots">...</span>'; html += '<button class="page-btn" data-page="' + total + '">' + total + "</button>"; }
    container.innerHTML = html;
    qa(container, ".page-btn").forEach(function (btn) {
      dom.on(btn, "click", function () {
        var p = parseInt(btn.getAttribute("data-page"), 10);
        if (p !== current) callback(p);
      });
    });
  }

  function escapeHtml(str) {
    if (!str) return "";
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ── INIT ──
  loadOverview();
})();
