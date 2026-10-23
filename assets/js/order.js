/* Musicology — formulaire de commande d'un service. */
(function () {
	'use strict';
	var O = window.MCLO || {};
	var T = O.i18n || {};
	document.querySelectorAll('.mcl-order__form').forEach(function (form) {
		/* Champs selon le type (livre audio / podcast) */
		var types = form.querySelectorAll('[name=type]');
		function showFor() {
			var cur = form.querySelector('[name=type]:checked');
			if (!cur) return;
			form.querySelectorAll('[data-show-for]').forEach(function (el) { el.hidden = el.dataset.showFor !== cur.value; });
		}
		types.forEach(function (r) { r.addEventListener('change', showFor); });
		showFor();
		document.querySelectorAll('[data-ab-type]').forEach(function (a) {
			a.addEventListener('click', function () {
				var r = form.querySelector('[name=type][value="' + a.dataset.abType + '"]');
				if (r) { r.checked = true; showFor(); }
			});
		});

		var err = form.querySelector('.mcl-wizard__error');
		var btn = form.querySelector('.mcl-order__submit');
		function fail(msg, el) {
			err.textContent = msg;
			err.hidden = false;
			if (el) {
				el.classList.add('has-error');
				setTimeout(function () { el.classList.remove('has-error'); }, 1800);
				var f = el.querySelector('input, textarea');
				if (f) f.focus();
			}
		}
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			err.hidden = true;
			var name = form.elements.name.value.trim();
			var mail = form.elements.email.value.trim();
			var tel = form.elements.phone.value.trim();
			if (!name) return fail(T.err_required, form.elements.name.closest('.mcl-q'));
			if (!mail && !tel) return fail(T.err_contact, form.elements.phone.closest('.mcl-q'));
			if (mail && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(mail)) return fail(T.err_email, form.elements.email.closest('.mcl-q'));
			var opt = form.querySelector('[data-req=option]');
			if (opt && !form.querySelector('[name=option]:checked')) return fail(T.err_choose, opt);

			var label = btn.querySelector('span');
			var old = label.textContent;
			label.textContent = T.sending;
			btn.disabled = true;
			fetch(O.ajax, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
				.then(function (r) { return r.json().catch(function () { return { success: false }; }); })
				.then(function (res) {
					if (res && res.success) {
						var box = form.parentNode.querySelector('.mcl-order__thanks');
						box.querySelector('.mcl-thanks__ref').textContent = res.data.ref;
						form.hidden = true;
						box.hidden = false;
						box.scrollIntoView({ behavior: 'smooth', block: 'center' });
					} else {
						fail((res && res.data && res.data.message) || T.err_server);
					}
				})
				.catch(function () { fail(T.err_server); })
				.then(function () { label.textContent = old; btn.disabled = false; });
		});
	});
})();
