/*
 * Clés d'accès (passkeys) : Face ID, empreinte digitale, Windows Hello, PIN de l'appareil
 * Utilisé par connexion.php (se connecter) et profil.php (ajouter une clé).
 * Le serveur (passkey.php) vérifie tout : ce fichier fait seulement le lien avec l'appareil.
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // ---------- Outils : base64url <-> octets ----------
  function versOctets(texte) {
    const b64 = texte.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((texte.length + 3) % 4);
    return Uint8Array.from(atob(b64), function (c) { return c.charCodeAt(0); });
  }
  function versTexte(buffer) {
    let s = '';
    new Uint8Array(buffer).forEach(function (o) { s += String.fromCharCode(o); });
    return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  function jeton() {
    const champ = document.querySelector('input[name="csrf_token"]');
    return champ ? champ.value : '';
  }

  // Envoie une requête POST à passkey.php et renvoie la réponse JSON
  async function appeler(action, extra) {
    const form = new FormData();
    form.append('csrf_token', jeton());
    form.append('action', action);
    Object.keys(extra || {}).forEach(function (cle) { form.append(cle, extra[cle]); });
    const reponse = await fetch('passkey.php', { method: 'POST', body: form, credentials: 'same-origin' });
    let json;
    try { json = await reponse.json(); } catch (e) { json = { ok: false, erreur: 'Session expirée, rechargez la page.' }; }
    return json;
  }

  function message(zone, texte, type) {
    if (!zone) return;
    zone.className = 'alert alert-' + (type || 'danger') + ' mt-3';
    zone.textContent = texte;
    zone.hidden = false;
  }

  function erreurAppareil(err) {
    if (err && err.name === 'NotAllowedError') return 'Opération annulée ou délai dépassé.';
    if (err && err.name === 'InvalidStateError') return 'Cette clé est déjà enregistrée sur cet appareil.';
    if (err && err.name === 'SecurityError') return 'Les clés d’accès fonctionnent seulement sur localhost ou en HTTPS.';
    return 'Votre appareil n’a pas pu utiliser la clé d’accès.';
  }

  const supporte = !!(window.PublicKeyCredential && navigator.credentials);

  /* ======================= CONNEXION ======================= */
  const boutonConnexion = document.getElementById('btnPasskeyConnexion');
  if (boutonConnexion) {
    const zone = document.getElementById('passkeyMessage');
    if (!supporte) {
      boutonConnexion.disabled = true;
      message(zone, 'Ce navigateur ne gère pas les clés d’accès.', 'warning');
    }
    boutonConnexion.addEventListener('click', async function () {
      boutonConnexion.disabled = true;
      try {
        const r = await appeler('options-connexion');
        if (!r.ok) throw new Error(r.erreur);
        const o = r.options;
        const credential = await navigator.credentials.get({
          publicKey: {
            challenge: versOctets(o.challenge),
            rpId: o.rpId,
            userVerification: o.userVerification,
            timeout: o.timeout
          }
        });
        const resultat = await appeler('connexion', {
          donnees: JSON.stringify({
            id: credential.id,
            clientDataJSON: versTexte(credential.response.clientDataJSON),
            authenticatorData: versTexte(credential.response.authenticatorData),
            signature: versTexte(credential.response.signature)
          })
        });
        if (!resultat.ok) throw new Error(resultat.erreur);
        window.location.href = resultat.redirection;
      } catch (err) {
        message(zone, err.name && err.name.match(/^(NotAllowed|InvalidState|Security)Error$/) ? erreurAppareil(err) : err.message);
        boutonConnexion.disabled = false;
      }
    });
  }

  /* ================= AJOUTER UNE CLÉ (profil) ================= */
  const formCle = document.getElementById('formPasskey');
  if (formCle) {
    const zone = document.getElementById('passkeyMessage');
    const champNom = document.getElementById('nomPasskey');
    const erreurNom = document.getElementById('erreur-nomPasskey');
    if (!supporte) {
      formCle.querySelector('button').disabled = true;
      message(zone, 'Ce navigateur ne gère pas les clés d’accès.', 'warning');
    }
    formCle.addEventListener('submit', async function (event) {
      event.preventDefault();
      const nom = champNom.value.trim();
      if (nom === '' || nom.length > 50) {
        erreurNom.textContent = 'Donnez un nom à la clé (50 caractères maximum).';
        champNom.classList.add('is-invalid');
        return;
      }
      erreurNom.textContent = '';
      champNom.classList.remove('is-invalid');
      const bouton = formCle.querySelector('button');
      bouton.disabled = true;
      try {
        const r = await appeler('options-inscription');
        if (!r.ok) throw new Error(r.erreur);
        const o = r.options;
        o.challenge = versOctets(o.challenge);
        o.user.id = versOctets(o.user.id);
        o.excludeCredentials = o.excludeCredentials.map(function (c) { return { type: c.type, id: versOctets(c.id) }; });

        const credential = await navigator.credentials.create({ publicKey: o });
        const resultat = await appeler('inscription', {
          nom: nom,
          donnees: JSON.stringify({
            clientDataJSON: versTexte(credential.response.clientDataJSON),
            attestationObject: versTexte(credential.response.attestationObject)
          })
        });
        if (!resultat.ok) throw new Error(resultat.erreur);
        window.location.href = 'profil.php?onglet=securite';
      } catch (err) {
        message(zone, err.name && err.name.match(/^(NotAllowed|InvalidState|Security)Error$/) ? erreurAppareil(err) : err.message);
        bouton.disabled = false;
      }
    });
  }
});
