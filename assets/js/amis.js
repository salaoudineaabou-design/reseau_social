/**
 * assets/js/amis.js
 * Gère la page "Amis" : liste de tous les utilisateurs avec leur statut
 * de relation, recherche en direct, et actions (ajouter/accepter/refuser/supprimer).
 */

let _tousLesUtilisateurs = [];

async function chargerAmis() {
  const conteneur = document.getElementById('grille-amis');
  conteneur.innerHTML = '<p class="chargement">Chargement...</p>';

  const user = utilisateurConnecte();

  try {
    const reponse = await fetch(API_URL + 'amis.php?user_id=' + user.id);
    const data = await reponse.json();

    _tousLesUtilisateurs = data.data || [];
    afficherListeAmis(_tousLesUtilisateurs);
  } catch (erreur) {
    conteneur.innerHTML = '<p class="erreur">Impossible de charger la liste.</p>';
  }
}

function afficherListeAmis(liste) {
  const conteneur = document.getElementById('grille-amis');
  if (liste.length === 0) {
    conteneur.innerHTML = '<p class="vide">Aucun utilisateur trouvé.</p>';
    return;
  }

  conteneur.innerHTML = '';
  liste.forEach(function (u) {
    conteneur.appendChild(construireCarteAmi(u));
  });
}

function construireCarteAmi(u) {
  const div = document.createElement('div');
  div.className = 'carte-ami';

  let actionHtml = '';
  if (u.relation === 'aucune' || u.relation === 'refuse') {
    actionHtml = `<button class="btn-relation ajouter" data-action="envoyer" data-cible="${u.id}">Ajouter</button>`;
  } else if (u.relation === 'demande_envoyee') {
    actionHtml = `<button class="btn-relation attente" disabled>Invitation envoyée</button>`;
  } else if (u.relation === 'demande_recue') {
    actionHtml = `
      <div class="btn-double">
        <button class="btn-accepter" data-action="accepter" data-relation="${u.relation_id}">Accepter</button>
        <button class="btn-refuser" data-action="refuser" data-relation="${u.relation_id}">Refuser</button>
      </div>`;
  } else if (u.relation === 'amis') {
    actionHtml = `<button class="btn-relation amis" data-action="supprimer" data-cible="${u.id}">✓ Amis (retirer)</button>`;
  }

  div.innerHTML = `
    <a href="#" data-vue="profil" data-user-id="${u.id}" style="text-decoration:none;color:inherit;">
      <img src="${texteSur(u.avatar)}" class="avatar" alt="">
      <h4></h4>
    </a>
    <p></p>
    ${actionHtml}
  `;
  div.querySelector('h4').textContent = u.prenom + ' ' + u.nom;
  div.querySelector('p').textContent = u.bio || '';

  return div;
}

async function gererActionAmi(bouton) {
  const user = utilisateurConnecte();
  const action = bouton.dataset.action;
  const corps = { user_id: user.id, action: action };

  if (bouton.dataset.cible) corps.cible_id = bouton.dataset.cible;
  if (bouton.dataset.relation) corps.relation_id = bouton.dataset.relation;

  try {
    const reponse = await fetch(API_URL + 'amis.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(corps),
    });
    const data = await reponse.json();

    if (data.success) {
      afficherBandeauGlobal(data.message, 'success');
      chargerAmis();
    } else {
      afficherBandeauGlobal(data.message || 'Erreur', 'error');
    }
  } catch (erreur) {
    afficherBandeauGlobal('Erreur réseau.', 'error');
  }
}

function initialiserAmis() {
  chargerAmis();

  document.getElementById('recherche-amis-input').addEventListener('input', function (e) {
    const terme = e.target.value.toLowerCase();
    const filtres = _tousLesUtilisateurs.filter(function (u) {
      return (u.nom + ' ' + u.prenom).toLowerCase().includes(terme);
    });
    afficherListeAmis(filtres);
  });

  document.getElementById('grille-amis').addEventListener('click', function (e) {
    const bouton = e.target.closest('button[data-action]');
    const lienProfil = e.target.closest('[data-vue="profil"]');

    if (bouton) gererActionAmi(bouton);
    else if (lienProfil) {
      e.preventDefault();
      naviguer('profil', { userId: lienProfil.dataset.userId });
    }
  });
}
