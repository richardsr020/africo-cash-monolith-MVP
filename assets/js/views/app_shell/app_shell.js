(function bootAppShell(windowObject, documentObject) {
  "use strict";

  const dom = windowObject.AfricoDom;
  const api = windowObject.AfricoApi;
  const auth = windowObject.AfricoAuth;

  function loginPath() {
    return `/connexion?return=${encodeURIComponent(windowObject.location.pathname)}`;
  }

  function requireToken() {
    if (!auth.getToken()) {
      windowObject.location.replace(loginPath());
      return false;
    }

    return true;
  }

  function apiMessage(error, fallback) {
    return error.response?.data?.error?.message || error.message || fallback;
  }

  async function syncSession(showSuccess = false) {
    try {
      const response = await api.get("/me");
      const user = response.data.data.user;
      const label = dom.query("[data-current-user]");

      if (label && user) {
        label.textContent = `${user.full_name} · ${user.africo_number}`;
      }

      if (showSuccess) {
        dom.showToast("Données actualisées.", "success");
      }
    } catch (error) {
      auth.clearToken();
      dom.showToast(apiMessage(error, "Session expirée."), "error");
      windowObject.setTimeout(() => {
        windowObject.location.replace(loginPath());
      }, 600);
    }
  }

  // ========== TOPBAR SCROLL GLASS ==========
  function initTopbarScroll() {
    const topbar = dom.query('.app-topbar');
    if (!topbar) return;

    function checkScroll() {
      topbar.classList.toggle('scrolled', windowObject.scrollY > 10);
    }

    checkScroll();
    windowObject.addEventListener('scroll', checkScroll, { passive: true });
  }

  // ========== GESTION DE LA SIDEBAR ==========
  function initSidebar() {
    const sidebar = dom.query('.app-sidebar');
    const toggleBtn = dom.query('[data-sidebar-toggle]');
    
    if (!sidebar || !toggleBtn) return;
    
    let overlay = null;
    const isMobile = windowObject.innerWidth <= 1040;
    
    // Sauvegarder l'état de la sidebar dans localStorage
    function saveSidebarState(isCollapsed) {
      try {
        localStorage.setItem('sidebar_collapsed', isCollapsed);
      } catch(e) {}
    }
    
    // Charger l'état sauvegardé
    function loadSidebarState() {
      try {
        const saved = localStorage.getItem('sidebar_collapsed');
        if (saved === 'true' && windowObject.innerWidth > 1040) {
          sidebar.classList.add('collapsed');
          const layout = dom.query('.app-layout');
          if (layout) {
            layout.style.gridTemplateColumns = 'var(--sidebar-collapsed-width) minmax(0, 1fr)';
          }
        }
      } catch(e) {}
    }
    
    // Créer l'overlay pour mobile
    function createOverlay() {
      if (overlay) return overlay;
      
      overlay = documentObject.createElement('div');
      overlay.className = 'sidebar-overlay';
      documentObject.body.appendChild(overlay);
      
      overlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
      });
      
      return overlay;
    }
    
    // Fonction pour toggle sidebar
    function toggleSidebar() {
      if (windowObject.innerWidth <= 1040) {
        // Mode mobile : overlay
        const currentOverlay = createOverlay();
        sidebar.classList.toggle('mobile-open');
        currentOverlay.classList.toggle('active');
      } else {
        // Mode desktop : collapse
        sidebar.classList.toggle('collapsed');
        const layout = dom.query('.app-layout');
        
        if (sidebar.classList.contains('collapsed')) {
          layout.style.gridTemplateColumns = 'var(--sidebar-collapsed-width) minmax(0, 1fr)';
          saveSidebarState(true);
        } else {
          layout.style.gridTemplateColumns = '';
          saveSidebarState(false);
        }
      }
    }
    
    // Gérer le redimensionnement
    let resizeTimer;
    function handleResize() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        const layout = dom.query('.app-layout');
        
        if (windowObject.innerWidth <= 1040) {
          // Mode mobile
          if (!overlay) {
            createOverlay();
          }
          sidebar.classList.remove('collapsed');
          if (layout) {
            layout.style.gridTemplateColumns = '';
          }
        } else {
          // Mode desktop
          if (overlay) {
            overlay.remove();
            overlay = null;
          }
          sidebar.classList.remove('mobile-open');
          
          // Restaurer l'état collapsed si sauvegardé
          try {
            const saved = localStorage.getItem('sidebar_collapsed');
            if (saved === 'true') {
              sidebar.classList.add('collapsed');
              if (layout) {
                layout.style.gridTemplateColumns = 'var(--sidebar-collapsed-width) minmax(0, 1fr)';
              }
            } else {
              sidebar.classList.remove('collapsed');
              if (layout) {
                layout.style.gridTemplateColumns = '';
              }
            }
          } catch(e) {}
        }
      }, 250);
    }
    
    // Initialiser
    if (windowObject.innerWidth <= 1040) {
      createOverlay();
    } else {
      loadSidebarState();
    }
    
    // Écouter les événements
    toggleBtn.addEventListener('click', toggleSidebar);
    windowObject.addEventListener('resize', handleResize);
  }
  
  // ========== GESTION DU MODE DASHBOARD (simple/advanced) ==========
  function initDashboardMode() {
    var sidebar = dom.query('[data-sidebar]');
    var toggleBtn = dom.query('[data-toggle-sidebar-mode]');
    var labelEl = dom.query('[data-sidebar-mode-label]');
    if (!sidebar) return;

    function applyMode(mode) {
      var isAdvanced = mode === 'advanced';
      sidebar.classList.toggle('sidebar-mode-simple', !isAdvanced);
      sidebar.classList.toggle('sidebar-mode-advanced', isAdvanced);
      if (labelEl) labelEl.textContent = isAdvanced ? 'Mode simple' : 'Mode avancé';
      if (toggleBtn) {
        var icon = toggleBtn.querySelector('i');
        if (icon) icon.className = 'fa-solid ' + (isAdvanced ? 'fa-layer-group' : 'fa-chart-simple');
      }
    }

    var saved = 'simple';
    try { saved = localStorage.getItem('dashboard_advanced') === 'true' ? 'advanced' : 'simple'; } catch (e) {}
    applyMode(saved);

    if (toggleBtn) {
      dom.on(toggleBtn, 'click', function () {
        var isAdvanced = !sidebar.classList.contains('sidebar-mode-simple');
        var newMode = isAdvanced ? 'simple' : 'advanced';
        applyMode(newMode);
        try { localStorage.setItem('dashboard_advanced', newMode === 'advanced'); } catch (e) {}
        windowObject.dispatchEvent(new CustomEvent('dashboard-mode-change', { detail: { mode: newMode } }));
      });
    }

    windowObject.addEventListener('dashboard-mode-change', function (e) {
      applyMode(e.detail.mode);
    });
  }

  // ========== GESTION DU THÈME ==========
  function initTheme() {
    const themeToggle = dom.query('[data-theme-toggle]');
    if (!themeToggle) return;
    
    // Charger le thème sauvegardé
    const savedTheme = localStorage.getItem('theme');
    const themeIcon = themeToggle.querySelector('i');
    const themeLabel = themeToggle.querySelector('[data-theme-label]');
    
    function setTheme(theme) {
      if (theme === 'dark') {
        documentObject.documentElement.setAttribute('data-theme', 'dark');
        if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
        if (themeLabel) themeLabel.textContent = 'Light';
        localStorage.setItem('theme', 'dark');
      } else {
        documentObject.documentElement.removeAttribute('data-theme');
        if (themeIcon) themeIcon.className = 'fa-solid fa-moon';
        if (themeLabel) themeLabel.textContent = 'Dark';
        localStorage.setItem('theme', 'light');
      }
    }
    
    if (savedTheme === 'dark') {
      setTheme('dark');
    }
    
    themeToggle.addEventListener('click', () => {
      const isDark = documentObject.documentElement.getAttribute('data-theme') === 'dark';
      setTheme(isDark ? 'light' : 'dark');
    });
  }
  
  // ========== GESTION DES BOUTONS RESPONSIVE ==========
  function initResponsiveButtons() {
    // Ajouter des classes pour les écrans mobiles
    function checkMobileView() {
      const isMobile = windowObject.innerWidth <= 768;
      const btns = dom.queryAll('.btn-soft, .btn-primary, .theme-toggle');
      
      btns.forEach(btn => {
        if (isMobile) {
          btn.classList.add('mobile-icon-only');
        } else {
          btn.classList.remove('mobile-icon-only');
        }
      });
    }
    
    checkMobileView();
    windowObject.addEventListener('resize', () => {
      clearTimeout(windowObject.resized);
      windowObject.resized = setTimeout(checkMobileView, 250);
    });
  }

  // ========== INITIALISATION PRINCIPALE ==========
  documentObject.addEventListener("DOMContentLoaded", () => {
    syncSession();
    
    // Initialiser tous les composants UI
    initTopbarScroll();
    initSidebar();
    initDashboardMode();
    initTheme();
    initResponsiveButtons();

    // Sync manuelle
    dom.on(dom.query("[data-sync-view]"), "click", () => {
      syncSession(true);
    });

    // Logout
    dom.on(dom.query("[data-logout]"), "click", async () => {
      try {
        await api.post("/auth/logout");
        dom.showToast("Session fermée.", "success");
      } catch (error) {
        dom.showToast(apiMessage(error, "Session locale fermée."), "info");
      } finally {
        auth.clearToken();
        windowObject.setTimeout(() => {
          windowObject.location.assign("/connexion");
        }, 300);
      }
    });

    // Actions API
    dom.queryAll("[data-api-action]").forEach((button) => {
      dom.on(button, "click", () => {
        dom.showToast("Action ouverte dans votre espace Africo Cash.", "success");
      });
    });
    
    // Fermer la sidebar mobile quand on clique sur un lien
    const sidebarLinks = dom.queryAll('.app-nav a');
    sidebarLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (windowObject.innerWidth <= 1040) {
          const sidebar = dom.query('.app-sidebar');
          const overlay = dom.query('.sidebar-overlay');
          if (sidebar) sidebar.classList.remove('mobile-open');
          if (overlay) overlay.classList.remove('active');
        }
      });
    });
  });
  
  // Support pour les variables CSS personnalisées
  if (!windowObject.CSS?.supports('backdrop-filter', 'blur(10px)')) {
    // Fallback pour les navigateurs qui ne supportent pas backdrop-filter
    const style = documentObject.createElement('style');
    style.textContent = `
      .app-topbar, .app-sidebar {
        backdrop-filter: none !important;
        background: var(--color-bg) !important;
      }
    `;
    documentObject.head.appendChild(style);
  }
  
})(window, document);