/**
 * api.js - Couche d'accès à l'API PHP (AJAX/fetch) + gestion de session
 * via sessionStorage, conformément aux exigences de l'énoncé
 * (aucun rechargement de page, session équivalente PHP gérée en JS).
 */

const API_BASE = '../../api/'; // relatif depuis vues/clients/ ou vues/back-office/

const Session = {
  setToken(token) { sessionStorage.setItem('session_token', token); },
  getToken() { return sessionStorage.getItem('session_token'); },
  setUser(user) { sessionStorage.setItem('current_user', JSON.stringify(user)); },
  getUser() {
    const raw = sessionStorage.getItem('current_user');
    return raw ? JSON.parse(raw) : null;
  },
  clear() {
    sessionStorage.removeItem('session_token');
    sessionStorage.removeItem('current_user');
  },
  isLoggedIn() { return !!this.getToken(); },
};

/**
 * Appel API générique en JSON.
 */
async function apiCall(endpoint, method = 'GET', body = null) {
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  const token = Session.getToken();
  if (token) options.headers['Authorization'] = 'Bearer ' + token;
  if (body) options.body = JSON.stringify(body);

  const res = await fetch(API_BASE + endpoint, options);
  const data = await res.json().catch(() => ({ success: false, message: 'Réponse invalide du serveur.' }));
  if (res.status === 401) {
    Session.clear();
    window.location.href = 'login.html';
  }
  return data;
}

/**
 * Appel API avec upload de fichier (FormData) - pour images.
 */
async function apiUpload(endpoint, formData) {
  const options = { method: 'POST', body: formData, headers: {} };
  const token = Session.getToken();
  if (token) options.headers['Authorization'] = 'Bearer ' + token;

  const res = await fetch(API_BASE + endpoint, options);
  const data = await res.json().catch(() => ({ success: false, message: 'Réponse invalide du serveur.' }));
  if (res.status === 401) {
    Session.clear();
    window.location.href = 'login.html';
  }
  return data;
}

function requireLogin() {
  if (!Session.isLoggedIn()) window.location.href = 'login.html';
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000;
  if (diff < 60) return "à l'instant";
  if (diff < 3600) return Math.floor(diff / 60) + ' min';
  if (diff < 86400) return Math.floor(diff / 3600) + ' h';
  return Math.floor(diff / 86400) + ' j';
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}
