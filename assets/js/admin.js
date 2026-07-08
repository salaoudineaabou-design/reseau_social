/**
 * assets/js/admin.js
 * Gère le back-office : connexion admin/modérateur séparée du client,
 * tableau de bord (statistiques), gestion des utilisateurs et
 * modération des articles.
 */

async function gererConnexionAdmin() {
  const email = document.getElementById('admin-email').value.trim();
  const password = document.getElementById('admin-password').value;
  const btn = document.getElementById('btn-admin-login');

  if (!email || !password) {
    afficherMessage('admin-login-error', 'Remplis tous les champs.', 'error');
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Connexion...';

  try {
    const reponse = await fetch(API_URL + 'admin/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email, mot_de_passe: password }),
    });
    const data = await reponse.json();

    if (reponse.ok && data.success) {
      sessionStorage.setItem('admin_user', JSON.stringify(data.admin));
      naviguer('admin-dashboard');
    } else {
      afficherMessage('admin-login-error', data.message || 'Erreur inconnue', 'error');
    }
  } catch (erreur) {
    afficherMessage('admin-login-error', 'Impossible de joindre le serveur.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Se connecter';
  }
}

function gererDeconnexionAdmin() {
  sessionStorage.removeItem('admin_user');
  naviguer('admin-login');
}

/** Construit le squelette commun (sidebar + zone de contenu) du back-office. */
function construireLayoutAdmin(vueActive) {
  const admin = adminConnecte();
  const app = document.getElementById('app');
  app.classList.add('large');

  app.innerHTML = `
    <div class="admin-layout">
      <aside class="admin-sidebar">
        <a href="#" data-vue="admin-dashboard" class="${vueActive === 'dashboard' ? 'actif' : ''}">📊 Tableau de bord</a>
        <a href="#" data-vue="admin-users" class="${vueActive === 'users' ? 'actif' : ''}">👤 Utilisateurs</a>
        <a href="#" data-vue="admin-articles" class="${vueActive === 'articles' ? 'actif' : ''}">📝 Articles</a>
        <a href="#" data-vue="accueil">🌐 Retour au site</a>
        <a href="#" id="btn-admin-logout">🚪 Déconnexion</a>
      </aside>
      <div class="admin-content" id="admin-content"></div>
    </div>
  `;

  document.querySelectorAll('.admin-sidebar a[data-vue]').forEach(function (lien) {
    lien.addEventListener('click', function (e) {
      e.preventDefault();
      naviguer(lien.dataset.vue);
    });
  });
  document.getElementById('btn-admin-logout').addEventListener('click', function (e) {
    e.preventDefault();
    gererDeconnexionAdmin();
  });
}

async function initialiserAdminDashboard() {
  construireLayoutAdmin('dashboard');
  const admin = adminConnecte();
  const contenu = document.getElementById('admin-content');
  contenu.innerHTML = '<p class="chargement">Chargement des statistiques...</p>';

  try {
    const reponse = await fetch(API_URL + 'admin/stats.php?user_id=' + admin.id);
    const data = await reponse.json();

    if (!data.success) {
      contenu.innerHTML = '<p class="erreur">' + texteSur(data.message) + '</p>';
      return;
    }

    const s = data.data;
    contenu.innerHTML = `
      <h2 style="margin-bottom:1.25rem;">Tableau de bord</h2>
      <div class="stats-grille">
        <div class="stat-carte"><div class="valeur">${s.total_users}</div><div class="label">Utilisateurs</div></div>
        <div class="stat-carte"><div class="valeur">${s.total_articles}</div><div class="label">Articles</div></div>
        <div class="stat-carte"><div class="valeur">${s.total_likes}</div><div class="label">Likes</div></div>
        <div class="stat-carte"><div class="valeur">${s.total_messages}</div><div class="label">Messages</div></div>
        <div class="stat-carte"><div class="valeur">${s.users_bannis}</div><div class="label">Comptes bannis</div></div>
      </div>
      <h3 style="margin-bottom:0.75rem;">Articles les plus commentés</h3>
      <table class="table-admin">
        <thead><tr><th>Aperçu</th><th>Commentaires</th></tr></thead>
        <tbody>
          ${s.articles_populaires.map(a => `<tr><td>${texteSur(a.apercu)}...</td><td>${a.nb_commentaires}</td></tr>`).join('')}
        </tbody>
      </table>
    `;
  } catch (erreur) {
    contenu.innerHTML = '<p class="erreur">Erreur lors du chargement des statistiques.</p>';
  }
}

async function initialiserAdminUsers() {
  construireLayoutAdmin('users');
  const admin = adminConnecte();
  const contenu = document.getElementById('admin-content');

  contenu.innerHTML = `
    <h2 style="margin-bottom:1rem;">Gestion des utilisateurs</h2>
    <div class="recherche-admin"><input type="text" id="recherche-users" placeholder="Rechercher un utilisateur..."></div>
    <div id="tableau-users"><p class="chargement">Chargement...</p></div>
  `;

  async function recharger(q) {
    const url = API_URL + 'admin/users.php?user_id=' + admin.id + (q ? '&q=' + encodeURIComponent(q) : '');
    const reponse = await fetch(url);
    const data = await reponse.json();
    afficherTableauUsers(data.data || [], admin);
  }

  document.getElementById('recherche-users').addEventListener('input', function (e) {
    recharger(e.target.value.trim());
  });

  document.getElementById('tableau-users').addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-action]');
    if (btn) gererActionUser(btn, admin, () => recharger(document.getElementById('recherche-users').value.trim()));
  });

  recharger('');
}

function afficherTableauUsers(liste, admin) {
  const conteneur = document.getElementById('tableau-users');
  if (liste.length === 0) {
    conteneur.innerHTML = '<p class="vide">Aucun utilisateur trouvé.</p>';
    return;
  }

  const lignes = liste.map(function (u) {
    const roleTag = `<span class="tag-role ${u.role}">${u.role}</span>`;
    const banniTag = u.is_banned == 1 ? '<span class="tag-banni">Banni</span>' : '';
    const estAdminConnecte = admin.role === 'admin';
    const estMoi = parseInt(u.id, 10) === parseInt(admin.id, 10);

    let actions = '';
    if (!estMoi) {
      actions += u.is_banned == 1
        ? `<button class="btn-debannir" data-action="debannir" data-id="${u.id}">Débannir</button>`
        : `<button class="btn-bannir" data-action="bannir" data-id="${u.id}">Bannir</button>`;

      if (estAdminConnecte && u.role === 'user') {
        actions += `<button class="btn-promouvoir" data-action="promouvoir_modo" data-id="${u.id}">Promouvoir modérateur</button>`;
      }
      if (estAdminConnecte && (u.role === 'user' || u.role === 'moderateur')) {
        actions += `<button class="btn-promouvoir" data-action="promouvoir_admin" data-id="${u.id}">Promouvoir admin</button>`;
      }
      if (estAdminConnecte && u.role === 'moderateur') {
        actions += `<button class="btn-promouvoir" data-action="revoquer_role" data-id="${u.id}">Révoquer</button>`;
      }
      if (estAdminConnecte && u.role === 'admin') {
        actions += `<button class="btn-promouvoir" data-action="revoquer_role" data-id="${u.id}">Rétrograder</button>`;
      }
      if (estAdminConnecte) {
        actions += `<button class="btn-supprimer" data-action="supprimer" data-id="${u.id}">Supprimer</button>`;
      }
    }

    return `<tr>
      <td>${texteSur(u.prenom)} ${texteSur(u.nom)}</td>
      <td>${texteSur(u.email)}</td>
      <td>${roleTag} ${banniTag}</td>
      <td class="actions-admin">${actions}</td>
    </tr>`;
  }).join('');

  conteneur.innerHTML = `
    <table class="table-admin">
      <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Actions</th></tr></thead>
      <tbody>${lignes}</tbody>
    </table>
  `;
}

async function gererActionUser(bouton, admin, callbackRecharger) {
  if (bouton.dataset.action === 'supprimer' && !confirm('Supprimer définitivement ce compte et tout son contenu ?')) {
    return;
  }

  try {
    const reponse = await fetch(API_URL + 'admin/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: admin.id, action: bouton.dataset.action, cible_id: bouton.dataset.id }),
    });
    const data = await reponse.json();
    afficherBandeauGlobal(data.message, data.success ? 'success' : 'error');
    if (data.success) callbackRecharger();
  } catch (erreur) {
    afficherBandeauGlobal('Erreur réseau.', 'error');
  }
}

async function initialiserAdminArticles() {
  construireLayoutAdmin('articles');
  const admin = adminConnecte();
  const contenu = document.getElementById('admin-content');
  contenu.innerHTML = `
    <h2 style="margin-bottom:1rem;">Modération des articles</h2>
    <div id="tableau-articles"><p class="chargement">Chargement...</p></div>
  `;

  async function recharger() {
    const reponse = await fetch(API_URL + 'admin/articles.php?user_id=' + admin.id);
    const data = await reponse.json();
    afficherTableauArticles(data.data || []);
  }

  document.getElementById('tableau-articles').addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-action="supprimer"]');
    if (!btn) return;
    if (!confirm('Supprimer cet article ?')) return;

    fetch(API_URL + 'admin/articles.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: admin.id, action: 'supprimer', article_id: btn.dataset.id }),
    })
      .then(r => r.json())
      .then(data => {
        afficherBandeauGlobal(data.message, data.success ? 'success' : 'error');
        if (data.success) recharger();
      });
  });

  recharger();
}

function afficherTableauArticles(liste) {
  const conteneur = document.getElementById('tableau-articles');
  if (liste.length === 0) {
    conteneur.innerHTML = '<p class="vide">Aucun article.</p>';
    return;
  }

  const lignes = liste.map(function (a) {
    return `<tr>
      <td>${texteSur(a.auteur_prenom)} ${texteSur(a.auteur_nom)}</td>
      <td>${texteSur(a.contenu.substring(0, 60))}${a.contenu.length > 60 ? '...' : ''}</td>
      <td>${a.nb_likes} 👍 / ${a.nb_commentaires} 💬</td>
      <td class="actions-admin"><button class="btn-supprimer" data-action="supprimer" data-id="${a.id}">Supprimer</button></td>
    </tr>`;
  }).join('');

  conteneur.innerHTML = `
    <table class="table-admin">
      <thead><tr><th>Auteur</th><th>Contenu</th><th>Stats</th><th>Actions</th></tr></thead>
      <tbody>${lignes}</tbody>
    </table>
  `;
}
