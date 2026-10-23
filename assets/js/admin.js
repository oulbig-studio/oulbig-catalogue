/* Musicology — admin : médiathèque pour les liens + onglets des paroles. */
jQuery(function ($) {
	$(document).on('click', '.mcl-pick', function (e) {
		e.preventDefault();
		var target = $('#' + $(this).data('target'));
		var frame = wp.media({ title: 'Choisir un fichier', library: { type: $(this).data('type') }, multiple: false });
		frame.on('select', function () {
			target.val(frame.state().get('selection').first().toJSON().url);
		});
		frame.open();
	});
	$(document).on('click', '.mcl-tabs-admin nav a', function (e) {
		e.preventDefault();
		var box = $(this).closest('.mcl-tabs-admin'), t = $(this).data('tab');
		box.find('nav a').removeClass('is-active');
		$(this).addClass('is-active');
		box.find('.mcl-tab').removeClass('is-active').filter('[data-tab="' + t + '"]').addClass('is-active');
	});
});
