/**
 * assets/js/chat.js
 * Gère la messagerie : liste des conversations, affichage des messages,
 * envoi, et rafraîchissement automatique par polling (setInterval)
 * toutes les 3 secondes tant que la vue chat est active.
 */

let _conversationActive = null;
let _dernierTimestamp = '1970-01-01 00:00:00';
let _intervalPolling = null;

async function chargerConversations(idAOuvrir) {
  const conteneur = document.getElementById('liste-conversations');
  const user = utilisateurConnecte();

  try {
    const reponse = await fetch(API_URL + 'conversations.php?user_id=' + user.id);
    const data = await reponse.json();

    conteneur.innerHTML = '';
    if (!data.data || data.data.length === 0) {
      conteneur.innerHTML = '<p class="vide" style="padding:1rem;">Aucune conversation. Va sur le profil d\'un ami pour lui écrire.</p>';
    } else {
      data.data.forEach(function (conv) {
        conteneur.appendChild(construireItemConversation(conv));
      });
    }

    if (idAOuvrir) {
      ouvrirConversationAvecUtilisateur(idAOuvrir);
    }
  } catch (erreur) {
    conteneur.innerHTML = '<p class="erreur">Erreur de chargement.</p>';
  }
}

function construireItemConversation(conv) {
  const div = document.createElement('div');
  div.className = 'conv-item';
  div.dataset.convId = conv.conv_id;
  div.dataset.autreId = conv.autre_id;
  div.dataset.autreNom = conv.autre_prenom + ' ' + conv.autre_nom;
  div.dataset.autreAvatar = conv.autre_avatar;

  const badge = conv.non_lus > 0 ? `<span class="badge-notif">${conv.non_lus}</span>` : '';

  div.innerHTML = `
    <img src="${texteSur(conv.autre_avatar)}" class="avatar-sm" alt="">
    <div>
      <div class="conv-nom"></div>
      <div class="conv-dernier"></div>
    </div>
    ${badge}
  `;
  div.querySelector('.conv-nom').textContent = conv.autre_prenom + ' ' + conv.autre_nom;
  div.querySelector('.conv-dernier').textContent = conv.dernier_message || 'Nouvelle conversation';

  return div;
}

/** Ouvre (ou crée si besoin) une conversation à partir de l'id d'un autre utilisateur. */
async function ouvrirConversationAvecUtilisateur(autreId) {
  const itemExistant = document.querySelector(`.conv-item[data-autre-id="${autreId}"]`);

  if (itemExistant) {
    ouvrirConversation(itemExistant.dataset.convId, itemExistant.dataset.autreNom, itemExistant.dataset.autreAvatar, autreId);
    return;
  }

  // Pas encore de conversation existante : on affiche une zone de saisie
  // qui créera la conversation au premier message envoyé.
  try {
    const reponse = await fetch(API_URL + 'profil.php?user_id=' + autreId);
    const data = await reponse.json();
    if (data.success) {
      ouvrirConversation(null, data.data.prenom + ' ' + data.data.nom, data.data.avatar, autreId);
    }
  } catch (erreur) {
    afficherBandeauGlobal('Impossible d\'ouvrir la conversation.', 'error');
  }
}

function ouvrirConversation(convId, nomAffiche, avatarAffiche, autreId) {
  document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
  const item = document.querySelector(`.conv-item[data-autre-id="${autreId}"]`);
  if (item) item.classList.add('active');

  _conversationActive = { convId: convId, autreId: autreId };
  _dernierTimestamp = '1970-01-01 00:00:00';

  document.getElementById('chat-entete').innerHTML = `
    <img src="${texteSur(avatarAffiche)}" class="avatar-sm" alt="">
    <span></span>
  `;
  document.getElementById('chat-entete').querySelector('span').textContent = nomAffiche;

  document.getElementById('zone-messages').innerHTML = '';
  document.getElementById('zone-chat-active').style.display = 'flex';
  document.getElementById('chat-vide').style.display = 'none';

  if (convId) chargerMessages(true);

  demarrerPolling();
}

async function chargerMessages(defilerVersBas) {
  if (!_conversationActive || !_conversationActive.convId) return;
  const user = utilisateurConnecte();

  try {
    const url = API_URL + 'messages.php?conversation_id=' + _conversationActive.convId
      + '&user_id=' + user.id + '&depuis=' + encodeURIComponent(_dernierTimestamp);
    const reponse = await fetch(url);
    const data = await reponse.json();

    if (data.success && data.data.length > 0) {
      const zone = document.getElementById('zone-messages');
      data.data.forEach(function (m) {
        zone.appendChild(construireMessage(m, user.id));
        _dernierTimestamp = m.created_at;
      });
      if (defilerVersBas) zone.scrollTop = zone.scrollHeight;
    }
  } catch (erreur) {
    // Échec silencieux : le polling réessaiera au tour suivant
  }
}

function construireMessage(m, monId) {
  const div = document.createElement('div');
  const estMoi = parseInt(m.expediteur_id, 10) === parseInt(monId, 10);
  div.className = 'message' + (estMoi ? ' moi' : '');

  const imageHtml = m.image
    ? `<img src="${texteSur(m.image)}" class="msg-image" alt="Image envoyée">`
    : '';
  const contenuHtml = m.contenu ? '<span class="msg-contenu"></span>' : '';

  div.innerHTML = `
    <img src="${texteSur(m.exp_avatar)}" class="avatar-xs" alt="">
    <div class="bulle">
      ${imageHtml}
      ${contenuHtml}
      <span class="msg-heure"></span>
    </div>
  `;
  if (m.contenu) div.querySelector('.msg-contenu').textContent = m.contenu;
  div.querySelector('.msg-heure').textContent = new Date(m.created_at.replace(' ', 'T'))
    .toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

  return div;
}

async function envoyerMessage() {
  const input = document.getElementById('input-message');
  const fichierInput = document.getElementById('input-image-message');
  const contenu = input.value.trim();
  const fichier = fichierInput.files[0];

  if (!contenu && !fichier) return;
  if (!_conversationActive) return;

  const user = utilisateurConnecte();
  input.value = '';

  try {
    let reponse;
    if (fichier) {
      const formData = new FormData();
      formData.append('user_id', user.id);
      formData.append('destinataire_id', _conversationActive.autreId);
      formData.append('contenu', contenu);
      formData.append('image', fichier);
      reponse = await fetch(API_URL + 'messages.php', { method: 'POST', body: formData });
      fichierInput.value = '';
    } else {
      reponse = await fetch(API_URL + 'messages.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: user.id,
          destinataire_id: _conversationActive.autreId,
          contenu: contenu,
        }),
      });
    }
    const data = await reponse.json();

    if (data.success) {
      _conversationActive.convId = data.conv_id;
      chargerMessages(true);
      chargerConversations(); // rafraîchit la sidebar (dernier message, tri)
    } else {
      afficherBandeauGlobal(data.message || 'Message non envoyé.', 'error');
    }
  } catch (erreur) {
    afficherBandeauGlobal('Message non envoyé (erreur réseau).', 'error');
  }
}

function demarrerPolling() {
  arreterPolling();
  _intervalPolling = setInterval(function () {
    chargerMessages(true);
  }, 3000);
}

function arreterPolling() {
  if (_intervalPolling) {
    clearInterval(_intervalPolling);
    _intervalPolling = null;
  }
}

function initialiserChat(options) {
  chargerConversations(options && options.autreId ? options.autreId : null);

  document.getElementById('recherche-conv').addEventListener('input', function (e) {
    const terme = e.target.value.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(function (item) {
      const nom = item.dataset.autreNom.toLowerCase();
      item.style.display = nom.includes(terme) ? 'flex' : 'none';
    });
  });

  document.getElementById('liste-conversations').addEventListener('click', function (e) {
    const item = e.target.closest('.conv-item');
    if (item) {
      ouvrirConversation(item.dataset.convId, item.dataset.autreNom, item.dataset.autreAvatar, item.dataset.autreId);
    }
  });

  document.getElementById('btn-envoyer-message').addEventListener('click', envoyerMessage);
  document.getElementById('input-message').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') envoyerMessage();
  });
  document.getElementById('input-image-message').addEventListener('change', function (e) {
    const label = document.querySelector('.btn-piece-jointe');
    if (e.target.files[0]) {
      label.textContent = '🖼️';
      label.title = e.target.files[0].name;
    } else {
      label.textContent = '📎';
      label.title = 'Envoyer une image';
    }
  });
}
