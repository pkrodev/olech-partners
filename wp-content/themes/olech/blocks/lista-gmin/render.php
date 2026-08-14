<?php
/**
 * Lista gmin — pole ACF unikalne_gminy to textarea, jedna gmina na linię
 * (darmowy ACF, bez Repeatera — patrz komentarz w inc/acf-pola.php).
 */

return function () {
	$post_id = get_the_ID();
	$raw     = $post_id ? get_field( 'unikalne_gminy', $post_id ) : '';
	$gminy   = array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) );

	ob_start();
	?>
	<div class="olech-sekcja olech-gminy">
		<h2>Obszar obsługi</h2>
		<?php if ( $gminy ) : ?>
			<ul class="olech-gminy__lista">
				<?php foreach ( $gminy as $gmina ) : ?>
					<li><?php echo esc_html( $gmina ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p>{{LOREM: lista min. 3 obsługiwanych gmin}}</p>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
};
