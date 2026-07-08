/**
 * assets/js/auth.js
 * Gère l'inscription, la connexion, la déconnexion et la réinitialisation
 * de mot de passe. Toutes les fonctions communiquent avec l'API via Fetch
 * et ne provoquent jamais de rechargement de page.
 */

/** Initialise les écouteurs propres au fragment login.html */
function initialiserLogin() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('succes') === 'compte_active') {
    afficherMessage('login-success', 'Compte activé ! Vous pouvez vous connecter.', 'success');
  }
  if (params.get('erreur') === 'token_invalide') {
    afficherMessage('login-error', 'Lien de confirmation invalide ou déjà utilisé.', 'error');
  }
}

async function gererConnexion() {
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const btnLogin = document.getElementById('btn-login');

  if (!email || !password) {
    afficherMessage('login-error', 'Remplis tous les champs.', 'error');
    return;
  }

  btnLogin.disabled = true;
  btnLogin.textContent = 'Connexion en cours...';

  try {
    const reponse = await fetch(API_URL + 'login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email, mot_de_passe: password }),
    });
    const data = await reponse.json();

    if (reponse.ok && data.success) {
      sessionStorage.setItem('user', JSON.stringify(data.user));
      mettreAJourNavConnecte(data.user);
      naviguer('accueil');
    } else {
      afficherMessage('login-error', data.message || 'Erreur inconnue', 'error');
    }
  } catch (erreur) {
    afficherMessage('login-error', 'Impossible de joindre le serveur.', 'error');
  } finally {
    btnLogin.disabled = false;
    btnLogin.textContent = 'Se connecter';
  }
}

async function gererInscription() {
  const nom = document.getElementById('register-nom').value.trim();
  const prenom = document.getElementById('register-prenom').value.trim();
  const email = document.getElementById('register-email').value.trim();
  const password = document.getElementById('register-password').value;
  const btn = document.getElementById('btn-register');

  if (!nom || !prenom || !email || !password) {
    afficherMessage('register-error', 'Remplis tous les champs.', 'error');
    return;
  }
  if (password.length < 8) {
    afficherMessage('register-error', 'Le mot de passe doit contenir au moins 8 caractères.', 'error');
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Création en cours...';

  try {
    const reponse = await fetch(API_URL + 'register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nom: nom, prenom: prenom, email: email, mot_de_passe: password }),
    });
    const data = await reponse.json();

    if (reponse.ok && data.success) {
      afficherMessage('register-success', data.message, 'success');
      setTimeout(() => naviguer('login'), 1800);
    } else {
      afficherMessage('register-error', data.message || 'Erreur inconnue', 'error');
    }
  } catch (erreur) {
    afficherMessage('register-error', 'Impossible de joindre le serveur.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = "S'inscrire";
  }
}

async function gererMotDePasseOublie() {
  const email = document.getElementById('forgot-email').value.trim();
  const btn = document.getElementById('btn-forgot');
  if (!email) {
    afficherMessage('forgot-error', 'Renseigne ton email.', 'error');
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Envoi en cours...';

  try {
    const reponse = await fetch(API_URL + 'forgot_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email }),
    });
    const data = await reponse.json();
    afficherMessage('forgot-success', data.message, 'success');
  } catch (erreur) {
    afficherMessage('forgot-error', 'Impossible de joindre le serveur.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Envoyer le lien';
  }
}

async function gererReinitialisation() {
  const token = sessionStorage.getItem('reset_token');
  const nouveau = document.getElementById('reset-password').value;
  const confirmation = document.getElementById('reset-password-confirm').value;
  const btn = document.getElementById('btn-reset');

  if (!token) {
    afficherMessage('reset-error', 'Lien invalide, redemande une réinitialisation.', 'error');
    return;
  }
  if (nouveau.length < 8) {
    afficherMessage('reset-error', 'Le mot de passe doit contenir au moins 8 caractères.', 'error');
    return;
  }
  if (nouveau !== confirmation) {
    afficherMessage('reset-error', 'Les deux mots de passe ne correspondent pas.', 'error');
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Modification...';

  try {
    const reponse = await fetch(API_URL + 'reset_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: token, nouveau_mdp: nouveau }),
    });
    const data = await reponse.json();

    if (reponse.ok && data.success) {
      sessionStorage.removeItem('reset_token');
      afficherMessage('reset-success', data.message, 'success');
      setTimeout(() => naviguer('login'), 1800);
    } else {
      afficherMessage('reset-error', data.message || 'Erreur inconnue', 'error');
    }
  } catch (erreur) {
    afficherMessage('reset-error', 'Impossible de joindre le serveur.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Changer le mot de passe';
  }
}

function gererDeconnexion() {
  arreterPolling();
  sessionStorage.removeItem('user');
  mettreAJourNavDeconnecte();
  naviguer('login');
}
