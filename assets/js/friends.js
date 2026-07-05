/**
 * friends.js - Gestion des amis : liste des utilisateurs inscrits,
 * envoi/réception/gestion des invitations, consultation de profils.
 */

requireLogin();
const me = Session.getUser();

function userCardTemplate(u) {
  let actionBtn = '';
  if (u.friendship_status === 'none') {
    actionBtn = `<button class="btn small btn-add" data-id="${u.id}">Ajouter</button>`;
  } else if (u.friendship_status === 'pending_sent') {
    actionBtn = `<button class="btn small secondary" disabled>Invitation envoyée</button>`;
  } else if (u.friendship_status === 'pending_received') {
    actionBtn = `<button class="btn small btn-accept" data-fid="${u.friendship_id}">Accepter</button>`;
  } else if (u.friendship_status === 'friends') {
    actionBtn = `<span class="badge user">Amis</span>`;
  }
  return `<div class="user-card">
    <img src="../../${u.avatar}">
    <div class="name"><a href="profil-public.html?id=${u.id}">${escapeHtml(u.prenom)} ${escapeHtml(u.nom)}</a></div>
    ${actionBtn}
  </div>`;
}

function requestRowTemplate(r) {
  return `<div class="friend-request-row">
    <img src="../../${r.avatar}">
    <div class="info"><b>${escapeHtml(r.prenom)} ${escapeHtml(r.nom)}</b> souhaite être votre ami(e)</div>
    <button class="btn small btn-accept-req" data-fid="${r.friendship_id}">Accepter</button>
    <button class="btn small secondary btn-refuse-req" data-fid="${r.friendship_id}">Refuser</button>
  </div>`;
}

async function loadRequests() {
  const res = await apiCall('get_friends.php');
  const container = document.getElementById('pendingRequests');
  if (!res.success || !res.pending_received.length) {
    container.innerHTML = '<p class="loading">Aucune invitation en attente.</p>';
  } else {
    container.innerHTML = res.pending_received.map(requestRowTemplate).join('');
    container.querySelectorAll('.btn-accept-req').forEach(btn => btn.onclick = () => respond(btn.dataset.fid, 'accept'));
    container.querySelectorAll('.btn-refuse-req').forEach(btn => btn.onclick = () => respond(btn.dataset.fid, 'refuse'));
  }
}

async function respond(friendshipId, action) {
  await apiCall('respond_friend_request.php', 'POST', { friendship_id: Number(friendshipId), action });
  loadRequests();
  loadUsers();
}

async function loadUsers(search = '') {
  const grid = document.getElementById('userGrid');
  grid.innerHTML = '<div class="loading">Chargement...</div>';
  const res = await apiCall('get_users.php' + (search ? '?search=' + encodeURIComponent(search) : ''));
  grid.innerHTML = res.success && res.users.length
    ? res.users.map(userCardTemplate).join('')
    : '<p class="loading">Aucun utilisateur trouvé.</p>';

  grid.querySelectorAll('.btn-add').forEach(btn => btn.onclick = async () => {
    const res = await apiCall('send_friend_request.php', 'POST', { friend_id: Number(btn.dataset.id) });
    if (res.success) loadUsers(document.getElementById('searchInput').value);
  });
  grid.querySelectorAll('.btn-accept').forEach(btn => btn.onclick = () => respond(btn.dataset.fid, 'accept'));
}

document.getElementById('searchInput').addEventListener('input', (e) => loadUsers(e.target.value));

loadRequests();
loadUsers();
