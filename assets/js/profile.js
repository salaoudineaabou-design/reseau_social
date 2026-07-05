/**
 * profile.js - Modification du profil personnel et du mot de passe
 */

requireLogin();
const me = Session.getUser();

function fillForm() {
  document.getElementById('avatarPreview').src = '../../' + me.avatar;
  document.getElementById('prenom').value = me.prenom;
  document.getElementById('nom').value = me.nom;
  document.getElementById('bio').value = me.bio || '';
}

document.getElementById('avatarInput').addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) document.getElementById('avatarPreview').src = URL.createObjectURL(file);
});

document.getElementById('profileForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const msg = document.getElementById('profileMsg');
  const fd = new FormData();
  fd.append('prenom', document.getElementById('prenom').value);
  fd.append('nom', document.getElementById('nom').value);
  fd.append('bio', document.getElementById('bio').value);
  const avatarFile = document.getElementById('avatarInput').files[0];
  if (avatarFile) fd.append('avatar', avatarFile);

  const res = await apiUpload('update_profile.php', fd);
  msg.textContent = res.message;
  msg.className = res.success ? 'success-msg' : 'error-msg';

  if (res.success) {
    me.prenom = document.getElementById('prenom').value;
    me.nom = document.getElementById('nom').value;
    me.bio = document.getElementById('bio').value;
    if (res.avatar) me.avatar = res.avatar;
    Session.setUser(me);
  }
});

document.getElementById('passwordForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const msg = document.getElementById('passwordMsg');
  const current_password = document.getElementById('currentPassword').value;
  const new_password = document.getElementById('newPassword').value;
  const res = await apiCall('change_password.php', 'POST', { current_password, new_password });
  msg.textContent = res.message;
  msg.className = res.success ? 'success-msg' : 'error-msg';
  if (res.success) document.getElementById('passwordForm').reset();
});

fillForm();
