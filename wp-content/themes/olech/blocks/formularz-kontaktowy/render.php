<?php
/**
 * Formularz kontaktowy — wysyłka obsłużona w inc/formularz.php.
 */

return function () {
	ob_start();
	?>
	<div class="olech-sekcja olech-formularz" id="formularz">
		<h2>Zostaw zgłoszenie</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="olech-formularz__form">
			<?php wp_nonce_field( 'olech_formularz', 'olech_formularz_nonce' ); ?>
			<input type="hidden" name="action" value="olech_formularz" />
			<input type="text" name="www" value="" class="olech-formularz__honeypot" tabindex="-1" autocomplete="off" aria-hidden="true" />

			<label>Imię
				<input type="text" name="imie" required />
			</label>
			<label>Telefon lub e-mail
				<input type="text" name="kontakt" required />
			</label>
			<label>Wiadomość
				<textarea name="wiadomosc" rows="5" required></textarea>
			</label>
			<label class="olech-formularz__zgoda">
				<input type="checkbox" name="zgoda" required />
				Zgadzam się na przetwarzanie danych w celu kontaktu w sprawie zgłoszenia.
			</label>
			<p class="olech-formularz__tajemnica">Zgłoszenie objęte jest tajemnicą zawodową detektywa.</p>
			<button type="submit" class="olech-btn">Wyślij zgłoszenie</button>
			<p class="olech-formularz__czas-odpowiedzi">{{LOREM: deklarowany czas odpowiedzi na zgłoszenie}}</p>
		</form>
	</div>
	<?php
	return ob_get_clean();
};
