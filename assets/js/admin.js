/**
 * admin.js - Fonctions communes au back-office (garde d'accès, sidebar dynamique)
 */

function requireStaffLogin() {
  const user = Session.getUser();
  if (!Session.isLoggedIn() || !user || !['admin', 'moderateur'].includes(user.role)) {
    Session.clear();
    window.location.href = 'login.html';
  }
  return user;
}

async function adminLogout() {
  await apiCall('logout.php', 'POST');
  Session.clear();
  window.location.href = 'login.html';
}

function renderAdminSidebar(activePage) {
  const user = Session.getUser();
  const isAdmin = user.role === 'admin';
  const links = [
    { href: 'dashboard.html', label: '📊 Tableau de bord' },
    { href: 'articles.html', label: '📝 Articles' },
    { href: 'utilisateurs.html', label: '👥 Utilisateurs' },
  ];
  if (isAdmin) links.push({ href: 'administrateurs.html', label: '🛡️ Modérateurs & Admins' });

  document.getElementById('adminSidebar').innerHTML = `
    <div class="brand">Back-Office</div>
    ${links.map(l => `<a href="${l.href}" class="${l.href === activePage ? 'active' : ''}">${l.label}</a>`).join('')}
    <a href="#" onclick="adminLogout()">🚪 Déconnexion</a>
    <div style="padding:14px 20px;color:#999;font-size:12px;">Connecté : ${escapeHtml(user.prenom)} (${user.role})</div>
  `;
}
