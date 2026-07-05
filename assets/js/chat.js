/**
 * chat.js - Messagerie instantanée simulée par intervalle JS (toutes les 3s),
 * conformément à l'énoncé (sockets Node.js en option, sinon polling JS).
 */

requireLogin();
const me = Session.getUser();

let activeUserId = null;
let lastMessageId = 0;
let pollInterval = null;

function convoTemplate(c) {
  const time = c.last_message_time ? timeAgo(c.last_message_time) : '';
  return `<div class="conversation-item" data-id="${c.id}" data-name="${escapeHtml(c.prenom)} ${escapeHtml(c.nom)}" data-avatar="${c.avatar}">
    <img src="../../${c.avatar}">
    <div class="meta">
      <div class="name">${escapeHtml(c.prenom)} ${escapeHtml(c.nom)}</div>
      <div class="last">${c.last_message ? escapeHtml(c.last_message) : '📷 Image'}</div>
    </div>
    ${c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : ''}
    <span class="time" style="font-size:11px;">${time}</span>
  </div>`;
}

async function loadConversations() {
  const res = await apiCall('get_conversations.php');
  const list = document.getElementById('conversationList');
  if (!res.success || !res.conversations.length) {
    list.innerHTML = '<p class="loading">Aucune conversation. Recherchez un ami pour commencer.</p>';
    return;
  }
  list.innerHTML = res.conversations.map(convoTemplate).join('');
  list.querySelectorAll('.conversation-item').forEach(item => {
    item.onclick = () => openConversation(item.dataset.id, item.dataset.name, item.dataset.avatar);
  });
}

async function searchFriends(query) {
  const res = await apiCall('get_users.php?search=' + encodeURIComponent(query));
  const list = document.getElementById('conversationList');
  if (!query) { loadConversations(); return; }
  list.innerHTML = res.users.map(u => `
    <div class="conversation-item" data-id="${u.id}" data-name="${escapeHtml(u.prenom)} ${escapeHtml(u.nom)}" data-avatar="${u.avatar}">
      <img src="../../${u.avatar}">
      <div class="meta"><div class="name">${escapeHtml(u.prenom)} ${escapeHtml(u.nom)}</div></div>
    </div>`).join('') || '<p class="loading">Aucun résultat.</p>';
  list.querySelectorAll('.conversation-item').forEach(item => {
    item.onclick = () => openConversation(item.dataset.id, item.dataset.name, item.dataset.avatar);
  });
}

document.getElementById('chatSearch').addEventListener('input', (e) => searchFriends(e.target.value.trim()));

function bubbleTemplate(m) {
  const mine = Number(m.sender_id) === Number(me.id);
  let content = '';
  if (m.image) content += `<img src="../../${m.image}">`;
  if (m.content) content += escapeHtml(m.content);
  return `<div class="msg-bubble ${mine ? 'mine' : 'theirs'}">${content}</div>`;
}

function openConversation(userId, name, avatar) {
  activeUserId = Number(userId);
  lastMessageId = 0;
  document.getElementById('chatHeader').classList.remove('hidden');
  document.getElementById('chatHeaderName').textContent = name;
  document.getElementById('chatHeaderAvatar').src = '../../' + avatar;
  document.getElementById('chatForm').classList.remove('hidden');
  document.getElementById('chatEmpty').classList.add('hidden');
  document.getElementById('chatMessages').innerHTML = '';

  document.querySelectorAll('.conversation-item').forEach(el => el.classList.toggle('active', el.dataset.id === String(userId)));

  fetchMessages(true);
  if (pollInterval) clearInterval(pollInterval);
  pollInterval = setInterval(() => fetchMessages(false), 3000); // polling toutes les 3 secondes
}

async function fetchMessages(scroll) {
  if (!activeUserId) return;
  const res = await apiCall(`get_messages.php?user_id=${activeUserId}&since_id=${lastMessageId}`);
  if (!res.success || !res.messages.length) return;
  const container = document.getElementById('chatMessages');
  res.messages.forEach(m => {
    container.insertAdjacentHTML('beforeend', bubbleTemplate(m));
    lastMessageId = Math.max(lastMessageId, m.id);
  });
  if (scroll) container.scrollTop = container.scrollHeight;
  else container.scrollTop = container.scrollHeight;
}

document.getElementById('chatForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!activeUserId) return;
  const input = document.getElementById('chatText');
  const fileInput = document.getElementById('chatImage');
  const content = input.value.trim();
  if (!content && !fileInput.files[0]) return;

  const fd = new FormData();
  fd.append('receiver_id', activeUserId);
  fd.append('content', content);
  if (fileInput.files[0]) fd.append('image', fileInput.files[0]);

  const res = await apiUpload('send_message.php', fd);
  if (res.success) {
    input.value = '';
    fileInput.value = '';
    document.getElementById('chatMessages').insertAdjacentHTML('beforeend', bubbleTemplate(res.message));
    lastMessageId = Math.max(lastMessageId, res.message.id);
    document.getElementById('chatMessages').scrollTop = 999999;
    loadConversations();
  }
});

loadConversations();
// Rafraîchit la liste des conversations toutes les 5s (nouveaux messages entrants)
setInterval(loadConversations, 5000);
