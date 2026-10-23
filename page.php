<?php
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="mcl-pagehead">
		<div class="mcl-wrap"><h1 class="mcl-h1"><?php the_title(); ?></h1></div>
	</section>
	<section class="mcl-section mcl-section--tight">
		<div class="mcl-wrap mcl-wrap--narrow mcl-prose"><?php the_content(); ?></div>
	</section>
	<?php
endwhile;
get_footer();
