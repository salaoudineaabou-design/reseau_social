/**
 * assets/js/feed.js
 * Gère le flux d'actualité : chargement des articles, publication,
 * likes/dislikes et commentaires (chargement + ajout en AJAX, sans
 * jamais recharger la page).
 */

/** Charge et affiche les articles du flux principal. */
async function chargerArticles() {
  const conteneur = document.getElementById('liste-articles');
  if (!conteneur) return;
  conteneur.innerHTML = '<p class="chargement">Chargement des articles...</p>';

  const user = utilisateurConnecte();
  const userIdParam = user ? '?user_id=' + user.id : '';

  try {
    const reponse = await fetch(API_URL + 'articles.php' + userIdParam);
    const data = await reponse.json();

    if (!data.success || data.data.length === 0) {
      conteneur.innerHTML = '<p class="vide">Aucun article pour le moment. Sois le premier à publier !</p>';
      return;
    }

    conteneur.innerHTML = '';
    data.data.forEach(function (article) {
      conteneur.appendChild(construireCarteArticle(article));
    });
  } catch (erreur) {
    conteneur.innerHTML = '<p class="erreur">Impossible de charger les articles.</p>';
  }
}

/** Construit le DOM d'une carte article à partir des données JSON. */
function construireCarteArticle(article) {
  const carte = document.createElement('div');
  carte.className = 'carte-article';
  carte.dataset.articleId = article.id;

  const imageHtml = article.image
    ? `<img src="${texteSur(article.image)}" class="article-image" alt="Image de l'article">`
    : '';

  const votLike = article.mon_vote === 'like' ? 'actif' : '';
  const votDislike = article.mon_vote === 'dislike' ? 'actif' : '';

  carte.innerHTML = `
    <div class="article-header">
      <img src="${texteSur(article.auteur_avatar)}" class="avatar" alt="">
      <div>
        <a href="#" class="auteur-nom" data-vue="profil" data-user-id="${article.auteur_id}" style="text-decoration:none;color:inherit;">
          ${texteSur(article.auteur_prenom)} ${texteSur(article.auteur_nom)}
        </a>
        <div class="article-date">${formaterDate(article.created_at)}</div>
      </div>
    </div>
    <div class="article-contenu"></div>
    ${imageHtml}
    <div class="article-actions">
      <button class="btn-like ${votLike}" data-type="like" data-id="${article.id}">
        👍 <span class="nb-like">${article.nb_likes}</span>
      </button>
      <button class="btn-like ${votDislike}" data-type="dislike" data-id="${article.id}">
        👎 <span class="nb-dislike">${article.nb_dislikes}</span>
      </button>
      <button class="btn-commentaires" data-id="${article.id}">
        💬 <span class="nb-comm">${article.nb_commentaires}</span> commentaire(s)
      </button>
    </div>
    <div class="zone-commentaires" id="commentaires-${article.id}" style="display:none;"></div>
  `;
  // Le contenu de l'article est inséré via textContent pour bloquer toute injection HTML
  carte.querySelector('.article-contenu').textContent = article.contenu;

  return carte;
}

/** Gère le clic sur un bouton like/dislike (délégation d'événement). */
async function gererClicLike(bouton) {
  const user = utilisateurConnecte();
  if (!user) { naviguer('login'); return; }

  const articleId = bouton.dataset.id;
  const type = bouton.dataset.type;

  try {
    const reponse = await fetch(API_URL + 'like.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: user.id, article_id: articleId, type: type }),
    });
    const data = await reponse.json();
    if (!data.success) return;

    const carte = bouton.closest('.carte-article');
    carte.querySelector('.nb-like').textContent = data.nb_likes;
    carte.querySelector('.nb-dislike').textContent = data.nb_dislikes;

    carte.querySelectorAll('.btn-like').forEach(b => b.classList.remove('actif'));
    if (data.mon_vote) {
      carte.querySelector(`.btn-like[data-type="${data.mon_vote}"]`).classList.add('actif');
    }
  } catch (erreur) {
    afficherBandeauGlobal('Erreur réseau lors du vote.', 'error');
  }
}

/** Affiche/masque et charge les commentaires d'un article au premier clic. */
async function gererClicCommentaires(bouton) {
  const articleId = bouton.dataset.id;
  const zone = document.getElementById('commentaires-' + articleId);

  const estVisible = zone.style.display !== 'none';
  if (estVisible) { zone.style.display = 'none'; return; }

  zone.style.display = 'block';
  if (zone.dataset.charge === 'true') return;

  zone.innerHTML = '<p class="chargement">Chargement...</p>';

  try {
    const reponse = await fetch(API_URL + 'commentaires.php?article_id=' + articleId);
    const data = await reponse.json();

    zone.innerHTML = '';
    data.data.forEach(function (c) {
      zone.appendChild(construireCommentaire(c));
    });
    zone.appendChild(construireFormulaireCommentaire(articleId));
    zone.dataset.charge = 'true';
  } catch (erreur) {
    zone.innerHTML = '<p class="erreur">Impossible de charger les commentaires.</p>';
  }
}

function construireCommentaire(c) {
  const div = document.createElement('div');
  div.className = 'commentaire';
  div.innerHTML = `
    <img src="${texteSur(c.auteur_avatar)}" class="avatar-xs" alt="">
    <div>
      <strong></strong>
      <span></span>
    </div>
  `;
  div.querySelector('strong').textContent = c.auteur_prenom + ' ' + c.auteur_nom;
  div.querySelector('span').textContent = c.contenu;
  return div;
}

function construireFormulaireCommentaire(articleId) {
  const div = document.createElement('div');
  div.className = 'saisie-commentaire';
  div.innerHTML = `
    <input type="text" class="input-commentaire" placeholder="Écrire un commentaire..." maxlength="500">
    <button class="btn-envoyer-commentaire" data-id="${articleId}">Envoyer</button>
  `;
  return div;
}

async function gererEnvoiCommentaire(bouton) {
  const user = utilisateurConnecte();
  if (!user) { naviguer('login'); return; }

  const articleId = bouton.dataset.id;
  const input = bouton.previousElementSibling;
  const contenu = input.value.trim();
  if (!contenu) return;

  bouton.disabled = true;

  try {
    const reponse = await fetch(API_URL + 'commentaires.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: user.id, article_id: articleId, contenu: contenu }),
    });
    const data = await reponse.json();

    if (data.success) {
      const zone = document.getElementById('commentaires-' + articleId);
      const nouveauCommentaire = construireCommentaire(data.commentaire);
      nouveauCommentaire.classList.add('nouveau');
      zone.insertBefore(nouveauCommentaire, zone.querySelector('.saisie-commentaire'));
      input.value = '';

      const carte = document.querySelector(`.carte-article[data-article-id="${articleId}"]`);
      const compteur = carte.querySelector('.nb-comm');
      compteur.textContent = parseInt(compteur.textContent, 10) + 1;
    } else {
      afficherBandeauGlobal(data.message || 'Erreur lors de l\'envoi du commentaire', 'error');
    }
  } catch (erreur) {
    afficherBandeauGlobal('Erreur réseau.', 'error');
  } finally {
    bouton.disabled = false;
  }
}

/** Publie un nouvel article (avec image optionnelle) via FormData. */
async function gererPublicationArticle() {
  const user = utilisateurConnecte();
  if (!user) { naviguer('login'); return; }

  const texte = document.getElementById('nouvel-article').value.trim();
  const fichierInput = document.getElementById('image-article');
  const btn = document.getElementById('btn-publier');

  if (!texte) {
    afficherBandeauGlobal('Le contenu ne peut pas être vide.', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('user_id', user.id);
  formData.append('contenu', texte);
  if (fichierInput.files[0]) {
    formData.append('image', fichierInput.files[0]);
  }

  btn.disabled = true;
  btn.textContent = 'Publication...';

  try {
    const reponse = await fetch(API_URL + 'articles.php', { method: 'POST', body: formData });
    const data = await reponse.json();

    if (data.success) {
      document.getElementById('nouvel-article').value = '';
      fichierInput.value = '';
      const conteneur = document.getElementById('liste-articles');
      const carte = construireCarteArticle(data.article);
      conteneur.insertBefore(carte, conteneur.firstChild);
      if (conteneur.querySelector('.vide')) conteneur.querySelector('.vide').remove();
    } else {
      afficherBandeauGlobal(data.message || 'Erreur lors de la publication', 'error');
    }
  } catch (erreur) {
    afficherBandeauGlobal('Erreur réseau lors de la publication.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Publier';
  }
}

/** Attache les écouteurs délégués une seule fois pour la vue accueil. */
function initialiserAccueil() {
  chargerArticles();

  document.getElementById('btn-publier').addEventListener('click', gererPublicationArticle);

  document.getElementById('liste-articles').addEventListener('click', function (e) {
    const btnLike = e.target.closest('.btn-like');
    const btnComm = e.target.closest('.btn-commentaires');
    const btnEnvoi = e.target.closest('.btn-envoyer-commentaire');
    const lienAuteur = e.target.closest('[data-vue="profil"]');

    if (btnLike) gererClicLike(btnLike);
    else if (btnComm) gererClicCommentaires(btnComm);
    else if (btnEnvoi) gererEnvoiCommentaire(btnEnvoi);
    else if (lienAuteur) {
      e.preventDefault();
      naviguer('profil', { userId: lienAuteur.dataset.userId });
    }
  });

  // Permet d'envoyer un commentaire avec la touche Entrée
  document.getElementById('liste-articles').addEventListener('keypress', function (e) {
    if (e.key === 'Enter' && e.target.classList.contains('input-commentaire')) {
      e.target.nextElementSibling.click();
    }
  });
}
