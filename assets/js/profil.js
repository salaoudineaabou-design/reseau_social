/**
 * assets/js/profil.js
 * Gère l'affichage d'un profil (le sien ou celui d'un ami) et,
 * uniquement pour son propre profil, l'édition des informations,
 * le changement de mot de passe et l'upload d'avatar.
 */

/**
 * Charge et affiche un profil.
 * @param {string|number} userId - id du profil à afficher.
 */
async function chargerProfil(userId) {
  const conteneur = document.getElementById('contenu-profil');
  conteneur.innerHTML = '<p class="chargement">Chargement du profil...</p>';

  try {
    const reponse = await fetch(API_URL + 'profil.php?user_id=' + userId);
    const data = await reponse.json();

    if (!data.success) {
      conteneur.innerHTML = '<p class="erreur">Profil introuvable.</p>';
      return;
    }

    const profil = data.data;
    const connecte = utilisateurConnecte();
    const estMonProfil = connecte && parseInt(userId, 10) === connecte.id;

    conteneur.innerHTML = '';
    conteneur.appendChild(construireEnteteProfil(profil, estMonProfil));

    if (estMonProfil) {
      conteneur.appendChild(construireFormulaireEdition(profil));
      conteneur.appendChild(construireFormulaireMotDePasse());
    }
  } catch (erreur) {
    conteneur.innerHTML = '<p class="erreur">Impossible de charger le profil.</p>';
  }
}

function construireEnteteProfil(profil, estMonProfil) {
  const div = document.createElement('div');
  div.className = 'profil-header';

  const badgeRole = profil.role !== 'user'
    ? `<span class="tag-role ${profil.role}">${profil.role === 'admin' ? 'Administrateur' : 'Modérateur'}</span>`
    : '';

  const boutonMessage = estMonProfil
    ? ''
    : `<button class="btn-primary" id="btn-envoyer-message-profil" style="width:auto;padding:0.5rem 1.2rem;margin-top:0.75rem;">💬 Envoyer un message</button>`;

  div.innerHTML = `
    <img src="${texteSur(profil.avatar)}" class="avatar-grand" id="avatar-affiche" alt="">
    ${estMonProfil ? '<div><input type="file" id="input-avatar" accept="image/*" style="font-size:0.8rem;"></div>' : ''}
    <h2></h2>
    ${badgeRole}
    <p class="bio"></p>
    <div class="profil-stats">
      <div><strong>${profil.nb_articles}</strong>Articles</div>
      <div><strong>${profil.nb_amis}</strong>Amis</div>
    </div>
    ${boutonMessage}
  `;
  div.querySelector('h2').textContent = profil.prenom + ' ' + profil.nom;
  div.querySelector('.bio').textContent = profil.bio || 'Aucune biographie renseignée.';

  if (estMonProfil) {
    setTimeout(() => {
      const input = document.getElementById('input-avatar');
      if (input) input.addEventListener('change', () => gererUploadAvatar(input));
    }, 0);
  } else {
    setTimeout(() => {
      const btn = document.getElementById('btn-envoyer-message-profil');
      if (btn) btn.addEventListener('click', () => naviguer('chat', { autreId: profil.id }));
    }, 0);
  }

  return div;
}

function construireFormulaireEdition(profil) {
  const div = document.createElement('div');
  div.className = 'profil-form-section';
  div.innerHTML = `
    <h3>Modifier mes informations</h3>
    <div id="edit-profil-msg"></div>
    <div class="form-group">
      <label for="edit-nom">Nom</label>
      <input type="text" id="edit-nom" value="${texteSur(profil.nom)}">
    </div>
    <div class="form-group">
      <label for="edit-prenom">Prénom</label>
      <input type="text" id="edit-prenom" value="${texteSur(profil.prenom)}">
    </div>
    <div class="form-group">
      <label for="edit-bio">Bio</label>
      <textarea id="edit-bio">${texteSur(profil.bio || '')}</textarea>
    </div>
    <button class="btn-primary" id="btn-save-profil" style="width:auto;padding:0.6rem 1.5rem;">Enregistrer</button>
  `;
  setTimeout(() => {
    document.getElementById('btn-save-profil').addEventListener('click', gererEnregistrementProfil);
  }, 0);
  return div;
}

function construireFormulaireMotDePasse() {
  const div = document.createElement('div');
  div.className = 'profil-form-section';
  div.innerHTML = `
    <h3>Changer mon mot de passe</h3>
    <div id="edit-mdp-msg"></div>
    <div class="form-group">
      <label for="ancien-mdp">Mot de passe actuel</label>
      <input type="password" id="ancien-mdp">
    </div>
    <div class="form-group">
      <label for="nouveau-mdp">Nouveau mot de passe</label>
      <input type="password" id="nouveau-mdp">
    </div>
    <button class="btn-primary" id="btn-save-mdp" style="width:auto;padding:0.6rem 1.5rem;">Changer le mot de passe</button>
  `;
  setTimeout(() => {
    document.getElementById('btn-save-mdp').addEventListener('click', gererChangementMotDePasse);
  }, 0);
  return div;
}

async function gererEnregistrementProfil() {
  const user = utilisateurConnecte();
  const nom = document.getElementById('edit-nom').value.trim();
  const prenom = document.getElementById('edit-prenom').value.trim();
  const bio = document.getElementById('edit-bio').value.trim();

  try {
    const reponse = await fetch(API_URL + 'profil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: user.id, nom: nom, prenom: prenom, bio: bio }),
    });
    const data = await reponse.json();

    if (data.success) {
      sessionStorage.setItem('user', JSON.stringify(data.user));
      mettreAJourNavConnecte(data.user);
      afficherMessage('edit-profil-msg', 'Profil mis à jour avec succès.', 'success');
    } else {
      afficherMessage('edit-profil-msg', data.message || 'Erreur', 'error');
    }
  } catch (erreur) {
    afficherMessage('edit-profil-msg', 'Erreur réseau.', 'error');
  }
}

async function gererChangementMotDePasse() {
  const user = utilisateurConnecte();
  const ancien = document.getElementById('ancien-mdp').value;
  const nouveau = document.getElementById('nouveau-mdp').value;

  if (nouveau.length < 8) {
    afficherMessage('edit-mdp-msg', 'Le nouveau mot de passe doit contenir au moins 8 caractères.', 'error');
    return;
  }

  try {
    const reponse = await fetch(API_URL + 'profil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: user.id, action: 'changer_mdp', ancien_mdp: ancien, nouveau_mdp: nouveau }),
    });
    const data = await reponse.json();

    if (data.success) {
      afficherMessage('edit-mdp-msg', data.message, 'success');
      document.getElementById('ancien-mdp').value = '';
      document.getElementById('nouveau-mdp').value = '';
    } else {
      afficherMessage('edit-mdp-msg', data.message || 'Erreur', 'error');
    }
  } catch (erreur) {
    afficherMessage('edit-mdp-msg', 'Erreur réseau.', 'error');
  }
}

async function gererUploadAvatar(input) {
  const user = utilisateurConnecte();
  if (!input.files[0]) return;

  const formData = new FormData();
  formData.append('user_id', user.id);
  formData.append('avatar', input.files[0]);

  try {
    const reponse = await fetch(API_URL + 'upload_avatar.php', { method: 'POST', body: formData });
    const data = await reponse.json();

    if (data.success) {
      document.getElementById('avatar-affiche').src = data.avatar;
      user.avatar = data.avatar;
      sessionStorage.setItem('user', JSON.stringify(user));
      mettreAJourNavConnecte(user);
      afficherBandeauGlobal('Photo de profil mise à jour.', 'success');
    } else {
      afficherBandeauGlobal(data.message || 'Erreur lors de l\'upload', 'error');
    }
  } catch (erreur) {
    afficherBandeauGlobal('Erreur réseau lors de l\'upload.', 'error');
  }
}
