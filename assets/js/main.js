/* Musicology — lecteurs, comptage hit-parade, onglets, menu. */
(function () {
	'use strict';
	var C = window.MCL || {};
	var counted = {};

	function count(post, kind) {
		post = parseInt(post, 10);
		if (!post || !C.ajax) return;
		var key = post + ':' + kind;
		if (counted[key]) return;
		counted[key] = true;
		var fd = new FormData();
		fd.append('action', 'mcl_play');
		fd.append('nonce', C.nonce);
		fd.append('post', post);
		fd.append('kind', kind);
		fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
	}

	function fmt(s) {
		if (!isFinite(s) || s < 0) return '--:--';
		s = Math.floor(s);
		return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2);
	}

	/* ---------- Lecteur audio maison ---------- */
	var players = [];
	document.querySelectorAll('.mcl-player').forEach(function (el) {
		var a = el.querySelector('audio');
		var btn = el.querySelector('.mcl-player__btn');
		var bar = el.querySelector('.mcl-player__bar');
		var fill = el.querySelector('.mcl-player__fill');
		var buf = el.querySelector('.mcl-player__buffer');
		var cur = el.querySelector('.mcl-cur');
		var dur = el.querySelector('.mcl-dur');
		if (!a || !btn) return;
		players.push(a);

		btn.addEventListener('click', function () {
			if (a.paused) {
				players.forEach(function (o) { if (o !== a) o.pause(); });
				document.querySelectorAll('video').forEach(function (v) { v.pause(); });
				a.play();
			} else {
				a.pause();
			}
		});
		a.addEventListener('play', function () { el.classList.add('is-playing'); count(el.dataset.post, el.dataset.kind || 'audio'); });
		a.addEventListener('pause', function () { el.classList.remove('is-playing'); });
		a.addEventListener('ended', function () { el.classList.remove('is-playing'); });
		a.addEventListener('loadedmetadata', function () { dur.textContent = fmt(a.duration); });
		a.addEventListener('timeupdate', function () {
			var p = a.duration ? (a.currentTime / a.duration) * 100 : 0;
			fill.style.width = p + '%';
			bar.setAttribute('aria-valuenow', Math.round(p));
			cur.textContent = fmt(a.currentTime);
		});
		a.addEventListener('progress', function () {
			try { if (a.buffered.length && a.duration) buf.style.width = (a.buffered.end(a.buffered.length - 1) / a.duration) * 100 + '%'; } catch (e) {}
		});
		function seek(clientX) {
			var r = bar.getBoundingClientRect();
			var x = (clientX - r.left) / r.width;
			if (document.documentElement.dir === 'rtl') x = 1 - x;
			if (a.duration) a.currentTime = Math.max(0, Math.min(1, x)) * a.duration;
		}
		bar.addEventListener('click', function (e) { seek(e.clientX); });
		bar.addEventListener('keydown', function (e) {
			if (!a.duration) return;
			if (e.key === 'ArrowRight') a.currentTime = Math.min(a.duration, a.currentTime + 5);
			if (e.key === 'ArrowLeft') a.currentTime = Math.max(0, a.currentTime - 5);
		});
	});

	/* ---------- Vidéos directes ---------- */
	document.querySelectorAll('.mcl-embed video').forEach(function (v) {
		var wrap = v.closest('.mcl-embed');
		v.addEventListener('play', function () {
			players.forEach(function (o) { o.pause(); });
			count(wrap.dataset.post, wrap.dataset.kind || 'video');
		});
	});

	/* ---------- Embeds au clic (YouTube, Vimeo…) ---------- */
	document.querySelectorAll('.mcl-embed--lazy').forEach(function (wrap) {
		var btn = wrap.querySelector('.mcl-embed__poster');
		if (!btn) return;
		btn.addEventListener('click', function () {
			var f = document.createElement('iframe');
			f.src = wrap.dataset.src;
			f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
			f.allowFullscreen = true;
			f.setAttribute('frameborder', '0');
			wrap.innerHTML = '';
			wrap.appendChild(f);
			wrap.classList.add('is-loaded');
			players.forEach(function (o) { o.pause(); });
			count(wrap.dataset.post, wrap.dataset.kind || 'video');
		});
	});

	/* ---------- Autres iframes : on compte quand le visiteur clique dedans ---------- */
	window.addEventListener('blur', function () {
		setTimeout(function () {
			var f = document.activeElement;
			if (f && f.tagName === 'IFRAME') {
				var w = f.closest('.mcl-embed');
				if (w && w.dataset.post) count(w.dataset.post, w.dataset.kind || 'video');
			}
		}, 0);
	});
	document.querySelectorAll('.mcl-countlink').forEach(function (a) {
		a.addEventListener('click', function () {
			var w = a.closest('.mcl-embed');
			if (w) count(w.dataset.post, w.dataset.kind);
		});
	});

	/* ---------- Onglets des paroles ---------- */
	document.querySelectorAll('.mcl-lyrics').forEach(function (box) {
		box.querySelectorAll('.mcl-tabs button').forEach(function (b) {
			b.addEventListener('click', function () {
				box.querySelectorAll('.mcl-tabs button').forEach(function (x) { x.classList.toggle('is-active', x === b); x.setAttribute('aria-selected', x === b); });
				box.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.toggle('is-active', p.dataset.panel === b.dataset.tab); });
			});
		});
	});

	/* ---------- Hit-parade : chansons / clips × période ---------- */
	var ctrl = document.querySelector('.mcl-charts-ctrl');
	if (ctrl) {
		var state = { kind: 'audio', period: 'all' };
		ctrl.querySelectorAll('[data-group] button').forEach(function (b) {
			b.addEventListener('click', function () {
				var g = b.parentNode.dataset.group;
				state[g] = b.dataset.val;
				b.parentNode.querySelectorAll('button').forEach(function (x) { x.classList.toggle('is-active', x === b); });
				document.querySelectorAll('.mcl-charts__panel').forEach(function (p) {
					p.classList.toggle('is-active', p.dataset.kind === state.kind && p.dataset.period === state.period);
				});
			});
		});
	}

	/* ---------- Copier le lien d'un article ---------- */
	document.querySelectorAll('.mcl-copy').forEach(function (b) {
		b.addEventListener('click', function () {
			var done = function () { var t = b.textContent; b.textContent = b.dataset.done; setTimeout(function () { b.textContent = t; }, 1800); };
			if (navigator.clipboard) { navigator.clipboard.writeText(b.dataset.url).then(done, done); } else { done(); }
		});
	});

	/* ---------- Menu mobile + en-tête ---------- */
	var burger = document.querySelector('.mcl-burger');
	if (burger) {
		burger.addEventListener('click', function () {
			var open = document.body.classList.toggle('mcl-nav-open');
			burger.setAttribute('aria-expanded', open);
		});
	}
	var header = document.querySelector('.mcl-header');
	function onScroll() { if (header) header.classList.toggle('is-scrolled', window.scrollY > 20); }
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();

	/* ---------- Apparition au défilement ---------- */
	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
		}, { threshold: 0.15 });
		document.querySelectorAll('.mcl-drop, .mcl-steps li, .mcl-card, .mcl-chart__row, .mcl-svc, .mcl-post, .mcl-process__list li').forEach(function (el) { el.classList.add('mcl-reveal'); io.observe(el); });
	}
})();
