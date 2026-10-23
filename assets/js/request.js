/* Musicology — formulaire de demande en étapes. */
(function () {
	'use strict';
	var R = window.MCLR || {};
	var T = R.i18n || {};
	var form = document.getElementById('mcl-wizard');
	if (!form) return;

	var steps = Array.prototype.slice.call(form.querySelectorAll('.mcl-step'));
	var total = R.steps || 6;
	var last = steps.length; // étape de vérification
	var current = 1;
	var prev = form.querySelector('.mcl-prev');
	var next = form.querySelector('.mcl-next');
	var submit = form.querySelector('.mcl-submit');
	var errBox = form.querySelector('.mcl-wizard__error');
	var stepLbl = form.querySelector('.mcl-progress__step');
	var pctLbl = form.querySelector('.mcl-progress__pct');
	var bar = form.querySelector('.mcl-progress__bar');
	var storeKey = 'mcl_req_' + (R.lang || 'fr');

	function fill(s, a, b) {
		return String(s).replace('%1$s', a).replace('%2$s', b).replace('%s', a).replace('%%', '%');
	}

	/* ---------- Prénom dynamique dans les titres ---------- */
	function personalise() {
		var v = (form.recipient.value || '').trim();
		form.querySelectorAll('[data-name]').forEach(function (el) { el.textContent = v || T.this_person; });
		form.querySelector('.mcl-input-big').classList.toggle('is-ok', v.length > 1);
	}
	form.recipient.addEventListener('input', personalise);

	/* ---------- Progression ---------- */
	function render() {
		steps.forEach(function (s) { s.classList.toggle('is-active', +s.dataset.step === current); });
		var pct = current >= last ? 100 : Math.round((current / total) * 100);
		stepLbl.textContent = current >= last ? T.last_step : fill(T.step_of, current, total);
		pctLbl.textContent = fill(T.complete, pct);
		bar.style.setProperty('--p', pct + '%');
		prev.hidden = current === 1;
		next.hidden = current >= last;
		submit.hidden = current < last;
		next.querySelector('span').textContent = current === last - 1 ? T.finish : T.next;
		hideError();
		if (current === last) buildRecap();
		var top = form.getBoundingClientRect().top + window.scrollY - 90;
		if (window.scrollY > top) window.scrollTo({ top: top, behavior: 'smooth' });
		save();
	}

	function showError(msg, el) {
		errBox.textContent = msg;
		errBox.hidden = false;
		if (el) {
			el.classList.add('has-error');
			setTimeout(function () { el.classList.remove('has-error'); }, 1800);
			var f = el.querySelector('input:not([type=radio]):not([type=checkbox]), textarea');
			if (f) f.focus();
		}
	}
	function hideError() { errBox.hidden = true; }

	/* ---------- Validation d'une étape ---------- */
	function validate(n) {
		var step = form.querySelector('.mcl-step[data-step="' + n + '"]');
		var reqs = step.querySelectorAll('[data-required]');
		for (var i = 0; i < reqs.length; i++) {
			var name = reqs[i].dataset.required;
			var fields = form.querySelectorAll('[name="' + name + '"]');
			if (!fields.length) continue;
			var f = fields[0];
			var ok = (f.type === 'radio' || f.type === 'checkbox')
				? Array.prototype.some.call(fields, function (x) { return x.checked; })
				: f.value.trim().length > 0;
			if (!ok) {
				showError(f.type === 'radio' ? T.err_choose : T.err_required, reqs[i]);
				return false;
			}
		}
		if (n === 5) {
			var mode = form.querySelector('[name=mode]:checked').value;
			var txt = mode === 'lyrics' ? form.lyrics : form.story;
			if (txt.value.trim().length < 3) { showError(T.err_story, txt.closest('.mcl-q')); return false; }
		}
		if (n === 6) {
			var ph = form.photo.files && form.photo.files[0];
			if (ph && (ph.size > 5 * 1024 * 1024 || !/^image\/(jpeg|png|webp)$/.test(ph.type))) {
				showError(T.err_photo, form.photo.closest('.mcl-q'));
				return false;
			}
		}
		if (n === last) {
			var em = form.email.value.trim(), tel = form.phone.value.trim();
			if (!em && !tel) { showError(T.err_contact, form.email.closest('.mcl-q')); return false; }
			if (em && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(em)) { showError(T.err_email, form.email.closest('.mcl-q')); return false; }
		}
		return true;
	}

	next.addEventListener('click', function () {
		if (!validate(current)) return;
		current = Math.min(last, current + 1);
		render();
	});
	prev.addEventListener('click', function () { current = Math.max(1, current - 1); render(); });

	/* Clic sur un choix unique → étape suivante automatique quand tout est rempli (étapes 1 et 3 exclues : plusieurs questions). */
	form.querySelectorAll('.mcl-choice input[type=radio]').forEach(function (r) {
		r.addEventListener('change', function () { hideError(); save(); });
	});

	/* Entrée = suivant (hors textarea) */
	form.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit' && current < last) {
			e.preventDefault();
			next.click();
		}
	});

	/* ---------- Histoire / paroles ---------- */
	form.querySelectorAll('[name=mode]').forEach(function (r) {
		r.addEventListener('change', function () {
			form.querySelectorAll('.mcl-mode-panel').forEach(function (p) { p.classList.toggle('is-active', p.dataset.mode === r.value); });
		});
	});

	/* ---------- Boutons d'inspiration ---------- */
	form.querySelectorAll('.mcl-insp').forEach(function (box) {
		var ta = document.getElementById(box.dataset.target);
		box.querySelectorAll('button').forEach(function (b) {
			b.addEventListener('click', function () {
				var t = b.dataset.text;
				ta.value = (ta.value.trim() ? ta.value.replace(/\s*$/, '') + '\n\n' : '') + t;
				ta.focus();
				ta.setSelectionRange(ta.value.length, ta.value.length);
				b.classList.add('is-used');
				save();
			});
		});
	});

	/* ---------- Aperçu photo ---------- */
	var fileBox = form.querySelector('.mcl-file');
	form.photo.addEventListener('change', function () {
		var f = form.photo.files[0];
		var pv = fileBox.querySelector('.mcl-file__preview');
		pv.style.backgroundImage = '';
		fileBox.classList.remove('has-file');
		if (f && /^image\//.test(f.type)) {
			var url = URL.createObjectURL(f);
			pv.style.backgroundImage = 'url(' + url + ')';
			fileBox.classList.add('has-file');
			fileBox.querySelector('strong').textContent = f.name;
		}
	});

	/* ---------- Récapitulatif ---------- */
	function labelOf(input) {
		var s = input.closest('label').querySelector('span');
		return s ? s.textContent.trim() : input.value;
	}
	function checkedLabels(name) {
		return Array.prototype.map.call(form.querySelectorAll('[name="' + name + '"]:checked'), labelOf).join(', ');
	}
	function titleOf(n) {
		var h = form.querySelector('.mcl-step[data-step="' + n + '"] .mcl-q__label, .mcl-step[data-step="' + n + '"] .mcl-step__title');
		return h ? h.textContent.trim() : '';
	}
	function buildRecap() {
		var dl = form.querySelector('.mcl-recap__list');
		var mode = form.querySelector('[name=mode]:checked').value;
		var rows = [
			[form.querySelector('[data-step="1"] .mcl-q__label').textContent, checkedLabels('relation'), 1],
			[form.querySelectorAll('[data-step="1"] .mcl-q__label')[1].textContent, checkedLabels('occasion'), 1],
			[form.querySelector('label[for=mcl-recipient]').textContent, form.recipient.value.trim(), 2],
			[form.querySelector('label[for=mcl-date]').childNodes[0].textContent, form.occasion_date.value, 2],
			[form.querySelector('[data-step="3"] .mcl-q__label').textContent, checkedLabels('song_lang'), 3],
			[form.querySelectorAll('[data-step="3"] .mcl-q__label')[1].textContent, checkedLabels('genre'), 3],
			[form.querySelectorAll('[data-step="3"] .mcl-q__label')[2].childNodes[0].textContent, checkedLabels('voice'), 3],
			[titleOf(4), checkedLabels('qualities[]') + (form.own_words.value.trim() ? ' — ' + form.own_words.value.trim() : ''), 4],
			[form.querySelector(mode === 'lyrics' ? 'label[for=mcl-lyrics]' : 'label[for=mcl-story]').textContent, (mode === 'lyrics' ? form.lyrics : form.story).value.trim(), 5],
			[form.querySelector('label[for=mcl-msg]').childNodes[0].textContent, form.message.value.trim(), 6],
			[form.querySelectorAll('[data-step="6"] .mcl-q__label')[1].textContent, checkedLabels('clip'), 6]
		];
		dl.innerHTML = '';
		rows.forEach(function (r) {
			if (!r[1]) return;
			var wrap = document.createElement('div');
			var dt = document.createElement('dt');
			var dd = document.createElement('dd');
			var ed = document.createElement('button');
			dt.textContent = r[0].replace(/\s+/g, ' ').trim();
			dd.textContent = r[1].length > 260 ? r[1].slice(0, 260) + '…' : r[1];
			ed.type = 'button';
			ed.className = 'mcl-recap__edit';
			ed.textContent = '✎';
			ed.setAttribute('aria-label', dt.textContent);
			ed.addEventListener('click', function () { current = r[2]; render(); });
			dt.appendChild(ed);
			wrap.appendChild(dt);
			wrap.appendChild(dd);
			dl.appendChild(wrap);
		});
	}

	/* ---------- Prix : clip, livraison express, pourboire ---------- */
	var P = R.price || {};
	function dh(n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' DH'; }
	function updateTotal() {
		var clip = form.querySelector('[name=clip]:checked');
		var hasClip = clip && clip.value === 'yes';
		var exp = form.elements.express && form.elements.express.checked;
		var sub = (P.song || 0) + (hasClip ? (P.clip || 0) : 0) + (exp ? (P.express || 0) : 0);
		form.querySelectorAll('[data-tip-amount]').forEach(function (el) {
			el.textContent = dh(sub * (+el.dataset.tipAmount) / 100);
		});
		var custom = parseInt(form.elements.tip_custom.value, 10) || 0;
		var pctEl = form.querySelector('[name=tip_pct]:checked');
		var tip = custom > 0 ? custom : Math.round(sub * (pctEl ? +pctEl.value : 0) / 100);
		var row = function (k, show) { var r = form.querySelector('[data-sum="' + k + '"]'); if (r) r.hidden = !show; };
		row('clip', hasClip); row('express', exp); row('tip', tip > 0);
		var t = form.querySelector('[data-sum-tip]'); if (t) t.textContent = '+' + dh(tip);
		var tot = form.querySelector('[data-sum-total]'); if (tot) tot.textContent = dh(sub + tip);
		var box = form.querySelector('.mcl-upsell'); if (box) box.classList.toggle('is-on', !!exp);
	}
	form.querySelectorAll('[name=clip], [name=express], [name=tip_pct]').forEach(function (el) {
		el.addEventListener('change', function () {
			if (el.name === 'tip_pct') form.elements.tip_custom.value = '';
			updateTotal();
		});
	});
	form.elements.tip_custom.addEventListener('input', function () {
		if ((parseInt(form.elements.tip_custom.value, 10) || 0) > 0) {
			form.querySelectorAll('[name=tip_pct]').forEach(function (r) { r.checked = false; });
		}
		updateTotal();
	});
	form.querySelectorAll('[data-tip-step]').forEach(function (b) {
		b.addEventListener('click', function () {
			var v = Math.max(0, Math.min(5000, (parseInt(form.elements.tip_custom.value, 10) || 0) + (+b.dataset.tipStep)));
			form.elements.tip_custom.value = v || '';
			form.querySelectorAll('[name=tip_pct]').forEach(function (r) { r.checked = !v && r.value === '0'; });
			updateTotal();
		});
	});

	/* ---------- Brouillon local (survit à un rechargement) ---------- */
	function save() {
		try {
			var data = {};
			Array.prototype.forEach.call(form.elements, function (el) {
				if (!el.name || el.type === 'file' || el.type === 'hidden' || el.name === 'website') return;
				if (el.type === 'radio' || el.type === 'checkbox') {
					if (el.checked) { (data[el.name] = data[el.name] || []).push(el.value); }
				} else if (el.value) {
					data[el.name] = el.value;
				}
			});
			data._step = current;
			localStorage.setItem(storeKey, JSON.stringify(data));
		} catch (e) {}
	}
	function restore() {
		try {
			var data = JSON.parse(localStorage.getItem(storeKey) || 'null');
			if (!data) return;
			Array.prototype.forEach.call(form.elements, function (el) {
				if (!el.name || !(el.name in data) || el.type === 'file' || el.type === 'hidden') return;
				if (el.type === 'radio' || el.type === 'checkbox') {
					el.checked = data[el.name].indexOf(el.value) !== -1;
				} else {
					el.value = data[el.name];
				}
			});
			var m = form.querySelector('[name=mode]:checked');
			if (m) m.dispatchEvent(new Event('change'));
		} catch (e) {}
	}
	form.addEventListener('input', function () { save(); });
	restore();
	personalise();
	updateTotal();

	/* ---------- Envoi ---------- */
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		if (!validate(last)) return;
		for (var n = 1; n < last; n++) {
			if (!validate(n)) { current = n; render(); validate(n); return; }
		}
		var label = submit.querySelector('span');
		var old = label.textContent;
		label.textContent = T.sending;
		submit.disabled = true;
		form.classList.add('is-sending');

		var fd = new FormData(form);
		// N'envoyer que le texte du mode choisi.
		if (fd.get('mode') === 'lyrics') fd.set('story', ''); else fd.set('lyrics', '');

		fetch(R.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { return { success: false }; }); })
			.then(function (res) {
				if (res && res.success) {
					try { localStorage.removeItem(storeKey); } catch (x) {}
					form.hidden = true;
					var t = document.querySelector('.mcl-thanks');
					t.querySelector('.mcl-thanks__ref').textContent = res.data.ref;
					t.hidden = false;
					window.scrollTo({ top: t.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
				} else {
					showError((res && res.data && res.data.message) || T.err_server);
				}
			})
			.catch(function () { showError(T.err_server); })
			.then(function () {
				label.textContent = old;
				submit.disabled = false;
				form.classList.remove('is-sending');
			});
	});

	render();
})();
