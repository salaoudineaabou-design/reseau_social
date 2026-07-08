/**
 * assets/js/app.js
 * Petit routeur SPA : charge dynamiquement les fragments HTML des
 * "vues" dans #app via Fetch, sans jamais recharger la page complète.
 * Gère aussi les gardes d'authentification (client / back-office).
 */

const API_URL = 'api/';

/* ---------------------------------------------------------------------
   Table des routes
   - fragment : chemin du fichier HTML à injecter dans #app (null = géré
     entièrement en JS, comme les vues du back-office qui construisent
     leur propre layout).
   - init     : fonction appelée une fois le fragment injecté.
   - auth     : 'public' (accessible à tous), 'client' (utilisateur connecté
     requis) ou 'admin' (admin/modérateur connecté requis).
--------------------------------------------------------------------- */
const ROUTES = {
  'login':            { fragment: 'vues/clients/login.html',       init: vueLogin,           auth: 'public' },
  'register':         { fragment: 'vues/clients/register.html',    init: vueRegister,        auth: 'public' },
  'forgot':           { fragment: 'vues/clients/forgot.html',      init: vueForgot,          auth: 'public' },
  'reset':            { fragment: 'vues/clients/reset-password.html', init: vueReset,        auth: 'public' },
  'accueil':          { fragment: 'vues/clients/accueil.html',     init: initialiserAccueil, auth: 'client' },
  'profil':           { fragment: 'vues/clients/profil.html',      init: vueProfil,          auth: 'client' },
  'profil-moi':       { fragment: 'vues/clients/profil.html',      init: vueProfilMoi,       auth: 'client' },
  'amis':             { fragment: 'vues/clients/amis.html',        init: initialiserAmis,    auth: 'client' },
  'chat':             { fragment: 'vues/clients/chat.html',        init: vueChat,            auth: 'client' },
  'admin-login':      { fragment: 'vues/back-office/login-admin.html', init: vueAdminLogin,  auth: 'public-admin' },
  'admin-dashboard':  { fragment: null,                            init: initialiserAdminDashboard, auth: 'admin' },
  'admin-users':      { fragment: null,                            init: initialiserAdminUsers,     auth: 'admin' },
  'admin-articles':   { fragment: null,                            init: initialiserAdminArticles,  auth: 'admin' },
};

const _cacheFragments = {};

/**
 * Navigue vers une vue : gère les gardes d'auth, charge le fragment
 * (avec mise en cache) et exécute son initialisation.
 */
async function naviguer(nomVue, params) {
  const route = ROUTES[nomVue];
  if (!route) { naviguer('accueil'); return; }

  // Gardes d'authentification
  if (route.auth === 'client' && !utilisateurConnecte()) {
    naviguer('login');
    return;
  }
  if (route.auth === 'admin' && !adminConnecte()) {
    naviguer('admin-login');
    return;
  }

  arreterPolling();

  const app = document.getElementById('app');
  app.classList.toggle('large', nomVue.startsWith('admin-'));

  if (route.fragment) {
    try {
      if (!_cacheFragments[route.fragment]) {
        const reponse = await fetch(route.fragment);
        _cacheFragments[route.fragment] = await reponse.text();
      }
      app.innerHTML = _cacheFragments[route.fragment];
    } catch (erreur) {
      app.innerHTML = '<p class="erreur">Impossible de charger cette page.</p>';
      return;
    }
  }

  route.init(params || {});
  history.replaceState(null, '', window.location.pathname);
}

/* ---------------------------------------------------------------------
   Initialisateurs spécifiques à certaines routes (glue code léger ;
   la logique métier vit dans auth.js / profil.js / chat.js / admin.js)
--------------------------------------------------------------------- */

function vueLogin() {
  initialiserLogin();
  document.getElementById('btn-login').addEventListener('click', gererConnexion);
  document.getElementById('login-password').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') gererConnexion();
  });
  document.querySelectorAll('[data-vue]').forEach(attacherLienNavigation);
}

function vueRegister() {
  document.getElementById('btn-register').addEventListener('click', gererInscription);
  document.querySelectorAll('[data-vue]').forEach(attacherLienNavigation);
}

function vueForgot() {
  document.getElementById('btn-forgot').addEventListener('click', gererMotDePasseOublie);
  document.querySelectorAll('[data-vue]').forEach(attacherLienNavigation);
}

function vueReset() {
  const params = new URLSearchParams(window.location.search);
  const token = params.get('token');
  if (token) sessionStorage.setItem('reset_token', token);

  if (!sessionStorage.getItem('reset_token')) {
    document.getElementById('reset-form-zone').style.display = 'none';
    afficherMessage('reset-error', 'Lien manquant ou expiré. Redemande une réinitialisation.', 'error');
  }

  document.getElementById('btn-reset').addEventListener('click', gererReinitialisation);
  document.querySelectorAll('[data-vue]').forEach(attacherLienNavigation);
}

function vueProfil(params) {
  const userId = params.userId || new URLSearchParams(window.location.search).get('user_id');
  chargerProfil(userId || utilisateurConnecte().id);
}

function vueProfilMoi() {
  chargerProfil(utilisateurConnecte().id);
}

function vueChat(params) {
  initialiserChat(params);
}

function vueAdminLogin() {
  document.getElementById('btn-admin-login').addEventListener('click', gererConnexionAdmin);
  document.getElementById('admin-password').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') gererConnexionAdmin();
  });
}

/** Attache la navigation SPA à un lien [data-vue] (évite le rechargement de page). */
function attacherLienNavigation(lien) {
  lien.addEventListener('click', function (e) {
    e.preventDefault();
    naviguer(lien.dataset.vue, { userId: lien.dataset.userId });
  });
}

/* ---------------------------------------------------------------------
   Démarrage de l'application
--------------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', function () {
  // Délégation globale pour tous les liens [data-vue] de la navbar
  // et de tout contenu injecté dynamiquement.
  document.body.addEventListener('click', function (e) {
    const lien = e.target.closest('[data-vue]');
    const btnLogout = e.target.closest('#btn-logout');

    if (btnLogout) {
      e.preventDefault();
      gererDeconnexion();
      return;
    }
    if (lien && lien.closest('#navbar')) {
      e.preventDefault();
      naviguer(lien.dataset.vue, { userId: lien.dataset.userId });
    }
  });

  const user = utilisateurConnecte();
  if (user) {
    mettreAJourNavConnecte(user);
  } else {
    mettreAJourNavDeconnecte();
  }

  const params = new URLSearchParams(window.location.search);
  const paramAction = params.get('action');

  if (paramAction === 'reset') {
    naviguer('reset');
  } else if (user) {
    naviguer('accueil');
  } else {
    naviguer('login');
  }
});
