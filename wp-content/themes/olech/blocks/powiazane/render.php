<?php
/**
 * Renderuje pole relationship ACF (uslugi_powiazane / poradniki_powiazane)
 * jako listę linków. Atrybuty ustawiane wprost w markupie szablonu, np.:
 * <!-- wp:olech/powiazane {"pole":"uslugi_powiazane","etykieta":"Usługi powiązane"} /-->
 */

return function ( $attributes ) {
	$post_id  = get_the_ID();
	$pole     = $attributes['pole'] ?? '';
	$etykieta = $attributes['etykieta'] ?? 'Powiązane';

	if ( ! $post_id || ! $pole ) {
		return '';
	}

	$powiazane = get_field( $pole, $post_id );
	if ( ! $powiazane || ! is_array( $powiazane ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="olech-sekcja olech-powiazane">
		<h2><?php echo esc_html( $etykieta ); ?></h2>
		<ul>
			<?php foreach ( $powiazane as $wpis ) :
				$wpis_id = is_object( $wpis ) ? $wpis->ID : $wpis;
				?>
				<li><a href="<?php echo esc_url( get_permalink( $wpis_id ) ); ?>"><?php echo esc_html( get_the_title( $wpis_id ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
};
