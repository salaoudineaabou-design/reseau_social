/**
 * auth.js - Gère inscription, connexion, mot de passe oublié / réinitialisation
 * Toutes les actions se font en AJAX, sans rechargement de page.
 */

function showMsg(el, text, type = 'error') {
  el.textContent = text;
  el.className = type === 'error' ? 'error-msg' : 'success-msg';
  el.classList.remove('hidden');
}

// ---------- Formulaire d'inscription ----------
const registerForm = document.getElementById('registerForm');
if (registerForm) {
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('msg');
    const payload = {
      prenom: document.getElementById('prenom').value,
      nom: document.getElementById('nom').value,
      email: document.getElementById('email').value,
      password: document.getElementById('password').value,
    };
    const res = await apiCall('register.php', 'POST', payload);
    showMsg(msg, res.message, res.success ? 'success' : 'error');
    if (res.success) registerForm.reset();
  });
}

// ---------- Formulaire de connexion ----------
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('msg');
    const payload = {
      email: document.getElementById('email').value,
      password: document.getElementById('password').value,
    };
    const res = await apiCall('login.php', 'POST', payload);
    if (res.success) {
      Session.setToken(res.session_token);
      Session.setUser(res.user);
      window.location.href = 'accueil.html';
    } else {
      showMsg(msg, res.message, 'error');
    }
  });
}

// ---------- Mot de passe oublié ----------
const forgotForm = document.getElementById('forgotForm');
if (forgotForm) {
  forgotForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('msg');
    const email = document.getElementById('email').value;
    const res = await apiCall('forgot_password.php', 'POST', { email });
    showMsg(msg, res.message, res.success ? 'success' : 'error');
  });
}

// ---------- Réinitialisation du mot de passe ----------
const resetForm = document.getElementById('resetForm');
if (resetForm) {
  const params = new URLSearchParams(window.location.search);
  const token = params.get('token');
  resetForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('msg');
    const password = document.getElementById('password').value;
    const res = await apiCall('reset_password.php', 'POST', { token, password });
    showMsg(msg, res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => (window.location.href = 'login.html'), 1500);
  });
}

// ---------- Déconnexion (utilisée depuis la navbar) ----------
async function logout() {
  await apiCall('logout.php', 'POST');
  Session.clear();
  window.location.href = 'login.html';
}
