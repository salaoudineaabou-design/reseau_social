/**
 * feed.js - Page d'accueil : publication, likes/dislikes, commentaires
 * Aucun rechargement de page (fetch + injection DOM).
 */

requireLogin();
const me = Session.getUser();

function renderNavbar() {
  document.getElementById('navUserAvatar').src = '../../' + me.avatar;
  document.getElementById('navUserName').textContent = me.prenom;
}

function postTemplate(p) {
  const likeActive = p.my_reaction === 'like' ? 'active like' : '';
  const dislikeActive = p.my_reaction === 'dislike' ? 'active dislike' : '';
  return `
  <div class="card post" data-post-id="${p.id}">
    <div class="post-header">
      <img src="../../${p.avatar}" alt="avatar">
      <div>
        <div class="name">${escapeHtml(p.prenom)} ${escapeHtml(p.nom)}</div>
        <div class="time">${timeAgo(p.created_at)}</div>
      </div>
    </div>
    <div class="post-content">${escapeHtml(p.content)}</div>
    ${p.image ? `<img class="post-image" src="../../${p.image}">` : ''}
    <div class="post-stats">
      <span>👍 <span class="like-count">${p.likes}</span> &nbsp; 👎 <span class="dislike-count">${p.dislikes}</span></span>
      <span class="comments-toggle-count">${p.comments_count} commentaire(s)</span>
    </div>
    <div class="post-actions">
      <button class="btn-like ${likeActive}"><span>👍</span> J'aime</button>
      <button class="btn-dislike ${dislikeActive}"><span>👎</span> Je n'aime pas</button>
      <button class="btn-comment-toggle"><span>💬</span> Commenter</button>
    </div>
    <div class="comments-section">
      <div class="comments-list"></div>
      <div class="comment-input-row">
        <input type="text" placeholder="Écrire un commentaire..." class="comment-input">
        <button class="btn small btn-send-comment">Envoyer</button>
      </div>
    </div>
  </div>`;
}

async function loadFeed() {
  const feedEl = document.getElementById('feed');
  feedEl.innerHTML = '<div class="loading">Chargement du fil d\'actualité...</div>';
  const res = await apiCall('get_posts.php');
  if (!res.success) { feedEl.innerHTML = '<div class="error-msg">Erreur de chargement.</div>'; return; }
  feedEl.innerHTML = res.posts.length
    ? res.posts.map(postTemplate).join('')
    : '<div class="loading">Aucune publication pour le moment.</div>';
  attachPostEvents();
}

function attachPostEvents() {
  document.querySelectorAll('.post').forEach((postEl) => {
    const postId = postEl.dataset.postId;

    postEl.querySelector('.btn-like').onclick = () => react(postEl, postId, 'like');
    postEl.querySelector('.btn-dislike').onclick = () => react(postEl, postId, 'dislike');

    postEl.querySelector('.btn-comment-toggle').onclick = () => {
      const section = postEl.querySelector('.comments-section');
      section.classList.toggle('open');
      if (section.classList.contains('open')) loadComments(postEl, postId);
    };

    postEl.querySelector('.btn-send-comment').onclick = () => sendComment(postEl, postId);
    postEl.querySelector('.comment-input').addEventListener('keypress', (e) => {
      if (e.key === 'Enter') sendComment(postEl, postId);
    });
  });
}

async function react(postEl, postId, type) {
  const res = await apiCall('like_post.php', 'POST', { post_id: Number(postId), type });
  if (!res.success) return;
  postEl.querySelector('.like-count').textContent = res.likes;
  postEl.querySelector('.dislike-count').textContent = res.dislikes;
  postEl.querySelector('.btn-like').classList.toggle('active', res.my_reaction === 'like');
  postEl.querySelector('.btn-like').classList.toggle('like', res.my_reaction === 'like');
  postEl.querySelector('.btn-dislike').classList.toggle('active', res.my_reaction === 'dislike');
  postEl.querySelector('.btn-dislike').classList.toggle('dislike', res.my_reaction === 'dislike');
}

function commentTemplate(c) {
  return `<div class="comment">
    <img src="../../${c.avatar}">
    <div class="bubble"><b>${escapeHtml(c.prenom)} ${escapeHtml(c.nom)}</b>${escapeHtml(c.content)}</div>
  </div>`;
}

async function loadComments(postEl, postId) {
  const list = postEl.querySelector('.comments-list');
  list.innerHTML = '<div class="loading">Chargement...</div>';
  const res = await apiCall('get_comments.php?post_id=' + postId);
  list.innerHTML = res.success && res.comments.length
    ? res.comments.map(commentTemplate).join('')
    : '<div class="loading">Aucun commentaire.</div>';
}

async function sendComment(postEl, postId) {
  const input = postEl.querySelector('.comment-input');
  const content = input.value.trim();
  if (!content) return;
  const res = await apiCall('comment_post.php', 'POST', { post_id: Number(postId), content });
  if (res.success) {
    input.value = '';
    postEl.querySelector('.comments-list').insertAdjacentHTML('beforeend', commentTemplate({
      ...res.comment, avatar: me.avatar,
    }));
    const countEl = postEl.querySelector('.comments-toggle-count');
    countEl.textContent = (parseInt(countEl.textContent) + 1) + ' commentaire(s)';
  }
}

// ---------- Publication d'un nouvel article ----------
const postForm = document.getElementById('postForm');
if (postForm) {
  postForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const content = document.getElementById('postContent').value.trim();
    const imageInput = document.getElementById('postImage');
    if (!content) return;
    const fd = new FormData();
    fd.append('content', content);
    if (imageInput.files[0]) fd.append('image', imageInput.files[0]);
    const res = await apiUpload('create_post.php', fd);
    if (res.success) {
      postForm.reset();
      loadFeed();
    }
  });
}

renderNavbar();
loadFeed();
