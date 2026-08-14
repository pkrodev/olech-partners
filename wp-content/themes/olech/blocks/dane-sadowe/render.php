<?php
/**
 * Zakres obsługi (model pracy, sekcja 6.3) + sądy właściwe (sekcja 8.1 pkt 3, 5).
 */

return function () {
	$post_id     = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$nazwa       = get_the_title( $post_id );
	$miejscownik = get_field( 'nazwa_miejscownik', $post_id ) ?: ( 'w ' . $nazwa );
	$okregowy    = get_field( 'unikalne_sad_okregowy', $post_id );
	$rejonowy    = get_field( 'unikalne_sad_rejonowy', $post_id );

	ob_start();
	?>
	<div class="olech-sekcja olech-zakres-obslugi">
		<h2>Zakres obsługi <?php echo esc_html( $miejscownik ); ?></h2>
		<p>Bazę operacyjną mamy w Radomiu oraz każdym mieście wojewódzkim. Sprawy <?php echo esc_html( $miejscownik ); ?> prowadzimy dojazdowo — start działań zwykle w ciągu 24 h od potwierdzenia zlecenia.</p>
	</div>
	<div class="olech-sekcja olech-sady">
		<h2>Sądy właściwe dla <?php echo esc_html( $miejscownik ); ?></h2>
		<?php if ( $okregowy || $rejonowy ) : ?>
			<ul>
				<?php if ( $okregowy ) : ?><li><strong>Sąd okręgowy:</strong> <?php echo esc_html( $okregowy ); ?></li><?php endif; ?>
				<?php if ( $rejonowy ) : ?><li><strong>Sąd rejonowy:</strong> <?php echo esc_html( $rejonowy ); ?></li><?php endif; ?>
			</ul>
		<?php else : ?>
			<p>{{LOREM: dane sądu okręgowego i rejonowego dla tej lokalizacji}}</p>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
};
