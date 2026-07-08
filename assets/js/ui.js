/**
 * assets/js/ui.js
 * Fonctions utilitaires réutilisées par tous les autres scripts :
 * affichage de messages, formatage de dates, mise à jour de la navbar.
 */

/**
 * Affiche un message temporaire (erreur / succès / warning) dans un
 * élément donné, puis le masque automatiquement après 5 secondes.
 */
function afficherMessage(elementId, texte, type) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.textContent = texte; // textContent : jamais innerHTML pour du texte utilisateur
  el.className = 'msg-' + type;
  el.style.display = 'block';
  clearTimeout(el._timeoutMsg);
  el._timeoutMsg = setTimeout(function () {
    el.style.display = 'none';
    el.textContent = '';
  }, 5000);
}

/** Formate une date SQL 'YYYY-MM-DD HH:MM:SS' en texte relatif lisible. */
function formaterDate(dateStr) {
  const d = new Date(dateStr.replace(' ', 'T'));
  const maintenant = new Date();
  const diffMs = maintenant - d;
  const diffMin = Math.floor(diffMs / 60000);

  if (isNaN(diffMin)) return dateStr;
  if (diffMin < 1) return "À l'instant";
  if (diffMin < 60) return 'Il y a ' + diffMin + ' min';
  if (diffMin < 1440) return 'Il y a ' + Math.floor(diffMin / 60) + ' h';
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
}

/** Échappe une chaîne pour affichage sûr dans un attribut/texte simple. */
function texteSur(valeur) {
  const div = document.createElement('div');
  div.textContent = valeur == null ? '' : String(valeur);
  return div.innerHTML;
}

/** Récupère l'utilisateur connecté (client) depuis sessionStorage. */
function utilisateurConnecte() {
  try {
    return JSON.parse(sessionStorage.getItem('user') || 'null');
  } catch (e) {
    return null;
  }
}

/** Récupère l'admin/modérateur connecté depuis sessionStorage. */
function adminConnecte() {
  try {
    return JSON.parse(sessionStorage.getItem('admin_user') || 'null');
  } catch (e) {
    return null;
  }
}

/** Reconstruit la navbar selon l'état connecté (utilisateur normal). */
function mettreAJourNavConnecte(user) {
  const navLinks = document.getElementById('nav-links');
  if (!navLinks) return;

  let lienAdmin = '';
  if (user.role === 'admin' || user.role === 'moderateur') {
    lienAdmin = '<a href="#" data-vue="admin-dashboard">🛠️ Back-office</a>';
  }

  navLinks.innerHTML = `
    <a href="#" data-vue="accueil">🏠 Accueil</a>
    <a href="#" data-vue="amis">👥 Amis</a>
    <a href="#" data-vue="chat">💬 Messages</a>
    <a href="#" data-vue="profil-moi" class="nav-profil">
      <img src="${user.avatar}" class="nav-avatar" alt="">
      <span></span>
    </a>
    ${lienAdmin}
    <a href="#" id="btn-logout">Déconnexion</a>
  `;
  // Le prénom est injecté via textContent pour éviter tout risque XSS
  navLinks.querySelector('.nav-profil span').textContent = user.prenom;
}

/** Remet la navbar à l'état déconnecté. */
function mettreAJourNavDeconnecte() {
  const navLinks = document.getElementById('nav-links');
  if (!navLinks) return;
  navLinks.innerHTML = `
    <a href="#" data-vue="login">Connexion</a>
    <a href="#" data-vue="register">Inscription</a>
  `;
}

/** Affiche un message ponctuel dans le bandeau global (haut de page). */
function afficherBandeauGlobal(texte, type) {
  const bandeau = document.getElementById('bandeau-global');
  if (!bandeau) return;
  const el = document.createElement('div');
  el.className = 'msg-' + type;
  el.textContent = texte;
  bandeau.innerHTML = '';
  bandeau.appendChild(el);
  setTimeout(function () {
    bandeau.innerHTML = '';
  }, 6000);
}
