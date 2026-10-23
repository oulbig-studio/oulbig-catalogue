<?php
if ( is_category() || is_tag() || is_date() || is_author() ) {
	require __DIR__ . '/home.php';
	return;
}
require __DIR__ . '/index.php';
