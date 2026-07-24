<?php
/**
 * Plugin Name: Trivia Nationals – Event Schedule Manager
 * Description: Admin editor for homepage event schedule — descriptions, titles, times, and tags. Includes a Schedule Mode toggle that shows times on the public site.
 * Version: 2.0
 * Author: Trivia Nationals
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'template_redirect', function () {
	if ( is_admin() ) return;
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( strtolower( $path ) !== 'tickets' ) return;
	wp_safe_redirect( home_url( '/#tickets' ), 301 );
	exit;
}, 1 );

// ─── WooCommerce page header & lighter styling ───────────────────────────────

add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_woocommerce' ) ) return;
	if ( ! ( is_cart() || is_checkout() || is_account_page() || is_woocommerce() ) ) return;
	?>
	<style id="tn-ticket-flow-css">
	@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap');
	body.single-product,
	body.woocommerce-page,
	body.woocommerce-cart,
	body.woocommerce-checkout {
		--tn-bg: #070812;
		--tn-panel: #111525;
		--tn-text: #f7f8ff;
		--tn-muted: #cdd4ea;
		--tn-cyan: #00e5ff;
		--tn-pink: #ff2d95;
		--tn-gold: #ffd166;
		--tn-line: rgba(255,255,255,0.12);
		background:
			radial-gradient(circle at 18% 14%, rgba(0,229,255,0.16), transparent 28rem),
			radial-gradient(circle at 82% 18%, rgba(255,45,149,0.13), transparent 30rem),
			linear-gradient(180deg, rgba(7,8,18,0.3), var(--tn-bg) 60%),
			var(--tn-bg) !important;
		color: var(--tn-text) !important;
		font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
		letter-spacing: 0;
	}
	body.woocommerce-page::before,
	body.woocommerce-cart::before,
	body.woocommerce-checkout::before { display: none !important; }
	.tn-site-header {
		display: block;
		position: sticky;
		top: 0;
		z-index: 1000;
		width: 100%;
		padding: 1.05rem clamp(1rem, 4vw, 2rem);
		background: rgba(7,8,18,0.86);
		border-bottom: 1px solid var(--tn-line);
		backdrop-filter: blur(14px);
		color: var(--tn-text);
		text-decoration: none;
		font-family: Outfit, sans-serif;
		font-weight: 900;
		font-size: 0.9rem;
		letter-spacing: 0.1em;
		text-transform: uppercase;
		transition: opacity 0.2s;
	}
	.tn-site-header:hover { opacity: 0.8; }
	body.single-product .navbar,
	body.woocommerce-cart .navbar,
	body.woocommerce-checkout .navbar,
	body.single-product .inner-main-title,
	body.woocommerce-cart .inner-main-title,
	body.woocommerce-checkout .inner-main-title,
	body.single-product .page-title,
	body.woocommerce-cart .page-title,
	body.woocommerce-checkout .page-title { display: none !important; }
	body.single-product .site-content,
	body.single-product .content-area,
	body.single-product .site-main,
	body.single-product #main,
	body.woocommerce-cart .site-content,
	body.woocommerce-checkout .site-content {
		background: transparent !important;
	}
	body.single-product div.product,
	body.woocommerce-cart .woocommerce,
	body.woocommerce-checkout .woocommerce {
		width: min(1160px, calc(100% - 2rem)) !important;
		max-width: none !important;
		margin: 0 auto !important;
		padding: clamp(2rem, 5vw, 4rem) 0 clamp(3rem, 6vw, 5rem) !important;
	}
	body.single-product div.product {
		display: grid !important;
		grid-template-columns: minmax(0, 1fr) minmax(330px, 430px);
		gap: clamp(1.2rem, 4vw, 3rem);
		align-items: start;
	}
	body.single-product div.product .woocommerce-product-gallery,
	body.single-product div.product .images {
		width: auto !important;
		float: none !important;
		margin: 0 !important;
	}
	body.single-product div.product .summary {
		float: none !important;
		width: auto !important;
		margin: 0 !important;
	}
	body.single-product div.product .images,
	body.single-product div.product .summary,
	body.woocommerce-cart table.shop_table,
	body.woocommerce-cart .cart_totals,
	body.woocommerce-checkout form.checkout .col2-set,
	body.woocommerce-checkout #order_review,
	body.woocommerce-checkout .woocommerce-checkout-payment,
	body.woocommerce-checkout table.shop_table {
		border: 1px solid var(--tn-line) !important;
		border-radius: 8px !important;
		background:
			linear-gradient(180deg, rgba(255,255,255,0.055), rgba(17,21,37,0.86)) !important;
		box-shadow: 0 18px 70px rgba(0,0,0,0.28) !important;
	}
	body.single-product div.product .summary {
		padding: clamp(1.25rem, 3vw, 2rem) !important;
	}
	body.single-product .product_title {
		margin: 0 0 0.8rem !important;
		color: var(--tn-text) !important;
		font-family: Outfit, sans-serif !important;
		font-size: clamp(3rem, 6vw, 6.6rem) !important;
		line-height: 0.9 !important;
		font-weight: 900 !important;
		text-transform: uppercase !important;
		letter-spacing: 0 !important;
		-webkit-text-fill-color: var(--tn-text) !important;
	}
	body.single-product p.price,
	body.single-product .price,
	body.single-product .woocommerce-Price-amount {
		color: var(--tn-cyan) !important;
		font-family: Outfit, sans-serif !important;
		font-size: clamp(2rem, 4vw, 3.3rem) !important;
		font-weight: 900 !important;
		line-height: 1 !important;
	}
	body.single-product .elementor-widget-woocommerce-product-price,
	body.single-product p.price,
	body.single-product .price,
	body.single-product .woocommerce-Price-amount,
	body.single-product .woocommerce-Price-amount bdi {
		width: auto !important;
		max-width: none !important;
		min-width: max-content !important;
		white-space: nowrap !important;
		word-break: keep-all !important;
		overflow-wrap: normal !important;
		hyphens: none !important;
	}
	body.single-product p.price {
		display: inline-flex !important;
		align-items: baseline !important;
	}
	body.single-product .woocommerce-product-details__short-description,
	body.single-product .woocommerce-product-details__short-description p {
		color: #dfe4f5 !important;
		font-size: clamp(1.1rem, 2vw, 1.35rem) !important;
		line-height: 1.5 !important;
		font-weight: 700 !important;
	}
	body.single-product .wapf-field-container,
	body.single-product .cart {
		margin-top: 1rem !important;
	}
	body.single-product .wapf-field-label,
	body.single-product .wapf-field label,
	body.woocommerce-checkout label,
	body.woocommerce-cart table.shop_table th,
	body.woocommerce-checkout table.shop_table th {
		color: var(--tn-muted) !important;
		font-family: Outfit, sans-serif !important;
		font-size: 0.7rem !important;
		font-weight: 900 !important;
		letter-spacing: 0.1em !important;
		text-transform: uppercase !important;
	}
	body.single-product input,
	body.single-product select,
	body.single-product textarea,
	body.woocommerce-cart input,
	body.woocommerce-checkout input,
	body.woocommerce-checkout select,
	body.woocommerce-checkout textarea,
	body.woocommerce-checkout .select2-container--default .select2-selection--single {
		background: rgba(7,8,18,0.72) !important;
		border: 1px solid rgba(255,255,255,0.14) !important;
		border-radius: 8px !important;
		color: var(--tn-text) !important;
		min-height: 44px !important;
	}
	body.single-product input:focus,
	body.single-product textarea:focus,
	body.woocommerce-cart input:focus,
	body.woocommerce-checkout input:focus,
	body.woocommerce-checkout select:focus,
	body.woocommerce-checkout textarea:focus {
		border-color: rgba(0,229,255,0.48) !important;
		box-shadow: 0 0 0 3px rgba(0,229,255,0.1) !important;
		outline: none !important;
	}
	body.single-product .single_add_to_cart_button,
	body.woocommerce-cart a.checkout-button,
	body.woocommerce-checkout #place_order,
	body.woocommerce a.button,
	body.woocommerce button.button,
	body.woocommerce input.button {
		border: 0 !important;
		border-radius: 999px !important;
		background: linear-gradient(135deg, var(--tn-cyan), var(--tn-pink)) !important;
		color: #fff !important;
		font-family: Outfit, sans-serif !important;
		font-size: 0.82rem !important;
		font-weight: 900 !important;
		letter-spacing: 0.08em !important;
		text-transform: uppercase !important;
		padding: 0.85rem 1.2rem !important;
		box-shadow: 0 16px 42px rgba(0,229,255,0.18), 0 10px 32px rgba(255,45,149,0.15) !important;
	}
	body.single-product .quantity input.qty,
	body.woocommerce-cart .quantity input.qty {
		width: 70px !important;
		text-align: center !important;
		font-family: Outfit, sans-serif !important;
		font-weight: 900 !important;
	}
	body.woocommerce-cart .coupon #coupon_code,
	body.woocommerce-cart .coupon input[name="coupon_code"] {
		width: clamp(10.5rem, 18vw, 13rem) !important;
		min-width: 10.5rem !important;
		font-size: 0.78rem !important;
	}
	body.woocommerce-cart .woocommerce::before,
	body.woocommerce-checkout .woocommerce::before {
		display: block;
		margin: 0 0 clamp(1.25rem, 3vw, 2rem);
		color: var(--tn-text);
		font-family: Outfit, sans-serif;
		font-size: clamp(3rem, 8vw, 6rem);
		line-height: 0.9;
		font-weight: 900;
		letter-spacing: 0;
		text-transform: uppercase;
	}
	body.woocommerce-cart .woocommerce::before { content: 'Your Cart'; }
	body.woocommerce-checkout .woocommerce::before { content: 'Checkout'; }
	body.woocommerce-cart table.shop_table,
	body.woocommerce-checkout table.shop_table {
		border-collapse: separate !important;
		border-spacing: 0 !important;
		overflow: hidden !important;
	}
	body.woocommerce-cart table.shop_table td,
	body.woocommerce-checkout table.shop_table td,
	body.woocommerce-cart table.shop_table th,
	body.woocommerce-checkout table.shop_table th {
		background: transparent !important;
		border-color: rgba(255,255,255,0.09) !important;
		color: var(--tn-text) !important;
		padding: 1rem !important;
	}
	body.woocommerce-cart .cart_totals,
	body.woocommerce-checkout form.checkout .col2-set,
	body.woocommerce-checkout #order_review,
	body.woocommerce-checkout .woocommerce-checkout-payment {
		padding: clamp(1.2rem, 2.5vw, 1.8rem) !important;
	}
	body.woocommerce-cart .cart_totals h2,
	body.woocommerce-checkout h3 {
		color: var(--tn-text) !important;
		font-family: Outfit, sans-serif !important;
		font-weight: 900 !important;
		line-height: 1 !important;
	}
	body.woocommerce-page .woocommerce-message,
	body.woocommerce-page .woocommerce-info,
	body.woocommerce-page .woocommerce-error,
	body.single-product .woocommerce-message {
		border: 1px solid rgba(0,229,255,0.2) !important;
		border-top: 1px solid rgba(0,229,255,0.45) !important;
		border-radius: 8px !important;
		background: rgba(17,21,37,0.9) !important;
		color: var(--tn-text) !important;
	}
	body.single-product .product_meta,
	body.single-product .related,
	body.single-product .upsells,
	body.single-product .woocommerce-tabs { display: none !important; }
	body.woocommerce-page .shop_table,
	body.woocommerce-page .cart-collaterals,
	body.woocommerce-page .woocommerce-checkout-review-order-table {
		background: #1a1a2e !important; border-color: rgba(255,255,255,0.08) !important;
		border-radius: 12px; overflow: hidden;
	}
	body.woocommerce-page table.shop_table th {
		background: #12121f !important; color: #8888a0 !important;
		font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase;
	}
	body.woocommerce-page table.shop_table td {
		border-color: rgba(255,255,255,0.06) !important;
		color: #f0f0f5 !important; background: transparent !important;
	}
	body.woocommerce-page .cart-collaterals .cart_totals,
	body.woocommerce-page .woocommerce-checkout-payment {
		background: #1a1a2e !important; border: 1px solid rgba(255,255,255,0.08) !important;
		border-radius: 12px !important; padding: 1.5rem !important;
	}
	body.woocommerce-page .woocommerce-cart-form__contents .product-name a { color: #00e5ff !important; }
	body.woocommerce-page h1, body.woocommerce-page h2, body.woocommerce-page h3 { color: #f0f0f5 !important; }
	body.woocommerce-page .wc-block-components-notice-banner,
	body.woocommerce-page .woocommerce-info {
		background: rgba(0,229,255,0.08) !important; border-color: #00e5ff !important; color: #f0f0f5 !important;
	}
	body.single-product,
	body.single-product p,
	body.single-product li,
	body.woocommerce-page,
	body.woocommerce-page p,
	body.woocommerce-page li,
	body.woocommerce-page td,
	body.woocommerce-page address,
	body.woocommerce-page .woocommerce-billing-fields,
	body.woocommerce-page .woocommerce-shipping-fields,
	body.woocommerce-page .woocommerce-additional-fields,
	body.woocommerce-page .woocommerce-privacy-policy-text {
		color: #e6eaff !important;
	}
	body.single-product .wapf-field-label,
	body.single-product .wapf-field label,
	body.single-product .wapf-field-description,
	body.woocommerce-page label,
	body.woocommerce-page table.shop_table th,
	body.woocommerce-page .woocommerce-info,
	body.woocommerce-page .woocommerce-message,
	body.woocommerce-page .woocommerce-error,
	body.woocommerce-page .wc-gift-card-entry label,
	body.woocommerce-page .optional,
	body.woocommerce-page .required {
		color: #cdd4ea !important;
	}
	body.single-product .wapf-field-description,
	body.woocommerce-page .woocommerce-privacy-policy-text,
	body.woocommerce-page .woocommerce-terms-and-conditions-wrapper,
	body.woocommerce-page .woocommerce-form__label,
	body.woocommerce-page .woocommerce-checkout-payment,
	body.woocommerce-page .payment_box,
	body.woocommerce-page .product-name,
	body.woocommerce-page .product-total {
		color: #dfe4f5 !important;
	}
	body.woocommerce-page a,
	body.single-product a {
		color: #5eeaff !important;
	}
	@media (max-width: 860px) {
		body.single-product div.product {
			display: block !important;
		}
		body.single-product div.product .summary {
			margin-top: 1rem !important;
		}
		body.single-product .product_title,
		body.woocommerce-cart .woocommerce::before,
		body.woocommerce-checkout .woocommerce::before {
			font-size: clamp(2.6rem, 14vw, 4.5rem) !important;
		}
		body.woocommerce-cart .woocommerce,
		body.woocommerce-checkout .woocommerce,
		body.single-product div.product {
			width: min(100% - 1.25rem, 1160px) !important;
		}
		body.woocommerce-cart .actions,
		body.woocommerce-cart .coupon {
			display: grid !important;
			grid-template-columns: 1fr !important;
		}
	}
	@media (max-width: 520px) {
		body.woocommerce-checkout .woocommerce::before {
			font-size: clamp(2.25rem, 11.5vw, 3.4rem) !important;
			white-space: nowrap !important;
		}
		body.woocommerce-cart .woocommerce::before {
			font-size: clamp(2.45rem, 13vw, 3.75rem) !important;
		}
		body.single-product .product_title {
			font-size: clamp(2.45rem, 13vw, 3.85rem) !important;
		}
		body.single-product div.product,
		body.woocommerce-cart .woocommerce,
		body.woocommerce-checkout .woocommerce {
			padding-top: 1.6rem !important;
		}
		body.single-product div.product .summary,
		body.woocommerce-cart .cart_totals,
		body.woocommerce-checkout form.checkout .col2-set,
		body.woocommerce-checkout #order_review,
		body.woocommerce-checkout .woocommerce-checkout-payment {
			padding: 1.2rem !important;
		}
	}
	</style>
	<?php
} );

add_action( 'wp_body_open', function () {
	if ( ! function_exists( 'is_woocommerce' ) ) return;
	if ( ! ( is_cart() || is_checkout() || is_account_page() || is_woocommerce() ) ) return;
	echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="tn-site-header">Trivia Nationals 2026</a>';
} );

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
	?>
	<script>
	(function(){
		var cartUrl = <?php echo wp_json_encode( esc_url_raw( $cart_url ) ); ?>;
		function addCartLinks() {
			document.querySelectorAll('.tn-event-nav-links, nav .nav-links').forEach(function(nav) {
				if (nav.querySelector('a[href*="/cart"], .tn-nav-cart-link')) return;
				var link = document.createElement('a');
				link.className = 'tn-nav-cart-link';
				link.href = cartUrl;
				link.textContent = 'Cart';
				if (nav.tagName === 'UL') {
					var item = document.createElement('li');
					item.appendChild(link);
					nav.appendChild(item);
				} else {
					nav.appendChild(link);
				}
			});
		}
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', addCartLinks);
		else addCartLinks();
	})();
	</script>
	<?php
}, 19 );

// ─── Front-end/Admin: Venue YouTube links ───────────────────────────────────

function tn_tde_default_venue_videos() {
	return [
		[
			'title'       => 'South Point Hotel, Casino & Spa full tour',
			'url'         => 'https://www.youtube.com/watch?v=Pr2rB3VoXfs',
			'description' => 'A broad walkthrough of the resort, casino, restaurants, pool, and guest areas.',
		],
		[
			'title'       => 'South Point room tour',
			'url'         => 'https://www.youtube.com/watch?v=HPPgV61EPbM',
			'description' => 'A closer look at a South Point guest room for attendees planning their stay.',
		],
	];
}

/**
 * Add a direct electronic-ticket link immediately above the homepage countdown.
 * The homepage itself is Elementor-managed, so this is inserted at runtime to
 * keep the destination and presentation under plugin control.
 */
add_action( 'wp_footer', function() {
	if ( ! is_front_page() ) return;
	?>
	<style id="tn-home-ticket-link-styles">
		body.home .countdown-section {
			align-items: center;
			flex-direction: column;
			gap: 1.15rem;
		}
		body.home .tn-home-ticket-link {
			align-items: center;
			background: linear-gradient(135deg, var(--cyan, #00e6ff), #00c9db);
			border-radius: 999px;
			box-shadow: 0 0 30px rgba(0, 230, 255, 0.3);
			color: var(--bg-dark, #071019) !important;
			display: inline-flex;
			font-family: var(--font-display, Outfit, Inter, sans-serif);
			font-size: 0.88rem;
			font-weight: 800;
			gap: 0.55rem;
			justify-content: center;
			letter-spacing: 0.08em;
			padding: 0.82rem 1.5rem;
			text-decoration: none !important;
			text-transform: uppercase;
			transition: transform 0.2s, box-shadow 0.3s;
		}
		body.home .tn-home-ticket-link:hover,
		body.home .tn-home-ticket-link:focus-visible {
			box-shadow: 0 0 50px rgba(0, 230, 255, 0.5);
			transform: translateY(-2px);
		}
	</style>
	<script id="tn-home-ticket-link-script">
	(function() {
		var section = document.querySelector('.countdown-section');
		if (!section || section.querySelector('.tn-home-ticket-link')) return;
		var link = document.createElement('a');
		link.className = 'tn-home-ticket-link';
		link.href = <?php echo wp_json_encode( home_url( '/my-tickets/' ) ); ?>;
		link.textContent = 'View My Tickets →';
		section.insertBefore(link, section.firstChild);
	})();
	</script>
	<?php
}, 30 );

function tn_tde_clean_venue_videos( $videos ) {
	if ( ! is_array( $videos ) ) return [];
	$clean = [];
	foreach ( $videos as $video ) {
		$title = sanitize_text_field( $video['title'] ?? '' );
		$url   = esc_url_raw( $video['url'] ?? '' );
		$desc  = sanitize_text_field( $video['description'] ?? '' );
		if ( $title === '' && $url === '' && $desc === '' ) continue;
		if ( $url === '' || ! preg_match( '#^https?://(www\.)?(youtube\.com|youtu\.be)/#i', $url ) ) continue;
		$clean[] = [
			'title'       => $title ?: 'Venue video',
			'url'         => $url,
			'description' => $desc,
		];
	}
	return $clean;
}

function tn_tde_get_venue_videos() {
	$videos = get_option( 'tn_venue_videos', null );
	if ( $videos === null || $videos === false ) {
		return tn_tde_default_venue_videos();
	}
	return tn_tde_clean_venue_videos( $videos );
}

function tn_tde_youtube_id_from_url( $url ) {
	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) ) return '';
	$host = strtolower( preg_replace( '/^www\./', '', $parts['host'] ) );
	if ( $host === 'youtu.be' ) {
		return trim( $parts['path'] ?? '', '/' );
	}
	if ( $host === 'youtube.com' ) {
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			if ( ! empty( $query['v'] ) ) return sanitize_text_field( $query['v'] );
		}
		if ( preg_match( '#/(embed|shorts)/([^/?]+)#', $parts['path'] ?? '', $match ) ) {
			return sanitize_text_field( $match[2] );
		}
	}
	return '';
}

add_action( 'wp_head', function () {
	if ( is_admin() || ! ( is_front_page() || is_page( 5 ) ) ) return;
	if ( empty( tn_tde_get_venue_videos() ) ) return;
	?>
	<style id="tn-venue-videos-css">
	body.home .venue-map {
		aspect-ratio: auto !important;
		display: block !important;
		overflow: visible !important;
		border: 0 !important;
		background: transparent !important;
	}
	body.home .venue-map iframe {
		display: block !important;
		width: 100% !important;
		height: auto !important;
		min-height: 320px !important;
		aspect-ratio: 16 / 10 !important;
		border: 1px solid rgba(255,255,255,0.08) !important;
		border-radius: 16px !important;
		background: var(--bg-card) !important;
	}
	body.home .tn-venue-videos {
		grid-column: span 2;
		margin-top: 0.25rem;
		margin-bottom: 2.5rem;
		padding: 1rem;
		border: 1px solid rgba(255,255,255,0.08);
		border-radius: 16px;
		background: linear-gradient(180deg, rgba(255,255,255,0.045), rgba(17,21,37,0.82));
		box-shadow: 0 18px 50px rgba(0,0,0,0.22);
	}
	body.home .tn-venue-videos-head {
		display: flex;
		align-items: baseline;
		justify-content: space-between;
		gap: 1rem;
		margin-bottom: 0.8rem;
	}
	body.home .tn-venue-videos-kicker {
		margin: 0;
		color: var(--cyan);
		font-family: var(--font-display);
		font-size: 0.72rem;
		font-weight: 800;
		letter-spacing: 0.1em;
		text-transform: uppercase;
	}
	body.home .tn-venue-videos-count {
		color: var(--gray);
		font-size: 0.78rem;
		white-space: nowrap;
	}
	body.home .tn-venue-video-list {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 0.75rem;
	}
	body.home .tn-venue-video-card {
		display: grid;
		grid-template-columns: 118px minmax(0, 1fr);
		gap: 0.85rem;
		align-items: center;
		padding: 0.65rem;
		border: 1px solid rgba(255,255,255,0.08);
		border-radius: 12px;
		background: rgba(7,8,18,0.48);
		color: inherit;
		text-decoration: none !important;
		transition: border-color 0.18s ease, transform 0.18s ease;
	}
	body.home .tn-venue-video-card:hover {
		border-color: rgba(0,229,255,0.42);
		transform: translateY(-1px);
	}
	body.home .tn-venue-video-thumb {
		position: relative;
		aspect-ratio: 16 / 9;
		overflow: hidden;
		border-radius: 8px;
		background: rgba(255,255,255,0.08);
	}
	body.home .tn-venue-video-thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}
	body.home .tn-venue-video-play {
		position: absolute;
		inset: 50% auto auto 50%;
		display: grid;
		place-items: center;
		width: 2.1rem;
		height: 2.1rem;
		border-radius: 999px;
		background: rgba(255,45,149,0.92);
		color: #fff;
		font-size: 0.82rem;
		transform: translate(-50%, -50%);
	}
	body.home .tn-venue-video-title {
		display: block;
		color: var(--white);
		font-family: var(--font-display);
		font-size: 0.95rem;
		font-weight: 800;
		line-height: 1.15;
	}
	body.home .tn-venue-video-desc {
		display: block;
		margin-top: 0.25rem;
		color: var(--gray);
		font-size: 0.8rem;
		line-height: 1.35;
	}
	body.home .tn-venue-video-cta {
		display: block;
		margin-top: 0.45rem;
		color: var(--cyan);
		font-family: var(--font-display);
		font-size: 0.7rem;
		font-weight: 800;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	@media (max-width: 640px) {
		body.home .tn-venue-videos {
			grid-column: auto;
			margin-bottom: 2rem;
		}
		body.home .tn-venue-video-list {
			grid-template-columns: 1fr;
		}
		body.home .tn-venue-video-card {
			grid-template-columns: 96px minmax(0, 1fr);
		}
		body.home .tn-venue-videos-head {
			display: block;
		}
		body.home .tn-venue-videos-count {
			display: block;
			margin-top: 0.25rem;
		}
	}
	</style>
	<?php
} );

add_action( 'wp_footer', function () {
	if ( is_admin() || ! ( is_front_page() || is_page( 5 ) ) ) return;
	$videos = array_map( function( $video ) {
		$id = tn_tde_youtube_id_from_url( $video['url'] );
		return [
			'title'       => $video['title'],
			'url'         => $video['url'],
			'description' => $video['description'],
			'thumb'       => $id ? 'https://img.youtube.com/vi/' . rawurlencode( $id ) . '/hqdefault.jpg' : '',
		];
	}, tn_tde_get_venue_videos() );
	if ( empty( $videos ) ) return;
	?>
	<script>
	(function(){
		var videos = <?php echo wp_json_encode( $videos ); ?>;
		function esc(value) {
			return String(value || '').replace(/[&<>"']/g, function(ch) {
				return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[ch];
			});
		}
		function insertVenueVideos() {
			var map = document.querySelector('#venue .venue-map');
			if (!map || map.querySelector('.tn-venue-videos') || !videos.length) return;
			var panel = document.createElement('aside');
			panel.className = 'tn-venue-videos';
			panel.setAttribute('aria-label', 'South Point hotel videos');
			panel.innerHTML =
				'<div class="tn-venue-videos-head">' +
					'<p class="tn-venue-videos-kicker">Hotel video guides</p>' +
					'<span class="tn-venue-videos-count">' + videos.length + ' video' + (videos.length === 1 ? '' : 's') + '</span>' +
				'</div>' +
				'<div class="tn-venue-video-list">' +
					videos.map(function(video) {
						return '<a class="tn-venue-video-card" href="' + esc(video.url) + '" target="_blank" rel="noopener">' +
							'<span class="tn-venue-video-thumb">' +
								(video.thumb ? '<img src="' + esc(video.thumb) + '" alt="">' : '') +
								'<span class="tn-venue-video-play">▶</span>' +
							'</span>' +
							'<span>' +
								'<strong class="tn-venue-video-title">' + esc(video.title) + '</strong>' +
								(video.description ? '<span class="tn-venue-video-desc">' + esc(video.description) + '</span>' : '') +
								'<span class="tn-venue-video-cta">Watch on YouTube</span>' +
							'</span>' +
						'</a>';
					}).join('') +
				'</div>';
			var grid = map.parentElement;
			while (grid && !grid.classList.contains('venue-grid')) {
				grid = grid.parentElement;
			}
			if (grid) grid.appendChild(panel);
			else map.appendChild(panel);
		}
		function centerVenueMapOnSouthPoint() {
			var iframe = document.querySelector('#venue .venue-map iframe');
			if (!iframe) return;
			var southPointMap = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3226.614!2d-115.1761154!3d36.0119389!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80c8cf67dcce63fd%3A0x1bcf3613f0f7e29b!2sSouth%20Point%20Hotel%20Casino%20%26%20Spa!5e0!3m2!1sen!2sus!4v1680000000000';
			var fresh = iframe.cloneNode(false);
			fresh.src = southPointMap;
			fresh.title = iframe.title || 'Map to South Point Hotel Casino and Spa';
			fresh.loading = 'eager';
			fresh.setAttribute('referrerpolicy', iframe.getAttribute('referrerpolicy') || 'no-referrer-when-downgrade');
			iframe.parentNode.replaceChild(fresh, iframe);
		}
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', insertVenueVideos);
		else insertVenueVideos();
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', centerVenueMapOnSouthPoint);
		else centerVenueMapOnSouthPoint();
	})();
	</script>
	<?php
}, 21 );

add_action( 'wp_footer', function () {
	if ( is_admin() || ! ( is_front_page() || is_page( 5 ) ) ) return;

	$jeopardy_page_id = tn_tde_get_jeopardy_page_id();
	$jeopardy_page = $jeopardy_page_id ? get_post( $jeopardy_page_id ) : null;
	$jeopardy_content = $jeopardy_page ? tn_tde_render_page_body_content( $jeopardy_page ) : '';
	$how_it_works_page_id = tn_tde_get_how_it_works_page_id();
	$how_it_works_page = $how_it_works_page_id ? get_post( $how_it_works_page_id ) : null;
	$how_it_works_content = $how_it_works_page ? tn_tde_render_page_body_content( $how_it_works_page ) : '';
	$quotes = tn_tde_get_homepage_quotes();
	$faqs = tn_tde_get_homepage_faqs();
	$jeopardy_video_url = 'https://trivianationals.org/wp-content/uploads/2026/07/tn-jeopardy-preview.mp4';
$jeopardy_video_poster_url = 'https://trivianationals.org/wp-content/uploads/2026/07/tn-jeopardy-preview-poster.jpg';
	?>
	<div id="tn-managed-homepage-sections" hidden>
		<section id="jeopardy" class="tn-managed-section tn-jeopardy-section" aria-labelledby="tn-jeopardy-title">
			<div class="container">
				<p class="section-label">Jeopardy</p>
				<div class="tn-jeopardy-content">
					<div class="tn-jeopardy-main">
						<h2 class="section-title" id="tn-jeopardy-title"><?php echo esc_html( $jeopardy_page ? get_the_title( $jeopardy_page ) : 'Jeopardy at Trivia Nationals' ); ?></h2>
						<div class="tn-jeopardy-copy"><?php echo $jeopardy_content ?: '<p>Jeopardy staff will be onsite throughout Trivia Nationals weekend. More details are coming soon.</p>'; ?></div>
					</div>
					<aside class="tn-jeopardy-video-card" aria-label="Jeopardy preview video">
						<div class="tn-jeopardy-video-frame">
							<video controls playsinline preload="metadata" poster="<?php echo esc_url( $jeopardy_video_poster_url ); ?>">
								<source src="<?php echo esc_url( $jeopardy_video_url ); ?>" type="video/mp4">
							</video>
						</div>
					</aside>
				</div>
			</div>
		</section>
		<section id="how-it-works" class="tn-managed-section tn-how-it-works-section" aria-labelledby="tn-how-it-works-title">
			<div class="container">
				<p class="section-label">How It Works</p>
				<h2 class="section-title" id="tn-how-it-works-title"><?php echo esc_html( $how_it_works_page ? get_the_title( $how_it_works_page ) : 'How It Works' ); ?></h2>
				<div class="tn-how-it-works-content">
					<div class="tn-how-it-works-copy"><?php echo $how_it_works_content ?: '<p>Add an overview of registration, event selection, team formation, and what first-time attendees should expect.</p>'; ?></div>
				</div>
			</div>
		</section>
		<?php if ( $quotes ) : ?>
			<section id="quotes" class="tn-managed-section tn-quotes-section" aria-labelledby="tn-quotes-title">
				<div class="container">
					<p class="section-label">Past Attendees</p>
					<h2 class="section-title" id="tn-quotes-title">What Players Say</h2>
					<div class="tn-quotes-grid">
						<?php foreach ( $quotes as $quote ) : ?>
							<article class="tn-quote-card">
								<blockquote><?php echo esc_html( $quote['quote'] ); ?></blockquote>
								<cite><?php echo esc_html( $quote['credit'] ); ?></cite>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( $faqs ) : ?>
			<section id="faq-section" class="tn-managed-section tn-faq-section" aria-labelledby="tn-faq-title">
				<div class="container">
					<p class="section-label">FAQ</p>
					<h2 class="section-title" id="tn-faq-title">Good Things To Know</h2>
					<div class="tn-faq-list">
						<?php foreach ( $faqs as $faq ) : ?>
							<details class="tn-faq-item">
								<summary class="tn-faq-question"><?php echo esc_html( $faq['question'] ); ?></summary>
								<div class="tn-faq-answer"><?php echo wpautop( wp_kses_post( $faq['answer'] ) ); ?></div>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php
}, 8 );

add_action( 'wp_head', function () {
	if ( is_admin() ) return;
	?>
	<style id="tn-footer-newsletter-css">
	body footer .tn-footer-newsletter {
		display: grid;
		grid-template-columns: minmax(0, 0.9fr) minmax(360px, 1.1fr);
		align-items: center;
		gap: clamp(1.2rem, 4vw, 2.5rem);
		width: min(1080px, calc(100% - 2rem));
		margin: 0 auto 2.25rem;
		padding: clamp(1.15rem, 3vw, 1.65rem);
		border: 1px solid rgba(0,229,255,0.22);
		border-radius: 14px;
		background:
			linear-gradient(135deg, rgba(0,229,255,0.08), rgba(255,45,149,0.055)),
			rgba(12,16,31,0.72);
		box-shadow: 0 18px 54px rgba(0,0,0,0.24);
		text-align: left;
	}
	body footer .tn-footer-newsletter-kicker {
		margin: 0 0 0.4rem;
		color: #00e5ff;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: 0.74rem;
		font-weight: 900;
		letter-spacing: 0.12em;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter h2 {
		margin: 0 0 0.45rem;
		color: #f7f8ff;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: clamp(1.5rem, 3vw, 2.15rem);
		font-weight: 900;
		letter-spacing: 0;
		line-height: 1.02;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter-copy p:last-child {
		margin: 0;
		max-width: 34rem;
		color: #cdd4ea;
		font-size: 0.95rem;
		line-height: 1.55;
	}
	body footer .tn-footer-newsletter .mc4wp-form-fields {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 0.7rem;
	}
	body footer .tn-footer-newsletter .mc4wp-form-fields p {
		margin: 0;
	}
	body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(3),
	body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(4) {
		grid-column: span 2;
	}
	body footer .tn-footer-newsletter label {
		display: grid;
		gap: 0.34rem;
		color: #cdd4ea;
		font-size: 0.74rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter input[type="text"],
	body footer .tn-footer-newsletter input[type="email"] {
		width: 100%;
		min-height: 46px;
		padding: 0.72rem 0.85rem;
		border: 1px solid rgba(255,255,255,0.14);
		border-radius: 8px;
		background: rgba(7,8,18,0.72);
		color: #f7f8ff;
		font: 600 0.95rem Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
	}
	body footer .tn-footer-newsletter input[type="text"]:focus,
	body footer .tn-footer-newsletter input[type="email"]:focus {
		border-color: rgba(0,229,255,0.72);
		box-shadow: 0 0 0 3px rgba(0,229,255,0.12);
		outline: none;
	}
	body footer .tn-footer-newsletter input[type="submit"] {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 46px;
		width: 100%;
		border: 1px solid rgba(255,209,102,0.82);
		border-radius: 999px;
		background: #ffd166;
		color: #071019;
		cursor: pointer;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: 0.82rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter input[type="submit"]:hover {
		background: #ffe08a;
	}
	body footer .tn-footer-newsletter .mc4wp-response {
		grid-column: span 2;
		color: #cdd4ea;
		font-size: 0.9rem;
	}
	@media (max-width: 760px) {
		body footer .tn-footer-newsletter,
		body footer .tn-footer-newsletter .mc4wp-form-fields {
			grid-template-columns: 1fr;
		}
		body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(3),
		body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(4),
		body footer .tn-footer-newsletter .mc4wp-response {
			grid-column: auto;
		}
	}
	</style>
	<?php
}, 18 );

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	$form_markup = shortcode_exists( 'mc4wp_form' ) ? do_shortcode( '[mc4wp_form id="17615"]' ) : '';
	if ( trim( $form_markup ) === '' ) return;
	?>
	<div id="tn-home-newsletter-source" hidden><?php echo $form_markup; ?></div>
	<script>
	(function(){
		function installFooterNewsletter() {
			var footer = document.querySelector('footer');
			var source = document.getElementById('tn-home-newsletter-source');
			if (!footer || !source || footer.querySelector('.tn-footer-newsletter')) return;
			var form = source.querySelector('.mc4wp-form');
			if (!form) return;
			footer.querySelectorAll('.widget_mc4wp_form_widget').forEach(function(widget) {
				widget.remove();
			});
			var panel = document.createElement('section');
			panel.className = 'tn-footer-newsletter';
			panel.setAttribute('aria-label', 'Trivia Nationals email signup');
			panel.innerHTML =
				'<div class="tn-footer-newsletter-copy">' +
					'<p class="tn-footer-newsletter-kicker">Stay in the loop</p>' +
					'<h2>Get Trivia Nationals updates</h2>' +
					'<p>Schedule notes, ticket reminders, and the occasional bit of useful weekend intel. No noise.</p>' +
				'</div>' +
				'<div class="tn-footer-newsletter-form"></div>';
			panel.querySelector('.tn-footer-newsletter-form').appendChild(form);
			footer.insertBefore(panel, footer.firstElementChild || null);
			source.remove();
		}
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', installFooterNewsletter);
		else installFooterNewsletter();
	})();
	</script>
	<?php
}, 99 );

add_action( 'wp_head', function () {
	if ( is_admin() || ! ( is_front_page() || is_page( 5 ) || is_page( 18797 ) ) ) return;
	$home_event_list = tn_tde_get_home_event_list();
	$home_event_types = tn_tde_home_event_types();
	$homepage_sections = tn_tde_get_homepage_sections();
	$homepage_section_definitions = tn_tde_homepage_section_definitions();
	$hero_background_url = plugins_url( 'assets/tn-hero-champions-2025.png', __FILE__ );
	?>
	<style id="tn-home-iterative-css">
	nav .nav-links a.tn-nav-cart-link {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 0.38rem 0.68rem;
		border: 1px solid rgba(0,229,255,0.42);
		border-radius: 999px;
		background: rgba(0,229,255,0.1);
		color: #00e5ff !important;
	}
	nav .nav-links a.tn-nav-cart-link:hover {
		background: rgba(0,229,255,0.18);
		border-color: rgba(0,229,255,0.7);
		color: #fff !important;
	}
	body.page-id-5 #hero.hero {
		align-items: center !important;
		background:
			linear-gradient(90deg, rgba(7,8,18,0.94) 0%, rgba(7,8,18,0.78) 44%, rgba(7,8,18,0.52) 100%),
			linear-gradient(180deg, rgba(7,8,18,0.28) 0%, rgba(7,8,18,0.88) 100%),
			url('<?php echo esc_url( $hero_background_url ); ?>') center 28% / cover no-repeat !important;
		height: auto !important;
		justify-content: center !important;
		min-height: 0 !important;
		padding: clamp(3rem, 6vw, 5rem) 2rem !important;
	}
	body.page-id-5 #hero.hero h1 {
		font-size: clamp(3.2rem, 8vw, 6rem) !important;
		line-height: 0.96 !important;
		margin-bottom: 1rem !important;
	}
	body.page-id-5 #hero.hero .hero-badge {
		margin-bottom: 1rem !important;
	}
	body.page-id-5 #hero.hero .hero-sub {
		margin-bottom: 1.35rem !important;
	}
	body footer .tn-footer-newsletter {
		display: grid;
		grid-template-columns: minmax(0, 0.9fr) minmax(360px, 1.1fr);
		align-items: center;
		gap: clamp(1.2rem, 4vw, 2.5rem);
		width: min(1080px, calc(100% - 2rem));
		margin: 0 auto 2.25rem;
		padding: clamp(1.15rem, 3vw, 1.65rem);
		border: 1px solid rgba(0,229,255,0.22);
		border-radius: 14px;
		background:
			linear-gradient(135deg, rgba(0,229,255,0.08), rgba(255,45,149,0.055)),
			rgba(12,16,31,0.72);
		box-shadow: 0 18px 54px rgba(0,0,0,0.24);
		text-align: left;
	}
	body footer .tn-footer-newsletter-kicker {
		margin: 0 0 0.4rem;
		color: #00e5ff;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: 0.74rem;
		font-weight: 900;
		letter-spacing: 0.12em;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter h2 {
		margin: 0 0 0.45rem;
		color: #f7f8ff;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: clamp(1.5rem, 3vw, 2.15rem);
		font-weight: 900;
		letter-spacing: 0;
		line-height: 1.02;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter-copy p:last-child {
		margin: 0;
		max-width: 34rem;
		color: #cdd4ea;
		font-size: 0.95rem;
		line-height: 1.55;
	}
	body footer .tn-footer-newsletter .mc4wp-form-fields {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 0.7rem;
	}
	body footer .tn-footer-newsletter .mc4wp-form-fields p {
		margin: 0;
	}
	body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(3) {
		grid-column: span 2;
	}
	body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(4) {
		grid-column: span 2;
	}
	body footer .tn-footer-newsletter label {
		display: grid;
		gap: 0.34rem;
		color: #cdd4ea;
		font-size: 0.74rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter input[type="text"],
	body footer .tn-footer-newsletter input[type="email"] {
		width: 100%;
		min-height: 46px;
		padding: 0.72rem 0.85rem;
		border: 1px solid rgba(255,255,255,0.14);
		border-radius: 8px;
		background: rgba(7,8,18,0.72);
		color: #f7f8ff;
		font: 600 0.95rem Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
	}
	body footer .tn-footer-newsletter input[type="text"]:focus,
	body footer .tn-footer-newsletter input[type="email"]:focus {
		border-color: rgba(0,229,255,0.72);
		box-shadow: 0 0 0 3px rgba(0,229,255,0.12);
		outline: none;
	}
	body footer .tn-footer-newsletter input[type="submit"] {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 46px;
		width: 100%;
		border: 1px solid rgba(255,209,102,0.82);
		border-radius: 999px;
		background: #ffd166;
		color: #071019;
		cursor: pointer;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: 0.82rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	body footer .tn-footer-newsletter input[type="submit"]:hover {
		background: #ffe08a;
	}
	body footer .tn-footer-newsletter .mc4wp-response {
		grid-column: span 2;
		color: #cdd4ea;
		font-size: 0.9rem;
	}
	body.page-id-5 .schedule {
		background:
			radial-gradient(circle at 20% 0%, rgba(0,229,255,0.1), transparent 28rem),
			radial-gradient(circle at 84% 8%, rgba(255,45,149,0.1), transparent 30rem) !important;
		outline: none !important;
	}
	body.page-id-5 .schedule:focus,
	body.page-id-5 #schedule:focus {
		outline: none !important;
	}
	body.page-id-5 .schedule .container {
		max-width: 1180px !important;
	}
	body.page-id-5 .schedule .section-label {
		margin-bottom: 0.85rem !important;
	}
	body.page-id-5 .schedule .section-title {
		font-size: clamp(2.1rem, 3.4vw, 3.25rem) !important;
		margin-bottom: 1.2rem !important;
		max-width: none !important;
		white-space: nowrap;
	}
	body.page-id-5 .schedule-tabs {
		display: none !important;
	}
	body.page-id-5 .tn-home-event-list {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 0.85rem;
		margin: 0;
		padding: 0;
		list-style: none;
	}
	body.page-id-5 .tn-home-event-list li {
		display: flex;
		align-items: stretch;
		min-height: 72px;
		padding: 0;
		border: 1px solid color-mix(in srgb, var(--tn-event-color, #00e5ff) 45%, rgba(255,255,255,0.12));
		border-radius: 8px;
		background:
			linear-gradient(90deg, color-mix(in srgb, var(--tn-event-color, #00e5ff) 22%, transparent) 0%, rgba(17,21,37,0.9) 42%),
			linear-gradient(180deg, rgba(255,255,255,0.045), rgba(17,21,37,0.88));
		box-shadow: 0 12px 34px rgba(0,0,0,0.18), inset 4px 0 0 var(--tn-event-color, #00e5ff);
		color: #f7f8ff;
		font-family: var(--font-display, Outfit, sans-serif);
		line-height: 1.18;
		overflow: hidden;
	}
	body.page-id-5 .tn-home-event-card {
		display: flex;
		flex-direction: column;
		justify-content: center;
		gap: 0.32rem;
		width: 100%;
		padding: 0.95rem 1rem 0.95rem 1.1rem;
	}
	body.page-id-5 .tn-home-event-title {
		font-size: 1.06rem;
		font-weight: 900;
	}
	body.page-id-5 .tn-home-event-type {
		color: var(--tn-event-color, #00e5ff);
		font-size: 0.68rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	body.page-id-5 .tn-home-event-actions {
		display: flex;
		flex-wrap: wrap;
		gap: 0.65rem;
		margin-top: 1.15rem;
	}
	body.page-id-5 .tn-schedule-full-link {
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		border: 1px solid rgba(255,209,102,0.72) !important;
		border-radius: 999px !important;
		background: #ffd166 !important;
		color: #071019 !important;
		font-family: var(--font-display, Outfit, sans-serif) !important;
		font-size: 0.78rem !important;
		font-weight: 900 !important;
		letter-spacing: 0.04em !important;
		padding: 0.78rem 1.15rem !important;
		text-decoration: none !important;
		text-transform: uppercase !important;
	}
	body.page-id-5 .tn-schedule-full-link:hover {
		background: #ffe08a !important;
		border-color: rgba(255,224,138,0.95) !important;
		color: #071019 !important;
		transform: translateY(-1px);
	}
	body.page-id-5 .tn-managed-section {
		padding: clamp(3.5rem, 8vw, 6rem) 0;
		background: #070812;
		color: #dfe4f5;
	}
	body.page-id-5 .tn-managed-section .container {
		width: min(1180px, calc(100% - 2rem));
		margin: 0 auto;
	}
	body.page-id-5 .tn-managed-section .section-label {
		margin: 0 0 0.85rem;
		color: var(--cyan, #00e5ff);
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: 0.78rem;
		font-weight: 900;
		letter-spacing: 0.12em;
		text-transform: uppercase;
	}
	body.page-id-5 .tn-managed-section .section-title {
		width: 100%;
		max-width: none;
		margin: 0 0 1.3rem;
		color: var(--white, #f7f8ff);
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: clamp(2.8rem, 7vw, 5.6rem);
		line-height: 0.92;
		font-weight: 900;
		letter-spacing: 0;
		text-transform: uppercase;
	}
	body.page-id-5 .tn-jeopardy-section .section-title {
		font-size: clamp(2.35rem, 4.9vw, 4.1rem);
		line-height: 0.98;
	}
	body.page-id-5 .tn-jeopardy-section {
		background:
			radial-gradient(circle at 18% 18%, rgba(0,229,255,0.13), transparent 28rem),
			linear-gradient(180deg, rgba(17,21,37,0.42), #070812);
	}
	body.page-id-5 .tn-how-it-works-section {
		background:
			radial-gradient(circle at 84% 20%, rgba(255,209,102,0.12), transparent 26rem),
			linear-gradient(180deg, #080a15, rgba(12,15,28,0.96));
	}
	body.page-id-5 .tn-how-it-works-section .section-title {
		font-size: clamp(2.35rem, 5.5vw, 4.35rem);
		line-height: 1.02;
		text-transform: none;
	}
	body.page-id-5 .tn-jeopardy-content {
		display: grid;
		grid-template-columns: minmax(0, 1fr) minmax(280px, 410px);
		gap: clamp(1.5rem, 5vw, 4.75rem);
		align-items: start;
		max-width: 100%;
	}
	body.page-id-5 .tn-how-it-works-content {
		max-width: 100%;
	}
	body.page-id-5 .tn-jeopardy-video-card {
		justify-self: end;
		width: min(100%, 410px);
		padding: clamp(1rem, 2vw, 1.35rem);
		border: 1px solid rgba(0,229,255,0.68);
		border-radius: 16px;
		background: linear-gradient(180deg, rgba(12,16,31,0.96), rgba(8,10,20,0.94));
		box-shadow: 0 24px 70px rgba(0,0,0,0.34);
	}
	body.page-id-5 .tn-jeopardy-video-frame {
		position: relative;
		overflow: hidden;
		border-radius: 12px;
		background: #050713;
	}
	body.page-id-5 .tn-jeopardy-video-frame video {
		display: block;
		width: 100%;
		aspect-ratio: 9 / 16;
		object-fit: cover;
		background: #050713;
	}
	body.page-id-5 .tn-jeopardy-copy {
		color: #dfe4f5;
		font-size: clamp(1rem, 1.7vw, 1.2rem);
		line-height: 1.65;
		font-weight: 500;
	}
	body.page-id-5 .tn-jeopardy-copy::after {
		content: "";
		display: table;
		clear: both;
	}
	body.page-id-5 .tn-jeopardy-copy blockquote {
		margin: 0;
		padding: 0;
		border: 0;
		color: inherit;
		font: inherit;
	}
	body.page-id-5 .tn-jeopardy-copy p {
		margin: 0 0 1rem;
	}
	body.page-id-5 .tn-jeopardy-copy img {
		max-width: 100%;
		height: auto;
	}
	body.page-id-5 .tn-jeopardy-copy strong,
	body.page-id-5 .tn-jeopardy-copy b {
		color: #f7f8ff;
		font-weight: 800;
	}
	body.page-id-5 .tn-jeopardy-copy .alignleft,
	body.page-id-5 .tn-jeopardy-copy img.alignleft,
	body.page-id-5 .tn-jeopardy-copy figure.alignleft {
		float: left;
		margin: 0.25rem clamp(1rem, 3vw, 1.75rem) 1rem 0;
	}
	body.page-id-5 .tn-jeopardy-copy .alignright,
	body.page-id-5 .tn-jeopardy-copy img.alignright,
	body.page-id-5 .tn-jeopardy-copy figure.alignright {
		float: right;
		margin: 0.25rem 0 1rem clamp(1rem, 3vw, 1.75rem);
	}
	body.page-id-5 .tn-jeopardy-copy .aligncenter,
	body.page-id-5 .tn-jeopardy-copy img.aligncenter,
	body.page-id-5 .tn-jeopardy-copy figure.aligncenter {
		display: block;
		float: none;
		margin: 0.5rem auto 1.25rem;
		text-align: center;
	}
	body.page-id-5 .tn-jeopardy-copy figure.wp-block-image {
		max-width: 100%;
	}
	body.page-id-5 .tn-jeopardy-copy figure.wp-block-image img {
		display: block;
	}
	body.page-id-5 .tn-how-it-works-copy {
		color: #dfe4f5;
		font-size: clamp(1rem, 1.7vw, 1.2rem);
		line-height: 1.65;
		font-weight: 500;
		max-width: 100%;
	}
	body.page-id-5 .tn-how-it-works-copy::after {
		content: "";
		display: table;
		clear: both;
	}
	body.page-id-5 .tn-how-it-works-copy p {
		margin: 0 0 1rem;
	}
	body.page-id-5 .tn-how-it-works-copy img {
		max-width: 100%;
		height: auto;
	}
	body.page-id-5 .tn-how-it-works-copy strong,
	body.page-id-5 .tn-how-it-works-copy b {
		color: #f7f8ff;
		font-weight: 800;
	}
	body.page-id-5 .tn-how-it-works-copy .alignleft,
	body.page-id-5 .tn-how-it-works-copy img.alignleft,
	body.page-id-5 .tn-how-it-works-copy figure.alignleft {
		float: left;
		margin: 0.25rem clamp(1rem, 3vw, 1.75rem) 1rem 0;
	}
	body.page-id-5 .tn-how-it-works-copy .alignright,
	body.page-id-5 .tn-how-it-works-copy img.alignright,
	body.page-id-5 .tn-how-it-works-copy figure.alignright {
		float: right;
		margin: 0.25rem 0 1rem clamp(1rem, 3vw, 1.75rem);
	}
	body.page-id-5 .tn-how-it-works-copy .aligncenter,
	body.page-id-5 .tn-how-it-works-copy img.aligncenter,
	body.page-id-5 .tn-how-it-works-copy figure.aligncenter {
		display: block;
		float: none;
		margin: 0.5rem auto 1.25rem;
		text-align: center;
	}
	body.page-id-5 .tn-how-it-works-copy .e-con-inner {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: clamp(0.9rem, 2vw, 1.2rem);
		max-width: none;
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-element,
	body.page-id-5 .tn-how-it-works-copy .elementor-widget-container {
		min-width: 0;
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-widget-call-to-action {
		height: 100%;
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-cta {
		position: relative;
		height: 100%;
		min-height: 100%;
		overflow: hidden;
		border: 1px solid rgba(255,255,255,0.14) !important;
		border-radius: 8px !important;
		background:
			linear-gradient(135deg, rgba(0,229,255,0.13), transparent 46%),
			linear-gradient(180deg, rgba(255,255,255,0.075), rgba(255,255,255,0.035)) !important;
		box-shadow: inset 0 1px 0 rgba(255,255,255,0.12), 0 18px 46px rgba(0,0,0,0.22) !important;
		color: #d7def0 !important;
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-cta::before {
		content: "";
		position: absolute;
		inset: 0 auto 0 0;
		width: 4px;
		background: linear-gradient(180deg, #00e5ff, #ffd166);
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-cta__content {
		position: relative;
		display: flex;
		flex-direction: column;
		gap: 0.75rem;
		height: 100%;
		padding: clamp(1.1rem, 2.6vw, 1.6rem) !important;
		background: transparent !important;
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-cta__title {
		margin: 0;
		color: #f7f8ff !important;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: clamp(1.25rem, 2.4vw, 1.65rem);
		font-weight: 900;
		letter-spacing: 0;
		line-height: 1.05;
		text-transform: uppercase;
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-cta__description {
		margin: 0;
		color: #d7def0 !important;
		font-size: clamp(0.95rem, 1.2vw, 1.02rem);
		font-weight: 500;
		line-height: 1.55;
	}
	body.page-id-5 .tn-how-it-works-copy .elementor-cta__description br {
		display: none;
	}
	body.page-id-5 .tn-faq-answer {
		color: #d7def0;
		font-size: clamp(0.95rem, 1.35vw, 1.05rem);
		line-height: 1.62;
		font-weight: 500;
	}
	body.page-id-5 .tn-jeopardy-copy {
		max-width: 100%;
	}
	body.page-id-5 .tn-jeopardy-copy p:first-child,
	body.page-id-5 .tn-how-it-works-copy p:first-child,
	body.page-id-5 .tn-faq-answer p:first-child { margin-top: 0; }
	body.page-id-5 .tn-quotes-section {
		background:
			radial-gradient(circle at 82% 12%, rgba(255,209,102,0.12), transparent 26rem),
			#070812;
	}
	body.page-id-5 .tn-quotes-grid {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 0.9rem;
	}
	body.page-id-5 .tn-quote-card {
		min-height: 100%;
		padding: clamp(1.2rem, 2.5vw, 1.7rem);
		border: 1px solid rgba(255,209,102,0.28);
		border-radius: 8px;
		background: rgba(255,209,102,0.075);
	}
	body.page-id-5 .tn-quote-card blockquote {
		margin: 0;
		color: #f7f8ff;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: clamp(1.2rem, 2vw, 1.55rem);
		line-height: 1.22;
		font-weight: 850;
	}
	body.page-id-5 .tn-quote-card cite {
		display: block;
		margin-top: 1rem;
		color: #cdd4ea;
		font-style: normal;
		font-weight: 800;
	}
	body.page-id-5 .tn-faq-section {
		background:
			radial-gradient(circle at 18% 6%, rgba(255,45,149,0.1), transparent 24rem),
			#070812;
	}
	body.page-id-5 .tn-faq-list {
		display: grid;
		gap: 0.75rem;
	}
	body.page-id-5 .tn-faq-item {
		border: 1px solid rgba(255,255,255,0.12);
		border-radius: 8px;
		background: rgba(17,21,37,0.82);
		overflow: hidden;
	}
	body.page-id-5 .tn-faq-question {
		width: 100%;
		padding: 1rem 1.15rem;
		border: 0;
		background: transparent;
		color: #f7f8ff;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		font-family: var(--font-display, Outfit, sans-serif);
		font-size: 1rem;
		font-weight: 900;
		text-align: left;
		text-transform: uppercase;
	}
	body.page-id-5 .tn-faq-question::after {
		content: '+';
		color: #00e5ff;
		font-size: 1.35rem;
		line-height: 1;
	}
	body.page-id-5 .tn-faq-item[open] .tn-faq-question::after { content: '-'; }
	body.page-id-5 .tn-faq-answer {
		padding: 0 1.15rem 1.1rem;
	}
	body.page-id-5 .tn-faq-answer a {
		color: var(--cyan, #00e5ff);
		text-decoration: underline;
		text-underline-offset: 0.16em;
	}
	@media (max-width: 767px) {
		nav .nav-links {
			align-items: center;
		}
		body.page-id-5 .tn-home-event-list {
			grid-template-columns: 1fr;
		}
		body footer .tn-footer-newsletter,
		body footer .tn-footer-newsletter .mc4wp-form-fields {
			grid-template-columns: 1fr;
		}
		body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(3),
		body footer .tn-footer-newsletter .mc4wp-form-fields p:nth-child(4),
		body footer .tn-footer-newsletter .mc4wp-response {
			grid-column: auto;
		}
		body.page-id-5 .tn-jeopardy-content,
		body.page-id-5 .tn-how-it-works-content,
		body.page-id-5 .tn-quotes-grid {
			grid-template-columns: 1fr;
		}
		body.page-id-5 .tn-jeopardy-video-card {
			justify-self: center;
			width: min(100%, 360px);
		}
		body.page-id-5 .tn-how-it-works-copy .e-con-inner {
			grid-template-columns: 1fr;
		}
		body.page-id-5 #hero.hero {
			padding: 2.5rem 1rem !important;
		}
		body.page-id-5 #hero.hero h1 {
			font-size: clamp(2.75rem, 14vw, 4.5rem) !important;
		}
		body.page-id-5 .schedule .section-title {
			white-space: normal;
		}
	}
	</style>
	<script>
	(function(){
		var events = <?php echo wp_json_encode( $home_event_list ); ?>;
		var eventTypes = <?php echo wp_json_encode( $home_event_types ); ?>;
		var sectionOrder = <?php echo wp_json_encode( $homepage_sections ); ?>;
		var sectionDefinitions = <?php echo wp_json_encode( $homepage_section_definitions ); ?>;
		function applyHomepageSections() {
			var managed = document.getElementById('tn-managed-homepage-sections');
			var homeParent = null;
			sectionOrder.some(function(item) {
				var def = sectionDefinitions[item.key];
				var el = def ? document.querySelector(def.selector) : null;
				if (el && !el.classList.contains('tn-managed-section') && el.parentNode) {
					homeParent = el.parentNode;
					return true;
				}
				return false;
			});
			if (managed) {
				var insertionParent = homeParent || document.body;
				var footerAnchor = insertionParent.querySelector(':scope > footer');
				Array.from(managed.children).forEach(function(section) {
					section.hidden = false;
					if (footerAnchor && footerAnchor.parentNode === insertionParent) {
						insertionParent.insertBefore(section, footerAnchor);
					} else {
						insertionParent.appendChild(section);
					}
				});
				managed.remove();
			}
			var sections = sectionOrder.map(function(item) {
				var def = sectionDefinitions[item.key];
				var el = def ? document.querySelector(def.selector) : null;
				return el ? { item: item, def: def, el: el } : null;
			}).filter(Boolean);
			if (!sections.length) return;
			var parent = sections[0].el.parentNode;
			var footerAnchor = parent ? parent.querySelector(':scope > footer') : null;
			sections.forEach(function(entry) {
				entry.el.hidden = entry.item.visible === false;
				entry.el.style.display = entry.item.visible === false ? 'none' : '';
				if (entry.el.parentNode === parent) {
					if (footerAnchor && footerAnchor.parentNode === parent) {
						parent.insertBefore(entry.el, footerAnchor);
					} else {
						parent.appendChild(entry.el);
					}
				}
			});
			var nav = document.querySelector('body.page-id-5 nav .nav-links');
			if (!nav) return;
			var navLinks = Array.from(nav.querySelectorAll('a[href*="#"]'));
			sections.forEach(function(entry) {
				if (!entry.def.nav || entry.item.visible === false) return;
				var link = navLinks.find(function(anchor) {
					return (anchor.getAttribute('href') || '').split('/').pop() === entry.def.nav;
				});
				if (link) nav.appendChild(link);
			});
		}
		function renderHomeEventList() {
			var schedule = document.querySelector('body.page-id-5 .schedule');
			if (!schedule || schedule.querySelector('.tn-home-event-list')) return;
			var tabs = schedule.querySelector('.schedule-tabs');
			var firstDay = schedule.querySelector('#day-friday, #day-saturday, #day-sunday');
			var label = schedule.querySelector('.section-label');
			var title = schedule.querySelector('.section-title');
			if (label) label.textContent = 'Event List';
			if (title) title.textContent = "Here are just a few of the weekend's events.";
			if (tabs) tabs.remove();
			schedule.querySelectorAll('#day-friday, #day-saturday, #day-sunday').forEach(function(day) {
				day.style.display = 'none';
			});
			var list = document.createElement('ul');
			list.className = 'tn-home-event-list';
			events.forEach(function(event) {
				var title = typeof event === 'string' ? event : (event && event.title ? event.title : '');
				var typeKey = typeof event === 'object' && event ? event.type : '';
				var type = eventTypes[typeKey] || null;
				if (!title) return;
				var item = document.createElement('li');
				if (type && type.color) item.style.setProperty('--tn-event-color', type.color);
				var card = document.createElement('div');
				card.className = 'tn-home-event-card';
				var titleEl = document.createElement('span');
				titleEl.className = 'tn-home-event-title';
				titleEl.textContent = title;
				card.appendChild(titleEl);
				if (type && type.label && typeKey !== 'none') {
					var typeEl = document.createElement('span');
					typeEl.className = 'tn-home-event-type';
					typeEl.textContent = type.label;
					card.appendChild(typeEl);
				}
				item.appendChild(card);
				list.appendChild(item);
			});
			var actions = document.createElement('div');
			actions.className = 'tn-home-event-actions';
			var signupLink = document.createElement('a');
			signupLink.className = 'tn-schedule-full-link tn-event-signup-link';
			signupLink.href = <?php echo wp_json_encode( home_url( '/event-signups/' ) ); ?>;
			signupLink.textContent = 'Sign Up for Events';
			actions.appendChild(signupLink);
			var link = document.createElement('a');
			link.className = 'tn-schedule-full-link';
			link.href = <?php echo wp_json_encode( home_url( '/full-schedule/' ) ); ?>;
			link.textContent = 'Full Schedule';
			actions.appendChild(link);
			if (firstDay && firstDay.parentNode) {
				firstDay.parentNode.insertBefore(actions, firstDay);
				firstDay.parentNode.insertBefore(list, actions);
			} else {
				schedule.appendChild(list);
				schedule.appendChild(actions);
			}
		}
		function initHomeControls() {
			renderHomeEventList();
			applyHomepageSections();
		}
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initHomeControls);
		else initHomeControls();
	})();
	</script>
	<?php
}, 9 );

// ─── Front-end: Shared event detail page styling ────────────────────────────

add_action( 'wp_head', function () {
	if ( is_admin() ) return;
	?>
	<style id="tn-event-detail-css">
	@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap');
	html, body { background: #070812; }
	body {
		color: #e6eaff;
	}
	body p,
	body li,
	body td,
	body dd,
	body figcaption,
	body .elementor-widget-text-editor,
	body .elementor-widget-text-editor p {
		color: #dfe4f5;
	}
	body small,
	body label,
	body .elementor-heading-title small,
	body .tn-muted,
	body .tn-eyebrow,
	body .tn-event-nav-links a {
		color: #cdd4ea;
	}
	body.page-template-elementor_canvas { overflow-x: hidden; }
	.tn-event-nav-section,
	.tn-event-hero-section,
	.tn-event-main-section,
	.tn-event-row,
	.tn-event-section,
	.tn-event-final-section {
		--tn-bg: #070812;
		--tn-panel: #111525;
		--tn-text: #f7f8ff;
		--tn-muted: #cdd4ea;
		--tn-cyan: #00e5ff;
		--tn-pink: #ff2d95;
		--tn-gold: #ffd166;
		--tn-line: rgba(255,255,255,0.12);
		--event-accent: #00e5ff;
		color: var(--tn-text);
		font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		letter-spacing: 0;
	}
	.tn-event-nav-section {
		position: sticky;
		top: 0;
		z-index: 20;
		background: rgba(7,8,18,0.78);
		border-bottom: 1px solid var(--tn-line);
		backdrop-filter: blur(14px);
		height: auto !important;
		min-height: 0 !important;
		padding: 0 !important;
	}
	.admin-bar .tn-event-nav-section { top: 32px; }
	.tn-event-nav-section .elementor-container,
	.tn-event-nav-section .e-con-inner {
		width: min(1900px, calc(100% - 2rem));
		max-width: none;
		min-height: 62px !important;
		padding: 0 !important;
	}
	.tn-event-nav-section .elementor-column-wrap,
	.tn-event-nav-section .elementor-widget-wrap,
	.tn-event-nav-widget,
	.tn-event-nav-widget .elementor-widget-container {
		margin: 0 !important;
		padding: 0 !important;
	}
	.tn-event-nav {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1rem;
		width: 100%;
	}
	.tn-event-nav a {
		font-family: Outfit, sans-serif;
		font-weight: 900;
		text-decoration: none;
		text-transform: uppercase;
		letter-spacing: 0.1em;
	}
	.tn-brand { color: var(--tn-text); font-size: 0.8rem; white-space: nowrap; }
	.tn-event-nav-links { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: flex-end; }
	.tn-event-nav-links a { color: var(--tn-muted); font-size: 0.72rem; }
	.tn-event-nav-links a:hover { color: var(--event-accent); }
	.tn-event-nav-links a.tn-nav-cart-link {
		padding: 0.36rem 0.62rem;
		border: 1px solid rgba(0,229,255,0.32);
		border-radius: 999px;
		background: rgba(0,229,255,0.08);
		color: var(--event-accent);
	}
	.tn-event-nav-links a.tn-nav-cart-link:hover {
		border-color: rgba(0,229,255,0.62);
		background: rgba(0,229,255,0.14);
		color: var(--tn-text);
	}
	.tn-event-hero-section {
		background:
			linear-gradient(180deg, rgba(7,8,18,0.48), var(--tn-bg) 58%),
			linear-gradient(135deg, rgba(0,229,255,0.16), transparent 38%, rgba(255,45,149,0.12));
	}
	.tn-event-main-section {
		background:
			radial-gradient(circle at 18% 8%, rgba(0,229,255,0.16), transparent 28rem),
			radial-gradient(circle at 82% 12%, rgba(255,45,149,0.15), transparent 30rem),
			linear-gradient(180deg, rgba(7,8,18,0.28), var(--tn-bg) 72%),
			linear-gradient(135deg, rgba(0,229,255,0.13), transparent 38%, rgba(255,45,149,0.12));
	}
	.tn-event-main-section > .elementor-container,
	.tn-event-main-section > .e-con-inner {
		width: min(1160px, calc(100% - 2rem));
		max-width: none;
		padding: clamp(1.1rem, 2.5vw, 2.25rem) 0 clamp(2rem, 4vw, 3.5rem) !important;
	}
	.tn-event-main-section .elementor-column-wrap,
	.tn-event-main-section .elementor-widget-wrap {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}
	.tn-event-row {
		background: transparent !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	.tn-event-row > .elementor-container,
	.tn-event-row > .e-con-inner {
		width: 100%;
		max-width: none;
		padding: 0 !important;
	}
	.tn-event-row + .tn-event-row {
		margin-top: clamp(1.25rem, 2.4vw, 2rem) !important;
	}
	.tn-event-hero-row {
		margin-bottom: clamp(0.75rem, 1.8vw, 1.25rem) !important;
	}
	.tn-event-main-section .tn-final-card .elementor-widget-wrap {
		align-items: flex-start !important;
		justify-content: flex-start !important;
		gap: 0.65rem !important;
		min-height: 0 !important;
	}
	.tn-event-main-section .tn-final-card .elementor-widget {
		margin-bottom: 0.35rem !important;
	}
	.tn-event-main-section .elementor-column {
		padding-left: 0.45rem !important;
		padding-right: 0.45rem !important;
	}
	.tn-event-main-section .tn-event-row > .elementor-container {
		margin-left: -0.45rem !important;
		margin-right: -0.45rem !important;
		width: calc(100% + 0.9rem) !important;
	}
	.tn-event-main-section .tn-card-column .elementor-widget-wrap,
	.tn-event-main-section .tn-quote-card .elementor-widget-wrap,
	.tn-event-main-section .tn-final-card .elementor-widget-wrap {
		padding: clamp(1.2rem, 2.4vw, 1.85rem) !important;
	}
	.tn-event-main-section .tn-card-column .elementor-widget + .elementor-widget,
	.tn-event-main-section .tn-quote-card .elementor-widget + .elementor-widget,
	.tn-event-main-section .tn-final-card .elementor-widget + .elementor-widget {
		margin-top: 0.45rem !important;
	}
	.tn-event-main-section .tn-hero-panel {
		margin-top: 0.15rem;
	}
	.tn-event-hero-section > .elementor-container,
	.tn-event-hero-section > .e-con-inner {
		width: min(1160px, calc(100% - 2rem));
		max-width: none;
		min-height: calc(100vh - 74px);
		align-items: center;
		padding: clamp(3.5rem, 8vw, 7rem) 0 clamp(2.5rem, 6vw, 5rem);
	}
	.tn-kicker-widget .elementor-heading-title {
		display: inline-flex;
		width: fit-content;
		margin: 0 0 1.15rem;
		padding: 0.38rem 0.65rem;
		border: 1px solid rgba(0,229,255,0.42);
		border-radius: 999px;
		background: rgba(0,229,255,0.12);
		color: var(--event-accent);
		font-family: Outfit, sans-serif;
		font-size: 0.72rem;
		font-weight: 800;
		letter-spacing: 0.09em;
		text-transform: uppercase;
	}
	.tn-title-widget .elementor-heading-title {
		margin: 0;
		color: var(--tn-text);
		font-family: Outfit, sans-serif;
		font-size: clamp(4rem, 9vw, 8rem);
		line-height: 0.82;
		font-weight: 900;
		text-transform: uppercase;
		letter-spacing: 0;
		white-space: nowrap;
	}
	.tn-title-accent-widget .elementor-heading-title { color: var(--event-accent); }
	.tn-lede-widget,
	.tn-lede-widget .elementor-widget-container {
		max-width: 680px;
	}
	.tn-lede-widget p,
	.tn-lede-widget .elementor-widget-container {
		color: #dfe4f5;
		font-size: clamp(1.1rem, 2vw, 1.42rem);
		line-height: 1.55;
		font-weight: 700;
	}
	.tn-button-row .elementor-widget-wrap {
		display: flex;
		gap: 0.8rem;
		flex-wrap: wrap;
	}
	.tn-button-primary .elementor-button,
	.tn-button-secondary .elementor-button {
		min-height: 44px;
		padding: 0.78rem 1.1rem !important;
		border-radius: 999px !important;
		font-family: Outfit, sans-serif;
		font-size: 0.78rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		text-transform: uppercase;
		transition: transform 0.18s ease;
	}
	.tn-button-primary .elementor-button {
		border: 0 !important;
		background: linear-gradient(135deg, var(--event-accent), var(--tn-pink)) !important;
		color: #fff !important;
	}
	.tn-button-secondary .elementor-button {
		border: 1px solid var(--tn-line) !important;
		background: rgba(255,255,255,0.06) !important;
		color: var(--tn-text) !important;
	}
	.tn-button-primary .elementor-button:hover,
	.tn-button-secondary .elementor-button:hover { transform: translateY(-1px); }
	.tn-button-primary.elementor-widget,
	.tn-button-secondary.elementor-widget {
		display: inline-block;
		width: auto;
		margin: 0.4rem 0.8rem 0 0;
	}
	.tn-hero-panel {
		border: 1px solid var(--tn-line);
		border-radius: 8px;
		background: rgba(17,21,37,0.78);
		box-shadow: 0 24px 80px rgba(0,0,0,0.38);
		overflow: hidden;
	}
	.tn-scoreboard {
		display: grid;
		grid-template-columns: 1fr auto 1fr;
		gap: 0.75rem;
		align-items: center;
		padding: 1rem;
		border-bottom: 1px solid var(--tn-line);
		background: rgba(0,0,0,0.2);
		font-family: Outfit, sans-serif;
		text-transform: uppercase;
	}
	.tn-scoreboard strong { display: block; font-size: 0.74rem; letter-spacing: 0.09em; color: var(--tn-muted); }
	.tn-scoreboard span { display: block; margin-top: 0.2rem; font-size: 1.15rem; font-weight: 900; color: var(--tn-text); }
	.tn-scoreboard .tn-versus { color: var(--tn-gold); font-weight: 900; letter-spacing: 0.08em; }
	.tn-court {
		position: relative;
		min-height: 300px;
		margin: 1rem;
		border: 2px solid rgba(255,255,255,0.2);
		border-radius: 8px;
		background:
			linear-gradient(90deg, rgba(0,229,255,0.08), transparent 49%, rgba(255,45,149,0.09)),
			repeating-linear-gradient(0deg, rgba(255,255,255,0.04), rgba(255,255,255,0.04) 1px, transparent 1px, transparent 30px);
		overflow: hidden;
	}
	.tn-court::before,
	.tn-court::after {
		content: '';
		position: absolute;
		top: 0;
		bottom: 0;
		width: 2px;
		background: rgba(255,255,255,0.22);
	}
	.tn-court::before { left: 50%; }
	.tn-court::after { left: 25%; box-shadow: 150px 0 0 rgba(255,255,255,0.12); }
	.tn-court-label {
		position: absolute;
		left: 1rem;
		bottom: 1rem;
		max-width: 230px;
		color: var(--tn-muted);
		font-size: 0.82rem;
		line-height: 1.45;
	}
	.tn-court-label strong {
		display: block;
		color: var(--tn-text);
		font-family: Outfit, sans-serif;
		font-size: 1.08rem;
		margin-bottom: 0.25rem;
	}
	.tn-ball {
		position: absolute;
		width: 58px;
		height: 58px;
		border-radius: 50%;
		background: radial-gradient(circle at 34% 28%, #fff5, transparent 18%), linear-gradient(135deg, var(--tn-pink), #8a2dff);
		box-shadow: 0 10px 30px rgba(255,45,149,0.28);
	}
	.tn-ball.one { top: 22%; left: 18%; }
	.tn-ball.two { width: 42px; height: 42px; top: 48%; left: 58%; background: radial-gradient(circle at 34% 28%, #fff7, transparent 18%), linear-gradient(135deg, var(--tn-cyan), #246bff); }
	.tn-ball.three { width: 34px; height: 34px; top: 28%; right: 16%; background: radial-gradient(circle at 34% 28%, #fff7, transparent 18%), linear-gradient(135deg, var(--tn-gold), #ff6a3d); }
	.tn-event-section,
	.tn-event-final-section {
		background: var(--tn-bg);
		height: auto !important;
		min-height: 0 !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	.tn-event-section > .elementor-container,
	.tn-event-section > .e-con-inner,
	.tn-event-final-section > .elementor-container,
	.tn-event-final-section > .e-con-inner {
		width: min(1100px, calc(100% - 2rem));
		max-width: none;
		height: auto !important;
		min-height: 0 !important;
		padding: 0 !important;
	}
	.tn-card-column .elementor-widget-wrap {
		min-height: 100%;
		padding: 1.25rem;
		border: 1px solid var(--tn-line);
		border-radius: 8px;
		background: rgba(17,21,37,0.84);
	}
	.tn-card-accent .elementor-widget-wrap {
		border-color: rgba(0,229,255,0.32);
		background: linear-gradient(180deg, rgba(0,229,255,0.1), rgba(17,21,37,0.88));
	}
	.tn-card-column .elementor-heading-title {
		margin: 0 0 0.65rem;
		color: var(--tn-text);
		font-family: Outfit, sans-serif;
		line-height: 1.05;
	}
	.tn-card-column .elementor-widget-text-editor,
	.tn-card-column .elementor-widget-text-editor p {
		color: var(--tn-muted);
		font-size: 0.98rem;
		line-height: 1.65;
	}
	.tn-quote-section .elementor-container,
	.tn-quote-section .e-con-inner {
		width: min(1100px, calc(100% - 2rem));
		max-width: none;
		height: auto !important;
		min-height: 0 !important;
		padding: 0 !important;
	}
	.tn-quote-card .elementor-widget-wrap {
		padding: clamp(1.5rem, 4vw, 2.2rem);
		border: 1px solid rgba(255,209,102,0.32);
		border-radius: 8px;
		background: rgba(255,209,102,0.08);
	}
	.tn-quote-text .elementor-widget-container {
		color: var(--tn-text);
		font-family: Outfit, sans-serif;
		font-size: clamp(1.4rem, 3vw, 2.25rem);
		line-height: 1.18;
		font-weight: 800;
	}
	.tn-quote-cite .elementor-widget-container {
		color: var(--tn-muted);
		line-height: 1.45;
	}
	.tn-quote-cite strong { color: var(--tn-cyan); }
	.tn-final-card .elementor-widget-wrap {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 1.5rem;
		padding: clamp(1.5rem, 4vw, 2.25rem);
		border: 1px solid var(--tn-line);
		border-radius: 8px;
		background: linear-gradient(135deg, rgba(0,229,255,0.12), rgba(255,45,149,0.12));
	}
	.tn-final-title .elementor-heading-title {
		margin: 0;
		color: var(--tn-text);
		font-family: Outfit, sans-serif;
		font-size: clamp(1.7rem, 4vw, 3rem);
		line-height: 1;
	}
	.tn-final-text .elementor-widget-container {
		color: var(--tn-muted);
		line-height: 1.6;
	}
	.tn-event-facts-panel {
		align-self: center;
		flex: 0 1 390px;
		width: min(100%, 390px);
		margin-left: auto;
		padding: clamp(1.2rem, 2.5vw, 1.75rem);
		border: 1px solid rgba(255,255,255,0.16);
		border-radius: 8px;
		background:
			linear-gradient(180deg, rgba(255,255,255,0.075), rgba(17,21,37,0.9)),
			radial-gradient(circle at 100% 0%, rgba(0,229,255,0.12), transparent 16rem);
		box-shadow: 0 20px 70px rgba(0,0,0,0.3);
	}
	.tn-event-facts-label {
		margin: 0 0 0.85rem;
		color: var(--event-accent, #00e5ff);
		font-family: Outfit, sans-serif;
		font-size: 0.72rem;
		font-weight: 900;
		letter-spacing: 0.1em;
		text-transform: uppercase;
	}
	.tn-event-facts-title {
		margin: 0 0 1rem;
		color: var(--tn-text, #f7f8ff);
		font-family: Outfit, sans-serif;
		font-size: clamp(1.7rem, 3vw, 2.5rem);
		line-height: 0.98;
		font-weight: 900;
		letter-spacing: 0;
		text-transform: uppercase;
	}
	.tn-event-facts-list {
		display: grid;
		gap: 0;
		margin: 0;
	}
	.tn-event-fact {
		display: grid;
		grid-template-columns: 5.6rem 1fr;
		gap: 0.9rem;
		padding: 0.8rem 0;
		border-top: 1px solid rgba(255,255,255,0.1);
	}
	.tn-event-fact:first-child { border-top: 0; }
	.tn-event-fact dt {
		margin: 0;
		color: var(--tn-muted, #aeb4c6);
		font-family: Outfit, sans-serif;
		font-size: 0.68rem;
		font-weight: 900;
		letter-spacing: 0.1em;
		text-transform: uppercase;
	}
	.tn-event-fact dd {
		margin: 0;
		color: var(--tn-text, #f7f8ff);
		font-size: 1rem;
		font-weight: 700;
		line-height: 1.35;
	}
	.tn-event-facts-note {
		margin: 0.85rem 0 0;
		color: var(--tn-muted, #aeb4c6);
		font-size: 0.86rem;
		line-height: 1.5;
	}
	.tn-event-hero-section > .elementor-container.tn-has-event-facts,
	.tn-event-hero-section > .e-con-inner.tn-has-event-facts,
	.tn-event-hero-section > .elementor-container:has(.tn-event-facts-panel),
	.tn-event-hero-section > .e-con-inner:has(.tn-event-facts-panel) {
		display: flex !important;
		flex-wrap: nowrap !important;
		align-items: center !important;
	}
	.tn-event-hero-section > .elementor-container.tn-has-event-facts .tn-event-hero-copy,
	.tn-event-hero-section > .e-con-inner.tn-has-event-facts .tn-event-hero-copy,
	.tn-event-hero-section > .elementor-container:has(.tn-event-facts-panel) .tn-event-hero-copy,
	.tn-event-hero-section > .e-con-inner:has(.tn-event-facts-panel) .tn-event-hero-copy {
		flex: 1 1 0 !important;
		min-width: 0 !important;
		width: auto !important;
		max-width: calc(100% - 500px) !important;
	}
	.tn-event-hero-section > .elementor-container.tn-has-event-facts .tn-title-widget .elementor-heading-title,
	.tn-event-hero-section > .e-con-inner.tn-has-event-facts .tn-title-widget .elementor-heading-title,
	.tn-event-hero-section > .elementor-container:has(.tn-event-facts-panel) .tn-title-widget .elementor-heading-title,
	.tn-event-hero-section > .e-con-inner:has(.tn-event-facts-panel) .tn-title-widget .elementor-heading-title {
		font-size: clamp(3.8rem, 6.2vw, 7.1rem);
		overflow-wrap: normal;
		white-space: normal !important;
	}
	.tn-presented-section {
		background: var(--tn-bg, #070812);
		color: var(--tn-text, #f7f8ff);
		font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		padding: clamp(1rem, 2vw, 1.35rem) 0 clamp(2rem, 4vw, 3rem);
	}
	.tn-presented-inner {
		width: min(1100px, calc(100% - 2rem));
		max-width: none;
		margin: 0 auto;
	}
	.tn-presented-kicker {
		margin: 0 0 0.7rem;
		color: var(--event-accent, #00e5ff);
		font-family: Outfit, sans-serif;
		font-size: 0.72rem;
		font-weight: 900;
		letter-spacing: 0.1em;
		text-transform: uppercase;
	}
	.tn-presented-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 0.85rem;
	}
	.tn-presenter-card {
		display: grid;
		grid-template-columns: auto 1fr;
		gap: 1rem;
		align-items: start;
		padding: clamp(1.2rem, 2.4vw, 1.65rem);
		border: 1px solid var(--tn-line, rgba(255,255,255,0.12));
		border-radius: 8px;
		background: linear-gradient(180deg, rgba(255,255,255,0.055), rgba(17,21,37,0.86));
		box-shadow: 0 18px 70px rgba(0,0,0,0.24);
	}
	.tn-presenter-photo {
		width: 76px;
		height: 76px;
		border-radius: 8px;
		object-fit: cover;
		border: 1px solid rgba(255,255,255,0.16);
		background: rgba(255,255,255,0.08);
	}
	.tn-presenter-name {
		margin: 0 0 0.35rem;
		color: var(--tn-text, #f7f8ff);
		font-family: Outfit, sans-serif;
		font-size: clamp(1.25rem, 2vw, 1.6rem);
		line-height: 1.05;
	}
	.tn-presenter-bio {
		margin: 0;
		color: var(--tn-muted, #aeb4c6);
		font-size: 0.98rem;
		line-height: 1.6;
	}
	@media (max-width: 860px) {
		.admin-bar .tn-event-nav-section { top: 46px; }
		.tn-event-nav { align-items: flex-start; flex-direction: column; }
		.tn-event-nav-links { justify-content: flex-start; }
		.tn-event-hero-section > .elementor-container,
		.tn-event-hero-section > .e-con-inner { min-height: auto; }
		.tn-title-widget .elementor-heading-title { font-size: clamp(3.2rem, 15vw, 6.5rem); white-space: normal; }
		.tn-final-card .elementor-widget-wrap { align-items: flex-start; flex-direction: column; }
		.tn-card-column .elementor-widget-wrap,
		.tn-quote-card .elementor-widget-wrap,
		.tn-final-card .elementor-widget-wrap {
			height: auto !important;
			min-height: 0 !important;
		}
		.tn-event-section > .elementor-container,
		.tn-event-final-section > .elementor-container {
			display: block !important;
		}
		.tn-event-section .elementor-column,
		.tn-event-final-section .elementor-column {
			width: 100% !important;
			margin: 0 0 0.75rem !important;
			padding-left: 0 !important;
			padding-right: 0 !important;
		}
		.tn-event-section .elementor-column:last-child,
		.tn-event-final-section .elementor-column:last-child { margin-bottom: 0 !important; }
		.tn-event-facts-panel {
			flex-basis: 100%;
			width: 100%;
			margin: 1rem 0 0;
		}
		.tn-event-hero-section > .elementor-container.tn-has-event-facts,
		.tn-event-hero-section > .e-con-inner.tn-has-event-facts,
		.tn-event-hero-section > .elementor-container:has(.tn-event-facts-panel),
		.tn-event-hero-section > .e-con-inner:has(.tn-event-facts-panel) {
			flex-wrap: wrap !important;
		}
		.tn-event-hero-section > .elementor-container.tn-has-event-facts .tn-event-hero-copy,
		.tn-event-hero-section > .e-con-inner.tn-has-event-facts .tn-event-hero-copy,
		.tn-event-hero-section > .elementor-container:has(.tn-event-facts-panel) .tn-event-hero-copy,
		.tn-event-hero-section > .e-con-inner:has(.tn-event-facts-panel) .tn-event-hero-copy {
			flex-basis: 100% !important;
			max-width: 100% !important;
		}
		.tn-presenter-card { grid-template-columns: 1fr; }
	}
	@media (max-width: 520px) {
		.tn-scoreboard { grid-template-columns: 1fr; }
		.tn-scoreboard .tn-versus { display: none; }
		.tn-court { min-height: 240px; }
	}

	/* Editable Elementor event pages: visual polish pass */
	.tn-event-hero-section {
		background:
			radial-gradient(circle at 18% 22%, rgba(0,229,255,0.16), transparent 28rem),
			radial-gradient(circle at 82% 28%, rgba(255,45,149,0.15), transparent 30rem),
			linear-gradient(180deg, rgba(7,8,18,0.28), var(--tn-bg) 72%),
			linear-gradient(135deg, rgba(0,229,255,0.13), transparent 38%, rgba(255,45,149,0.12));
	}
	.tn-event-hero-section > .elementor-container,
	.tn-event-hero-section > .e-con-inner {
		align-items: center !important;
		min-height: auto !important;
		padding-top: clamp(1rem, 2.5vw, 2.8rem) !important;
		padding-bottom: clamp(0.8rem, 1.6vw, 1.25rem) !important;
	}
	.tn-event-hero-section .elementor-column-wrap,
	.tn-event-hero-section .elementor-widget-wrap {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}
	.tn-event-hero-section .elementor-widget,
	.tn-event-section .elementor-widget,
	.tn-event-final-section .elementor-widget {
		margin-bottom: 0.45rem !important;
	}
	.tn-event-hero-section .elementor-widget:last-child,
	.tn-event-section .elementor-widget:last-child,
	.tn-event-final-section .elementor-widget:last-child {
		margin-bottom: 0 !important;
	}
	.tn-event-hero-copy > .elementor-widget-wrap {
		align-content: flex-start !important;
		align-items: flex-start !important;
	}
	.tn-title-widget .elementor-heading-title {
		text-shadow: 0 18px 46px rgba(0,0,0,0.38);
	}
	.tn-title-accent-widget .elementor-heading-title {
		text-shadow: 0 0 34px rgba(0,229,255,0.18);
	}
	.tn-lede-widget {
		margin-top: 0.45rem !important;
		margin-bottom: 0.45rem !important;
	}
	.tn-button-primary.elementor-widget,
	.tn-button-secondary.elementor-widget {
		margin-top: 0.25rem !important;
	}
	.tn-button-primary .elementor-button {
		box-shadow: 0 16px 42px rgba(0,229,255,0.18), 0 10px 32px rgba(255,45,149,0.15);
	}
	.tn-button-secondary .elementor-button {
		box-shadow: inset 0 0 0 1px rgba(255,255,255,0.04);
	}
	.tn-hero-panel,
	.tn-card-column .elementor-widget-wrap,
	.tn-quote-card .elementor-widget-wrap,
	.tn-final-card .elementor-widget-wrap {
		box-shadow: 0 18px 70px rgba(0,0,0,0.28);
	}
	.tn-hero-panel {
		border-color: rgba(255,255,255,0.18);
		background: rgba(14,17,31,0.88);
	}
	.tn-card-column .elementor-widget-wrap {
		background: linear-gradient(180deg, rgba(255,255,255,0.055), rgba(17,21,37,0.86));
	}
	.tn-card-accent .elementor-widget-wrap {
		box-shadow: 0 20px 70px rgba(0,229,255,0.08), 0 18px 70px rgba(0,0,0,0.28);
	}
	.tn-event-hero-section + .tn-event-section > .elementor-container,
	.tn-event-hero-section + .tn-event-section > .e-con-inner {
		padding-top: 0 !important;
	}
	.tn-event-hero-section + .tn-event-section,
	.tn-event-section + .tn-event-section,
	.tn-event-section + .tn-event-final-section {
		margin-top: clamp(0.8rem, 1.4vw, 1.15rem) !important;
	}
	.tn-event-section > .elementor-container,
	.tn-event-section > .e-con-inner,
	.tn-event-final-section > .elementor-container,
	.tn-event-final-section > .e-con-inner {
		padding-top: 0 !important;
		padding-bottom: 0 !important;
	}
	.tn-event-section .elementor-column,
	.tn-event-final-section .elementor-column {
		padding-left: 0.45rem !important;
		padding-right: 0.45rem !important;
	}
	.tn-event-section > .elementor-container,
	.tn-event-final-section > .elementor-container {
		margin-left: auto !important;
		margin-right: auto !important;
	}
	@media (min-width: 900px) {
		.tn-event-hero-section > .elementor-container,
		.tn-event-hero-section > .e-con-inner {
			gap: clamp(2rem, 5vw, 4.5rem);
		}
	}
	@media (max-width: 700px) {
		.tn-event-hero-section > .elementor-container,
		.tn-event-hero-section > .e-con-inner {
			padding-top: 1rem;
		}
		.tn-title-widget .elementor-heading-title {
			font-size: clamp(2.8rem, 14vw, 3.6rem) !important;
			line-height: 0.86 !important;
			white-space: normal !important;
			overflow-wrap: normal !important;
			word-break: normal !important;
		}
		.tn-event-hero-copy,
		.tn-event-hero-copy > .elementor-widget-wrap {
			min-width: 0 !important;
			max-width: 100% !important;
		}
	}
	</style>
	<?php
} );

// Make Elementor's editor shell/preview more closely match the public event pages.
add_action( 'elementor/editor/after_enqueue_styles', function () {
	wp_register_style( 'tn-event-detail-editor-shell', false );
	wp_enqueue_style( 'tn-event-detail-editor-shell' );
	wp_add_inline_style( 'tn-event-detail-editor-shell', '
		#elementor-preview,
		#elementor-preview-responsive-wrapper {
			background: #070812 !important;
		}
	' );
} );

add_action( 'elementor/preview/enqueue_styles', function () {
	wp_register_style( 'tn-event-detail-editor-preview', false );
	wp_enqueue_style( 'tn-event-detail-editor-preview' );
	wp_add_inline_style( 'tn-event-detail-editor-preview', '
		body.elementor-editor-active,
		body.elementor-editor-preview {
			background: #070812 !important;
			overflow-x: hidden;
		}
		body.elementor-editor-active .tn-event-section,
		body.elementor-editor-active .tn-event-final-section,
		body.elementor-editor-preview .tn-event-section,
		body.elementor-editor-preview .tn-event-final-section {
			height: auto !important;
			min-height: 0 !important;
			margin-bottom: 0 !important;
			padding-top: 0 !important;
			padding-bottom: 0 !important;
		}
		body.elementor-editor-active .tn-event-section > .elementor-container,
		body.elementor-editor-active .tn-event-section > .e-con-inner,
		body.elementor-editor-active .tn-event-final-section > .elementor-container,
		body.elementor-editor-active .tn-event-final-section > .e-con-inner,
		body.elementor-editor-preview .tn-event-section > .elementor-container,
		body.elementor-editor-preview .tn-event-section > .e-con-inner,
		body.elementor-editor-preview .tn-event-final-section > .elementor-container,
		body.elementor-editor-preview .tn-event-final-section > .e-con-inner {
			height: auto !important;
			min-height: 0 !important;
			padding-top: 0 !important;
			padding-bottom: 0 !important;
		}
		body.elementor-editor-active .tn-card-column .elementor-widget-wrap,
		body.elementor-editor-active .tn-quote-card .elementor-widget-wrap,
		body.elementor-editor-active .tn-final-card .elementor-widget-wrap,
		body.elementor-editor-preview .tn-card-column .elementor-widget-wrap,
		body.elementor-editor-preview .tn-quote-card .elementor-widget-wrap,
		body.elementor-editor-preview .tn-final-card .elementor-widget-wrap {
			height: auto !important;
			min-height: 0 !important;
		}
	' );
} );

// ─── Front-end: Schedule Mode time display ──────────────────────────────────

add_action( 'wp_footer', function () {
	if ( get_option( 'tn_schedule_mode', 'off' ) !== 'on' ) return;
	if ( ! is_front_page() && ! is_page( 5 ) ) return;
	?>
	<style>
	.schedule-item .tn-time-badge {
		display: inline-block;
		font-size: 0.72rem;
		font-weight: 600;
		color: #00e5ff;
		background: rgba(0,229,255,0.08);
		border: 1px solid rgba(0,229,255,0.2);
		border-radius: 4px;
		padding: 0.15rem 0.5rem;
		margin-right: 0.5rem;
		letter-spacing: 0.02em;
		vertical-align: middle;
		white-space: nowrap;
		font-family: 'Outfit', -apple-system, sans-serif;
	}
	</style>
	<script>
	(function(){
		document.querySelectorAll('.schedule-item[data-start]').forEach(function(item){
			var start = item.getAttribute('data-start');
			var end   = item.getAttribute('data-end');
			if (!start) return;
			var nameEl = item.querySelector('.event-name');
			if (!nameEl || nameEl.querySelector('.tn-time-badge')) return;
			var badge = document.createElement('span');
			badge.className = 'tn-time-badge';
			badge.textContent = end ? start + ' – ' + end : start;
			nameEl.insertBefore(badge, nameEl.firstChild);
		});
	})();
	</script>
	<?php
} );

// ─── Front-end: Optional event "More Info" button ──────────────────────────

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	if ( ! is_front_page() && ! is_page( 5 ) ) return;
	$info_posts = get_posts( [
		'post_type'      => [ 'page', 'post' ],
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );
	$published_info_urls = [];
	foreach ( $info_posts as $post_id ) {
		$url = get_permalink( $post_id );
		if ( $url ) $published_info_urls[] = $url;
	}
	?>
	<style>
	.event-modal-more {
		display: none;
		width: 100%;
		margin-top: 1.35rem;
		padding: 0.95rem 1.25rem;
		border-radius: 999px;
		background: linear-gradient(135deg, var(--cyan, #00e5ff), var(--pink, #ff2d95)) !important;
		color: var(--white) !important;
		font-family: var(--font-display);
		font-size: 0.9rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		justify-content: center;
		text-decoration: none;
		text-transform: uppercase;
		border: 1px solid rgba(255,255,255,0.18) !important;
	}
	.event-modal-card {
		max-height: calc(100dvh - 2rem);
		overflow-y: auto;
		-webkit-overflow-scrolling: touch;
	}
	#modal-desc .tn-event-modal-graphic,
	#modal-desc img {
		display: block;
		width: 100%;
		max-width: 100%;
		height: auto;
		margin: 0 0 1rem;
		border-radius: 10px;
		border: 1px solid rgba(255,255,255,0.14);
		background: rgba(255,255,255,0.06);
	}
	.event-modal-more {
		position: sticky;
		bottom: 0.35rem;
		z-index: 2;
		box-shadow: 0 -16px 28px rgba(17,21,37,0.78), 0 16px 36px rgba(0,0,0,0.28);
	}
	.event-modal-more:hover {
		color: var(--white);
		transform: translateY(-1px);
	}
	@media (max-width: 640px) {
		.event-modal-overlay {
			align-items: flex-end;
			padding: 0.75rem;
		}
		.event-modal-card {
			display: flex;
			flex-direction: column;
			width: 100%;
			max-height: min(82dvh, calc((var(--tn-vh, 1vh) * 82)));
			overflow: hidden;
			padding: 1.65rem 1.25rem calc(1rem + env(safe-area-inset-bottom));
			border-radius: 16px;
		}
		#modal-tag,
		#modal-title,
		.event-modal-close {
			flex: 0 0 auto;
		}
		#modal-desc {
			flex: 1 1 auto;
			min-height: 0;
			overflow-y: auto;
			-webkit-overflow-scrolling: touch;
			padding-right: 0.2rem;
		}
		#modal-desc .tn-event-modal-graphic,
		#modal-desc img {
			display: block;
			width: 100%;
			max-width: 100%;
			height: auto;
			margin: 0 0 1rem;
			border-radius: 10px;
			border: 1px solid rgba(255,255,255,0.14);
			background: rgba(255,255,255,0.06);
		}
		.event-modal-more {
			width: 100%;
			justify-content: center;
			margin-top: 1rem;
			position: relative;
			bottom: auto;
			flex: 0 0 auto;
			min-height: 52px;
		}
	}
	</style>
	<script>
	(function(){
		function setViewportUnit() {
			document.documentElement.style.setProperty('--tn-vh', (window.innerHeight * 0.01) + 'px');
		}
		setViewportUnit();
		window.addEventListener('resize', setViewportUnit);

		var PUBLISHED_INFO_URLS = <?php echo wp_json_encode( array_values( $published_info_urls ) ); ?>;
		var DYNAMIC_EVENT_BASE = <?php echo wp_json_encode( home_url( '/event-info/' ) ); ?>;
		var publishedUrlMap = {};

		function normalizeInfoUrl(url) {
			url = String(url || '').trim();
			if (!url || url.charAt(0) === '#') return '';
			try {
				var parsed = new URL(url, window.location.origin);
				if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') return '';
				if (parsed.origin !== window.location.origin) return '';
				parsed.hash = '';
				parsed.search = '';
				var normalized = parsed.origin + parsed.pathname.replace(/\/+$/, '') + '/';
				return normalized;
			} catch(e) {}
			return '';
		}

		PUBLISHED_INFO_URLS.forEach(function(url) {
			var normalized = normalizeInfoUrl(url);
			if (normalized) publishedUrlMap[normalized] = url;
		});

		function safeInfoUrl(url) {
			var normalized = normalizeInfoUrl(url);
			return normalized && publishedUrlMap[normalized] ? publishedUrlMap[normalized] : '';
		}

		function eventSlug(value) {
			return String(value || '')
				.toLowerCase()
				.replace(/&/g, ' ')
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/^-+|-+$/g, '') || 'event';
		}

		function dynamicEventUrl(title) {
			return DYNAMIC_EVENT_BASE + eventSlug(title) + '/';
		}

		function safeDynamicEventUrl(url) {
			var normalized = normalizeInfoUrl(url);
			var base = normalizeInfoUrl(DYNAMIC_EVENT_BASE);
			return normalized && base && normalized.indexOf(base) === 0 ? normalized : '';
		}

		function moreButton() {
			var btn = document.getElementById('modal-more-info');
			if (btn) return btn;
			var desc = document.getElementById('modal-desc');
			if (!desc || !desc.parentNode) return null;
			btn = document.createElement('a');
			btn.id = 'modal-more-info';
			btn.className = 'event-modal-more';
			btn.textContent = 'More Info';
			desc.insertAdjacentElement('afterend', btn);
			return btn;
		}

		function setMoreButton(url) {
			var btn = document.getElementById('modal-more-info');
			if (!btn) return;
			btn.removeAttribute('href');
			btn.style.display = 'none';
		}

		function safeImageUrl(url) {
			url = String(url || '').trim();
			if (!url) return '';
			try {
				var parsed = new URL(url, window.location.origin);
				if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') return '';
				if (parsed.hostname !== window.location.hostname) return '';
				if (window.location.protocol === 'https:' && parsed.protocol === 'http:') {
					parsed.protocol = 'https:';
				}
				return parsed.href;
			} catch(e) {}
			return '';
		}

		function setModalGraphic(url, alt) {
			var desc = document.getElementById('modal-desc');
			if (!desc) return;
			var existing = desc.querySelector('.tn-event-modal-graphic');
			if (existing) existing.remove();
			var src = safeImageUrl(url);
			if (!src) return;
			var img = document.createElement('img');
			img.className = 'tn-event-modal-graphic';
			img.src = src;
			img.alt = String(alt || '').trim();
			desc.insertAdjacentElement('afterbegin', img);
		}

		function formatEventDescription(desc) {
			var allowedInline = { A: true, STRONG: true, B: true, EM: true, I: true, U: true, BR: true, P: true, UL: true, OL: true, LI: true, IMG: true };
			var template = document.createElement('template');
			template.innerHTML = desc || '';

			function prepareLink(anchor, href) {
				anchor.setAttribute('href', href);
				anchor.setAttribute('target', '_blank');
				anchor.setAttribute('rel', 'noopener noreferrer');
			}

			function clean(node) {
				Array.from(node.childNodes).forEach(function(child) {
					if (child.nodeType === Node.TEXT_NODE) return;
					if (child.nodeType !== Node.ELEMENT_NODE) {
						child.remove();
						return;
					}
					if (!allowedInline[child.tagName]) {
						child.replaceWith(document.createTextNode(child.textContent || ''));
						return;
					}
					Array.from(child.attributes).forEach(function(attr) {
						var name = attr.name.toLowerCase();
						if (child.tagName === 'A' && name === 'href') return;
						if (child.tagName === 'IMG' && ['src','alt','width','height','class'].indexOf(name) !== -1) return;
						child.removeAttribute(attr.name);
					});
					if (child.tagName === 'A') {
						var href = child.getAttribute('href') || '';
						if (!/^(https?:|mailto:|tel:|#)/i.test(href)) {
							child.replaceWith(document.createTextNode(child.textContent || ''));
							return;
						}
						prepareLink(child, href);
					}
					if (child.tagName === 'IMG') {
						var src = child.getAttribute('src') || '';
						if (!/^https?:/i.test(src)) {
							child.remove();
							return;
						}
					}
					clean(child);
				});
			}

			clean(template.content);
			return template.innerHTML;
		}

		function openModalFallback(title, tagLabel, tagClass, desc) {
			var tag = document.getElementById('modal-tag');
			var titleEl = document.getElementById('modal-title');
			var descEl = document.getElementById('modal-desc');
			var modal = document.getElementById('event-modal');
			if (tag) {
				tag.textContent = tagLabel || '';
				tag.className = 'event-tag ' + (tagClass || '');
			}
			if (titleEl) titleEl.textContent = title || '';
			if (descEl) descEl.innerHTML = formatEventDescription(desc || '');
			if (modal) modal.classList.add('open');
			document.body.style.overflow = 'hidden';
		}

		var originalOpen = window.openEventModal;
		window.openEventModal = function(title, tagLabel, tagClass, desc, infoUrl, imageUrl, imageAlt) {
			if (document.getElementById('event-modal')) {
				openModalFallback(title, tagLabel, tagClass, desc);
			} else if (typeof originalOpen === 'function') {
				originalOpen(title, tagLabel, tagClass, desc);
			}
			setMoreButton(infoUrl);
			setModalGraphic(imageUrl, imageAlt || title);
			var card = document.querySelector('.event-modal-card');
			var descEl = document.getElementById('modal-desc');
			if (card) card.scrollTop = 0;
			if (descEl) descEl.scrollTop = 0;
		};

		document.addEventListener('click', function(e) {
			var item = e.target.closest('.schedule-item[data-desc]');
			if (!item) return;
			var href = safeInfoUrl(item.getAttribute('data-info-url'));
			e.preventDefault();
			e.stopImmediatePropagation();
			window.openEventModal(
				item.dataset.title || '',
				item.dataset.tagLabel || '',
				item.dataset.tagClass || '',
				item.dataset.desc || '',
				dynamicEventUrl(item.dataset.title || '') || href,
				item.dataset.image || '',
				item.dataset.imageAlt || item.dataset.title || ''
			);
		}, true);
	})();
	</script>
	<?php
}, 20 );

// ─── Front-end: Event detail page presenter section ─────────────────────────

function tn_tde_normalize_internal_url( $url ) {
	$url = trim( (string) $url );
	if ( $url === '' || $url[0] === '#' ) return '';
	$parts = wp_parse_url( $url );
	if ( ! $parts ) return '';
	$scheme = $parts['scheme'] ?? wp_parse_url( home_url(), PHP_URL_SCHEME );
	$host   = $parts['host'] ?? wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) return '';
	if ( strtolower( $host ) !== strtolower( wp_parse_url( home_url(), PHP_URL_HOST ) ) ) return '';
	$path = $parts['path'] ?? '/';
	return home_url( trailingslashit( $path ) );
}

function tn_tde_find_home_schedule_html( $nodes ) {
	if ( ! is_array( $nodes ) ) return '';
	foreach ( $nodes as $node ) {
		if (
			isset( $node['widgetType'], $node['settings']['html'] ) &&
			$node['widgetType'] === 'html' &&
			strpos( $node['settings']['html'], 'schedule-item' ) !== false
		) {
			return $node['settings']['html'];
		}
		if ( ! empty( $node['elements'] ) ) {
			$found = tn_tde_find_home_schedule_html( $node['elements'] );
			if ( $found ) return $found;
		}
	}
	return '';
}

function tn_tde_day_definitions() {
	return [
		'day-friday'   => [ 'label' => 'Friday', 'date' => 'August 7, 2026', 'iso' => '2026-08-07' ],
		'day-saturday' => [ 'label' => 'Saturday', 'date' => 'August 8, 2026', 'iso' => '2026-08-08' ],
		'day-sunday'   => [ 'label' => 'Sunday', 'date' => 'August 9, 2026', 'iso' => '2026-08-09' ],
	];
}

function tn_tde_location_options() {
	return [
		'office-registration-foyer' => 'Office/Registration Foyer',
		'grand-ballroom-a'          => 'Grand Ballroom A',
		'sonoma-a'                  => 'Sonoma A',
		'sonoma-b'                  => 'Sonoma B',
		'breakout-rooms'            => 'Breakout Rooms',
	];
}

function tn_tde_normalize_location( $value ) {
	$value = sanitize_title( (string) $value );
	$options = tn_tde_location_options();
	return isset( $options[ $value ] ) ? $value : '';
}

function tn_tde_location_label( $value ) {
	$options = tn_tde_location_options();
	$key = tn_tde_normalize_location( $value );
	return $key && isset( $options[ $key ] ) ? $options[ $key ] : 'Location TBA';
}

function tn_tde_parse_start_minutes( $value ) {
	$value = strtolower( trim( (string) $value ) );
	if ( $value === '' ) return null;
	if ( ! preg_match( '/^(\d{1,2})(?::(\d{2}))?\s*(a|am|p|pm)?$/', $value, $match ) ) return null;
	$hours = absint( $match[1] );
	$mins = isset( $match[2] ) && $match[2] !== '' ? absint( $match[2] ) : 0;
	$meridian = $match[3] ?? '';
	if ( $hours > 24 || $mins > 59 ) return null;
	if ( $meridian && $meridian[0] === 'p' && $hours < 12 ) $hours += 12;
	if ( $meridian && $meridian[0] === 'a' && $hours === 12 ) $hours = 0;
	return ( $hours * 60 ) + $mins;
}

function tn_tde_allowed_description_html() {
	return [
		'a'      => [ 'href' => true, 'target' => true, 'rel' => true ],
		'br'     => [],
		'strong' => [],
		'b'      => [],
		'em'     => [],
		'i'      => [],
		'u'      => [],
		'p'      => [],
		'div'    => [],
		'ul'     => [],
		'ol'     => [],
		'li'     => [],
		'img'    => [
			'src' => true,
			'alt' => true,
			'width' => true,
			'height' => true,
			'class' => true,
		],
	];
}

function tn_tde_clean_description_html( $html ) {
	$html = (string) $html;
	$html = wp_kses( $html, tn_tde_allowed_description_html() );
	$html = make_clickable( $html );
	$html = preg_replace_callback( '/<a\s+([^>]*href=[^>]*)>/i', function( $match ) {
		$tag = $match[0];
		if ( stripos( $tag, 'rel=' ) === false ) {
			$tag = rtrim( $tag, '>' ) . ' rel="noopener noreferrer">';
		}
		return $tag;
	}, $html );
	return $html;
}

function tn_tde_render_description_html( $html ) {
	$html = (string) $html;
	if ( trim( $html ) === '' ) return '';
	// Rich descriptions already carry their own block structure from the editor;
	// wpautop() is regex-based and mangles nesting when applied on top of that.
	// Only auto-paragraph legacy plain-text descriptions that have none.
	if ( ! preg_match( '/<(p|div|ul|ol|li|br)\b/i', $html ) ) {
		$html = wpautop( $html );
	}
	return wp_kses_post( $html );
}

function tn_tde_event_detail_slug( $event ) {
	$title = sanitize_text_field( $event['title'] ?? '' );
	$slug = sanitize_title( $title );
	return $slug ?: 'event';
}

function tn_tde_event_detail_url( $event ) {
	return home_url( '/event-info/' . tn_tde_event_detail_slug( $event ) . '/' );
}

function tn_tde_get_home_schedule_events() {
	$raw = get_post_meta( 5, '_elementor_data', true );
	if ( ! $raw ) return [];
	$data = json_decode( $raw, true );
	if ( ! $data ) return [];
	$html = tn_tde_find_home_schedule_html( is_array( $data ) ? $data : [ $data ] );
	if ( ! $html || ! class_exists( 'DOMDocument' ) ) return [];

	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );

	$events = [];
	$days = tn_tde_day_definitions();
	foreach ( $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " schedule-item ")]' ) as $item ) {
		$day_id = '';
		for ( $node = $item; $node; $node = $node->parentNode ) {
			if ( ! method_exists( $node, 'getAttribute' ) ) continue;
			$id = $node->getAttribute( 'id' );
			if ( isset( $days[ $id ] ) ) {
				$day_id = $id;
				break;
			}
		}
		if ( ! $day_id ) continue;
		$json = html_entity_decode( $item->getAttribute( 'data-presenters' ), ENT_QUOTES, 'UTF-8' );
		$presenters = json_decode( $json, true );
		$sessions_json = html_entity_decode( $item->getAttribute( 'data-sessions' ), ENT_QUOTES, 'UTF-8' );
		$sessions = tn_tde_clean_sessions( json_decode( $sessions_json, true ) );
		$start = sanitize_text_field( $item->getAttribute( 'data-start' ) );
		$end = sanitize_text_field( $item->getAttribute( 'data-end' ) );
		$location = tn_tde_normalize_location( $item->getAttribute( 'data-location' ) );
		$base_title = sanitize_text_field( $item->getAttribute( 'data-title' ) );
		$event_type = tn_tde_clean_event_type_key( $item->getAttribute( 'data-event-type' ) );
		$event_type_definition = tn_tde_event_type_definition( $event_type );
		$base_event = [
			'day_id' => $day_id,
			'day_label' => $days[ $day_id ]['label'],
			'date_label' => $days[ $day_id ]['date'],
			'date_iso' => $days[ $day_id ]['iso'],
			'title' => $base_title,
			'base_title' => $base_title,
			'session_label' => '',
			'description' => tn_tde_clean_description_html( html_entity_decode( $item->getAttribute( 'data-desc' ), ENT_QUOTES, 'UTF-8' ) ),
			'image' => esc_url_raw( $item->getAttribute( 'data-image' ) ),
			'image_alt' => sanitize_text_field( $item->getAttribute( 'data-image-alt' ) ),
			'info_url' => esc_url_raw( $item->getAttribute( 'data-info-url' ) ),
			'category' => sanitize_text_field( $item->getAttribute( 'data-tag-label' ) ) ?: 'Event',
			'category_class' => sanitize_html_class( $item->getAttribute( 'data-tag-class' ) ?: 'tag-special' ),
			'event_type' => $event_type,
			'event_type_label' => $event_type === tn_tde_default_schedule_event_type_key() ? '' : sanitize_text_field( $event_type_definition['label'] ?? '' ),
			'event_type_color' => sanitize_hex_color( $event_type_definition['color'] ?? '' ) ?: '',
			'start' => $start,
			'end' => $end,
			'start_minutes' => tn_tde_parse_start_minutes( $start ),
			'after_hours' => in_array( strtolower( $item->getAttribute( 'data-after-hours' ) ), [ '1', 'true', 'yes' ], true ),
			'location' => $location,
			'location_label' => tn_tde_location_label( $location ),
			'presenters' => tn_tde_clean_presenters( $presenters ),
			'signup_full' => false,
		];
		if ( $sessions ) {
			foreach ( $sessions as $session ) {
				$session_event = $base_event;
				$session_event['session_label'] = $session['label'];
				$session_event['title'] = $session['label'] ? $base_title . ': ' . $session['label'] : $base_title;
				$session_event['start'] = $session['start'];
				$session_event['end'] = $session['end'];
				$session_event['start_minutes'] = tn_tde_parse_start_minutes( $session['start'] );
				$session_event['after_hours'] = $base_event['after_hours'];
				$session_event['location'] = $session['location'];
				$session_event['location_label'] = tn_tde_location_label( $session['location'] );
				$session_event['signup_full'] = ! empty( $session['full'] );
				$events[] = $session_event;
			}
		} else {
			$events[] = $base_event;
		}
	}

	usort( $events, function( $a, $b ) {
		$days = array_keys( tn_tde_day_definitions() );
		$day_cmp = array_search( $a['day_id'], $days, true ) <=> array_search( $b['day_id'], $days, true );
		if ( $day_cmp !== 0 ) return $day_cmp;
		$at = $a['start_minutes'];
		$bt = $b['start_minutes'];
		if ( $at === null && $bt === null ) return 0;
		if ( $at === null ) return 1;
		if ( $bt === null ) return -1;
		return $at <=> $bt;
	} );

	return $events;
}

function tn_tde_clean_presenters( $presenters ) {
	if ( ! is_array( $presenters ) ) return [];
	return array_values( array_filter( array_map( function( $presenter ) {
		$name  = sanitize_text_field( $presenter['name'] ?? '' );
		$bio   = wp_kses_post( $presenter['bio'] ?? '' );
		$photo = esc_url_raw( $presenter['photo'] ?? '' );
		if ( $name === '' && wp_strip_all_tags( $bio ) === '' && $photo === '' ) return null;
		return [
			'name'  => $name,
			'bio'   => $bio,
			'photo' => $photo,
		];
	}, $presenters ) ) );
}

function tn_tde_clean_sessions( $sessions ) {
	if ( ! is_array( $sessions ) ) return [];
	return array_values( array_filter( array_map( function( $session ) {
		$label = sanitize_text_field( $session['label'] ?? '' );
		$start = sanitize_text_field( $session['start'] ?? '' );
		$end = sanitize_text_field( $session['end'] ?? '' );
		$location = tn_tde_normalize_location( $session['location'] ?? '' );
		$full = ! empty( $session['full'] );
		if ( $label === '' && $start === '' && $end === '' && $location === '' ) return null;
		return [
			'label' => $label,
			'start' => $start,
			'end' => $end,
			'location' => $location,
			'full' => $full,
		];
	}, $sessions ) ) );
}

// ─── Event signup forms + Google Sheets sync ────────────────────────────────

function tn_tde_signup_event_titles() {
	return [
		'Quiz Bowl',
		'BP Titans',
		'5x5',
		'5 x 5',
		'Trivia The Gathering',
		'Trivia the Gathering',
		'TTG',
		'Trivia Spelling Bee',
		'Academic Bee',
		'Pop Culture Bee',
		'Crossword Challenge',
		'IQA Individual Championship',
		'IQA Individual Quiz Championship',
		'IQA Knock Out Quiz with Steve Perry',
	];
}

function tn_tde_signup_team_event_titles() {
	return [ 'Quiz Bowl', 'BP Titans' ];
}

function tn_tde_signup_normalize_title( $title ) {
	return trim( (string) preg_replace( '/[^a-z0-9]+/', ' ', strtolower( (string) $title ) ) );
}

function tn_tde_signup_title_matches( $title, $allowed_titles ) {
	$title = tn_tde_signup_normalize_title( $title );
	foreach ( $allowed_titles as $allowed_title ) {
		$allowed = tn_tde_signup_normalize_title( $allowed_title );
		if ( $title === $allowed || strpos( $title, $allowed ) !== false ) return true;
	}
	return false;
}

function tn_tde_event_accepts_signup( $event ) {
	$title = $event['base_title'] ?? $event['title'] ?? '';
	if ( strpos( tn_tde_signup_normalize_title( $title ), 'finals' ) !== false ) return false;
	return tn_tde_signup_title_matches( $title, tn_tde_signup_event_titles() );
}

function tn_tde_event_is_team_signup( $event ) {
	$title = $event['base_title'] ?? $event['title'] ?? '';
	if ( strpos( tn_tde_signup_normalize_title( $title ), 'finals' ) !== false ) return false;
	return tn_tde_signup_title_matches( $title, tn_tde_signup_team_event_titles() );
}

function tn_tde_signup_split_flight_label( $label ) {
	$label = trim( (string) $label );
	if ( $label === '' ) return [];
	$clean_label = $label;
	if ( ! preg_match( '/^Flights?\s+(.+)$/i', $clean_label, $match ) ) {
		return [ $clean_label ];
	}
	$flight_text = trim( preg_replace( '/\)+$/', '', $match[1] ) );
	$flight_text = str_replace( [ '–', '—' ], '-', $flight_text );
	$flights = [];
	if ( preg_match( '/^([a-z])\s*-\s*([a-z])$/i', $flight_text, $range_match ) ) {
		$start = ord( strtoupper( $range_match[1] ) );
		$end = ord( strtoupper( $range_match[2] ) );
		if ( $start <= $end ) {
			for ( $letter = $start; $letter <= $end; $letter++ ) {
				$flights[] = 'Flight ' . chr( $letter );
			}
			return $flights;
		}
	}
	foreach ( preg_split( '/\s*(?:,|&|\band\b)\s*/i', $flight_text ) as $part ) {
		$part = trim( preg_replace( '/[^a-z0-9 -]+/i', '', preg_replace( '/\)+$/', '', $part ) ) );
		if ( $part === '' ) continue;
		$flights[] = preg_match( '/^Flight\s+/i', $part ) ? $part : 'Flight ' . $part;
	}
	return $flights ?: [ $clean_label ];
}

function tn_tde_signup_option_time_label( $event ) {
	$date = trim( sanitize_text_field( ( $event['day_label'] ?? '' ) . ( ! empty( $event['date_label'] ) ? ', ' . $event['date_label'] : '' ) ) );
	$start = trim( sanitize_text_field( $event['start'] ?? '' ) );
	$end = trim( sanitize_text_field( $event['end'] ?? '' ) );
	$time = $start && $end ? $start . ' - ' . $end : $start;
	if ( $date && $time ) return $date . ', ' . $time;
	return $date ?: $time;
}

function tn_tde_signup_is_ttg_event( $event ) {
	$title = $event['base_title'] ?? $event['title'] ?? '';
	return tn_tde_signup_title_matches( $title, [ 'Trivia The Gathering', 'Trivia the Gathering', 'TTG' ] );
}

function tn_tde_signup_ttg_flight_labels() {
	return [ 'Flight A', 'Flight B', 'Flight C' ];
}

function tn_tde_signup_is_friday_event( $event ) {
	$day_date = strtolower( trim( (string) ( $event['day_label'] ?? '' ) . ' ' . (string) ( $event['date_label'] ?? '' ) ) );
	return strpos( $day_date, 'friday' ) !== false;
}

function tn_tde_signup_custom_flight_time_label( $event, $flight_label ) {
	if ( ! tn_tde_signup_is_ttg_event( $event ) ) return '';
	if ( ! tn_tde_signup_is_friday_event( $event ) && empty( $event['_tn_tde_force_ttg_friday'] ) ) return '';
	$flight_key = strtoupper( trim( preg_replace( '/^Flight\s+/i', '', (string) $flight_label ) ) );
	$times = [
		'A' => '12:55 PM - 1:30 PM',
		'B' => '1:30 PM - 2:10 PM',
		'C' => '2:10 PM - 2:50 PM',
		'D' => '2:50 PM - 3:30 PM',
		'E' => '3:30 PM - 4:10 PM',
	];
	if ( empty( $times[ $flight_key ] ) ) return '';
	$date = trim( sanitize_text_field( ( $event['day_label'] ?? '' ) . ( ! empty( $event['date_label'] ) ? ', ' . $event['date_label'] : '' ) ) );
	if ( $date === '' && ! empty( $event['_tn_tde_force_ttg_friday'] ) ) $date = 'Friday, August 7, 2026';
	return $date ? $date . ', ' . $times[ $flight_key ] : $times[ $flight_key ];
}

function tn_tde_signup_flight_dedupe_key( $flight_label ) {
	$flight_label = trim( (string) $flight_label );
	if ( preg_match( '/^Flight\s+([a-z0-9]+)/i', $flight_label, $match ) ) {
		return 'flight-' . strtolower( $match[1] );
	}
	return strtolower( $flight_label );
}

function tn_tde_signup_flight_options_for_event( $event ) {
	$base_title = sanitize_text_field( $event['base_title'] ?? $event['title'] ?? '' );
	if ( $base_title === '' ) return [];
	if ( tn_tde_signup_is_ttg_event( $event ) ) {
		$waitlist_label = 'Waiting List - Any Flight';
		return [ [
			'value' => $waitlist_label,
			'label' => $waitlist_label,
			'flight' => $waitlist_label,
			'session' => '',
			'event' => $event,
			'event_slug' => tn_tde_event_detail_slug( $event ),
		] ];
	}
	$options = [];
	$ttg_fallback_event = null;
	$has_available_candidate = false;
	foreach ( tn_tde_get_home_schedule_events() as $candidate ) {
		if ( sanitize_text_field( $candidate['base_title'] ?? $candidate['title'] ?? '' ) !== $base_title ) continue;
		if ( ! empty( $candidate['signup_full'] ) ) continue;
		$has_available_candidate = true;
		if ( tn_tde_signup_is_ttg_event( $candidate ) ) {
			if ( tn_tde_signup_is_friday_event( $candidate ) || ! $ttg_fallback_event ) $ttg_fallback_event = $candidate;
		}
		$label = sanitize_text_field( $candidate['session_label'] ?? '' );
		$flight_labels = $label === '' && tn_tde_signup_is_ttg_event( $candidate ) && tn_tde_signup_is_friday_event( $candidate ) ? tn_tde_signup_ttg_flight_labels() : tn_tde_signup_split_flight_label( $label );
		if ( ! $flight_labels ) continue;
		$time_label = tn_tde_signup_option_time_label( $candidate );
		foreach ( $flight_labels as $flight_label ) {
			$flight_time_label = tn_tde_signup_custom_flight_time_label( $candidate, $flight_label ) ?: $time_label;
			$option_label = $flight_time_label ? $flight_label . ' - ' . $flight_time_label : $flight_label;
			$flight_key = tn_tde_signup_flight_dedupe_key( $flight_label );
			if ( isset( $options[ $flight_key ] ) ) continue; // Only show the first (earliest) occurrence of each flight letter.
			$options[ $flight_key ] = [
				'value' => $option_label,
				'label' => $option_label,
				'flight' => $flight_label,
				'session' => $label,
				'event' => $candidate,
				'event_slug' => tn_tde_event_detail_slug( $candidate ),
			];
		}
	}
	if ( ! $options && $has_available_candidate && tn_tde_signup_is_ttg_event( $event ) ) {
		$fallback_event = is_array( $ttg_fallback_event ) ? $ttg_fallback_event : $event;
		$fallback_event['_tn_tde_force_ttg_friday'] = true;
		foreach ( tn_tde_signup_ttg_flight_labels() as $flight_label ) {
			$option_label = $flight_label . ' - ' . tn_tde_signup_custom_flight_time_label( $fallback_event, $flight_label );
			$options[ strtolower( $option_label ) ] = [
				'value' => $option_label,
				'label' => $option_label,
				'flight' => $flight_label,
				'session' => '',
				'event' => $fallback_event,
				'event_slug' => tn_tde_event_detail_slug( $fallback_event ),
			];
		}
	}
	return array_values( $options );
}

function tn_tde_signup_option_for_value( $event, $flight_value ) {
	$flight_value = sanitize_text_field( $flight_value );
	foreach ( tn_tde_signup_flight_options_for_event( $event ) as $option ) {
		if ( $option['value'] === $flight_value ) return $option;
	}
	return null;
}

function tn_tde_signup_events_for_page() {
	$choices = [];
	foreach ( tn_tde_get_home_schedule_events() as $event ) {
		if ( ! tn_tde_event_accepts_signup( $event ) ) continue;
		$title = sanitize_text_field( $event['base_title'] ?? $event['title'] ?? '' );
		if ( $title === '' ) continue;
		$key = tn_tde_signup_normalize_title( $title );
		if ( isset( $choices[ $key ] ) ) continue;
		$choices[ $key ] = [
			'title' => $title . ( tn_tde_signup_is_ttg_event( $event ) ? ' - Waiting List' : '' ),
			'slug' => tn_tde_event_detail_slug( $event ),
			'isTeam' => tn_tde_event_is_team_signup( $event ),
			'isWaitlist' => tn_tde_signup_is_ttg_event( $event ),
			'flights' => array_map( static function( $option ) {
				return [
					'value' => $option['value'],
					'label' => $option['label'],
				];
			}, tn_tde_signup_flight_options_for_event( $event ) ),
		];
	}
	return array_values( $choices );
}

function tn_tde_render_event_signup_form( $event ) {
	if ( ! tn_tde_event_accepts_signup( $event ) ) return '';
	$status = isset( $_GET['tn_signup'] ) ? sanitize_key( wp_unslash( $_GET['tn_signup'] ) ) : '';
	$event_slug = tn_tde_event_detail_slug( $event );
	$flights = tn_tde_signup_flight_options_for_event( $event );
	$current_flight = sanitize_text_field( $event['session_label'] ?? '' );
	$is_team_signup = tn_tde_event_is_team_signup( $event );
	$is_waitlist = tn_tde_signup_is_ttg_event( $event );
	ob_start();
	?>
	<section class="tn-dynamic-event-signup" aria-label="<?php echo esc_attr( $event['base_title'] ?? $event['title'] ?? 'Event' ); ?> signup">
		<h2><?php echo $is_waitlist ? 'Join the Waiting List' : 'Sign Up'; ?></h2>
		<div class="tn-signup-note" role="note">
			<p><strong>Important:</strong> You must be registered for Trivia Nationals 2026 before signing up for events.</p>
			<?php if ( $is_waitlist ) : ?>
				<p><strong>All Trivia: The Gathering flights are currently full.</strong> Submit this form to join the waiting list. This does not guarantee a spot; we will contact you if space becomes available.</p>
			<?php else : ?>
				<p>You may sign up for only one flight per event.</p>
				<p>Flight selection is for denoting your preference. Because of limited capacity, flight assignments cannot be guaranteed, but every effort will be made to get you into the flight you choose.</p>
			<?php endif; ?>
		</div>
		<?php if ( $status === 'success' ) : ?>
			<div class="tn-signup-success-banner" role="status">
				<span class="tn-signup-success-check" aria-hidden="true"></span>
				<div>
					<strong><?php echo $is_waitlist ? 'You&#8217;re on the waiting list!' : 'You&#8217;re signed up!'; ?></strong>
					<p><?php echo $is_waitlist ? 'We will contact you if space becomes available. This waiting-list entry does not guarantee a spot.' : 'Your signup was received.'; ?> You can check or change your signups anytime from the <a href="<?php echo esc_url( home_url( '/event-signups/' ) ); ?>">Event Signups page</a>.</p>
				</div>
			</div>
		<?php elseif ( in_array( $status, [ 'invalid', 'closed', 'missing', 'spam', 'error' ], true ) ) : ?>
			<p class="tn-dynamic-event-signup-message is-error">Sorry, that signup could not be saved. Please check the required fields and try again.</p>
		<?php endif; ?>
		<form class="tn-dynamic-event-signup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="tn_tde_event_signup">
			<input type="hidden" name="tn_event_slug" value="<?php echo esc_attr( $event_slug ); ?>">
			<input type="hidden" name="tn_signup_redirect" value="<?php echo esc_url( remove_query_arg( 'tn_signup' ) ); ?>">
			<?php wp_nonce_field( 'tn_tde_event_signup', 'tn_tde_event_signup_nonce' ); ?>
			<p>
				<label for="tn_signup_name">Name *</label>
				<input type="text" id="tn_signup_name" name="tn_signup_name" required autocomplete="name">
			</p>
			<p>
				<label for="tn_signup_email">Contact Email *</label>
				<input type="email" id="tn_signup_email" name="tn_signup_email" required autocomplete="email">
			</p>
			<?php if ( $is_waitlist && $flights ) : ?>
				<input type="hidden" name="tn_signup_flight" value="<?php echo esc_attr( $flights[0]['value'] ); ?>">
			<?php elseif ( $flights ) : ?>
				<p>
					<label for="tn_signup_flight">Flight *</label>
					<select id="tn_signup_flight" name="tn_signup_flight" required>
						<option value="">Select a flight</option>
						<?php
						$current_flight_parts = tn_tde_signup_split_flight_label( $current_flight );
						$current_flight_selected = false;
						foreach ( $flights as $flight ) :
							$is_current_flight = ! $current_flight_selected && in_array( $flight['flight'], $current_flight_parts, true );
							if ( $is_current_flight ) $current_flight_selected = true;
							?>
							<option value="<?php echo esc_attr( $flight['value'] ); ?>" <?php selected( $is_current_flight ); ?>><?php echo esc_html( $flight['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endif; ?>
			<?php if ( $is_team_signup ) : ?>
				<p class="is-full">
					<label for="tn_signup_team">Team Name</label>
					<input type="text" id="tn_signup_team" name="tn_signup_team" autocomplete="organization">
				</p>
				<p class="is-full">
					<label for="tn_signup_team_members">Team Members</label>
					<textarea id="tn_signup_team_members" name="tn_signup_team_members" rows="3" placeholder="One person can register the whole team. List teammates here if you have them."></textarea>
				</p>
			<?php endif; ?>
			<p class="is-full">
				<label for="tn_signup_notes">Notes</label>
				<textarea id="tn_signup_notes" name="tn_signup_notes" rows="3"></textarea>
			</p>
			<p class="tn-signup-trap" aria-hidden="true">
				<label for="tn_signup_referrer_check">Leave this field blank</label>
				<input type="text" id="tn_signup_referrer_check" name="tn_signup_referrer_check" tabindex="-1" autocomplete="new-password">
			</p>
			<p class="is-full">
				<button type="submit" data-tn-saving-label="<?php echo $is_waitlist ? 'Joining Waiting List...' : 'Submitting...'; ?>"><?php echo $is_waitlist ? 'Join Waiting List' : 'Submit Signup'; ?></button>
			</p>
		</form>
	</section>
	<?php
	return (string) ob_get_clean();
}

add_action( 'wp_footer', function() {
	if ( is_admin() ) return;
	?>
	<style>
	.tn-signup-success-banner {
		display: flex;
		align-items: center;
		gap: 1rem;
		margin: 0 0 1.2rem;
		padding: 1.1rem 1.25rem;
		border: 1px solid rgba(53,230,159,0.55);
		border-radius: 12px;
		background:
			linear-gradient(135deg, rgba(53,230,159,0.16), rgba(0,230,255,0.08)),
			rgba(10,14,24,0.92);
		box-shadow: 0 0 0 1px rgba(53,230,159,0.12), 0 18px 60px rgba(0,0,0,0.3);
		animation: tnBannerIn 0.45s cubic-bezier(0.2, 0.9, 0.3, 1.2);
	}
	.tn-signup-success-banner strong {
		display: block;
		color: #35e69f;
		font-family: Outfit, Inter, sans-serif;
		font-size: 1.2rem;
		font-weight: 900;
		letter-spacing: 0.02em;
	}
	.tn-signup-success-banner p {
		margin: 0.2rem 0 0;
		color: #b7bdcf;
		font-size: 0.95rem;
		line-height: 1.45;
	}
	.tn-signup-success-banner a { color: #00e6ff; }
	.tn-signup-success-check {
		flex: 0 0 auto;
		width: 44px;
		height: 44px;
		border-radius: 50%;
		background: #35e69f;
		position: relative;
		animation: tnCheckPop 0.5s cubic-bezier(0.2, 0.9, 0.3, 1.6);
	}
	.tn-signup-success-check::after {
		content: '';
		position: absolute;
		left: 16px;
		top: 10px;
		width: 10px;
		height: 20px;
		border: solid #06121a;
		border-width: 0 4px 4px 0;
		transform: rotate(45deg);
	}
	@keyframes tnBannerIn {
		from { opacity: 0; transform: translateY(-8px); }
		to { opacity: 1; transform: none; }
	}
	@keyframes tnCheckPop {
		0% { transform: scale(0); }
		70% { transform: scale(1.15); }
		100% { transform: scale(1); }
	}
	.tn-signup-spinner {
		display: inline-block;
		width: 1em;
		height: 1em;
		margin-right: 0.5em;
		vertical-align: -0.15em;
		border: 2px solid currentColor;
		border-right-color: transparent;
		border-radius: 50%;
		animation: tnSpin 0.7s linear infinite;
	}
	@keyframes tnSpin { to { transform: rotate(360deg); } }
	form[data-tn-submitting] button { cursor: progress; }
	@media (prefers-reduced-motion: reduce) {
		.tn-signup-success-banner, .tn-signup-success-check { animation: none; }
		.tn-signup-spinner { animation-duration: 1.4s; }
	}
	</style>
	<script>
	(function(){
		var actions = ['tn_tde_event_signup', 'tn_tde_bulk_event_signup', 'tn_tde_email_signup_summary', 'tn_tde_manage_signup_update', 'tn_tde_manage_signup_cancel'];
		function guardForms() {
			document.querySelectorAll('form').forEach(function(form) {
				var action = form.querySelector('input[name="action"]');
				if (!action || actions.indexOf(action.value) === -1 || form.dataset.tnGuard) return;
				form.dataset.tnGuard = '1';
				form.addEventListener('submit', function(event) {
					if (event.defaultPrevented) return;
					if (form.dataset.tnSubmitting) {
						event.preventDefault();
						return;
					}
					form.dataset.tnSubmitting = '1';
					form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(button) {
						button.disabled = true;
						var label = button.dataset.tnSavingLabel || 'Saving...';
						if (button.tagName === 'INPUT') { button.value = label; return; }
						button.innerHTML = '<span class="tn-signup-spinner" aria-hidden="true"></span>' + label;
					});
				});
			});
		}
		function revealOutcome() {
			var banner = document.querySelector('.tn-signup-success-banner, .tn-signup-page-message.is-error, .tn-dynamic-event-signup-message.is-error');
			if (banner && banner.scrollIntoView) banner.scrollIntoView({ block: 'center', behavior: 'smooth' });
		}
		function init() { guardForms(); revealOutcome(); }
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
		else init();
	})();
	</script>
	<?php
}, 110 );

add_action( 'init', function() {
	register_post_type( 'tn_tde_signup', [
		'labels' => [
			'name' => 'Event Signups',
			'singular_name' => 'Event Signup',
			'menu_name' => 'Signups',
			'edit_item' => 'View Signup',
			'search_items' => 'Search Signups',
			'not_found' => 'No signups found',
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => 'trivia-desc-editor',
		'capability_type' => 'post',
		'capabilities' => [ 'create_posts' => 'do_not_allow' ],
		'map_meta_cap' => true,
		'supports' => [ 'title', 'custom-fields' ],
	] );
} );

function tn_tde_signup_meta_value( $post_id, $key ) {
	return (string) get_post_meta( $post_id, '_tn_tde_signup_' . $key, true );
}

function tn_tde_signup_statuses() {
	return [
		'active' => 'Active',
		'cancelled' => 'Cancelled',
		'spam' => 'Spam',
		'duplicate' => 'Duplicate',
	];
}

function tn_tde_signup_status( $post_id ) {
	$status = sanitize_key( tn_tde_signup_meta_value( $post_id, 'status' ) );
	$statuses = tn_tde_signup_statuses();
	return isset( $statuses[ $status ] ) ? $status : 'active';
}

function tn_tde_signup_status_label( $status ) {
	$statuses = tn_tde_signup_statuses();
	return $statuses[ $status ] ?? $statuses['active'];
}

add_filter( 'manage_tn_tde_signup_posts_columns', function( $columns ) {
	return [
		'cb' => $columns['cb'] ?? '<input type="checkbox" />',
		'title' => 'Signup',
		'tn_signup_event' => 'Event',
		'tn_signup_person' => 'Name',
		'tn_signup_email' => 'Contact Email',
		'tn_signup_flight' => 'Flight',
		'tn_signup_status' => 'Status',
		'tn_signup_sync' => 'Google Sheets',
		'date' => $columns['date'] ?? 'Date',
	];
} );

add_action( 'manage_tn_tde_signup_posts_custom_column', function( $column, $post_id ) {
	if ( $column === 'tn_signup_event' ) {
		$event = tn_tde_signup_meta_value( $post_id, 'event_title' );
		$session = tn_tde_signup_meta_value( $post_id, 'event_session' );
		echo esc_html( trim( $event . ( $session ? ' - ' . $session : '' ) ) );
		return;
	}
	if ( $column === 'tn_signup_person' ) {
		echo esc_html( tn_tde_signup_meta_value( $post_id, 'name' ) );
		return;
	}
	if ( $column === 'tn_signup_email' ) {
		$email = tn_tde_signup_meta_value( $post_id, 'email' );
		if ( $email ) {
			printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $email ), esc_html( $email ) );
		}
		return;
	}
	if ( $column === 'tn_signup_flight' ) {
		echo esc_html( tn_tde_signup_meta_value( $post_id, 'flight' ) );
		return;
	}
	if ( $column === 'tn_signup_status' ) {
		$status = tn_tde_signup_status( $post_id );
		$changed_at = tn_tde_signup_meta_value( $post_id, 'status_changed_at' );
		echo esc_html( tn_tde_signup_status_label( $status ) );
		if ( $changed_at ) {
			printf( '<br><small>%s</small>', esc_html( $changed_at ) );
		}
		return;
	}
	if ( $column === 'tn_signup_sync' ) {
		$status = tn_tde_signup_meta_value( $post_id, 'sync_status' );
		$error = tn_tde_signup_meta_value( $post_id, 'sync_error' );
		if ( $status === '' ) $status = 'pending';
		$label = ucfirst( $status );
		if ( $error ) {
			printf( '<strong>%1$s</strong><br><small>%2$s</small>', esc_html( $label ), esc_html( $error ) );
		} else {
			echo esc_html( $label );
		}
	}
}, 10, 2 );

add_action( 'add_meta_boxes', function() {
	add_meta_box( 'tn_tde_signup_details', 'Signup Details', 'tn_tde_render_signup_details_meta_box', 'tn_tde_signup', 'normal', 'high' );
} );

function tn_tde_render_signup_details_meta_box( $post ) {
	$fields = [
		'event_title' => 'Event',
		'event_session' => 'Session',
		'event_day' => 'Day',
		'event_date' => 'Date',
		'event_start' => 'Start',
		'event_end' => 'End',
		'event_location' => 'Location',
		'name' => 'Name',
		'email' => 'Contact Email',
		'flight' => 'Flight',
		'team' => 'Team Name',
		'team_members' => 'Team Members',
		'notes' => 'Notes',
		'status' => 'Signup Status',
		'status_changed_at' => 'Status Changed At',
		'status_reason' => 'Status Reason',
		'sync_status' => 'Google Sheets Status',
		'sync_error' => 'Google Sheets Error',
	];
	echo '<table class="widefat striped"><tbody>';
	foreach ( $fields as $key => $label ) {
		$value = tn_tde_signup_meta_value( $post->ID, $key );
		if ( $key === 'status' && $value === '' ) $value = 'active';
		if ( $key === 'sync_status' && $value === '' ) $value = 'pending';
		printf(
			'<tr><th style="width:180px;">%1$s</th><td style="white-space:pre-wrap;">%2$s</td></tr>',
			esc_html( $label ),
			esc_html( $value )
		);
	}
	echo '</tbody></table>';
}

function tn_tde_signup_status_action_url( $signup_id, $status ) {
	return wp_nonce_url(
		add_query_arg(
			[
				'action' => 'tn_tde_set_signup_status',
				'signup_id' => $signup_id,
				'signup_status' => $status,
			],
			admin_url( 'admin-post.php' )
		),
		'tn_tde_set_signup_status_' . $signup_id . '_' . $status
	);
}

add_filter( 'post_row_actions', function( $actions, $post ) {
	if ( ! $post || $post->post_type !== 'tn_tde_signup' || ! current_user_can( 'edit_post', $post->ID ) ) return $actions;
	$status = tn_tde_signup_status( $post->ID );
	$url = wp_nonce_url(
		add_query_arg(
			[
				'action' => 'tn_tde_resync_signup',
				'signup_id' => $post->ID,
			],
			admin_url( 'admin-post.php' )
		),
		'tn_tde_resync_signup_' . $post->ID
	);
	$actions['tn_tde_resync_signup'] = '<a href="' . esc_url( $url ) . '">Sync to Google Sheets</a>';
	$mark_url = wp_nonce_url(
		add_query_arg(
			[
				'action' => 'tn_tde_mark_signup_synced',
				'signup_id' => $post->ID,
			],
			admin_url( 'admin-post.php' )
		),
		'tn_tde_mark_signup_synced_' . $post->ID
	);
	$actions['tn_tde_mark_signup_synced'] = '<a href="' . esc_url( $mark_url ) . '">Mark synced</a>';
	if ( $status === 'active' ) {
		$actions['tn_tde_cancel_signup'] = '<a href="' . esc_url( tn_tde_signup_status_action_url( $post->ID, 'cancelled' ) ) . '">Cancel signup</a>';
		$actions['tn_tde_spam_signup'] = '<a href="' . esc_url( tn_tde_signup_status_action_url( $post->ID, 'spam' ) ) . '">Mark spam</a>';
	} else {
		$actions['tn_tde_restore_signup'] = '<a href="' . esc_url( tn_tde_signup_status_action_url( $post->ID, 'active' ) ) . '">Restore</a>';
	}
	return $actions;
}, 10, 2 );

function tn_tde_mark_signup_ids_synced( $signup_ids ) {
	$count = 0;
	foreach ( array_filter( array_map( 'absint', (array) $signup_ids ) ) as $signup_id ) {
		if ( ! current_user_can( 'edit_post', $signup_id ) ) continue;
		update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'synced' );
		delete_post_meta( $signup_id, '_tn_tde_signup_sync_error' );
		$count++;
	}
	return $count;
}

function tn_tde_resync_signup_ids( $signup_ids ) {
	$results = [ 'synced' => 0, 'failed' => 0 ];
	foreach ( array_filter( array_map( 'absint', (array) $signup_ids ) ) as $signup_id ) {
		if ( ! current_user_can( 'edit_post', $signup_id ) ) continue;
		update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'pending' );
		delete_post_meta( $signup_id, '_tn_tde_signup_sync_error' );
		$ok = tn_tde_sync_event_signup( $signup_id );
		$results[ $ok ? 'synced' : 'failed' ]++;
	}
	return $results;
}

function tn_tde_set_signup_ids_status( $signup_ids, $status, $reason = '' ) {
	$statuses = tn_tde_signup_statuses();
	$status = sanitize_key( $status );
	if ( ! isset( $statuses[ $status ] ) ) return 0;
	$count = 0;
	foreach ( array_filter( array_map( 'absint', (array) $signup_ids ) ) as $signup_id ) {
		if ( ! current_user_can( 'edit_post', $signup_id ) ) continue;
		update_post_meta( $signup_id, '_tn_tde_signup_status', $status );
		if ( $status === 'active' ) {
			delete_post_meta( $signup_id, '_tn_tde_signup_status_changed_at' );
			delete_post_meta( $signup_id, '_tn_tde_signup_status_reason' );
		} else {
			update_post_meta( $signup_id, '_tn_tde_signup_status_changed_at', current_time( 'mysql' ) );
			update_post_meta( $signup_id, '_tn_tde_signup_status_reason', sanitize_text_field( $reason ?: tn_tde_signup_status_label( $status ) ) );
		}
		update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'pending' );
		delete_post_meta( $signup_id, '_tn_tde_signup_sync_error' );
		tn_tde_sync_event_signup( $signup_id );
		$count++;
	}
	return $count;
}

function tn_tde_pending_signup_ids() {
	return get_posts( [
		'post_type' => 'tn_tde_signup',
		'post_status' => 'private',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'meta_query' => [
			'relation' => 'OR',
			[
				'key' => '_tn_tde_signup_sync_status',
				'value' => [ 'pending', 'failed' ],
				'compare' => 'IN',
			],
			[
				'key' => '_tn_tde_signup_sync_status',
				'compare' => 'NOT EXISTS',
			],
		],
	] );
}

// ─── Automatic retry for pending/failed Google Sheets signup syncs ──────────

add_filter( 'cron_schedules', function( $schedules ) {
	if ( ! isset( $schedules['tn_tde_fifteen_minutes'] ) ) {
		$schedules['tn_tde_fifteen_minutes'] = [
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display' => __( 'Every 15 Minutes (Trivia Nationals signup resync)', 'tn-tde' ),
		];
	}
	return $schedules;
} );

add_action( 'init', function() {
	if ( ! wp_next_scheduled( 'tn_tde_resync_pending_signups_cron' ) ) {
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'tn_tde_fifteen_minutes', 'tn_tde_resync_pending_signups_cron' );
	}
} );

add_action( 'tn_tde_resync_pending_signups_cron', function() {
	$pending_ids = tn_tde_pending_signup_ids();
	if ( ! $pending_ids ) return;
	tn_tde_resync_signup_ids( $pending_ids );
} );

register_deactivation_hook( __FILE__, function() {
	wp_clear_scheduled_hook( 'tn_tde_resync_pending_signups_cron' );
} );

add_action( 'admin_post_tn_tde_resync_signup', function() {
	$signup_id = isset( $_GET['signup_id'] ) ? absint( $_GET['signup_id'] ) : 0;
	if ( ! $signup_id || ! current_user_can( 'edit_post', $signup_id ) ) {
		wp_die( esc_html__( 'You do not have permission to sync this signup.', 'tn-tde' ) );
	}
	check_admin_referer( 'tn_tde_resync_signup_' . $signup_id );
	update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'pending' );
	delete_post_meta( $signup_id, '_tn_tde_signup_sync_error' );
	$ok = tn_tde_sync_event_signup( $signup_id );
	wp_safe_redirect( add_query_arg(
		[
			'post_type' => 'tn_tde_signup',
			'tn_signup_resync' => $ok ? 'synced' : 'failed',
		],
		admin_url( 'edit.php' )
	) );
	exit;
} );

add_action( 'admin_post_tn_tde_mark_signup_synced', function() {
	$signup_id = isset( $_GET['signup_id'] ) ? absint( $_GET['signup_id'] ) : 0;
	if ( ! $signup_id || ! current_user_can( 'edit_post', $signup_id ) ) {
		wp_die( esc_html__( 'You do not have permission to update this signup.', 'tn-tde' ) );
	}
	check_admin_referer( 'tn_tde_mark_signup_synced_' . $signup_id );
	tn_tde_mark_signup_ids_synced( [ $signup_id ] );
	wp_safe_redirect( add_query_arg(
		[
			'post_type' => 'tn_tde_signup',
			'tn_signup_marked_synced' => 1,
		],
		admin_url( 'edit.php' )
	) );
	exit;
} );

add_action( 'admin_post_tn_tde_set_signup_status', function() {
	$signup_id = isset( $_GET['signup_id'] ) ? absint( $_GET['signup_id'] ) : 0;
	$status = isset( $_GET['signup_status'] ) ? sanitize_key( wp_unslash( $_GET['signup_status'] ) ) : '';
	$statuses = tn_tde_signup_statuses();
	if ( ! $signup_id || ! current_user_can( 'edit_post', $signup_id ) || ! isset( $statuses[ $status ] ) ) {
		wp_die( esc_html__( 'You do not have permission to update this signup.', 'tn-tde' ) );
	}
	check_admin_referer( 'tn_tde_set_signup_status_' . $signup_id . '_' . $status );
	tn_tde_set_signup_ids_status( [ $signup_id ], $status );
	wp_safe_redirect( add_query_arg(
		[
			'post_type' => 'tn_tde_signup',
			'tn_signup_status_updated' => $status,
			'tn_signup_status_count' => 1,
		],
		admin_url( 'edit.php' )
	) );
	exit;
} );

add_action( 'admin_post_tn_tde_resync_pending_signups', function() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to sync signups.', 'tn-tde' ) );
	}
	check_admin_referer( 'tn_tde_resync_pending_signups' );
	$results = tn_tde_resync_signup_ids( tn_tde_pending_signup_ids() );
	wp_safe_redirect( add_query_arg(
		[
			'post_type' => 'tn_tde_signup',
			'tn_signup_bulk_synced' => $results['synced'],
			'tn_signup_bulk_failed' => $results['failed'],
		],
		admin_url( 'edit.php' )
	) );
	exit;
} );

add_filter( 'bulk_actions-edit-tn_tde_signup', function( $actions ) {
	$actions['tn_tde_resync_selected_signups'] = 'Sync to Google Sheets';
	$actions['tn_tde_mark_selected_signups_synced'] = 'Mark as synced';
	$actions['tn_tde_cancel_selected_signups'] = 'Cancel signup';
	$actions['tn_tde_spam_selected_signups'] = 'Mark as spam';
	$actions['tn_tde_restore_selected_signups'] = 'Restore signup';
	return $actions;
} );

add_filter( 'handle_bulk_actions-edit-tn_tde_signup', function( $redirect_url, $action, $post_ids ) {
	if ( ! current_user_can( 'edit_posts' ) ) return $redirect_url;
	if ( $action === 'tn_tde_mark_selected_signups_synced' ) {
		$count = tn_tde_mark_signup_ids_synced( $post_ids );
		return add_query_arg(
			[ 'tn_signup_marked_synced' => $count ],
			remove_query_arg( [ 'tn_signup_resync' ], $redirect_url )
		);
	}
	$status_actions = [
		'tn_tde_cancel_selected_signups' => 'cancelled',
		'tn_tde_spam_selected_signups' => 'spam',
		'tn_tde_restore_selected_signups' => 'active',
	];
	if ( isset( $status_actions[ $action ] ) ) {
		$status = $status_actions[ $action ];
		$count = tn_tde_set_signup_ids_status( $post_ids, $status );
		return add_query_arg(
			[
				'tn_signup_status_updated' => $status,
				'tn_signup_status_count' => $count,
			],
			remove_query_arg( [ 'tn_signup_resync' ], $redirect_url )
		);
	}
	if ( $action !== 'tn_tde_resync_selected_signups' ) return $redirect_url;
	$results = tn_tde_resync_signup_ids( $post_ids );
	return add_query_arg(
		[
			'tn_signup_bulk_synced' => $results['synced'],
			'tn_signup_bulk_failed' => $results['failed'],
		],
		remove_query_arg( [ 'tn_signup_resync' ], $redirect_url )
	);
}, 10, 3 );

add_action( 'admin_notices', function() {
	if ( ! is_admin() || ( $_GET['post_type'] ?? '' ) !== 'tn_tde_signup' ) return;
	$pending_count = count( tn_tde_pending_signup_ids() );
	if ( $pending_count && current_user_can( 'edit_posts' ) ) {
		$url = wp_nonce_url(
			add_query_arg( 'action', 'tn_tde_resync_pending_signups', admin_url( 'admin-post.php' ) ),
			'tn_tde_resync_pending_signups'
		);
		printf(
			'<div class="notice notice-warning"><p><strong>%1$d signup%2$s pending or failed Google Sheets sync.</strong> <a class="button button-primary" href="%3$s">Sync pending/failed signups now</a></p></div>',
			(int) $pending_count,
			$pending_count === 1 ? '' : 's',
			esc_url( $url )
		);
	}
	if ( ! empty( $_GET['tn_signup_resync'] ) ) {
		$status = sanitize_key( wp_unslash( $_GET['tn_signup_resync'] ) );
		if ( $status === 'synced' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Signup synced to Google Sheets.</p></div>';
		} elseif ( $status === 'failed' ) {
			echo '<div class="notice notice-error is-dismissible"><p>Signup could not sync to Google Sheets. Check the Google Sheets status column for the saved error.</p></div>';
		}
	}
	if ( isset( $_GET['tn_signup_bulk_synced'], $_GET['tn_signup_bulk_failed'] ) ) {
		$synced = absint( $_GET['tn_signup_bulk_synced'] );
		$failed = absint( $_GET['tn_signup_bulk_failed'] );
		printf(
			'<div class="notice %1$s is-dismissible"><p>Google Sheets sync complete: %2$d synced, %3$d failed.</p></div>',
			$failed ? 'notice-error' : 'notice-success',
			(int) $synced,
			(int) $failed
		);
	}
	if ( isset( $_GET['tn_signup_marked_synced'] ) ) {
		$count = absint( $_GET['tn_signup_marked_synced'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%1$d signup%2$s marked as synced.</p></div>',
			(int) $count,
			$count === 1 ? '' : 's'
		);
	}
	if ( isset( $_GET['tn_signup_status_updated'], $_GET['tn_signup_status_count'] ) ) {
		$status = sanitize_key( wp_unslash( $_GET['tn_signup_status_updated'] ) );
		$count = absint( $_GET['tn_signup_status_count'] );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%1$d signup%2$s set to %3$s. Google Sheets sync was attempted and the row status was saved in WordPress.</p></div>',
			(int) $count,
			$count === 1 ? '' : 's',
			esc_html( strtolower( tn_tde_signup_status_label( $status ) ) )
		);
	}
} );

add_action( 'admin_post_tn_tde_event_signup', 'tn_tde_handle_event_signup' );
add_action( 'admin_post_nopriv_tn_tde_event_signup', 'tn_tde_handle_event_signup' );
add_action( 'admin_post_tn_tde_bulk_event_signup', 'tn_tde_handle_bulk_event_signup' );
add_action( 'admin_post_nopriv_tn_tde_bulk_event_signup', 'tn_tde_handle_bulk_event_signup' );
add_action( 'admin_post_tn_tde_email_signup_summary', 'tn_tde_handle_email_signup_summary' );
add_action( 'admin_post_nopriv_tn_tde_email_signup_summary', 'tn_tde_handle_email_signup_summary' );
add_action( 'tn_tde_sync_event_signup', 'tn_tde_sync_event_signup', 10, 1 );

function tn_tde_create_event_signup( $event, $name, $email, $flight, $team, $team_members, $notes ) {
	$flight_option = $flight !== '' ? tn_tde_signup_option_for_value( $event, $flight ) : null;
	$signup_event = $flight_option && ! empty( $flight_option['event'] ) ? $flight_option['event'] : $event;
	$signup_id = wp_insert_post( [
		'post_type' => 'tn_tde_signup',
		'post_status' => 'private',
		'post_title' => sprintf( 'Signup: %s - %s', $signup_event['base_title'] ?? $signup_event['title'], $name ),
	], true );
	if ( is_wp_error( $signup_id ) ) return $signup_id;
	$meta = [
		'event_slug' => tn_tde_event_detail_slug( $signup_event ),
		'event_title' => sanitize_text_field( $signup_event['base_title'] ?? $signup_event['title'] ?? '' ),
		'event_session' => sanitize_text_field( $signup_event['session_label'] ?? '' ),
		'event_day' => sanitize_text_field( $signup_event['day_label'] ?? '' ),
		'event_date' => sanitize_text_field( $signup_event['date_label'] ?? '' ),
		'event_start' => sanitize_text_field( $signup_event['start'] ?? '' ),
		'event_end' => sanitize_text_field( $signup_event['end'] ?? '' ),
		'event_location' => sanitize_text_field( $signup_event['location_label'] ?? '' ),
		'name' => $name,
		'email' => $email,
		'flight' => $flight,
		'team' => $team,
		'team_members' => $team_members,
		'notes' => $notes,
		'status' => 'active',
		'status_changed_at' => '',
		'status_reason' => '',
		'sync_status' => 'pending',
	];
	foreach ( $meta as $key => $value ) {
		update_post_meta( $signup_id, '_tn_tde_signup_' . $key, $value );
	}
	tn_tde_sync_event_signup( (int) $signup_id, 8 );
	if ( tn_tde_signup_meta_value( $signup_id, 'sync_status' ) === 'pending' ) {
		tn_tde_queue_event_signup_sync( (int) $signup_id );
	}
	return $signup_id;
}

function tn_tde_handle_event_signup() {
	$slug = isset( $_POST['tn_event_slug'] ) ? sanitize_title( wp_unslash( $_POST['tn_event_slug'] ) ) : '';
	$redirect = isset( $_POST['tn_signup_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['tn_signup_redirect'] ) ) : home_url( '/' );
	if ( ! isset( $_POST['tn_tde_event_signup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_tde_event_signup_nonce'] ) ), 'tn_tde_event_signup' ) ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'invalid', $redirect ) );
		exit;
	}
	$honeypot = $_POST['tn_signup_referrer_check'] ?? $_POST['tn_signup_company'] ?? '';
	if ( trim( (string) wp_unslash( $honeypot ) ) !== '' ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'spam', $redirect ) );
		exit;
	}
	$event = tn_tde_get_event_by_detail_slug( $slug );
	if ( ! $event || ! tn_tde_event_accepts_signup( $event ) ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'closed', $redirect ) );
		exit;
	}
	$name = isset( $_POST['tn_signup_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tn_signup_name'] ) ) : '';
	$email = isset( $_POST['tn_signup_email'] ) ? sanitize_email( wp_unslash( $_POST['tn_signup_email'] ) ) : '';
	$flight = isset( $_POST['tn_signup_flight'] ) ? sanitize_text_field( wp_unslash( $_POST['tn_signup_flight'] ) ) : '';
	$team = tn_tde_event_is_team_signup( $event ) && isset( $_POST['tn_signup_team'] ) ? sanitize_text_field( wp_unslash( $_POST['tn_signup_team'] ) ) : '';
	$team_members = tn_tde_event_is_team_signup( $event ) && isset( $_POST['tn_signup_team_members'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tn_signup_team_members'] ) ) : '';
	$notes = isset( $_POST['tn_signup_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tn_signup_notes'] ) ) : '';
	$flight_options = tn_tde_signup_flight_options_for_event( $event );
	$flight_values = array_map( static function( $option ) { return $option['value']; }, $flight_options );
	if ( $name === '' || ! is_email( $email ) || ( $flight_options && ! in_array( $flight, $flight_values, true ) ) ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'missing', $redirect ) );
		exit;
	}
	$signup_id = tn_tde_create_event_signup( $event, $name, $email, $flight, $team, $team_members, $notes );
	if ( is_wp_error( $signup_id ) ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'error', $redirect ) );
		exit;
	}
	wp_safe_redirect( add_query_arg( 'tn_signup', 'success', $redirect ) );
	exit;
}

function tn_tde_handle_bulk_event_signup() {
	$redirect = isset( $_POST['tn_signup_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['tn_signup_redirect'] ) ) : home_url( '/event-signups/' );
	if ( ! isset( $_POST['tn_tde_bulk_event_signup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_tde_bulk_event_signup_nonce'] ) ), 'tn_tde_bulk_event_signup' ) ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'invalid', $redirect ) );
		exit;
	}
	$honeypot = $_POST['tn_signup_referrer_check'] ?? '';
	if ( trim( (string) wp_unslash( $honeypot ) ) !== '' ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'spam', $redirect ) );
		exit;
	}
	$name = isset( $_POST['tn_signup_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tn_signup_name'] ) ) : '';
	$email = isset( $_POST['tn_signup_email'] ) ? sanitize_email( wp_unslash( $_POST['tn_signup_email'] ) ) : '';
	$entries = isset( $_POST['tn_signup_events'] ) && is_array( $_POST['tn_signup_events'] ) ? wp_unslash( $_POST['tn_signup_events'] ) : [];
	if ( $name === '' || ! is_email( $email ) || ! $entries ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'missing', $redirect ) );
		exit;
	}
	$count = 0;
	foreach ( $entries as $entry ) {
		if ( ! is_array( $entry ) ) continue;
		$slug = isset( $entry['event_slug'] ) ? sanitize_title( $entry['event_slug'] ) : '';
		$event = tn_tde_get_event_by_detail_slug( $slug );
		if ( ! $event || ! tn_tde_event_accepts_signup( $event ) ) continue;
		$flight = isset( $entry['flight'] ) ? sanitize_text_field( $entry['flight'] ) : '';
		$flight_options = tn_tde_signup_flight_options_for_event( $event );
		$flight_values = array_map( static function( $option ) { return $option['value']; }, $flight_options );
		if ( $flight_options && ! in_array( $flight, $flight_values, true ) ) continue;
		$team = tn_tde_event_is_team_signup( $event ) && isset( $entry['team'] ) ? sanitize_text_field( $entry['team'] ) : '';
		$team_members = tn_tde_event_is_team_signup( $event ) && isset( $entry['team_members'] ) ? sanitize_textarea_field( $entry['team_members'] ) : '';
		$notes = isset( $entry['notes'] ) ? sanitize_textarea_field( $entry['notes'] ) : '';
		$signup_id = tn_tde_create_event_signup( $event, $name, $email, $flight, $team, $team_members, $notes );
		if ( ! is_wp_error( $signup_id ) ) $count++;
	}
	if ( $count < 1 ) {
		wp_safe_redirect( add_query_arg( 'tn_signup', 'missing', $redirect ) );
		exit;
	}
	wp_safe_redirect( add_query_arg( [ 'tn_signup' => 'success', 'tn_signup_count' => $count ], $redirect ) );
	exit;
}

function tn_tde_send_email_via_apps_script( $to, $subject, $html ) {
	$endpoint = trim( (string) get_option( 'tn_tde_signup_sheets_endpoint' ) );
	$secret = trim( (string) get_option( 'tn_tde_signup_sheets_secret' ) );
	if ( ! $endpoint || ! $secret ) return false;
	$response = wp_remote_post( $endpoint, [
		'timeout' => 15,
		'redirection' => 0,
		'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
		'body' => wp_json_encode( [ 'secret' => $secret, 'action' => 'send_email', 'to' => $to, 'subject' => $subject, 'html_body' => $html ] ),
	] );
	if ( is_wp_error( $response ) ) return false;
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code >= 300 && $code < 400 && wp_remote_retrieve_header( $response, 'location' ) ) {
		$response = wp_remote_get( wp_remote_retrieve_header( $response, 'location' ), [ 'timeout' => 15, 'redirection' => 5 ] );
		$code = is_wp_error( $response ) ? 500 : wp_remote_retrieve_response_code( $response );
	}
	$body = is_wp_error( $response ) ? '' : wp_remote_retrieve_body( $response );
	$result = json_decode( $body, true );
	return $code >= 200 && $code < 300 && is_array( $result ) && ! empty( $result['ok'] );
}

// Host PHP mail() delivery is unreliable (HostGator drops it); prefer the Apps Script Gmail relay.
function tn_tde_send_signup_email( $to, $subject, $html ) {
	if ( tn_tde_send_email_via_apps_script( $to, $subject, $html ) ) return true;
	return wp_mail( $to, $subject, $html, [
		'From: Trivia Nationals <info@trivianationals.org>',
		'Reply-To: Trivia Nationals <info@trivianationals.org>',
		'Content-Type: text/html; charset=UTF-8',
	] );
}

function tn_tde_handle_email_signup_summary() {
	$redirect = isset( $_POST['tn_signup_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['tn_signup_redirect'] ) ) : home_url( '/event-signups/' );
	if ( ! isset( $_POST['tn_tde_email_signup_summary_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_tde_email_signup_summary_nonce'] ) ), 'tn_tde_email_signup_summary' ) ) {
		wp_safe_redirect( add_query_arg( 'tn_lookup', 'invalid', $redirect ) );
		exit;
	}
	$honeypot = $_POST['tn_signup_lookup_check'] ?? $_POST['tn_signup_lookup_company'] ?? '';
	if ( trim( (string) wp_unslash( $honeypot ) ) !== '' ) {
		wp_safe_redirect( add_query_arg( 'tn_lookup', 'sent', $redirect ) );
		exit;
	}
	$email = isset( $_POST['tn_signup_lookup_email'] ) ? sanitize_email( wp_unslash( $_POST['tn_signup_lookup_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'tn_lookup', 'invalid', $redirect ) );
		exit;
	}
	$manage_token = tn_tde_issue_manage_signups_token( $email );
	$sent = tn_tde_send_signup_email(
		$email,
		'Your Trivia Nationals 2026 event signups',
		tn_tde_signup_summary_email_html( tn_tde_signup_summary_rows_for_email( $email ), $manage_token ? tn_tde_manage_signups_url( $manage_token ) : '' )
	);
	$status = $sent ? 'sent' : 'error';
	wp_safe_redirect( add_query_arg( 'tn_lookup', $status, $redirect ) );
	exit;
}

function tn_tde_signup_summary_rows_for_email( $email ) {
	$ids = get_posts( [
		'post_type' => 'tn_tde_signup',
		'post_status' => 'private',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'orderby' => 'date',
		'order' => 'ASC',
		'no_found_rows' => true,
		'meta_query' => [
			'relation' => 'AND',
			[
				'key' => '_tn_tde_signup_email',
				'value' => sanitize_email( $email ),
				'compare' => '=',
			],
			[
				'relation' => 'OR',
				[
					'key' => '_tn_tde_signup_status',
					'value' => 'active',
					'compare' => '=',
				],
				[
					'key' => '_tn_tde_signup_status',
					'compare' => 'NOT EXISTS',
				],
			],
		],
	] );
	$fields = [
		'event_title',
		'event_session',
		'event_day',
		'event_date',
		'event_start',
		'event_end',
		'event_location',
		'name',
		'email',
		'flight',
		'team',
		'team_members',
		'notes',
		'status',
		'status_changed_at',
		'status_reason',
	];
	return array_map( static function( $id ) use ( $fields ) {
		$row = [];
		foreach ( $fields as $field ) {
			$row[ $field ] = (string) get_post_meta( $id, '_tn_tde_signup_' . $field, true );
		}
		return $row;
	}, $ids );
}

function tn_tde_signup_summary_email_html( $signups, $manage_url = '' ) {
	$styles = 'font-family:Arial,sans-serif;color:#222;line-height:1.5;';
	$html = '<div style="' . esc_attr( $styles ) . '">';
	$html .= '<h2 style="margin:0 0 16px;color:#17406f;">Trivia Nationals 2026 Event Signups</h2>';

	if ( empty( $signups ) ) {
		$html .= '<p>We did not find any Trivia Nationals 2026 event signups associated with this email address.</p>';
		$html .= '<p>If you recently submitted a signup, please give it a minute and try again.</p>';
		$html .= '</div>';
		return $html;
	}

	$html .= '<p>Here are the Trivia Nationals 2026 event signups associated with this email address:</p>';
	$html .= '<ol style="padding-left:22px;">';
	foreach ( $signups as $signup ) {
		if ( ! is_array( $signup ) ) continue;
		$title = isset( $signup['event_title'] ) ? (string) $signup['event_title'] : 'Event signup';
		$html .= '<li style="margin:0 0 18px;">';
		$html .= '<strong style="font-size:16px;color:#17406f;">' . esc_html( $title ?: 'Event signup' ) . '</strong>';
		$html .= '<ul style="margin:8px 0 0;padding-left:18px;">';
		$html .= tn_tde_signup_summary_email_detail( 'Flight', $signup['flight'] ?? '' );
		$time = implode( ', ', array_filter( [
			isset( $signup['event_day'] ) ? (string) $signup['event_day'] : '',
			isset( $signup['event_date'] ) ? (string) $signup['event_date'] : '',
			isset( $signup['event_start'] ) ? (string) $signup['event_start'] : '',
		] ) );
		$html .= tn_tde_signup_summary_email_detail( 'Time', $time );
		$html .= tn_tde_signup_summary_email_detail( 'Location', $signup['event_location'] ?? '' );
		$html .= tn_tde_signup_summary_email_detail( 'Team Name', $signup['team'] ?? '' );
		$html .= tn_tde_signup_summary_email_detail( 'Team Members', $signup['team_members'] ?? '' );
		$html .= '</ul></li>';
	}
	$html .= '</ol>';
	if ( $manage_url ) {
		$html .= '<p style="margin:20px 0 6px;"><a href="' . esc_url( $manage_url ) . '" style="display:inline-block;padding:10px 18px;background:#17406f;color:#ffffff;border-radius:999px;text-decoration:none;font-weight:bold;">Manage Your Signups</a></p>';
		$html .= '<p style="color:#666;font-size:13px;margin:0;">Use this link to change flights or cancel a signup. It works for 72 hours and is unique to you &mdash; please do not forward it.</p>';
	}
	$html .= '</div>';
	return $html;
}

function tn_tde_signup_summary_email_detail( $label, $value ) {
	$value = trim( (string) $value );
	if ( $value === '' ) return '';
	return '<li><strong>' . esc_html( $label ) . ':</strong> ' . nl2br( esc_html( $value ) ) . '</li>';
}

// ─── Attendee self-service: manage signups via emailed magic link ───────────

function tn_tde_issue_manage_signups_token( $email ) {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) return '';
	$token = wp_generate_password( 32, false, false );
	set_transient( 'tn_tde_manage_' . $token, $email, 3 * DAY_IN_SECONDS );
	return $token;
}

function tn_tde_manage_signups_email_for_token( $token ) {
	$token = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $token );
	if ( strlen( $token ) !== 32 ) return '';
	$email = get_transient( 'tn_tde_manage_' . $token );
	return is_string( $email ) && is_email( $email ) ? $email : '';
}

function tn_tde_manage_signups_url( $token ) {
	return add_query_arg( 'tn_token', rawurlencode( $token ), home_url( '/manage-signups/' ) );
}

function tn_tde_active_signup_ids_for_email( $email ) {
	return get_posts( [
		'post_type' => 'tn_tde_signup',
		'post_status' => 'private',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'orderby' => 'date',
		'order' => 'ASC',
		'no_found_rows' => true,
		'meta_query' => [
			'relation' => 'AND',
			[
				'key' => '_tn_tde_signup_email',
				'value' => sanitize_email( $email ),
				'compare' => '=',
			],
			[
				'relation' => 'OR',
				[
					'key' => '_tn_tde_signup_status',
					'value' => 'active',
					'compare' => '=',
				],
				[
					'key' => '_tn_tde_signup_status',
					'compare' => 'NOT EXISTS',
				],
			],
		],
	] );
}

function tn_tde_is_manage_signups_request() {
	if ( is_admin() ) return false;
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	return strtolower( $path ) === 'manage-signups';
}

add_action( 'wp', function() {
	if ( ! tn_tde_is_manage_signups_request() ) return;
	global $wp_query;
	$wp_query->is_404 = false;
	$wp_query->is_page = true;
	$wp_query->is_singular = true;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function( $parts ) {
	if ( tn_tde_is_manage_signups_request() ) {
		$parts['title'] = 'Manage Signups';
	}
	return $parts;
} );

add_filter( 'body_class', function( $classes ) {
	if ( ! tn_tde_is_manage_signups_request() ) return $classes;
	$classes = array_diff( $classes, [ 'error404' ] );
	$classes[] = 'tn-event-signups-page';
	$classes[] = 'tn-manage-signups-page';
	return array_values( array_unique( $classes ) );
} );

add_action( 'template_redirect', function() {
	if ( ! tn_tde_is_manage_signups_request() ) return;
	status_header( 200 );
	nocache_headers();
	tn_tde_render_manage_signups_page();
	exit;
}, 2 );

function tn_tde_manage_signup_card_meta( $signup_id ) {
	$fields = [ 'event_slug', 'event_title', 'event_session', 'event_day', 'event_date', 'event_start', 'event_end', 'event_location', 'name', 'email', 'flight', 'team', 'team_members', 'notes' ];
	$meta = [];
	foreach ( $fields as $field ) {
		$meta[ $field ] = tn_tde_signup_meta_value( $signup_id, $field );
	}
	return $meta;
}

function tn_tde_render_manage_signups_page() {
	$token = isset( $_GET['tn_token'] ) ? sanitize_text_field( wp_unslash( $_GET['tn_token'] ) ) : '';
	$email = $token ? tn_tde_manage_signups_email_for_token( $token ) : '';
	$status = isset( $_GET['tn_manage'] ) ? sanitize_key( wp_unslash( $_GET['tn_manage'] ) ) : '';
	$lookup_status = isset( $_GET['tn_lookup'] ) ? sanitize_key( wp_unslash( $_GET['tn_lookup'] ) ) : '';
	$signup_ids = $email ? tn_tde_active_signup_ids_for_email( $email ) : [];
	get_header();
	?>
	<main class="tn-signup-page tn-manage-page">
		<style>
			body.tn-event-signups-page .inner-main-title,
			body.tn-event-signups-page .entry-header,
			body.tn-event-signups-page .page-header {
				display: none !important;
			}
			body.tn-event-signups-page .site-content,
			body.tn-event-signups-page .content-area,
			body.tn-event-signups-page .site-main,
			body.tn-event-signups-page .entry-content {
				margin: 0 !important;
				max-width: none !important;
				padding: 0 !important;
				width: 100% !important;
			}
			.tn-signup-page {
				--tn-grid-bg: #0a0a14;
				--tn-grid-panel: rgba(18,20,34,0.82);
				--tn-grid-panel-strong: rgba(25,29,48,0.94);
				--tn-grid-line: rgba(255,255,255,0.16);
				--tn-grid-text: #f0f0f5;
				--tn-grid-muted: #b7bdcf;
				--tn-grid-cyan: #00e6ff;
				--tn-grid-pink: #ff3ea5;
				--tn-grid-gold: #ffd166;
				color: var(--tn-grid-text);
				background:
					radial-gradient(circle at 18% 7%, rgba(0,230,255,0.18), transparent 24rem),
					radial-gradient(circle at 82% 0%, rgba(255,62,165,0.16), transparent 25rem),
					linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.012) 42%, rgba(0,0,0,0)),
					var(--tn-grid-bg);
				font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
				margin-left: calc(50% - 50vw);
				margin-right: calc(50% - 50vw);
				max-width: none;
				min-height: 100vh;
				padding: clamp(2.5rem, 7vw, 6rem) clamp(1rem, 4vw, 4rem) clamp(2.5rem, 6vw, 5rem);
				width: 100vw;
			}
			.tn-signup-page > * {
				margin: 0 auto;
				max-width: 1320px;
			}
			.tn-signup-nav {
				align-items: center;
				display: flex;
				gap: 1rem;
				justify-content: space-between;
				margin-bottom: clamp(1.4rem, 3vw, 2.6rem);
			}
			.tn-signup-brand {
				color: var(--tn-grid-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(1rem, 1.5vw, 1.35rem);
				font-weight: 900;
				line-height: 1;
				text-decoration: none;
				text-transform: uppercase;
			}
			.tn-signup-nav nav {
				align-items: center;
				display: flex;
				flex-wrap: wrap;
				gap: clamp(0.75rem, 2vw, 1.5rem);
				justify-content: flex-end;
			}
			.tn-signup-nav nav a {
				color: var(--tn-grid-muted);
				font-size: 0.84rem;
				font-weight: 800;
				text-decoration: none;
				text-transform: uppercase;
			}
			.tn-signup-nav nav a:hover,
			.tn-signup-nav nav a[aria-current="page"] {
				color: var(--tn-grid-cyan);
			}
			.tn-signup-page-inner {
				width: min(980px, 100%);
			}
			.tn-signup-page h1 {
				margin: 0 0 0.65rem;
				color: var(--tn-grid-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(2.6rem, 6vw, 5.2rem);
				font-weight: 900;
				letter-spacing: 0;
				line-height: 0.9;
				text-transform: uppercase;
			}
			.tn-signup-kicker {
				color: var(--tn-grid-cyan);
				font-size: clamp(0.8rem, 1.2vw, 1rem);
				font-weight: 900;
				letter-spacing: 0.12em;
				margin: 0 0 0.55rem;
				text-transform: uppercase;
			}
			.tn-signup-page-intro {
				max-width: 46rem;
				margin: 0 0 1.2rem;
				color: var(--tn-grid-muted);
				font-size: 1.05rem;
				line-height: 1.6;
			}
			.tn-signup-page-message {
				margin: 0 0 1rem;
				padding: 0.85rem 1rem;
				border-radius: 8px;
				background: var(--tn-grid-panel);
				border: 1px solid var(--tn-grid-line);
			}
			.tn-signup-page-message.is-success { color: #35e69f; }
			.tn-signup-page-message.is-error { color: #ff8a8a; }
			.tn-manage-cards {
				display: grid;
				gap: 1rem;
			}
			.tn-manage-card {
				display: grid;
				gap: 0.9rem;
				padding: clamp(1rem, 3vw, 1.4rem);
				border: 1px solid var(--tn-grid-line);
				border-radius: 8px;
				background: var(--tn-grid-panel-strong);
				box-shadow: 0 24px 80px rgba(0,0,0,0.28);
			}
			.tn-manage-card-head {
				display: flex;
				align-items: baseline;
				flex-wrap: wrap;
				gap: 0.5rem 1rem;
				justify-content: space-between;
			}
			.tn-manage-card-title {
				margin: 0;
				color: var(--tn-grid-cyan);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(1.2rem, 2.4vw, 1.7rem);
				font-weight: 900;
				letter-spacing: 0.04em;
				text-transform: uppercase;
			}
			.tn-manage-card-when {
				color: var(--tn-grid-muted);
				font-size: 0.92rem;
				font-weight: 700;
			}
			.tn-manage-card-facts {
				display: grid;
				gap: 0.3rem;
				margin: 0;
				color: var(--tn-grid-muted);
				font-size: 0.95rem;
				line-height: 1.5;
			}
			.tn-manage-card-facts strong { color: var(--tn-grid-text); }
			.tn-manage-card-actions {
				display: flex;
				flex-wrap: wrap;
				gap: 0.75rem 1rem;
				align-items: end;
				justify-content: space-between;
				border-top: 1px solid var(--tn-grid-line);
				padding-top: 0.9rem;
			}
			.tn-manage-flight-form {
				display: flex;
				flex-wrap: wrap;
				gap: 0.6rem;
				align-items: end;
			}
			.tn-manage-flight-form label {
				display: block;
				margin: 0 0 0.3rem;
				color: var(--tn-grid-muted);
				font-size: 0.78rem;
				font-weight: 800;
				letter-spacing: 0.08em;
				text-transform: uppercase;
			}
			.tn-manage-flight-form select {
				min-width: min(320px, 70vw);
				padding: 0.55rem 0.65rem;
				border: 1px solid var(--tn-grid-line);
				border-radius: 6px;
				background: rgba(7,8,18,0.72);
				color: var(--tn-grid-text);
			}
			.tn-signup-page button {
				border: 0;
				border-radius: 999px;
				cursor: pointer;
				font-family: Outfit, Inter, sans-serif;
				font-size: 0.85rem;
				font-weight: 900;
				letter-spacing: 0.06em;
				padding: 0.7rem 1.4rem;
				text-transform: uppercase;
			}
			button.tn-manage-save {
				background: linear-gradient(135deg, var(--tn-grid-cyan), #58f0ff);
				color: #06121a;
			}
			button.tn-manage-cancel {
				background: transparent;
				border: 1px solid rgba(255,138,138,0.65);
				color: #ff8a8a;
			}
			button.tn-manage-cancel:hover {
				background: rgba(255,138,138,0.12);
			}
			.tn-manage-empty,
			.tn-manage-expired {
				display: grid;
				gap: 0.9rem;
				padding: clamp(1rem, 3vw, 1.5rem);
				border: 1px solid rgba(0,230,255,0.22);
				border-radius: 8px;
				background:
					linear-gradient(135deg, rgba(0,230,255,0.08), rgba(255,62,165,0.04)),
					rgba(18,20,34,0.82);
			}
			.tn-manage-expired h2,
			.tn-manage-empty h2 {
				margin: 0;
				color: var(--tn-grid-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(1.3rem, 2.6vw, 1.9rem);
				font-weight: 900;
				line-height: 1.05;
				text-transform: uppercase;
			}
			.tn-manage-expired p,
			.tn-manage-empty p {
				margin: 0;
				color: var(--tn-grid-muted);
				line-height: 1.55;
			}
			.tn-manage-lookup-form {
				display: grid;
				grid-template-columns: minmax(0, 1fr) auto;
				gap: 0.75rem;
				align-items: end;
			}
			.tn-manage-lookup-form label {
				display: block;
				margin: 0 0 0.3rem;
				color: var(--tn-grid-muted);
				font-size: 0.78rem;
				font-weight: 800;
				letter-spacing: 0.08em;
				text-transform: uppercase;
			}
			.tn-manage-lookup-form input[type="email"] {
				width: 100%;
				padding: 0.6rem 0.7rem;
				border: 1px solid var(--tn-grid-line);
				border-radius: 6px;
				background: rgba(7,8,18,0.72);
				color: var(--tn-grid-text);
			}
			.tn-manage-lookup-form button {
				background: linear-gradient(135deg, var(--tn-grid-gold), #ffe29a);
				color: #241a02;
			}
			.tn-signup-trap {
				position: absolute !important;
				left: -9999px !important;
			}
			@media (max-width: 720px) {
				.tn-manage-lookup-form {
					grid-template-columns: 1fr;
				}
				.tn-manage-card-actions,
				.tn-manage-flight-form {
					align-items: stretch;
					flex-direction: column;
				}
				.tn-signup-page button {
					width: 100%;
				}
			}
		</style>
		<div class="tn-signup-nav">
			<a class="tn-signup-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Trivia Nationals 2026</a>
			<nav aria-label="Manage signups page navigation">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<a href="<?php echo esc_url( home_url( '/full-schedule/' ) ); ?>">Full Schedule</a>
				<a href="<?php echo esc_url( home_url( '/event-signups/' ) ); ?>">Signups</a>
				<a href="<?php echo esc_url( home_url( '/manage-signups/' ) ); ?>" aria-current="page">Manage</a>
			</nav>
		</div>
		<div class="tn-signup-page-inner">
			<p class="tn-signup-kicker">August 7 - 9, 2026 / Las Vegas</p>
			<h1>Manage Your Signups</h1>
			<?php if ( $status === 'updated' ) : ?>
				<p class="tn-signup-page-message is-success">Your flight was updated. A confirmation email is on its way.</p>
			<?php elseif ( $status === 'cancelled' ) : ?>
				<p class="tn-signup-page-message is-success">Your signup was cancelled. A confirmation email is on its way.</p>
			<?php elseif ( $status === 'duplicate' ) : ?>
				<p class="tn-signup-page-message is-error">You already have a signup for that flight, so nothing was changed.</p>
			<?php elseif ( in_array( $status, [ 'invalid', 'missing', 'error' ], true ) ) : ?>
				<p class="tn-signup-page-message is-error">Sorry, that change could not be saved. Please try again.</p>
			<?php endif; ?>
			<?php if ( $lookup_status === 'sent' ) : ?>
				<p class="tn-signup-page-message is-success">Thanks! Check that inbox for a fresh manage link.</p>
			<?php elseif ( in_array( $lookup_status, [ 'invalid', 'error' ], true ) ) : ?>
				<p class="tn-signup-page-message is-error">Sorry, we could not send that email. Please check the address and try again.</p>
			<?php endif; ?>
			<?php if ( ! $email ) : ?>
				<section class="tn-manage-expired" aria-labelledby="tn-manage-expired-title">
					<h2 id="tn-manage-expired-title">This link has expired</h2>
					<p>Manage links only work for 72 hours. Enter your contact email below and we&#8217;ll send you a fresh one, along with a summary of your current signups.</p>
					<form class="tn-manage-lookup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="tn_tde_email_signup_summary">
						<input type="hidden" name="tn_signup_redirect" value="<?php echo esc_url( home_url( '/manage-signups/' ) ); ?>">
						<?php wp_nonce_field( 'tn_tde_email_signup_summary', 'tn_tde_email_signup_summary_nonce' ); ?>
						<p>
							<label for="tn_manage_lookup_email">Contact Email</label>
							<input type="email" id="tn_manage_lookup_email" name="tn_signup_lookup_email" required autocomplete="email">
						</p>
						<p class="tn-signup-trap" aria-hidden="true">
							<label for="tn_manage_lookup_company">Leave this field blank</label>
							<input type="text" id="tn_manage_lookup_company" name="tn_signup_lookup_check" tabindex="-1" autocomplete="new-password">
						</p>
						<button type="submit" data-tn-saving-label="Sending...">Email Me a Link</button>
					</form>
				</section>
			<?php elseif ( ! $signup_ids ) : ?>
				<section class="tn-manage-empty" aria-labelledby="tn-manage-empty-title">
					<h2 id="tn-manage-empty-title">No active signups</h2>
					<p>There are no active event signups associated with <strong><?php echo esc_html( $email ); ?></strong>.</p>
					<p><a href="<?php echo esc_url( home_url( '/event-signups/' ) ); ?>" style="color: var(--tn-grid-cyan); font-weight: 800;">Sign up for events &rarr;</a></p>
				</section>
			<?php else : ?>
				<p class="tn-signup-page-intro">Signups for <strong style="color: var(--tn-grid-text);"><?php echo esc_html( $email ); ?></strong>. Change a flight or cancel a signup below &mdash; changes take effect immediately and you&#8217;ll get a confirmation email.</p>
				<div class="tn-manage-cards">
					<?php foreach ( $signup_ids as $signup_id ) :
						$meta = tn_tde_manage_signup_card_meta( $signup_id );
						$event = $meta['event_slug'] ? tn_tde_get_event_by_detail_slug( $meta['event_slug'] ) : null;
						$flights = $event ? tn_tde_signup_flight_options_for_event( $event ) : [];
						$when = trim( implode( ', ', array_filter( [ $meta['event_day'], $meta['event_date'] ] ) ) );
						$time = $meta['event_start'] && $meta['event_end'] ? $meta['event_start'] . ' - ' . $meta['event_end'] : $meta['event_start'];
						?>
						<section class="tn-manage-card" aria-label="<?php echo esc_attr( $meta['event_title'] ?: 'Event signup' ); ?>">
							<div class="tn-manage-card-head">
								<h2 class="tn-manage-card-title"><?php echo esc_html( $meta['event_title'] ?: 'Event signup' ); ?></h2>
								<?php if ( $when || $time ) : ?>
									<span class="tn-manage-card-when"><?php echo esc_html( trim( $when . ( $time ? ', ' . $time : '' ), ', ' ) ); ?></span>
								<?php endif; ?>
							</div>
							<dl class="tn-manage-card-facts">
								<?php if ( $meta['flight'] ) : ?><div><strong>Flight:</strong> <?php echo esc_html( $meta['flight'] ); ?></div><?php endif; ?>
								<?php if ( $meta['event_location'] ) : ?><div><strong>Location:</strong> <?php echo esc_html( $meta['event_location'] ); ?></div><?php endif; ?>
								<?php if ( $meta['team'] ) : ?><div><strong>Team:</strong> <?php echo esc_html( $meta['team'] ); ?></div><?php endif; ?>
								<?php if ( $meta['team_members'] ) : ?><div><strong>Team Members:</strong> <?php echo esc_html( $meta['team_members'] ); ?></div><?php endif; ?>
							</dl>
							<div class="tn-manage-card-actions">
								<?php if ( $flights ) : ?>
									<form class="tn-manage-flight-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="tn_tde_manage_signup_update">
										<input type="hidden" name="tn_token" value="<?php echo esc_attr( $token ); ?>">
										<input type="hidden" name="signup_id" value="<?php echo esc_attr( $signup_id ); ?>">
										<?php wp_nonce_field( 'tn_tde_manage_signup_' . $signup_id, 'tn_tde_manage_nonce' ); ?>
										<p style="margin:0;">
											<label for="tn_manage_flight_<?php echo esc_attr( $signup_id ); ?>">Flight</label>
											<select id="tn_manage_flight_<?php echo esc_attr( $signup_id ); ?>" name="tn_signup_flight" required>
												<?php foreach ( $flights as $flight ) : ?>
													<option value="<?php echo esc_attr( $flight['value'] ); ?>" <?php selected( $flight['value'] === $meta['flight'] ); ?>><?php echo esc_html( $flight['label'] ); ?></option>
												<?php endforeach; ?>
											</select>
										</p>
										<button type="submit" class="tn-manage-save" data-tn-saving-label="Saving...">Save Change</button>
									</form>
								<?php else : ?>
									<span class="tn-manage-card-when">Flight changes are not available for this event.</span>
								<?php endif; ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return window.confirm('Cancel your <?php echo esc_js( $meta['event_title'] ?: 'event' ); ?> signup? This removes you from the list.');">
									<input type="hidden" name="action" value="tn_tde_manage_signup_cancel">
									<input type="hidden" name="tn_token" value="<?php echo esc_attr( $token ); ?>">
									<input type="hidden" name="signup_id" value="<?php echo esc_attr( $signup_id ); ?>">
									<?php wp_nonce_field( 'tn_tde_manage_signup_' . $signup_id, 'tn_tde_manage_nonce' ); ?>
									<button type="submit" class="tn-manage-cancel" data-tn-saving-label="Cancelling...">Cancel Signup</button>
								</form>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</main>
	<?php
	get_footer();
}

function tn_tde_manage_signup_guard( $expect_signup ) {
	$token = isset( $_POST['tn_token'] ) ? sanitize_text_field( wp_unslash( $_POST['tn_token'] ) ) : '';
	$signup_id = isset( $_POST['signup_id'] ) ? absint( $_POST['signup_id'] ) : 0;
	$redirect = $token ? tn_tde_manage_signups_url( $token ) : home_url( '/manage-signups/' );
	$fail = static function( $code ) use ( $redirect ) {
		wp_safe_redirect( add_query_arg( 'tn_manage', $code, $redirect ) );
		exit;
	};
	$email = tn_tde_manage_signups_email_for_token( $token );
	if ( ! $email ) $fail( 'invalid' );
	if ( ! isset( $_POST['tn_tde_manage_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_tde_manage_nonce'] ) ), 'tn_tde_manage_signup_' . $signup_id ) ) $fail( 'invalid' );
	if ( $expect_signup ) {
		$signup = get_post( $signup_id );
		if ( ! $signup || $signup->post_type !== 'tn_tde_signup' ) $fail( 'invalid' );
		$signup_email = tn_tde_signup_meta_value( $signup_id, 'email' );
		if ( strtolower( trim( $signup_email ) ) !== strtolower( trim( $email ) ) ) $fail( 'invalid' );
		if ( tn_tde_signup_status( $signup_id ) !== 'active' ) $fail( 'invalid' );
	}
	return [ 'token' => $token, 'email' => $email, 'signup_id' => $signup_id, 'redirect' => $redirect, 'fail' => $fail ];
}

function tn_tde_send_manage_confirmation_email( $email, $subject, $intro, $signup_id, $token ) {
	$meta = tn_tde_manage_signup_card_meta( $signup_id );
	$when = trim( implode( ', ', array_filter( [ $meta['event_day'], $meta['event_date'], $meta['event_start'] ] ) ) );
	$html = '<div style="font-family:Arial,sans-serif;color:#222;line-height:1.5;">';
	$html .= '<h2 style="margin:0 0 16px;color:#17406f;">' . esc_html( $subject ) . '</h2>';
	$html .= '<p>' . esc_html( $intro ) . '</p>';
	$html .= '<ul style="margin:8px 0 16px;padding-left:18px;">';
	$html .= '<li><strong>Event:</strong> ' . esc_html( $meta['event_title'] ?: 'Event' ) . '</li>';
	$html .= tn_tde_signup_summary_email_detail( 'Flight', $meta['flight'] );
	$html .= tn_tde_signup_summary_email_detail( 'Time', $when );
	$html .= tn_tde_signup_summary_email_detail( 'Location', $meta['event_location'] );
	$html .= '</ul>';
	$html .= '<p><a href="' . esc_url( tn_tde_manage_signups_url( $token ) ) . '">Manage your signups</a> (link works for 72 hours from when it was first emailed).</p>';
	$html .= '<p style="color:#666;font-size:13px;">If you did not make this change, please contact info@trivianationals.org.</p>';
	$html .= '</div>';
	tn_tde_send_signup_email( $email, $subject, $html );
}

add_action( 'admin_post_tn_tde_manage_signup_update', 'tn_tde_handle_manage_signup_update' );
add_action( 'admin_post_nopriv_tn_tde_manage_signup_update', 'tn_tde_handle_manage_signup_update' );
add_action( 'admin_post_tn_tde_manage_signup_cancel', 'tn_tde_handle_manage_signup_cancel' );
add_action( 'admin_post_nopriv_tn_tde_manage_signup_cancel', 'tn_tde_handle_manage_signup_cancel' );

function tn_tde_handle_manage_signup_update() {
	$context = tn_tde_manage_signup_guard( true );
	$signup_id = $context['signup_id'];
	$fail = $context['fail'];
	$flight = isset( $_POST['tn_signup_flight'] ) ? sanitize_text_field( wp_unslash( $_POST['tn_signup_flight'] ) ) : '';
	$event_slug = tn_tde_signup_meta_value( $signup_id, 'event_slug' );
	$event = $event_slug ? tn_tde_get_event_by_detail_slug( $event_slug ) : null;
	if ( ! $event ) $fail( 'error' );
	$option = $flight !== '' ? tn_tde_signup_option_for_value( $event, $flight ) : null;
	if ( ! $option ) $fail( 'missing' );
	$current_flight = tn_tde_signup_meta_value( $signup_id, 'flight' );
	if ( $option['value'] === $current_flight ) {
		wp_safe_redirect( add_query_arg( 'tn_manage', 'updated', $context['redirect'] ) );
		exit;
	}
	foreach ( tn_tde_active_signup_ids_for_email( $context['email'] ) as $other_id ) {
		if ( (int) $other_id === (int) $signup_id ) continue;
		if ( tn_tde_signup_meta_value( $other_id, 'event_title' ) === tn_tde_signup_meta_value( $signup_id, 'event_title' )
			&& tn_tde_signup_meta_value( $other_id, 'flight' ) === $option['value'] ) {
			$fail( 'duplicate' );
		}
	}
	$target_event = ! empty( $option['event'] ) ? $option['event'] : $event;
	$updates = [
		'event_slug' => tn_tde_event_detail_slug( $target_event ),
		'event_title' => sanitize_text_field( $target_event['base_title'] ?? $target_event['title'] ?? '' ),
		'event_session' => sanitize_text_field( $option['session'] ?? '' ),
		'event_day' => sanitize_text_field( $target_event['day_label'] ?? '' ),
		'event_date' => sanitize_text_field( $target_event['date_label'] ?? '' ),
		'event_start' => sanitize_text_field( $target_event['start'] ?? '' ),
		'event_end' => sanitize_text_field( $target_event['end'] ?? '' ),
		'event_location' => sanitize_text_field( $target_event['location_label'] ?? '' ),
		'flight' => $option['value'],
		'sync_status' => 'pending',
	];
	foreach ( $updates as $key => $value ) {
		update_post_meta( $signup_id, '_tn_tde_signup_' . $key, $value );
	}
	delete_post_meta( $signup_id, '_tn_tde_signup_sync_error' );
	tn_tde_sync_event_signup( $signup_id, 8 );
	if ( tn_tde_signup_meta_value( $signup_id, 'sync_status' ) === 'pending' ) {
		tn_tde_queue_event_signup_sync( $signup_id );
	}
	tn_tde_send_manage_confirmation_email(
		$context['email'],
		'Your Trivia Nationals signup was updated',
		'Your event signup was moved to a new flight. Here are the updated details:',
		$signup_id,
		$context['token']
	);
	wp_safe_redirect( add_query_arg( 'tn_manage', 'updated', $context['redirect'] ) );
	exit;
}

function tn_tde_handle_manage_signup_cancel() {
	$context = tn_tde_manage_signup_guard( true );
	$signup_id = $context['signup_id'];
	update_post_meta( $signup_id, '_tn_tde_signup_status', 'cancelled' );
	update_post_meta( $signup_id, '_tn_tde_signup_status_changed_at', current_time( 'mysql' ) );
	update_post_meta( $signup_id, '_tn_tde_signup_status_reason', 'Cancelled by attendee via manage link' );
	update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'pending' );
	delete_post_meta( $signup_id, '_tn_tde_signup_sync_error' );
	tn_tde_sync_event_signup( $signup_id, 8 );
	if ( tn_tde_signup_meta_value( $signup_id, 'sync_status' ) === 'pending' ) {
		tn_tde_queue_event_signup_sync( $signup_id );
	}
	tn_tde_send_manage_confirmation_email(
		$context['email'],
		'Your Trivia Nationals signup was cancelled',
		'This event signup was cancelled and you have been removed from the list:',
		$signup_id,
		$context['token']
	);
	wp_safe_redirect( add_query_arg( 'tn_manage', 'cancelled', $context['redirect'] ) );
	exit;
}

function tn_tde_is_event_signups_request() {
	if ( is_admin() ) return false;
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	return strtolower( $path ) === 'event-signups';
}

add_action( 'wp', function() {
	if ( ! tn_tde_is_event_signups_request() ) return;
	global $wp_query;
	$wp_query->is_404 = false;
	$wp_query->is_page = true;
	$wp_query->is_singular = true;
	status_header( 200 );
} );

add_filter( 'document_title_parts', function( $parts ) {
	if ( tn_tde_is_event_signups_request() ) {
		$parts['title'] = 'Event Signups';
	}
	return $parts;
} );

add_filter( 'body_class', function( $classes ) {
	if ( ! tn_tde_is_event_signups_request() ) return $classes;
	$classes = array_diff( $classes, [ 'error404' ] );
	$classes[] = 'tn-event-signups-page';
	return array_values( array_unique( $classes ) );
} );

add_action( 'template_redirect', function() {
	if ( ! tn_tde_is_event_signups_request() ) return;
	status_header( 200 );
	nocache_headers();
	tn_tde_render_signup_page();
	exit;
}, 2 );

function tn_tde_render_signup_page() {
	$events = tn_tde_signup_events_for_page();
	$status = isset( $_GET['tn_signup'] ) ? sanitize_key( wp_unslash( $_GET['tn_signup'] ) ) : '';
	$lookup_status = isset( $_GET['tn_lookup'] ) ? sanitize_key( wp_unslash( $_GET['tn_lookup'] ) ) : '';
	$count = isset( $_GET['tn_signup_count'] ) ? absint( $_GET['tn_signup_count'] ) : 0;
	get_header();
	?>
	<main class="tn-signup-page">
		<style>
			body.tn-event-signups-page .inner-main-title,
			body.tn-event-signups-page .entry-header,
			body.tn-event-signups-page .page-header {
				display: none !important;
			}
			body.tn-event-signups-page .site-content,
			body.tn-event-signups-page .content-area,
			body.tn-event-signups-page .site-main,
			body.tn-event-signups-page .entry-content {
				margin: 0 !important;
				max-width: none !important;
				padding: 0 !important;
				width: 100% !important;
			}
			.tn-signup-page {
				--tn-grid-bg: #0a0a14;
				--tn-grid-panel: rgba(18,20,34,0.82);
				--tn-grid-panel-strong: rgba(25,29,48,0.94);
				--tn-grid-line: rgba(255,255,255,0.16);
				--tn-grid-text: #f0f0f5;
				--tn-grid-muted: #b7bdcf;
				--tn-grid-cyan: #00e6ff;
				--tn-grid-pink: #ff3ea5;
				--tn-grid-gold: #ffd166;
				color: var(--tn-grid-text);
				background:
					radial-gradient(circle at 18% 7%, rgba(0,230,255,0.18), transparent 24rem),
					radial-gradient(circle at 82% 0%, rgba(255,62,165,0.16), transparent 25rem),
					linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.012) 42%, rgba(0,0,0,0)),
					var(--tn-grid-bg);
				font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
				margin-left: calc(50% - 50vw);
				margin-right: calc(50% - 50vw);
				max-width: none;
				min-height: 100vh;
				padding: clamp(2.5rem, 7vw, 6rem) clamp(1rem, 4vw, 4rem) clamp(2.5rem, 6vw, 5rem);
				width: 100vw;
			}
			.tn-signup-page > * {
				margin: 0 auto;
				max-width: 1320px;
			}
			.tn-signup-nav {
				align-items: center;
				display: flex;
				gap: 1rem;
				justify-content: space-between;
				margin-bottom: clamp(1.4rem, 3vw, 2.6rem);
			}
			.tn-signup-brand {
				color: var(--tn-grid-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(1rem, 1.5vw, 1.35rem);
				font-weight: 900;
				line-height: 1;
				text-decoration: none;
				text-transform: uppercase;
			}
			.tn-signup-nav nav {
				align-items: center;
				display: flex;
				flex-wrap: wrap;
				gap: clamp(0.75rem, 2vw, 1.5rem);
				justify-content: flex-end;
			}
			.tn-signup-nav nav a {
				color: var(--tn-grid-muted);
				font-size: 0.84rem;
				font-weight: 800;
				text-decoration: none;
				text-transform: uppercase;
			}
			.tn-signup-nav nav a:hover,
			.tn-signup-nav nav a[aria-current="page"] {
				color: var(--tn-grid-cyan);
			}
			.tn-signup-page-inner {
				width: min(980px, 100%);
			}
			.tn-signup-page h1 {
				margin: 0 0 0.65rem;
				color: var(--tn-grid-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(3.2rem, 7vw, 6.4rem);
				font-weight: 900;
				letter-spacing: 0;
				line-height: 0.9;
				text-transform: uppercase;
			}
			.tn-signup-kicker {
				color: var(--tn-grid-cyan);
				font-size: clamp(0.8rem, 1.2vw, 1rem);
				font-weight: 900;
				letter-spacing: 0.12em;
				margin: 0 0 0.55rem;
				text-transform: uppercase;
			}
			.tn-signup-page-intro {
				max-width: 46rem;
				margin: 0 0 1rem;
				color: var(--tn-grid-muted);
				font-size: 1.05rem;
				line-height: 1.6;
			}
			.tn-signup-note {
				display: grid;
				gap: 0.35rem;
				margin: 0 0 1.2rem;
				padding: 0.95rem 1rem;
				border: 1px solid rgba(0,230,255,0.28);
				border-radius: 8px;
				background:
					linear-gradient(135deg, rgba(0,230,255,0.09), rgba(255,62,165,0.045)),
					rgba(18,20,34,0.78);
				color: var(--tn-grid-muted);
				line-height: 1.55;
			}
			.tn-signup-note p {
				margin: 0;
			}
			.tn-signup-note strong {
				color: var(--tn-grid-text);
			}
			.tn-signup-page-message {
				margin: 0 0 1rem;
				padding: 0.85rem 1rem;
				border-radius: 8px;
				background: var(--tn-grid-panel);
				border: 1px solid var(--tn-grid-line);
			}
			.tn-signup-page-message.is-success { color: #35e69f; }
			.tn-signup-page-message.is-error { color: #ff8a8a; }
			.tn-signup-page-form {
				display: grid;
				gap: 1rem;
				padding: clamp(1rem, 3vw, 1.5rem);
				border: 1px solid var(--tn-grid-line);
				border-radius: 8px;
				background: var(--tn-grid-panel-strong);
				box-shadow: 0 24px 80px rgba(0,0,0,0.28);
			}
			.tn-signup-lookup {
				display: grid;
				gap: 0.9rem;
				margin-top: 1.25rem;
				padding: clamp(1rem, 3vw, 1.35rem);
				border: 1px solid rgba(0,230,255,0.22);
				border-radius: 8px;
				background:
					linear-gradient(135deg, rgba(0,230,255,0.08), rgba(255,62,165,0.04)),
					rgba(18,20,34,0.82);
			}
			.tn-signup-lookup h2 {
				margin: 0;
				color: var(--tn-grid-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(1.45rem, 3vw, 2.2rem);
				font-weight: 900;
				line-height: 1;
				text-transform: uppercase;
			}
			.tn-signup-lookup p {
				margin: 0;
				color: var(--tn-grid-muted);
				line-height: 1.55;
			}
			.tn-signup-lookup-form {
				display: grid;
				grid-template-columns: minmax(0, 1fr) auto;
				gap: 0.75rem;
				align-items: end;
			}
			.tn-signup-common-fields,
			.tn-signup-event-row-fields {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 0.8rem;
			}
			.tn-signup-event-row {
				display: grid;
				gap: 0.85rem;
				padding: 1rem;
				border: 1px solid var(--tn-grid-line);
				border-radius: 8px;
				background: rgba(7,8,18,0.42);
			}
			.tn-signup-event-row-head {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 1rem;
			}
			.tn-signup-event-row-title {
				margin: 0;
				color: var(--tn-grid-cyan);
				font-family: Outfit, Inter, sans-serif;
				font-weight: 900;
				letter-spacing: 0.06em;
				text-transform: uppercase;
			}
			.tn-signup-page label {
				display: block;
				margin-bottom: 0.25rem;
				color: var(--tn-grid-muted);
				font-weight: 800;
			}
			.tn-signup-page input,
			.tn-signup-page select,
			.tn-signup-page textarea {
				width: 100%;
				border: 1px solid var(--tn-grid-line);
				border-radius: 8px;
				background: rgba(7,8,18,0.72);
				color: var(--tn-grid-text);
				padding: 0.72rem 0.85rem;
			}
			.tn-signup-page input:focus,
			.tn-signup-page select:focus,
			.tn-signup-page textarea:focus {
				border-color: rgba(0,230,255,0.72);
				box-shadow: 0 0 0 3px rgba(0,230,255,0.12);
				outline: none;
			}
			.tn-signup-page .is-full { grid-column: 1 / -1; }
			.tn-signup-team-fields {
				display: grid;
				gap: 0.8rem;
			}
			.tn-signup-page [hidden] {
				display: none !important;
			}
			.tn-signup-page button {
				border: 0;
				border-radius: 999px;
				cursor: pointer;
				font-family: Outfit, Inter, sans-serif;
				font-weight: 900;
				letter-spacing: 0.08em;
				padding: 0.78rem 1.1rem;
				text-transform: uppercase;
			}
			.tn-signup-add,
			.tn-signup-submit {
				background: var(--tn-grid-gold);
				color: #071019;
			}
			.tn-signup-remove {
				background: rgba(255,255,255,0.1);
				color: var(--tn-grid-text);
			}
			.tn-signup-actions {
				display: flex;
				gap: 0.75rem;
				flex-wrap: wrap;
			}
			.tn-signup-trap {
				position: absolute;
				left: -9999px;
			}
			@media (max-width: 820px) {
				.tn-signup-page {
					padding: 1rem;
				}
				.tn-signup-nav {
					align-items: flex-start;
					flex-direction: column;
				}
				.tn-signup-nav nav {
					justify-content: flex-start;
				}
				.tn-signup-page h1 {
					font-size: clamp(2.65rem, 12.5vw, 3.6rem);
					max-width: 8.5ch;
				}
			}
			@media (max-width: 720px) {
				.tn-signup-common-fields,
				.tn-signup-event-row-fields,
				.tn-signup-lookup-form {
					grid-template-columns: 1fr;
				}
				.tn-signup-actions button,
				.tn-signup-lookup-form button {
					width: 100%;
				}
			}
		</style>
		<div class="tn-signup-nav">
			<a class="tn-signup-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Trivia Nationals 2026</a>
			<nav aria-label="Signup page navigation">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<a href="<?php echo esc_url( home_url( '/#schedule' ) ); ?>">Schedule</a>
				<a href="<?php echo esc_url( home_url( '/full-schedule/' ) ); ?>">Full Schedule</a>
				<a href="<?php echo esc_url( home_url( '/event-signups/' ) ); ?>" aria-current="page">Signups</a>
			</nav>
		</div>
		<div class="tn-signup-page-inner">
			<p class="tn-signup-kicker">August 7 - 9, 2026 / Las Vegas</p>
			<h1>Event Signups</h1>
			<p class="tn-signup-page-intro">Choose one or more events, fill in the event-specific details, and submit everything together.</p>
			<div class="tn-signup-note" role="note">
				<p><strong>Important:</strong> You must be registered for Trivia Nationals 2026 before signing up for events.</p>
				<p>You may sign up for only one flight per event.</p>
				<p>Flight selection is for denoting your preference. Because of limited capacity, flight assignments cannot be guaranteed, but every effort will be made to get you into the flight you choose.</p>
			</div>
			<?php if ( $status === 'success' ) : ?>
				<div class="tn-signup-success-banner" role="status">
				<span class="tn-signup-success-check" aria-hidden="true"></span>
				<div>
					<strong><?php echo esc_html( $count > 1 ? $count . ' signups received!' : 'Signup received!' ); ?></strong>
					<p>You&#8217;re on the list. Want a record or need to make changes later? Use the email tool below to get a summary and a manage link.</p>
				</div>
			</div>
			<?php elseif ( in_array( $status, [ 'invalid', 'closed', 'missing', 'spam', 'error' ], true ) ) : ?>
				<p class="tn-signup-page-message is-error">Sorry, that signup could not be saved. Please check the required fields and try again.</p>
			<?php endif; ?>
			<form class="tn-signup-page-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-tn-signup-page-form>
				<input type="hidden" name="action" value="tn_tde_bulk_event_signup">
				<input type="hidden" name="tn_signup_redirect" value="<?php echo esc_url( home_url( '/event-signups/' ) ); ?>">
				<?php wp_nonce_field( 'tn_tde_bulk_event_signup', 'tn_tde_bulk_event_signup_nonce' ); ?>
				<div class="tn-signup-common-fields">
					<p>
						<label for="tn_signup_page_name">Name *</label>
						<input type="text" id="tn_signup_page_name" name="tn_signup_name" required autocomplete="name">
					</p>
					<p>
						<label for="tn_signup_page_email">Contact Email *</label>
						<input type="email" id="tn_signup_page_email" name="tn_signup_email" required autocomplete="email">
					</p>
				</div>
				<div data-tn-signup-events></div>
				<p class="tn-signup-trap" aria-hidden="true">
					<label for="tn_signup_page_referrer_check">Leave this field blank</label>
					<input type="text" id="tn_signup_page_referrer_check" name="tn_signup_referrer_check" tabindex="-1" autocomplete="new-password">
				</p>
				<div class="tn-signup-actions">
					<button type="button" class="tn-signup-add" data-tn-add-event>Add Another Event</button>
					<button type="submit" class="tn-signup-submit" data-tn-saving-label="Submitting Signups...">Submit Signups</button>
				</div>
			</form>
			<section class="tn-signup-lookup" aria-labelledby="tn-signup-lookup-title">
				<h2 id="tn-signup-lookup-title">Check or Manage Your Event Signups</h2>
				<p>Enter the contact email you used for event signups and we’ll email a summary of anything currently associated with that address, plus a secure link to change flights or cancel signups.</p>
				<?php if ( $lookup_status === 'sent' ) : ?>
					<p class="tn-signup-page-message is-success">Thanks! Check that inbox for your signup summary.</p>
				<?php elseif ( in_array( $lookup_status, [ 'invalid', 'error' ], true ) ) : ?>
					<p class="tn-signup-page-message is-error">Sorry, we could not send that summary. Please check the email address and try again.</p>
				<?php endif; ?>
				<form class="tn-signup-lookup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="tn_tde_email_signup_summary">
					<input type="hidden" name="tn_signup_redirect" value="<?php echo esc_url( home_url( '/event-signups/' ) ); ?>">
					<?php wp_nonce_field( 'tn_tde_email_signup_summary', 'tn_tde_email_signup_summary_nonce' ); ?>
					<p>
						<label for="tn_signup_lookup_email">Contact Email</label>
						<input type="email" id="tn_signup_lookup_email" name="tn_signup_lookup_email" required autocomplete="email">
					</p>
					<p class="tn-signup-trap" aria-hidden="true">
						<label for="tn_signup_lookup_company">Leave this field blank</label>
						<input type="text" id="tn_signup_lookup_company" name="tn_signup_lookup_check" tabindex="-1" autocomplete="new-password">
					</p>
					<button type="submit" class="tn-signup-submit" data-tn-saving-label="Sending...">Email My Signups</button>
				</form>
			</section>
		</div>
		<script>
		(function(){
			var events = <?php echo wp_json_encode( $events ); ?>;
			var list = document.querySelector('[data-tn-signup-events]');
			var addButton = document.querySelector('[data-tn-add-event]');
			var rowCount = 0;
			function option(text, value) {
				var el = document.createElement('option');
				el.textContent = text;
				el.value = value || '';
				return el;
			}
			function eventBySlug(slug) {
				return events.find(function(event) { return event.slug === slug; }) || null;
			}
			function refreshRow(row) {
				var select = row.querySelector('[data-tn-event-select]');
				var flightWrap = row.querySelector('[data-tn-flight-wrap]');
				var flightSelect = row.querySelector('[data-tn-flight-select]');
				var teamWrap = row.querySelector('[data-tn-team-wrap]');
				var event = eventBySlug(select.value);
				flightSelect.innerHTML = '';
				flightSelect.appendChild(option(event && event.isWaitlist ? 'Waiting List' : 'Select a flight', ''));
				if (event && event.flights && event.flights.length) {
					event.flights.forEach(function(flight) {
						flightSelect.appendChild(option(flight.label, flight.value));
					});
					if (event.isWaitlist && event.flights.length === 1) flightSelect.value = event.flights[0].value;
					flightSelect.required = true;
					flightWrap.hidden = false;
				} else {
					flightSelect.required = false;
					flightWrap.hidden = true;
				}
				teamWrap.hidden = !(event && event.isTeam);
			}
			function renumberRows() {
				list.querySelectorAll('[data-tn-signup-row]').forEach(function(row, index) {
					var title = row.querySelector('[data-tn-row-title]');
					var remove = row.querySelector('[data-tn-remove-event]');
					if (title) title.textContent = 'Event ' + (index + 1);
					if (remove) remove.hidden = index === 0 && list.querySelectorAll('[data-tn-signup-row]').length === 1;
				});
			}
			function addRow() {
				var index = rowCount++;
				var row = document.createElement('section');
				row.className = 'tn-signup-event-row';
				row.setAttribute('data-tn-signup-row', '');
				row.innerHTML =
					'<div class="tn-signup-event-row-head">' +
						'<p class="tn-signup-event-row-title" data-tn-row-title>Event</p>' +
						'<button type="button" class="tn-signup-remove" data-tn-remove-event>Remove</button>' +
					'</div>' +
					'<div class="tn-signup-event-row-fields">' +
						'<p class="is-full"><label>Event *</label><select name="tn_signup_events[' + index + '][event_slug]" required data-tn-event-select></select></p>' +
						'<p class="is-full" data-tn-flight-wrap hidden><label>Flight *</label><select name="tn_signup_events[' + index + '][flight]" data-tn-flight-select></select></p>' +
						'<div class="is-full tn-signup-team-fields" data-tn-team-wrap hidden>' +
							'<p><label>Team Name</label><input type="text" name="tn_signup_events[' + index + '][team]" autocomplete="organization"></p>' +
							'<p><label>Team Members</label><textarea name="tn_signup_events[' + index + '][team_members]" rows="3" placeholder="One person can register the whole team. List teammates here if you have them."></textarea></p>' +
						'</div>' +
						'<p class="is-full"><label>Notes</label><textarea name="tn_signup_events[' + index + '][notes]" rows="3"></textarea></p>' +
					'</div>';
				var eventSelect = row.querySelector('[data-tn-event-select]');
				eventSelect.appendChild(option('Select an event', ''));
				events.forEach(function(event) {
					eventSelect.appendChild(option(event.title, event.slug));
				});
				eventSelect.addEventListener('change', function() { refreshRow(row); });
				row.querySelector('[data-tn-remove-event]').addEventListener('click', function() {
					row.remove();
					if (!list.querySelector('[data-tn-signup-row]')) addRow();
					renumberRows();
				});
				list.appendChild(row);
				refreshRow(row);
				renumberRows();
			}
			if (addButton) addButton.addEventListener('click', addRow);
			addRow();
		})();
		</script>
	</main>
	<?php
	get_footer();
}

function tn_tde_queue_event_signup_sync( $signup_id ) {
	if ( function_exists( 'as_enqueue_async_action' ) ) {
		as_enqueue_async_action( 'tn_tde_sync_event_signup', [ $signup_id ], 'tn-event-signups', true );
		return;
	}
	if ( ! wp_next_scheduled( 'tn_tde_sync_event_signup', [ $signup_id ] ) ) {
		wp_schedule_single_event( time() + 5, 'tn_tde_sync_event_signup', [ $signup_id ] );
	}
}

function tn_tde_sync_event_signup( $signup_id, $timeout = 20 ) {
	$endpoint = trim( (string) get_option( 'tn_tde_signup_sheets_endpoint' ) );
	$secret = trim( (string) get_option( 'tn_tde_signup_sheets_secret' ) );
	$signup = get_post( absint( $signup_id ) );
	if ( ! $signup || $signup->post_type !== 'tn_tde_signup' ) return false;
	if ( ! $endpoint || ! $secret ) {
		update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'failed' );
		update_post_meta( $signup_id, '_tn_tde_signup_sync_error', 'Missing Google Sheets endpoint or secret.' );
		return false;
	}
	$fields = [ 'event_slug', 'event_title', 'event_session', 'event_day', 'event_date', 'event_start', 'event_end', 'event_location', 'name', 'email', 'flight', 'team', 'team_members', 'notes', 'status', 'status_changed_at', 'status_reason' ];
	$row = [
		'signup_id' => (string) $signup->ID,
		'submitted_at' => get_post_time( 'Y-m-d H:i:s', false, $signup ),
	];
	foreach ( $fields as $field ) {
		$row[ $field ] = (string) get_post_meta( $signup->ID, '_tn_tde_signup_' . $field, true );
	}
	if ( $row['status'] === '' ) $row['status'] = 'active';
	// Cancelled signups are removed from the Sheet instead of upserted; restoring one re-adds it on the next sync.
	$sync_action = $row['status'] === 'cancelled' ? 'event_signup_delete' : 'event_signup_upsert';
	$response = wp_remote_post( $endpoint, [
		'timeout' => max( 1, absint( $timeout ) ),
		'redirection' => 0,
		'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
		'body' => wp_json_encode( [ 'secret' => $secret, 'action' => $sync_action, 'signup' => $row ] ),
	] );
	if ( is_wp_error( $response ) ) {
		update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'failed' );
		update_post_meta( $signup_id, '_tn_tde_signup_sync_error', sanitize_text_field( $response->get_error_message() ) );
		return false;
	}
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code >= 300 && $code < 400 && wp_remote_retrieve_header( $response, 'location' ) ) {
		$response = wp_remote_get( wp_remote_retrieve_header( $response, 'location' ), [ 'timeout' => max( 1, absint( $timeout ) ), 'redirection' => 5 ] );
		$code = is_wp_error( $response ) ? 500 : wp_remote_retrieve_response_code( $response );
	}
	$body = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
	$result = json_decode( $body, true );
	if ( $code < 200 || $code >= 300 || ! is_array( $result ) || empty( $result['ok'] ) ) {
		update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'failed' );
		update_post_meta( $signup_id, '_tn_tde_signup_sync_error', sanitize_text_field( 'HTTP ' . $code . ': ' . $body ) );
		return false;
	}
	update_post_meta( $signup_id, '_tn_tde_signup_sync_status', 'synced' );
	delete_post_meta( $signup_id, '_tn_tde_signup_sync_error' );
	return true;
}

function tn_tde_home_event_types() {
	return [
		'none' => [
			'label' => 'No Event Type',
			'color' => '',
		],
		'jeopardy-buzzer' => [
			'label' => 'Jeopardy Style Individual Buzzer Games',
			'color' => '#00e5ff',
		],
		'specialty-quiz-league' => [
			'label' => 'Specialty Quiz League Formats',
			'color' => '#b96cff',
		],
		'quiz-bowl-team' => [
			'label' => 'Quiz Bowl Team Buzzer Events',
			'color' => '#4f8cff',
		],
		'pub-trivia-team' => [
			'label' => 'Pub Trivia Style Team Events',
			'color' => '#ffd166',
		],
		'game-show' => [
			'label' => 'Game Show Style Events',
			'color' => '#ff4fa3',
		],
		'individual-quizzes' => [
			'label' => 'Individual Quizzes',
			'color' => '#35e69f',
		],
		'individual-bee' => [
			'label' => 'Individual Bee Format Competitions',
			'color' => '#ff8a3d',
		],
		'specialty-events' => [
			'label' => 'Specialty Events',
			'color' => '#c8d2ff',
		],
	];
}

function tn_tde_default_home_event_type_key() {
	return 'specialty-events';
}

function tn_tde_default_schedule_event_type_key() {
	return 'none';
}

function tn_tde_clean_event_type_key( $type, $default = null ) {
	$types = tn_tde_home_event_types();
	$default = $default === null ? tn_tde_default_schedule_event_type_key() : $default;
	$type = sanitize_key( $type );
	return isset( $types[ $type ] ) ? $type : $default;
}

function tn_tde_event_type_definition( $type ) {
	$types = tn_tde_home_event_types();
	$type = tn_tde_clean_event_type_key( $type );
	return $types[ $type ] ?? $types[ tn_tde_default_schedule_event_type_key() ];
}

function tn_tde_guess_home_event_type( $title ) {
	$title = strtolower( (string) $title );
	if ( strpos( $title, 'jeopardy' ) !== false ) return 'jeopardy-buzzer';
	if ( strpos( $title, 'learnedleague' ) !== false || strpos( $title, 'connections' ) !== false || strpos( $title, 'quip' ) !== false || strpos( $title, 'iqa' ) !== false ) return 'specialty-quiz-league';
	if ( strpos( $title, 'quiz bowl' ) !== false ) return 'quiz-bowl-team';
	if ( strpos( $title, 'pub quiz' ) !== false || strpos( $title, 'team trivia' ) !== false || strpos( $title, 'muffy' ) !== false || strpos( $title, 'two for one' ) !== false || strpos( $title, 'group think' ) !== false || strpos( $title, 'music quiz' ) !== false || strpos( $title, 'smartypants' ) !== false ) return 'pub-trivia-team';
	if ( strpos( $title, 'game show' ) !== false || strpos( $title, 'lingo' ) !== false ) return 'game-show';
	if ( strpos( $title, 'bee' ) !== false ) return 'individual-bee';
	if ( strpos( $title, '5 x 5' ) !== false || strpos( $title, 'individual' ) !== false ) return 'individual-quizzes';
	return tn_tde_default_home_event_type_key();
}

function tn_tde_clean_home_event_list( $items ) {
	if ( ! is_array( $items ) ) return [];
	$types = tn_tde_home_event_types();
	$default_type = tn_tde_default_home_event_type_key();
	return array_values( array_filter( array_map( function( $item ) use ( $types, $default_type ) {
		$title = sanitize_text_field( is_array( $item ) ? ( $item['title'] ?? '' ) : $item );
		$type = sanitize_key( is_array( $item ) ? ( $item['type'] ?? tn_tde_guess_home_event_type( $title ) ) : tn_tde_guess_home_event_type( $title ) );
		if ( ! isset( $types[ $type ] ) ) $type = $default_type;
		if ( $title === '' ) return null;
		return [
			'title' => $title,
			'type'  => $type,
		];
	}, $items ) ) );
}

function tn_tde_default_home_event_list() {
	$titles = [];
	foreach ( tn_tde_get_home_schedule_events() as $event ) {
		$title = sanitize_text_field( $event['title'] ?? '' );
		if ( $title && ! in_array( $title, $titles, true ) ) {
			$titles[] = [
				'title' => $title,
				'type'  => tn_tde_guess_home_event_type( $title ),
			];
		}
	}
	return $titles;
}

function tn_tde_get_home_event_list() {
	$saved = get_option( 'tn_home_event_list', null );
	$clean = tn_tde_clean_home_event_list( is_array( $saved ) ? $saved : [] );
	return $clean ?: tn_tde_default_home_event_list();
}

function tn_tde_ensure_jeopardy_page() {
	$page_id = absint( get_option( 'tn_jeopardy_page_id', 0 ) );
	if ( $page_id && get_post( $page_id ) ) return $page_id;

	$page = get_page_by_path( 'jeopardy-at-trivia-nationals' );
	if ( $page ) {
		update_option( 'tn_jeopardy_page_id', $page->ID, false );
		return $page->ID;
	}

	if ( ! current_user_can( 'edit_pages' ) ) return 0;

	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Jeopardy at Trivia Nationals',
		'post_name'    => 'jeopardy-at-trivia-nationals',
		'post_content' => '<p>Jeopardy staff will be onsite throughout Trivia Nationals weekend. Add audition details, meet-and-greet information, schedule notes, and anything attendees should know before they arrive.</p>',
	] );

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_option( 'tn_jeopardy_page_id', $page_id, false );
		return $page_id;
	}

	return 0;
}

add_action( 'admin_init', function () {
	if ( current_user_can( 'edit_pages' ) ) tn_tde_ensure_jeopardy_page();
} );

function tn_tde_get_jeopardy_page_id() {
	$page_id = absint( get_option( 'tn_jeopardy_page_id', 0 ) );
	return $page_id && get_post( $page_id ) ? $page_id : tn_tde_ensure_jeopardy_page();
}

function tn_tde_ensure_how_it_works_page() {
	$page_id = absint( get_option( 'tn_how_it_works_page_id', 0 ) );
	if ( $page_id && get_post( $page_id ) ) return $page_id;

	$page = get_page_by_path( 'how-it-works-trivia-nationals' );
	if ( $page ) {
		update_option( 'tn_how_it_works_page_id', $page->ID, false );
		return $page->ID;
	}

	if ( ! current_user_can( 'edit_pages' ) ) return 0;

	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'How It Works',
		'post_name'    => 'how-it-works-trivia-nationals',
		'post_content' => '<p>Add an overview of registration, event selection, team formation, and what first-time attendees should expect at Trivia Nationals.</p>',
	] );

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_option( 'tn_how_it_works_page_id', $page_id, false );
		return $page_id;
	}

	return 0;
}

add_action( 'admin_init', function () {
	if ( current_user_can( 'edit_pages' ) ) tn_tde_ensure_how_it_works_page();
} );

function tn_tde_get_how_it_works_page_id() {
	$page_id = absint( get_option( 'tn_how_it_works_page_id', 0 ) );
	return $page_id && get_post( $page_id ) ? $page_id : tn_tde_ensure_how_it_works_page();
}

function tn_tde_render_page_body_content( $page ) {
	if ( ! $page || empty( $page->post_content ) ) return '';

	if (
		class_exists( '\Elementor\Plugin' ) &&
		isset( \Elementor\Plugin::$instance->frontend ) &&
		get_post_meta( $page->ID, '_elementor_edit_mode', true )
	) {
		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $page->ID );
		return $content ? wp_kses_post( $content ) : '';
	}

	$content = $page->post_content;
	if ( function_exists( 'do_blocks' ) ) {
		$content = do_blocks( $content );
	}
	$content = do_shortcode( $content );
	if ( ! has_blocks( $page->post_content ) ) {
		$content = wpautop( $content );
	}

	return wp_kses_post( $content );
}

function tn_tde_default_homepage_quotes() {
	return [
		[
			'quote'  => 'Trivia Nationals is the rare weekend that feels competitive, welcoming, and completely joyful at the same time.',
			'credit' => 'Past attendee',
		],
		[
			'quote'  => 'I came for the quizzes and left with a calendar full of new friends.',
			'credit' => 'Past attendee',
		],
		[
			'quote'  => 'Every format had its own personality, and the whole weekend was run with so much care.',
			'credit' => 'Past attendee',
		],
	];
}

function tn_tde_clean_homepage_quotes( $quotes ) {
	if ( ! is_array( $quotes ) ) return [];
	$clean = [];
	foreach ( $quotes as $quote ) {
		$text   = sanitize_textarea_field( $quote['quote'] ?? '' );
		$credit = sanitize_text_field( $quote['credit'] ?? '' );
		if ( $text === '' && $credit === '' ) continue;
		$clean[] = [
			'quote'  => $text,
			'credit' => $credit ?: 'Past attendee',
		];
	}
	return $clean;
}

function tn_tde_get_homepage_quotes() {
	$saved = get_option( 'tn_homepage_quotes', null );
	$clean = tn_tde_clean_homepage_quotes( is_array( $saved ) ? $saved : [] );
	return $clean ?: tn_tde_default_homepage_quotes();
}

function tn_tde_default_homepage_faqs() {
	return [
		[
			'question' => 'What if I do not know anyone yet?',
			'answer'   => 'Come anyway. Trivia Nationals is built to be friendly for solo attendees, and many team events can help match individual players with groups.',
		],
		[
			'question' => 'Where is Trivia Nationals 2026?',
			'answer'   => 'Trivia Nationals 2026 is at South Point Hotel, Casino & Spa in Las Vegas.',
		],
		[
			'question' => 'Do I need to be a serious trivia player?',
			'answer'   => 'No. Some events are highly competitive, but the weekend also includes social games, puzzle events, and casual formats.',
		],
	];
}

function tn_tde_clean_homepage_faqs( $faqs ) {
	if ( ! is_array( $faqs ) ) return [];
	$clean = [];
	foreach ( $faqs as $faq ) {
		$question = sanitize_text_field( $faq['question'] ?? '' );
		$answer   = wp_kses_post( $faq['answer'] ?? '' );
		if ( $question === '' && trim( wp_strip_all_tags( $answer ) ) === '' ) continue;
		$clean[] = [
			'question' => $question,
			'answer'   => $answer,
		];
	}
	return $clean;
}

function tn_tde_get_homepage_faqs() {
	$saved = get_option( 'tn_homepage_faqs', null );
	$clean = tn_tde_clean_homepage_faqs( is_array( $saved ) ? $saved : [] );
	return $clean ?: tn_tde_default_homepage_faqs();
}

function tn_tde_homepage_section_definitions() {
	return [
		'hero' => [
			'label' => 'Hero',
			'selector' => '#hero',
			'nav' => '',
		],
		'countdown' => [
			'label' => 'Countdown',
			'selector' => '.countdown-section',
			'nav' => '',
		],
		'about' => [
			'label' => 'About',
			'selector' => '#about',
			'nav' => '#about',
		],
		'schedule' => [
			'label' => 'Event List',
			'selector' => '#schedule',
			'nav' => '#schedule',
		],
		'jeopardy' => [
			'label' => 'Jeopardy',
			'selector' => '#jeopardy',
			'nav' => '#jeopardy',
		],
		'how-it-works' => [
			'label' => 'How It Works',
			'selector' => '#how-it-works',
			'nav' => '#how-it-works',
		],
		'venue' => [
			'label' => 'Venue',
			'selector' => '#venue',
			'nav' => '#venue',
		],
		'quotes' => [
			'label' => 'Quotes',
			'selector' => '#quotes',
			'nav' => '#quotes',
		],
		'faq' => [
			'label' => 'FAQ',
			'selector' => '#faq-section',
			'nav' => '#faq-section',
		],
		'tickets' => [
			'label' => 'Tickets',
			'selector' => '#tickets',
			'nav' => '#tickets',
		],
		'gallery' => [
			'label' => 'Gallery',
			'selector' => '#gallery',
			'nav' => '#gallery',
		],
	];
}

function tn_tde_clean_homepage_sections( $items ) {
	$definitions = tn_tde_homepage_section_definitions();
	if ( ! is_array( $items ) ) return [];
	$clean = [];
	foreach ( $items as $item ) {
		$key = sanitize_key( is_array( $item ) ? ( $item['key'] ?? '' ) : $item );
		if ( ! $key || ! isset( $definitions[ $key ] ) ) continue;
		$clean[ $key ] = [
			'key' => $key,
			'visible' => ! is_array( $item ) || ! array_key_exists( 'visible', $item ) || filter_var( $item['visible'], FILTER_VALIDATE_BOOLEAN ),
		];
	}
	foreach ( array_keys( $definitions ) as $key ) {
		if ( ! isset( $clean[ $key ] ) ) {
			$clean[ $key ] = [
				'key' => $key,
				'visible' => true,
			];
		}
	}
	return array_values( $clean );
}

function tn_tde_get_homepage_sections() {
	$saved = get_option( 'tn_homepage_sections', null );
	return tn_tde_clean_homepage_sections( is_array( $saved ) ? $saved : [] );
}

function tn_tde_dynamic_event_request_slug() {
	$path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
	$path = trim( (string) $path, '/' );
	if ( ! preg_match( '#^event-info/([^/]+)/?$#', $path, $match ) ) return '';
	return sanitize_title( rawurldecode( $match[1] ) );
}

function tn_tde_get_event_by_detail_slug( $slug ) {
	$slug = sanitize_title( $slug );
	if ( ! $slug ) return null;
	foreach ( tn_tde_get_home_schedule_events() as $event ) {
		if ( tn_tde_event_detail_slug( $event ) === $slug ) return $event;
	}
	return null;
}

function tn_tde_render_dynamic_event_detail_page( $event ) {
	$schedule_mode = get_option( 'tn_schedule_mode', 'off' ) === 'on';
	$title = $event['title'] ?: 'Trivia Nationals Event';
	$description = $event['description'] ?: '<p>Details for this Trivia Nationals event are coming soon.</p>';
	$presenter_names = array_values( array_filter( array_map( function( $presenter ) {
		return sanitize_text_field( $presenter['name'] ?? '' );
	}, $event['presenters'] ?? [] ) ) );
	$presenter_label = $presenter_names ? implode( ', ', $presenter_names ) : 'To be announced';
	$time_label = $schedule_mode ? tn_tde_time_label( $event ) : 'Schedule coming soon';
	global $wp_query;
	if ( $wp_query ) {
		$wp_query->is_404 = false;
		$wp_query->is_page = true;
	}
	add_filter( 'pre_get_document_title', function() use ( $title ) {
		return $title . ' - Trivia Nationals';
	}, 99 );
	status_header( 200 );
	nocache_headers();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
		<style>
			.tn-dynamic-event-detail {
				margin: 0;
				background: #070812;
				color: #f7f8ff;
				font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			}
			.tn-dynamic-event-detail.admin-bar {
				padding-top: 32px;
			}
			.tn-dynamic-event-page {
				--tn-bg: #070812;
				--tn-panel: #111525;
				--tn-text: #f7f8ff;
				--tn-muted: #cdd4ea;
				--tn-cyan: #00e5ff;
				--tn-pink: #ff2d95;
				--tn-gold: #ffd166;
				--tn-line: rgba(255,255,255,0.12);
				--event-accent: #00e5ff;
				min-height: 100vh;
				overflow-x: hidden;
				background:
					radial-gradient(circle at 18% 8%, rgba(0,229,255,0.16), transparent 28rem),
					radial-gradient(circle at 82% 12%, rgba(255,45,149,0.15), transparent 30rem),
					linear-gradient(180deg, rgba(7,8,18,0.28), var(--tn-bg) 72%),
					linear-gradient(135deg, rgba(0,229,255,0.13), transparent 38%, rgba(255,45,149,0.12));
			}
			.tn-dynamic-event-nav {
				position: relative;
				top: 0;
				z-index: 20;
				border-bottom: 1px solid var(--tn-line);
				background: rgba(7,8,18,0.92);
				backdrop-filter: blur(14px);
				box-shadow: 0 16px 36px rgba(0,0,0,0.24);
				padding: 0.35rem 0;
			}
			.tn-dynamic-event-nav * {
				box-sizing: border-box;
			}
			.admin-bar .tn-dynamic-event-nav { top: 0; }
			.tn-dynamic-event-nav-inner,
			.tn-dynamic-event-hero,
			.tn-dynamic-event-main {
				width: min(1160px, calc(100% - 2rem));
				margin: 0 auto;
			}
			.tn-dynamic-event-nav-inner {
				display: grid;
				grid-template-columns: max-content minmax(0, 1fr);
				align-items: center;
				justify-content: space-between;
				gap: clamp(1rem, 3vw, 2rem);
				min-height: 76px;
				padding: 0.9rem 0;
			}
			.tn-dynamic-event-nav a {
				display: inline-flex;
				align-items: center;
				color: var(--tn-muted);
				font-family: Outfit, Inter, sans-serif;
				font-size: 0.82rem;
				font-weight: 900;
				letter-spacing: 0.08em;
				line-height: 1.15;
				text-decoration: none;
				text-transform: uppercase;
			}
			.tn-dynamic-event-nav a:hover { color: var(--event-accent); }
			.tn-dynamic-event-brand {
				position: static !important;
				top: auto !important;
				float: none !important;
				width: auto !important;
				height: auto !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				color: var(--tn-text) !important;
				font-size: 0.95rem !important;
				letter-spacing: 0.08em !important;
				line-height: 1.15 !important;
				white-space: nowrap;
			}
			.tn-dynamic-event-links {
				position: static !important;
				top: auto !important;
				float: none !important;
				display: flex !important;
				align-items: center;
				justify-content: flex-end;
				gap: clamp(1rem, 2vw, 1.5rem);
				flex-wrap: wrap;
				width: auto !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				min-width: 0;
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				line-height: 1.15 !important;
				box-shadow: none !important;
				border: 0 !important;
				z-index: auto !important;
			}
			.tn-dynamic-event-links a {
				position: static !important;
				display: inline-flex !important;
				align-items: center !important;
				width: auto !important;
				height: auto !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				line-height: 1.15 !important;
				box-shadow: none !important;
				border: 0 !important;
				white-space: nowrap;
			}
			.tn-dynamic-event-hero {
				display: grid;
				grid-template-columns: minmax(0, 1.25fr) minmax(280px, 0.75fr);
				gap: clamp(1.2rem, 3vw, 2.4rem);
				align-items: center;
				min-height: 0;
				padding: clamp(2rem, 5vw, 4.2rem) 0 clamp(1rem, 2.8vw, 2rem);
			}
			.tn-dynamic-event-kicker {
				display: inline-flex;
				width: fit-content;
				margin: 0 0 1.15rem;
				padding: 0.38rem 0.65rem;
				border: 1px solid rgba(0,229,255,0.42);
				border-radius: 999px;
				background: rgba(0,229,255,0.12);
				color: var(--event-accent);
				font-family: Outfit, Inter, sans-serif;
				font-size: 0.72rem;
				font-weight: 800;
				letter-spacing: 0.09em;
				text-transform: uppercase;
			}
			.tn-dynamic-event-title {
				margin: 0;
				color: var(--tn-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(3.4rem, 8vw, 7rem);
				font-weight: 900;
				letter-spacing: 0;
				line-height: 0.86;
				text-transform: uppercase;
			}
			.tn-dynamic-event-buttons { display: flex; gap: 0.8rem; flex-wrap: wrap; margin-top: 1.4rem; }
			.tn-dynamic-event-button {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-height: 44px;
				padding: 0.78rem 1.1rem;
				border-radius: 999px;
				font-family: Outfit, Inter, sans-serif;
				font-size: 0.78rem;
				font-weight: 900;
				letter-spacing: 0.08em;
				text-decoration: none;
				text-transform: uppercase;
			}
			.tn-dynamic-event-button.is-primary {
				background: linear-gradient(135deg, var(--event-accent), var(--tn-pink));
				color: #fff;
			}
			.tn-dynamic-event-button.is-secondary {
				border: 1px solid var(--tn-line);
				background: rgba(255,255,255,0.06);
				color: var(--tn-text);
			}
			.tn-dynamic-event-panel,
			.tn-dynamic-event-content,
			.tn-dynamic-event-card {
				border: 1px solid var(--tn-line);
				border-radius: 8px;
				background: rgba(17,21,37,0.78);
				box-shadow: 0 24px 80px rgba(0,0,0,0.28);
			}
			.tn-dynamic-event-panel { overflow: hidden; }
			.tn-dynamic-event-image {
				display: block;
				width: 100%;
				aspect-ratio: 16 / 10;
				object-fit: cover;
				background: rgba(255,255,255,0.06);
			}
			.tn-dynamic-event-facts {
				display: grid;
				gap: 0;
				margin: 0;
				padding: 0.25rem 1.1rem 1.1rem;
			}
			.tn-dynamic-event-fact {
				display: grid;
				grid-template-columns: 6.2rem 1fr;
				gap: 1rem;
				padding: 0.85rem 0;
				border-top: 1px solid var(--tn-line);
			}
			.tn-dynamic-event-fact:first-child { border-top: 0; }
			.tn-dynamic-event-fact dt {
				color: var(--event-accent);
				font-family: Outfit, Inter, sans-serif;
				font-size: 0.68rem;
				font-weight: 900;
				letter-spacing: 0.09em;
				text-transform: uppercase;
			}
			.tn-dynamic-event-fact dd {
				margin: 0;
				color: var(--tn-text);
				font-weight: 800;
				line-height: 1.35;
			}
			.tn-dynamic-event-main {
				display: grid;
				grid-template-columns: minmax(0, 1.35fr) minmax(260px, 0.65fr);
				gap: clamp(1rem, 2.4vw, 1.8rem);
				align-items: start;
				padding: 0 0 clamp(2rem, 4vw, 3.5rem);
			}
			.tn-dynamic-event-content,
			.tn-dynamic-event-card {
				min-width: 0;
				overflow: visible;
				padding: clamp(1.2rem, 2.4vw, 1.85rem);
			}
			.tn-dynamic-event-content h2,
			.tn-dynamic-event-card h2 {
				margin: 0 0 0.75rem;
				color: var(--event-accent);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(1.6rem, 3vw, 2.5rem);
				font-weight: 900;
				line-height: 0.95;
				text-transform: uppercase;
			}
			.tn-dynamic-event-content p,
			.tn-dynamic-event-content li,
			.tn-dynamic-event-card p {
				color: #dfe4f5;
				font-size: 1rem;
				line-height: 1.7;
			}
			.tn-dynamic-event-signup {
				margin-top: 1rem;
				border: 1px solid var(--tn-line);
				border-radius: 8px;
				background: rgba(17,21,37,0.78);
				box-shadow: 0 24px 80px rgba(0,0,0,0.28);
				padding: clamp(1.2rem, 2.4vw, 1.85rem);
			}
			.tn-dynamic-event-signup h2 {
				margin: 0 0 0.75rem;
				color: var(--event-accent);
				font-family: Outfit, Inter, sans-serif;
				font-size: clamp(1.6rem, 3vw, 2.5rem);
				font-weight: 900;
				line-height: 0.95;
				text-transform: uppercase;
			}
			.tn-signup-note {
				display: grid;
				gap: 0.35rem;
				margin: 0 0 1.2rem;
				padding: 0.95rem 1rem;
				border: 1px solid rgba(0,230,255,0.28);
				border-radius: 8px;
				background:
					linear-gradient(135deg, rgba(0,230,255,0.09), rgba(255,62,165,0.045)),
					rgba(17,21,37,0.78);
				color: var(--tn-muted);
				line-height: 1.55;
			}
			.tn-signup-note p { margin: 0; }
			.tn-signup-note strong { color: var(--tn-text); }
			.tn-dynamic-event-signup-message { margin: 0 0 0.85rem; }
			.tn-dynamic-event-signup-message.is-success { color: #35e69f; }
			.tn-dynamic-event-signup-message.is-error { color: #ff8a8a; }
			.tn-dynamic-event-signup-form {
				display: grid;
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 0.8rem;
			}
			.tn-dynamic-event-signup-form p { margin: 0; }
			.tn-dynamic-event-signup-form .is-full { grid-column: 1 / -1; }
			.tn-dynamic-event-signup-form label {
				display: block;
				margin-bottom: 0.25rem;
				color: var(--tn-muted);
				font-weight: 800;
			}
			.tn-dynamic-event-signup-form input,
			.tn-dynamic-event-signup-form select,
			.tn-dynamic-event-signup-form textarea {
				width: 100%;
				border: 1px solid rgba(255,255,255,0.14);
				border-radius: 8px;
				background: rgba(7,8,18,0.72);
				color: var(--tn-text);
				padding: 0.72rem 0.85rem;
			}
			.tn-dynamic-event-signup-form button {
				border: 0;
				border-radius: 999px;
				background: var(--tn-gold);
				color: #071019;
				cursor: pointer;
				font-family: Outfit, Inter, sans-serif;
				font-weight: 900;
				letter-spacing: 0.08em;
				padding: 0.78rem 1.1rem;
				text-transform: uppercase;
			}
			.tn-signup-trap {
				position: absolute;
				left: -9999px;
			}
			.tn-dynamic-presenter-list {
				display: grid;
				gap: 0.85rem;
				margin: 0;
				padding: 0;
				list-style: none;
			}
			.tn-dynamic-presenter-list li {
				display: grid;
				grid-template-columns: 56px 1fr;
				gap: 0.75rem;
				align-items: start;
				min-width: 0;
			}
			.tn-dynamic-presenter-list img {
				width: 56px;
				height: 56px;
				border-radius: 8px;
				object-fit: cover;
			}
			.tn-dynamic-presenter-list strong {
				display: block;
				color: var(--tn-text);
				font-family: Outfit, Inter, sans-serif;
				font-size: 1rem;
				font-weight: 900;
				overflow-wrap: anywhere;
			}
			.tn-dynamic-presenter-body,
			.tn-dynamic-presenter-bio {
				display: block;
				margin-top: 0.1rem;
				color: var(--tn-muted);
				font-size: 0.86rem;
				line-height: 1.45;
				min-width: 0;
				overflow-wrap: anywhere;
				white-space: normal;
			}
			.tn-dynamic-presenter-bio p {
				margin: 0.25rem 0 0;
			}
			.tn-dynamic-presenter-bio ul,
			.tn-dynamic-presenter-bio ol {
				margin: 0.35rem 0 0 1.1rem;
				padding: 0;
			}
			@media (max-width: 800px) {
				.tn-dynamic-event-detail.admin-bar { padding-top: 46px; }
				.admin-bar .tn-dynamic-event-nav { top: 0; }
				.tn-dynamic-event-nav-inner { grid-template-columns: 1fr; align-items: flex-start; min-height: 0; padding: 0.85rem 0; }
				.tn-dynamic-event-links { justify-content: flex-start; }
				.tn-dynamic-event-hero,
				.tn-dynamic-event-main { grid-template-columns: 1fr; }
				.tn-dynamic-event-signup-form { grid-template-columns: 1fr; }
				.tn-dynamic-event-hero { min-height: auto; padding-top: 2rem; }
				.tn-dynamic-event-title { font-size: clamp(3rem, 15vw, 5.8rem); }
			}
		</style>
	</head>
	<body <?php body_class( 'tn-dynamic-event-detail' ); ?>>
	<?php wp_body_open(); ?>
	<div class="tn-dynamic-event-page">
		<header class="tn-dynamic-event-nav">
			<div class="tn-dynamic-event-nav-inner">
				<a class="tn-dynamic-event-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Trivia Nationals 2026</a>
				<nav class="tn-dynamic-event-links" aria-label="Event navigation">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<a href="<?php echo esc_url( home_url( '/full-schedule/' ) ); ?>">Full Schedule</a>
					<a href="<?php echo esc_url( home_url( '/#tickets' ) ); ?>">Tickets</a>
				</nav>
			</div>
		</header>
		<section class="tn-dynamic-event-hero">
			<div>
				<p class="tn-dynamic-event-kicker"><?php echo esc_html( $event['event_type_label'] ?: ( $event['category'] ?: 'Event' ) ); ?> / <?php echo esc_html( $event['day_label'] . ', ' . $event['date_label'] ); ?></p>
				<h1 class="tn-dynamic-event-title"><?php echo esc_html( $title ); ?></h1>
				<div class="tn-dynamic-event-buttons">
					<a class="tn-dynamic-event-button is-primary" href="<?php echo esc_url( home_url( '/#tickets' ) ); ?>">Get Tickets</a>
					<a class="tn-dynamic-event-button is-secondary" href="<?php echo esc_url( home_url( '/full-schedule/' ) ); ?>">Back to Schedule</a>
				</div>
			</div>
			<aside class="tn-dynamic-event-panel" aria-label="Event details">
				<?php if ( ! empty( $event['image'] ) ) : ?>
					<img class="tn-dynamic-event-image" src="<?php echo esc_url( $event['image'] ); ?>" alt="<?php echo esc_attr( $event['image_alt'] ?: $title ); ?>">
				<?php endif; ?>
				<dl class="tn-dynamic-event-facts">
					<div class="tn-dynamic-event-fact"><dt>Day</dt><dd><?php echo esc_html( $event['day_label'] . ', ' . $event['date_label'] ); ?></dd></div>
					<div class="tn-dynamic-event-fact"><dt>Time</dt><dd><?php echo esc_html( $time_label ); ?></dd></div>
					<div class="tn-dynamic-event-fact"><dt>Location</dt><dd><?php echo esc_html( $event['location_label'] ?: 'Location TBA' ); ?></dd></div>
					<div class="tn-dynamic-event-fact"><dt>Type</dt><dd><?php echo esc_html( $event['category'] ?: 'Event' ); ?></dd></div>
					<div class="tn-dynamic-event-fact"><dt>Hosts</dt><dd><?php echo esc_html( $presenter_label ); ?></dd></div>
				</dl>
			</aside>
		</section>
		<main class="tn-dynamic-event-main">
			<article class="tn-dynamic-event-content">
				<h2>About This Event</h2>
				<?php echo tn_tde_render_description_html( $description ); ?>
				<?php echo tn_tde_render_event_signup_form( $event ); ?>
			</article>
			<aside class="tn-dynamic-event-card">
				<h2>Presented By</h2>
				<?php if ( ! empty( $event['presenters'] ) ) : ?>
					<ul class="tn-dynamic-presenter-list">
						<?php foreach ( $event['presenters'] as $presenter ) : ?>
							<li>
								<?php if ( ! empty( $presenter['photo'] ) ) : ?>
									<img src="<?php echo esc_url( $presenter['photo'] ); ?>" alt="">
								<?php else : ?>
									<span aria-hidden="true"></span>
								<?php endif; ?>
								<div class="tn-dynamic-presenter-body">
									<strong><?php echo esc_html( $presenter['name'] ?: 'Presenter TBA' ); ?></strong>
									<?php if ( ! empty( $presenter['bio'] ) ) : ?>
										<div class="tn-dynamic-presenter-bio"><?php echo wpautop( wp_kses_post( $presenter['bio'] ) ); ?></div>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p>Presenter details will be announced soon.</p>
				<?php endif; ?>
			</aside>
		</main>
	</div>
	<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
}

add_action( 'template_redirect', function() {
	if ( is_admin() ) return;
	$slug = tn_tde_dynamic_event_request_slug();
	if ( ! $slug ) return;
	$event = tn_tde_get_event_by_detail_slug( $slug );
	if ( ! $event ) {
		status_header( 404 );
		nocache_headers();
		wp_die( esc_html__( 'Event not found.', 'trivia-desc-editor' ), esc_html__( 'Event not found', 'trivia-desc-editor' ), [ 'response' => 404 ] );
	}
	tn_tde_render_dynamic_event_detail_page( $event );
}, 0 );

function tn_tde_day_label_from_item( $item ) {
	$labels = [
		'day-friday'   => 'Friday, August 7',
		'day-saturday' => 'Saturday, August 8',
		'day-sunday'   => 'Sunday, August 9',
	];
	for ( $node = $item; $node; $node = $node->parentNode ) {
		if ( ! method_exists( $node, 'getAttribute' ) ) continue;
		$id = $node->getAttribute( 'id' );
		if ( isset( $labels[ $id ] ) ) return $labels[ $id ];
	}
	return '';
}

function tn_tde_get_event_data_for_current_page() {
	if ( ! is_singular( 'page' ) ) return null;
	$current_urls = array_filter( [
		tn_tde_normalize_internal_url( get_permalink() ),
		tn_tde_normalize_internal_url( home_url( '/' . get_post_field( 'post_name', get_queried_object_id() ) . '/' ) ),
	] );
	if ( empty( $current_urls ) ) return null;

	$raw = get_post_meta( 5, '_elementor_data', true );
	if ( ! $raw ) return null;
	$data = json_decode( $raw, true );
	if ( ! $data ) return null;
	$html = tn_tde_find_home_schedule_html( is_array( $data ) ? $data : [ $data ] );
	if ( ! $html ) return null;
	if ( ! class_exists( 'DOMDocument' ) ) return null;

	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	$xpath = new DOMXPath( $dom );
	foreach ( $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " schedule-item ")]' ) as $item ) {
		$info_url = $item->getAttribute( 'data-info-url' );
		if ( ! $info_url || ! in_array( tn_tde_normalize_internal_url( $info_url ), $current_urls, true ) ) continue;
		$json = html_entity_decode( $item->getAttribute( 'data-presenters' ), ENT_QUOTES, 'UTF-8' );
		$presenters = json_decode( $json, true );
		$start = sanitize_text_field( $item->getAttribute( 'data-start' ) );
		$end   = sanitize_text_field( $item->getAttribute( 'data-end' ) );
		$time  = $start && $end ? $start . ' - ' . $end : ( $start ?: 'Time TBA' );
		$event_type = tn_tde_clean_event_type_key( $item->getAttribute( 'data-event-type' ) );
		$event_type_definition = tn_tde_event_type_definition( $event_type );
		return [
			'title'      => sanitize_text_field( $item->getAttribute( 'data-title' ) ),
			'day'        => tn_tde_day_label_from_item( $item ) ?: 'Day TBA',
			'time'       => $time,
			'has_time'   => (bool) $start,
			'location'   => tn_tde_location_label( $item->getAttribute( 'data-location' ) ),
			'has_location' => (bool) tn_tde_normalize_location( $item->getAttribute( 'data-location' ) ),
			'category'   => sanitize_text_field( $item->getAttribute( 'data-tag-label' ) ) ?: 'Event',
			'event_type' => $event_type,
			'event_type_label' => $event_type === tn_tde_default_schedule_event_type_key() ? '' : sanitize_text_field( $event_type_definition['label'] ?? '' ),
			'event_type_color' => sanitize_hex_color( $event_type_definition['color'] ?? '' ) ?: '',
			'presenters' => tn_tde_clean_presenters( $presenters ),
		];
	}
	return null;
}

function tn_tde_get_presenters_for_current_page() {
	$event = tn_tde_get_event_data_for_current_page();
	return $event ? $event['presenters'] : [];
}

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	$event = tn_tde_get_event_data_for_current_page();
	if ( empty( $event ) ) return;
	$schedule_mode = get_option( 'tn_schedule_mode', 'off' ) === 'on';
	$presenter_names = array_values( array_filter( array_map( function( $presenter ) {
		return sanitize_text_field( $presenter['name'] ?? '' );
	}, $event['presenters'] ?? [] ) ) );
	$presenter_label = $presenter_names ? implode( ', ', $presenter_names ) : 'To be announced';
	$facts = [
		'title'     => $event['title'] ?: get_the_title(),
		'day'       => $event['day'] ?: 'Day TBA',
		'time'      => $schedule_mode && ! empty( $event['has_time'] ) ? ( $event['time'] ?: 'Time TBA' ) : '',
		'location'  => ! empty( $event['has_location'] ) ? ( $event['location'] ?: 'Location TBA' ) : '',
		'category'  => $event['event_type_label'] ?: ( $event['category'] ?: 'Event' ),
		'presenter' => $presenter_label,
	];
	?>
	<script>
	(function(){
		var facts = <?php echo wp_json_encode( $facts ); ?>;
		function esc(value) {
			return String(value || '').replace(/[&<>"']/g, function(ch) {
				return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[ch];
			});
		}
		function insertFactsPanel() {
			if (document.querySelector('.tn-event-facts-panel')) return;
			var hero = document.querySelector('.tn-event-hero-section > .elementor-container, .tn-event-hero-section > .e-con-inner');
			if (!hero) return;
			hero.classList.add('tn-has-event-facts');
			document.querySelectorAll('.tn-event-hero-visual').forEach(function(col) {
				if (col.querySelector('.tn-court-widget, .tn-hero-panel, .tn-court') || !col.textContent.trim()) {
					col.remove();
				}
			});
			var panel = document.createElement('aside');
			panel.className = 'tn-event-facts-panel';
			panel.setAttribute('aria-label', 'Event details');
			panel.innerHTML =
				'<p class="tn-event-facts-label">Event details</p>' +
				'<h2 class="tn-event-facts-title">' + esc(facts.title) + '</h2>' +
				'<dl class="tn-event-facts-list">' +
					'<div class="tn-event-fact"><dt>Day</dt><dd>' + esc(facts.day) + '</dd></div>' +
					(facts.time ? '<div class="tn-event-fact"><dt>Time</dt><dd>' + esc(facts.time) + '</dd></div>' : '') +
					(facts.location ? '<div class="tn-event-fact"><dt>Location</dt><dd>' + esc(facts.location) + '</dd></div>' : '') +
					'<div class="tn-event-fact"><dt>Type</dt><dd>' + esc(facts.category) + '</dd></div>' +
					'<div class="tn-event-fact"><dt>Hosts</dt><dd>' + esc(facts.presenter) + '</dd></div>' +
				'</dl>';
			hero.appendChild(panel);
		}
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', insertFactsPanel);
		else insertFactsPanel();
	})();
	</script>
	<?php
}, 7 );

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	$presenters = tn_tde_get_presenters_for_current_page();
	if ( empty( $presenters ) ) return;
	?>
	<section class="tn-presented-section" aria-label="Presented by">
		<div class="tn-presented-inner">
			<p class="tn-presented-kicker">Presented by</p>
			<div class="tn-presented-grid">
				<?php foreach ( $presenters as $presenter ) : ?>
					<article class="tn-presenter-card">
						<?php if ( ! empty( $presenter['photo'] ) ) : ?>
							<img class="tn-presenter-photo" src="<?php echo esc_url( $presenter['photo'] ); ?>" alt="<?php echo esc_attr( $presenter['name'] ?: 'Presenter' ); ?>">
						<?php endif; ?>
						<div>
							<?php if ( ! empty( $presenter['name'] ) ) : ?>
								<h2 class="tn-presenter-name"><?php echo esc_html( $presenter['name'] ); ?></h2>
							<?php endif; ?>
							<?php if ( ! empty( $presenter['bio'] ) ) : ?>
								<div class="tn-presenter-bio"><?php echo wpautop( $presenter['bio'] ); ?></div>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}, 8 );

// ─── Shortcode: Full schedule grid ──────────────────────────────────────────

function tn_tde_time_label( $event ) {
	$start = trim( (string) ( $event['start'] ?? '' ) );
	$end = trim( (string) ( $event['end'] ?? '' ) );
	if ( $start && $end ) return $start . ' - ' . $end;
	return $start ?: 'Time TBA';
}

function tn_tde_minutes_label( $minutes ) {
	$minutes = max( 0, min( 1439, (int) $minutes ) );
	$hours = (int) floor( $minutes / 60 );
	$mins = $minutes % 60;
	$meridian = $hours >= 12 ? 'PM' : 'AM';
	$hour_12 = $hours % 12;
	if ( $hour_12 === 0 ) $hour_12 = 12;
	return sprintf( '%d:%02d %s', $hour_12, $mins, $meridian );
}

function tn_tde_schedule_overlap_layouts( $events ) {
	$layouts = [];
	$events_by_location = [];

	foreach ( $events as $index => $event ) {
		$location_key = $event['location'] ?: 'breakout-rooms';
		if ( ! isset( $events_by_location[ $location_key ] ) ) {
			$events_by_location[ $location_key ] = [];
		}
		$events_by_location[ $location_key ][] = [
			'index' => $index,
			'start' => (int) $event['start_minutes'],
			'end' => (int) ( $event['end_minutes'] ?? ( $event['start_minutes'] + 60 ) ),
		];
		$layouts[ $index ] = [
			'index' => 0,
			'count' => 1,
		];
	}

	foreach ( $events_by_location as $location_events ) {
		usort( $location_events, function( $a, $b ) {
			if ( $a['start'] !== $b['start'] ) return $a['start'] <=> $b['start'];
			if ( $a['end'] !== $b['end'] ) return $a['end'] <=> $b['end'];
			return $a['index'] <=> $b['index'];
		} );

		$active = [];
		$cluster_indices = [];
		$cluster_max = 1;
		$finish_cluster = function() use ( &$layouts, &$cluster_indices, &$cluster_max ) {
			foreach ( $cluster_indices as $event_index ) {
				$layouts[ $event_index ]['count'] = max( 1, $cluster_max );
			}
			$cluster_indices = [];
			$cluster_max = 1;
		};

		foreach ( $location_events as $event ) {
			$active = array_values( array_filter( $active, function( $active_event ) use ( $event ) {
				return $active_event['end'] > $event['start'];
			} ) );

			if ( empty( $active ) && ! empty( $cluster_indices ) ) {
				$finish_cluster();
			}

			$used_columns = array_fill_keys( array_map( function( $active_event ) {
				return $active_event['column'];
			}, $active ), true );
			$column = 0;
			while ( isset( $used_columns[ $column ] ) ) {
				$column++;
			}

			$layouts[ $event['index'] ]['index'] = $column;
			$active[] = [
				'end' => $event['end'],
				'column' => $column,
			];
			$cluster_indices[] = $event['index'];
			$cluster_max = max( $cluster_max, count( $active ) );
		}

		if ( ! empty( $cluster_indices ) ) {
			$finish_cluster();
		}
	}

	return $layouts;
}

function tn_tde_render_full_schedule_shortcode() {
	$events = tn_tde_get_home_schedule_events();
	$days = tn_tde_day_definitions();
	$locations = tn_tde_location_options();
	if ( empty( $events ) ) {
		return '<p class="tn-full-schedule-empty">No schedule events are available yet.</p>';
	}

	$events_by_day = [];
	foreach ( $events as $event ) {
		$day = $event['day_id'];
		if ( ! isset( $events_by_day[ $day ] ) ) {
			$events_by_day[ $day ] = [
				'timed' => [],
				'after_hours' => [],
				'unscheduled' => [],
				'min' => null,
				'max' => null,
			];
		}
		if ( ! empty( $event['after_hours'] ) ) {
			$events_by_day[ $day ]['after_hours'][] = $event;
			continue;
		}
		if ( $event['start_minutes'] === null ) {
			$events_by_day[ $day ]['unscheduled'][] = $event;
			continue;
		}
		$end_minutes = tn_tde_parse_start_minutes( $event['end'] ?? '' );
		if ( $end_minutes !== null && $end_minutes <= $event['start_minutes'] ) {
			$end_minutes += 24 * 60;
		}
		if ( $end_minutes === null ) {
			$end_minutes = $event['start_minutes'] + 60;
		}
		$event['end_minutes'] = $end_minutes;
		$events_by_day[ $day ]['timed'][] = $event;
		$events_by_day[ $day ]['min'] = $events_by_day[ $day ]['min'] === null ? $event['start_minutes'] : min( $events_by_day[ $day ]['min'], $event['start_minutes'] );
		$events_by_day[ $day ]['max'] = $events_by_day[ $day ]['max'] === null ? $end_minutes : max( $events_by_day[ $day ]['max'], $end_minutes );
	}

	$event_style = function( $event, $extra = '' ) {
		$style = $extra;
		if ( ! empty( $event['event_type_color'] ) ) {
			$style .= ( $style ? ' ' : '' ) . '--tn-schedule-event-color: ' . sanitize_hex_color( $event['event_type_color'] ) . ';';
		}
		return trim( $style );
	};
	$event_type_label = function( $event ) {
		return ! empty( $event['event_type_label'] ) ? $event['event_type_label'] : ( $event['category'] ?? 'Event' );
	};

	ob_start();
	?>
	<div class="tn-full-schedule" data-tn-full-schedule>
		<div class="tn-full-schedule-nav">
			<a class="tn-full-schedule-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Trivia Nationals 2026</a>
			<nav aria-label="Schedule page navigation">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<a href="<?php echo esc_url( home_url( '/#schedule' ) ); ?>">Schedule</a>
				<a href="<?php echo esc_url( home_url( '/#venue' ) ); ?>">Venue</a>
				<a href="<?php echo esc_url( home_url( '/#tickets' ) ); ?>">Tickets</a>
			</nav>
		</div>
		<div class="tn-full-schedule-head">
			<p class="tn-full-schedule-kicker">August 7 - 9, 2026 / Las Vegas</p>
			<h2>Full Schedule</h2>
			<div class="tn-full-schedule-tabs" role="tablist" aria-label="Schedule days">
				<?php $tab_index = 0; foreach ( $days as $day_id => $day ) : ?>
					<button type="button" class="tn-full-schedule-tab<?php echo $tab_index === 0 ? ' is-active' : ''; ?>" data-day="<?php echo esc_attr( $day_id ); ?>" role="tab" aria-selected="<?php echo $tab_index === 0 ? 'true' : 'false'; ?>">
						<span><?php echo esc_html( $day['label'] ); ?></span>
						<small><?php echo esc_html( $day['date'] ); ?></small>
					</button>
				<?php $tab_index++; endforeach; ?>
			</div>
			<label class="tn-full-schedule-mobile-mode">
				<input type="checkbox" data-tn-mobile-list-toggle>
				<span>Use streamlined mobile view</span>
			</label>
		</div>

		<?php $panel_index = 0; foreach ( $days as $day_id => $day ) : ?>
			<section class="tn-full-schedule-day<?php echo $panel_index === 0 ? ' is-active' : ''; ?>" data-day-panel="<?php echo esc_attr( $day_id ); ?>">
				<?php
				$day_events = $events_by_day[ $day_id ] ?? [ 'timed' => [], 'after_hours' => [], 'unscheduled' => [], 'min' => null, 'max' => null ];
				$day_start = $day_events['min'] === null ? 9 * 60 : max( 0, floor( $day_events['min'] / 30 ) * 30 );
				$day_end = $day_events['max'] === null ? 18 * 60 : min( 24 * 60, ceil( $day_events['max'] / 30 ) * 30 );
				if ( $day_end <= $day_start ) $day_end = $day_start + 60;
				$slot_count = max( 2, (int) ceil( ( $day_end - $day_start ) / 30 ) );
				$location_keys = array_keys( $locations );
				$timed_layouts = tn_tde_schedule_overlap_layouts( $day_events['timed'] );
				$after_hours_by_location = array_fill_keys( $location_keys, [] );
				foreach ( $day_events['after_hours'] as $event ) {
					$after_location_key = $event['location'] ?: 'breakout-rooms';
					if ( ! isset( $after_hours_by_location[ $after_location_key ] ) ) {
						$after_location_key = 'breakout-rooms';
					}
					$after_hours_by_location[ $after_location_key ][] = $event;
				}
				?>
				<div class="tn-full-schedule-timeline-wrap">
					<div class="tn-full-schedule-locations" style="--tn-location-count: <?php echo esc_attr( count( $locations ) ); ?>">
						<div class="tn-full-schedule-time-spacer"></div>
						<?php foreach ( $locations as $location_label ) : ?>
							<div class="tn-full-schedule-location-head"><?php echo esc_html( $location_label ); ?></div>
						<?php endforeach; ?>
					</div>
					<div class="tn-full-schedule-timeline" style="--tn-location-count: <?php echo esc_attr( count( $locations ) ); ?>; --tn-slot-count: <?php echo esc_attr( $slot_count ); ?>;">
						<?php for ( $slot = 0; $slot <= $slot_count; $slot += 2 ) : ?>
							<?php $minutes = $day_start + ( $slot * 30 ); ?>
							<div class="tn-full-schedule-time-marker" style="grid-row: <?php echo esc_attr( $slot + 1 ); ?>;">
								<?php echo esc_html( tn_tde_minutes_label( $minutes ) ); ?>
							</div>
						<?php endfor; ?>
						<?php $lane_index = 0; foreach ( $locations as $location_key => $location_label ) : ?>
							<div class="tn-full-schedule-lane-bg" style="grid-column: <?php echo esc_attr( $lane_index + 2 ); ?>; grid-row: 1 / span <?php echo esc_attr( $slot_count ); ?>;" aria-hidden="true"></div>
						<?php $lane_index++; endforeach; ?>
						<?php foreach ( $day_events['timed'] as $event_index => $event ) : ?>
							<?php
							$location_key = $event['location'] ?: 'breakout-rooms';
							$location_index = array_search( $location_key, $location_keys, true );
							if ( $location_index === false ) $location_index = count( $location_keys ) - 1;
							$row_start = max( 1, (int) floor( ( $event['start_minutes'] - $day_start ) / 30 ) + 1 );
							$row_span = max( 1, (int) ceil( ( $event['end_minutes'] - $event['start_minutes'] ) / 30 ) );
							$overlap_layout = $timed_layouts[ $event_index ] ?? [ 'index' => 0, 'count' => 1 ];
							$overlap_count = max( 1, (int) $overlap_layout['count'] );
							$overlap_index = max( 0, min( $overlap_count - 1, (int) $overlap_layout['index'] ) );
							$modal_event = [
								'title' => $event['title'],
								'day' => $event['day_label'] . ', ' . $event['date_label'],
								'time' => tn_tde_time_label( $event ),
								'location' => $event['location_label'],
								'category' => $event_type_label( $event ),
								'eventType' => $event['event_type'],
								'eventTypeColor' => $event['event_type_color'],
								'description' => tn_tde_render_description_html( $event['description'] ),
								'image' => $event['image'],
								'imageAlt' => $event['image_alt'] ?: $event['title'],
								'infoUrl' => $event['info_url'],
								'detailUrl' => tn_tde_event_detail_url( $event ),
								'presenters' => array_values( array_filter( array_map( function( $presenter ) {
									return sanitize_text_field( $presenter['name'] ?? '' );
								}, $event['presenters'] ?? [] ) ) ),
							];
							?>
							<button type="button" class="tn-full-schedule-event <?php echo esc_attr( $event['category_class'] ); ?>" style="<?php echo esc_attr( $event_style( $event, 'grid-column: ' . ( $location_index + 2 ) . '; grid-row: ' . $row_start . ' / span ' . $row_span . '; --tn-overlap-count: ' . $overlap_count . '; --tn-overlap-index: ' . $overlap_index . ';' ) ); ?>" data-event="<?php echo esc_attr( wp_json_encode( $modal_event ) ); ?>">
								<span class="tn-full-schedule-time"><?php echo esc_html( tn_tde_time_label( $event ) ); ?></span>
								<span class="tn-full-schedule-title"><?php echo esc_html( $event['title'] ); ?></span>
								<span class="tn-full-schedule-location"><?php echo esc_html( $event['location_label'] ); ?></span>
								<span class="tn-full-schedule-type"><?php echo esc_html( $event_type_label( $event ) ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php if ( ! empty( $day_events['after_hours'] ) ) : ?>
					<div class="tn-full-schedule-after-hours" style="--tn-location-count: <?php echo esc_attr( count( $locations ) ); ?>">
						<h3>After Hours</h3>
						<div class="tn-full-schedule-after-spacer" aria-hidden="true"></div>
						<?php foreach ( $locations as $location_key => $location_label ) : ?>
							<div class="tn-full-schedule-after-location<?php echo empty( $after_hours_by_location[ $location_key ] ) ? ' is-empty' : ''; ?>">
								<h4><?php echo esc_html( $location_label ); ?></h4>
								<?php foreach ( $after_hours_by_location[ $location_key ] as $event ) : ?>
									<?php
									$modal_event = [
										'title' => $event['title'],
										'day' => $event['day_label'] . ', ' . $event['date_label'],
										'time' => tn_tde_time_label( $event ),
										'location' => $event['location_label'],
										'category' => $event_type_label( $event ),
										'eventType' => $event['event_type'],
										'eventTypeColor' => $event['event_type_color'],
										'description' => tn_tde_render_description_html( $event['description'] ),
										'image' => $event['image'],
										'imageAlt' => $event['image_alt'] ?: $event['title'],
										'infoUrl' => $event['info_url'],
										'detailUrl' => tn_tde_event_detail_url( $event ),
										'presenters' => array_values( array_filter( array_map( function( $presenter ) {
											return sanitize_text_field( $presenter['name'] ?? '' );
										}, $event['presenters'] ?? [] ) ) ),
									];
									?>
									<button type="button" class="tn-full-schedule-event tn-full-schedule-event-after-hours <?php echo esc_attr( $event['category_class'] ); ?>" style="<?php echo esc_attr( $event_style( $event ) ); ?>" data-event="<?php echo esc_attr( wp_json_encode( $modal_event ) ); ?>">
										<span class="tn-full-schedule-time"><?php echo esc_html( tn_tde_time_label( $event ) ); ?></span>
										<span class="tn-full-schedule-title"><?php echo esc_html( $event['title'] ); ?></span>
										<span class="tn-full-schedule-type"><?php echo esc_html( $event_type_label( $event ) ); ?></span>
									</button>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $day_events['unscheduled'] ) ) : ?>
					<div class="tn-full-schedule-unscheduled">
						<h3>Time TBA</h3>
						<div class="tn-full-schedule-unscheduled-list">
							<?php foreach ( $day_events['unscheduled'] as $event ) : ?>
								<?php
								$modal_event = [
									'title' => $event['title'],
									'day' => $event['day_label'] . ', ' . $event['date_label'],
									'time' => tn_tde_time_label( $event ),
									'location' => $event['location_label'],
									'category' => $event_type_label( $event ),
									'eventType' => $event['event_type'],
									'eventTypeColor' => $event['event_type_color'],
									'description' => tn_tde_render_description_html( $event['description'] ),
									'image' => $event['image'],
									'imageAlt' => $event['image_alt'] ?: $event['title'],
									'infoUrl' => $event['info_url'],
									'detailUrl' => tn_tde_event_detail_url( $event ),
									'presenters' => array_values( array_filter( array_map( function( $presenter ) {
										return sanitize_text_field( $presenter['name'] ?? '' );
									}, $event['presenters'] ?? [] ) ) ),
								];
								?>
								<button type="button" class="tn-full-schedule-event tn-full-schedule-event-unscheduled <?php echo esc_attr( $event['category_class'] ); ?>" style="<?php echo esc_attr( $event_style( $event ) ); ?>" data-event="<?php echo esc_attr( wp_json_encode( $modal_event ) ); ?>">
									<span class="tn-full-schedule-time"><?php echo esc_html( tn_tde_time_label( $event ) ); ?> · <?php echo esc_html( $event['location_label'] ); ?></span>
									<span class="tn-full-schedule-title"><?php echo esc_html( $event['title'] ); ?></span>
									<span class="tn-full-schedule-type"><?php echo esc_html( $event_type_label( $event ) ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</section>
		<?php $panel_index++; endforeach; ?>

		<div class="tn-full-schedule-signup-cta">
			<p class="tn-full-schedule-signup-kicker">Ready to choose your events?</p>
			<a href="<?php echo esc_url( home_url( '/event-signups/' ) ); ?>">Sign Up for Events</a>
		</div>

		<div class="tn-full-schedule-modal" aria-hidden="true">
			<div class="tn-full-schedule-backdrop" data-tn-schedule-close></div>
			<article class="tn-full-schedule-dialog" role="dialog" aria-modal="true" aria-labelledby="tn-full-schedule-modal-title">
				<button type="button" class="tn-full-schedule-close" data-tn-schedule-close aria-label="Close schedule event">×</button>
				<p class="tn-full-schedule-modal-kicker" data-modal-meta></p>
				<h2 id="tn-full-schedule-modal-title" data-modal-title></h2>
				<div class="tn-full-schedule-modal-facts">
					<span data-modal-time></span>
					<span data-modal-location></span>
					<span data-modal-category></span>
				</div>
				<img class="tn-full-schedule-modal-image" data-modal-image alt="" hidden>
				<div class="tn-full-schedule-modal-desc" data-modal-desc></div>
				<p class="tn-full-schedule-modal-presenters" data-modal-presenters hidden></p>
				<a class="tn-full-schedule-modal-link" data-modal-link hidden>More Info</a>
			</article>
		</div>
	</div>
	<style>
	body.page-id-18797,
	body.page-id-18797 #page {
		overflow-x: hidden;
	}
	body.page-id-18797 #navbar,
	body.page-id-18797 .inner-main-title,
	body.page-id-18797 .entry-header {
		display: none;
	}
	body.page-id-18797 .site-content,
	body.page-id-18797 .site-main,
	body.page-id-18797 .entry-content {
		margin: 0;
		max-width: none;
		padding: 0;
		width: 100%;
	}
	body.page-id-18797 .entry-content > p:empty {
		display: none;
	}
	.tn-full-schedule {
		--tn-grid-bg: #0a0a14;
		--tn-grid-panel: rgba(18,20,34,0.82);
		--tn-grid-panel-strong: rgba(25,29,48,0.94);
		--tn-grid-line: rgba(255,255,255,0.16);
		--tn-grid-text: #f0f0f5;
		--tn-grid-muted: #b7bdcf;
		--tn-grid-cyan: #00e6ff;
		--tn-grid-pink: #ff3ea5;
		--tn-grid-gold: #ffd166;
		color: var(--tn-grid-text);
		background:
			radial-gradient(circle at 18% 7%, rgba(0,230,255,0.18), transparent 24rem),
			radial-gradient(circle at 82% 0%, rgba(255,62,165,0.16), transparent 25rem),
			linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.012) 42%, rgba(0,0,0,0)),
			var(--tn-grid-bg);
		border-radius: 0;
		box-shadow: none;
		font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		margin-left: calc(50% - 50vw);
		margin-right: calc(50% - 50vw);
		max-width: none;
		min-height: 100vh;
		padding: clamp(2.5rem, 7vw, 6rem) clamp(1rem, 4vw, 4rem) clamp(2.5rem, 6vw, 5rem);
		width: 100vw;
	}
	.tn-full-schedule > * {
		margin-left: auto;
		margin-right: auto;
		max-width: 1320px;
	}
	.tn-full-schedule-nav {
		align-items: center;
		display: flex;
		gap: 1rem;
		justify-content: space-between;
		margin-bottom: clamp(1.4rem, 3vw, 2.6rem);
	}
	.tn-full-schedule-brand {
		color: var(--tn-grid-text);
		font-family: Outfit, Inter, sans-serif;
		font-size: clamp(1rem, 1.5vw, 1.35rem);
		font-weight: 900;
		line-height: 1;
		text-decoration: none;
		text-transform: uppercase;
	}
	.tn-full-schedule-nav nav {
		align-items: center;
		display: flex;
		flex-wrap: wrap;
		gap: clamp(0.75rem, 2vw, 1.5rem);
		justify-content: flex-end;
	}
	.tn-full-schedule-nav nav a {
		color: var(--tn-grid-muted);
		font-size: 0.84rem;
		font-weight: 800;
		text-decoration: none;
		text-transform: uppercase;
	}
	.tn-full-schedule-nav nav a:hover {
		color: var(--tn-grid-cyan);
	}
	.tn-full-schedule-signup-cta {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 1rem;
		width: min(680px, 100%);
		margin: clamp(1rem, 2.4vw, 1.5rem) auto 0;
		padding: clamp(1rem, 2.4vw, 1.3rem);
		border: 1px solid rgba(0,229,255,0.22);
		border-radius: 8px;
		background:
			linear-gradient(135deg, rgba(0,229,255,0.1), rgba(255,45,149,0.06)),
			rgba(17,21,37,0.74);
	}
	.tn-full-schedule-signup-cta p {
		margin: 0;
		color: var(--tn-grid-muted);
		line-height: 1.5;
		text-align: center;
	}
	.tn-full-schedule-signup-cta .tn-full-schedule-signup-kicker {
		margin-bottom: 0.2rem;
		color: var(--tn-grid-text);
		font-family: Outfit, Inter, sans-serif;
		font-size: 1rem;
		font-weight: 900;
		letter-spacing: 0;
		text-transform: uppercase;
	}
	.tn-full-schedule-signup-cta a {
		flex: 0 0 auto;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 44px;
		padding: 0.72rem 1.05rem;
		border: 1px solid rgba(255,209,102,0.85);
		border-radius: 999px;
		background: #ffd166;
		color: #071019;
		font-family: Outfit, Inter, sans-serif;
		font-size: 0.82rem;
		font-weight: 900;
		letter-spacing: 0.08em;
		text-decoration: none;
		text-transform: uppercase;
	}
	.tn-full-schedule-head {
		display: grid;
		gap: 1rem;
		margin-bottom: clamp(0.6rem, 1.6vw, 1.2rem);
		padding: clamp(1.4rem, 3.5vw, 3rem) 0 clamp(0.35rem, 1vw, 0.8rem);
		place-content: start;
		position: relative;
	}
	.tn-full-schedule-head::before {
		content: none !important;
		display: none !important;
	}
	.tn-full-schedule-head > * {
		position: relative;
		z-index: 1;
	}
	.tn-full-schedule-kicker {
		color: var(--tn-grid-cyan);
		font-size: clamp(0.8rem, 1.2vw, 1rem);
		font-weight: 900;
		letter-spacing: 0.12em;
		margin: 0;
		text-transform: uppercase;
	}
	.tn-full-schedule-head h2 {
		margin: 0;
		color: var(--tn-grid-text);
		font-family: Outfit, Inter, sans-serif;
		font-size: clamp(3.2rem, 7vw, 6.4rem);
		font-weight: 900;
		letter-spacing: 0;
		line-height: 0.9;
		max-width: none;
		text-transform: uppercase;
	}
	.tn-full-schedule-tabs { display: flex; gap: 0.6rem; flex-wrap: wrap; margin-top: 0.4rem; }
	.tn-full-schedule-mobile-mode {
		display: none;
		align-items: center;
		gap: 0.45rem;
		width: fit-content;
		margin-top: 0.75rem;
		color: var(--tn-grid-muted);
		font-size: 0.82rem;
		font-weight: 800;
	}
	.tn-full-schedule-mobile-mode input {
		accent-color: var(--tn-grid-cyan);
	}
	.tn-full-schedule-tab {
		border: 1px solid var(--tn-grid-line);
		border-radius: 8px;
		background: rgba(255,255,255,0.055);
		color: var(--tn-grid-text);
		cursor: pointer;
		padding: 0.85rem 1rem;
		text-align: left;
	}
	.tn-full-schedule-tab span { display: block; font-weight: 900; text-transform: uppercase; }
	.tn-full-schedule-tab small { color: var(--tn-grid-muted); }
	.tn-full-schedule-tab.is-active {
		border-color: rgba(0,230,255,0.7);
		background: linear-gradient(135deg, rgba(0,230,255,0.18), rgba(255,62,165,0.13));
		box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
	}
	.tn-full-schedule-day { display: none; padding: 0 !important; }
	.tn-full-schedule-day.is-active { display: block; }
	.tn-full-schedule-timeline-wrap {
		overflow: visible;
		padding-bottom: 0.5rem;
	}
	.tn-full-schedule-locations,
	.tn-full-schedule-timeline {
		display: grid;
		grid-template-columns: minmax(54px, 0.42fr) repeat(var(--tn-location-count), minmax(0, 1fr));
		min-width: 0;
		width: 100%;
	}
	.tn-full-schedule-locations {
		position: sticky;
		top: 0;
		z-index: 2;
		background: var(--tn-grid-bg);
		border: 1px solid var(--tn-grid-line);
		border-radius: 8px;
		overflow: hidden;
		margin-bottom: 0.5rem;
	}
	.tn-full-schedule-time-spacer,
	.tn-full-schedule-location-head {
		min-width: 0;
		padding: 0.76rem 0.62rem;
		border-right: 1px solid var(--tn-grid-line);
		background: rgba(255,255,255,0.055);
	}
	.tn-full-schedule-location-head {
		font-family: Outfit, Inter, sans-serif;
		font-size: clamp(0.62rem, 0.9vw, 0.82rem);
		font-weight: 900;
		line-height: 1.08;
		text-transform: uppercase;
		word-break: normal;
		overflow-wrap: anywhere;
	}
	.tn-full-schedule-location-head:last-child { border-right: 0; }
	.tn-full-schedule-timeline {
		position: relative;
		grid-template-rows: repeat(var(--tn-slot-count), minmax(42px, auto));
		border: 1px solid var(--tn-grid-line);
		border-radius: 8px;
		background:
			repeating-linear-gradient(to bottom, rgba(255,255,255,0.09) 0 1px, transparent 1px 42px),
			rgba(255,255,255,0.035);
		overflow: hidden;
	}
	.tn-full-schedule-time-marker {
		grid-column: 1;
		align-self: start;
		padding: 0.25rem 0.45rem 0 0;
		color: var(--tn-grid-muted);
		font-size: 0.72rem;
		font-weight: 800;
		text-align: right;
	}
	.tn-full-schedule-lane-bg {
		border-left: 1px solid var(--tn-grid-line);
		background: rgba(255,255,255,0.018);
		pointer-events: none;
	}
	.tn-full-schedule-event {
		display: grid;
		gap: 0.24rem;
		align-content: start;
		border: 1px solid color-mix(in srgb, var(--tn-schedule-event-color, #ffffff) 45%, rgba(255,255,255,0.12));
		border-radius: 6px;
		background:
			linear-gradient(90deg, color-mix(in srgb, var(--tn-schedule-event-color, #ffffff) 16%, transparent), transparent 52%),
			var(--tn-grid-panel);
		box-shadow: inset 3px 0 0 var(--tn-schedule-event-color, transparent);
		color: var(--tn-grid-text);
		cursor: pointer;
		margin: 0.22rem;
		min-height: 36px;
		overflow: hidden;
		padding: 0.5rem 0.52rem;
		position: relative;
		text-align: left;
		transform: translateX(calc(var(--tn-overlap-index, 0) * 100%));
		width: calc(100% / var(--tn-overlap-count, 1) - 0.44rem);
		z-index: calc(1 + var(--tn-overlap-index, 0));
	}
	.tn-full-schedule-event:hover {
		border-color: color-mix(in srgb, var(--tn-schedule-event-color, #00e5ff) 70%, rgba(0,230,255,0.55));
		transform: translateX(calc(var(--tn-overlap-index, 0) * 100%)) translateY(-1px);
	}
	.tn-full-schedule-time { color: var(--tn-grid-cyan); font-size: 0.7rem; font-weight: 900; }
	.tn-full-schedule-title {
		font-family: Outfit, Inter, sans-serif;
		font-size: clamp(0.76rem, 0.95vw, 0.94rem);
		font-weight: 900;
		line-height: 1.05;
		overflow-wrap: anywhere;
	}
	.tn-full-schedule-location {
		display: none;
		color: var(--tn-grid-gold);
		font-size: 0.72rem;
		font-weight: 800;
	}
	.tn-full-schedule-type { width: fit-content; color: var(--tn-grid-muted); font-size: 0.66rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; }
	.tn-full-schedule-after-hours {
		display: grid;
		grid-template-columns: minmax(54px, 0.42fr) repeat(var(--tn-location-count), minmax(0, 1fr));
		gap: 0.55rem;
		margin-top: 1rem;
		border: 1px solid var(--tn-grid-line);
		border-radius: 8px;
		background: rgba(255,255,255,0.045);
		padding: 0.85rem;
	}
	.tn-full-schedule-after-hours h3 {
		grid-column: 1 / -1;
		margin: 0 0 0.15rem;
		font-family: Outfit, Inter, sans-serif;
		font-size: 0.9rem;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.tn-full-schedule-after-spacer {
		min-width: 0;
	}
	.tn-full-schedule-after-location {
		display: grid;
		align-content: start;
		gap: 0.55rem;
		min-width: 0;
	}
	.tn-full-schedule-after-location h4 {
		margin: 0;
		color: var(--tn-grid-gold);
		font-family: Outfit, Inter, sans-serif;
		font-size: clamp(0.62rem, 0.9vw, 0.82rem);
		font-weight: 900;
		line-height: 1.08;
		text-transform: uppercase;
		overflow-wrap: anywhere;
	}
	.tn-full-schedule-event-after-hours {
		margin: 0;
	}
	.tn-full-schedule-unscheduled {
		margin-top: 1rem;
		border: 1px solid var(--tn-grid-line);
		border-radius: 8px;
		background: rgba(255,255,255,0.045);
		padding: 0.85rem;
	}
	.tn-full-schedule-unscheduled h3 {
		margin: 0 0 0.7rem;
		font-family: Outfit, Inter, sans-serif;
		font-size: 0.9rem;
		letter-spacing: 0.08em;
		text-transform: uppercase;
	}
	.tn-full-schedule-unscheduled-list {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
		gap: 0.55rem;
	}
	.tn-full-schedule-event-unscheduled { margin: 0; }
	.tn-full-schedule-modal {
		position: fixed;
		inset: 0;
		z-index: 100000;
		display: none;
		place-items: center;
		padding: 1rem;
	}
	.tn-full-schedule-modal.is-open { display: grid; }
	.tn-full-schedule-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.72); }
	.tn-full-schedule-dialog {
		position: relative;
		width: min(720px, 100%);
		max-height: min(88vh, 780px);
		overflow-y: auto;
		border: 1px solid var(--tn-grid-line);
		border-radius: 8px;
		background: var(--tn-grid-panel-strong);
		box-shadow: 0 28px 90px rgba(0,0,0,0.5);
		padding: clamp(1.25rem, 3vw, 2rem);
	}
	.tn-full-schedule-close {
		position: absolute;
		top: 0.75rem;
		right: 0.75rem;
		width: 36px;
		height: 36px;
		border: 1px solid var(--tn-grid-line);
		border-radius: 999px;
		background: rgba(255,255,255,0.08);
		color: var(--tn-grid-text);
		cursor: pointer;
		font-size: 1.5rem;
		line-height: 1;
	}
	.tn-full-schedule-modal-kicker { margin: 0 2.5rem 0.5rem 0; color: var(--tn-grid-cyan); font-weight: 900; letter-spacing: 0.08em; text-transform: uppercase; }
	.tn-full-schedule-dialog h2 { margin: 0 2.5rem 0.85rem 0; font-family: Outfit, Inter, sans-serif; font-size: clamp(1.8rem, 5vw, 3.2rem); line-height: 0.95; }
	.tn-full-schedule-modal-facts { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
	.tn-full-schedule-modal-facts span {
		border: 1px solid var(--tn-grid-line);
		border-radius: 999px;
		color: var(--tn-grid-muted);
		padding: 0.35rem 0.65rem;
		font-size: 0.82rem;
		font-weight: 700;
	}
	.tn-full-schedule-modal-image,
	.tn-full-schedule-modal-desc img {
		display: block;
		max-width: 100%;
		height: auto;
		border-radius: 8px;
		margin: 0 0 1rem;
	}
	.tn-full-schedule-modal-desc { color: var(--tn-grid-text); line-height: 1.7; }
	.tn-full-schedule-modal-desc a { color: var(--tn-grid-cyan); }
	.tn-full-schedule-modal-presenters { color: var(--tn-grid-muted); font-weight: 700; }
	.tn-full-schedule-modal-link {
		display: inline-flex;
		margin-top: 1rem;
		border-radius: 999px;
		background: linear-gradient(135deg, var(--tn-grid-cyan), var(--tn-grid-pink));
		color: #fff;
		font-family: Outfit, Inter, sans-serif;
		font-weight: 900;
		letter-spacing: 0.08em;
		padding: 0.75rem 1rem;
		text-decoration: none;
		text-transform: uppercase;
	}
	@media (max-width: 1080px) {
		.tn-full-schedule { padding: 1rem; }
		.tn-full-schedule-locations,
		.tn-full-schedule-timeline { grid-template-columns: 48px repeat(var(--tn-location-count), minmax(0, 1fr)); }
		.tn-full-schedule-location-head { padding-inline: 0.42rem; }
		.tn-full-schedule-event { padding: 0.45rem; }
	}
	@media (max-width: 820px) {
		.tn-full-schedule { box-shadow: none; }
		.tn-full-schedule-nav {
			align-items: flex-start;
			flex-direction: column;
		}
		.tn-full-schedule-nav nav {
			justify-content: flex-start;
		}
		.tn-full-schedule-signup-cta {
			align-items: flex-start;
			flex-direction: column;
		}
		.tn-full-schedule-signup-cta a {
			width: 100%;
		}
		.tn-full-schedule-head { padding-top: 1.25rem; }
		.tn-full-schedule-head h2 {
			font-size: clamp(2.65rem, 12.5vw, 3.6rem);
			max-width: 8.5ch;
		}
		.tn-full-schedule-mobile-mode { display: inline-flex; }
		.tn-full-schedule-tabs { display: grid; grid-template-columns: 1fr; }
		.tn-full-schedule-tab { width: 100%; }
		.tn-full-schedule-timeline-wrap {
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
		.tn-full-schedule-locations,
		.tn-full-schedule-timeline {
			min-width: 760px;
		}
		.tn-full-schedule.is-mobile-list .tn-full-schedule-locations,
		.tn-full-schedule.is-mobile-list .tn-full-schedule-time-marker,
		.tn-full-schedule.is-mobile-list .tn-full-schedule-lane-bg { display: none; }
		.tn-full-schedule.is-mobile-list .tn-full-schedule-timeline {
			display: grid;
			grid-template-columns: 1fr;
			gap: 0.55rem;
			border: 0;
			border-radius: 0;
			background: transparent;
			min-width: 0;
			overflow: visible;
		}
		.tn-full-schedule.is-mobile-list .tn-full-schedule-event {
			grid-column: auto !important;
			grid-row: auto !important;
			margin: 0;
			min-height: 0;
			padding: 0.78rem 0.85rem;
		}
		.tn-full-schedule.is-mobile-list .tn-full-schedule-location { display: block; }
		.tn-full-schedule.is-mobile-list .tn-full-schedule-title { font-size: 1.05rem; }
		.tn-full-schedule.is-mobile-list .tn-full-schedule-after-hours {
			grid-template-columns: 1fr;
		}
		.tn-full-schedule.is-mobile-list .tn-full-schedule-after-spacer,
		.tn-full-schedule.is-mobile-list .tn-full-schedule-after-location.is-empty {
			display: none;
		}
		.tn-full-schedule.is-mobile-list .tn-full-schedule-unscheduled-list { grid-template-columns: 1fr; }
	}
	</style>
	<script>
	(function(){
		document.querySelectorAll('[data-tn-full-schedule]').forEach(function(root) {
			var tabs = Array.from(root.querySelectorAll('.tn-full-schedule-tab'));
			var panels = Array.from(root.querySelectorAll('.tn-full-schedule-day'));
			var modal = root.querySelector('.tn-full-schedule-modal');
			var mobileToggle = root.querySelector('[data-tn-mobile-list-toggle]');
			if (mobileToggle) {
				mobileToggle.checked = false;
				root.classList.remove('is-mobile-list');
				mobileToggle.addEventListener('change', function() {
					root.classList.toggle('is-mobile-list', mobileToggle.checked);
				});
			}
			if (!modal) return;
			function setDay(day) {
				tabs.forEach(function(tab) {
					var active = tab.getAttribute('data-day') === day;
					tab.classList.toggle('is-active', active);
					tab.setAttribute('aria-selected', active ? 'true' : 'false');
				});
				panels.forEach(function(panel) {
					panel.classList.toggle('is-active', panel.getAttribute('data-day-panel') === day);
				});
			}
			function setText(selector, value) {
				var el = modal.querySelector(selector);
				if (el) el.textContent = value || '';
			}
			function open(event) {
				setText('[data-modal-meta]', event.day || '');
				setText('[data-modal-title]', event.title || '');
				setText('[data-modal-time]', event.time || 'Time TBA');
				setText('[data-modal-location]', event.location || 'Location TBA');
				setText('[data-modal-category]', event.category || 'Event');
				var desc = modal.querySelector('[data-modal-desc]');
				if (desc) desc.innerHTML = event.description || '';
				var img = modal.querySelector('[data-modal-image]');
				if (img) {
					if (event.image) {
						img.src = event.image;
						img.alt = event.imageAlt || event.title || '';
						img.hidden = false;
					} else {
						img.removeAttribute('src');
						img.hidden = true;
					}
				}
				var presenters = modal.querySelector('[data-modal-presenters]');
				if (presenters) {
					var names = Array.isArray(event.presenters) ? event.presenters.filter(Boolean) : [];
					presenters.textContent = names.length ? 'Presented by ' + names.join(', ') : '';
					presenters.hidden = !names.length;
				}
				var link = modal.querySelector('[data-modal-link]');
				if (link) {
					var detailHref = event.detailUrl || event.infoUrl || '';
					if (detailHref) {
						link.href = detailHref;
						link.hidden = false;
					} else {
						link.removeAttribute('href');
						link.hidden = true;
					}
				}
				modal.classList.add('is-open');
				modal.setAttribute('aria-hidden', 'false');
				document.body.style.overflow = 'hidden';
			}
			function close() {
				modal.classList.remove('is-open');
				modal.setAttribute('aria-hidden', 'true');
				document.body.style.overflow = '';
			}
			tabs.forEach(function(tab) {
				tab.addEventListener('click', function() { setDay(tab.getAttribute('data-day')); });
			});
			root.querySelectorAll('.tn-full-schedule-event').forEach(function(button) {
				button.addEventListener('click', function() {
					try { open(JSON.parse(button.getAttribute('data-event') || '{}')); } catch(e) {}
				});
			});
			root.querySelectorAll('[data-tn-schedule-close]').forEach(function(button) {
				button.addEventListener('click', close);
			});
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
			});
		});
	})();
	</script>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'trivia_nationals_full_schedule', 'tn_tde_render_full_schedule_shortcode' );

// ─── Front-end: Fix hash/anchor navigation ──────────────────────────────────
// The page's inline CSS sets overflow-x:hidden on body, which makes body the
// scroll container and breaks native browser anchor scrolling. This JS intercepts
// all #hash clicks and uses scrollIntoView instead, which always works.

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	?>
	<script>
	(function(){
		function tnScrollTo(hash) {
			if (!hash || hash === '#') return;
			try {
				var target = document.querySelector(hash);
				if (!target) return;
				// CSS scroll-behavior:smooth on <html> blocks all JS-triggered scrolls in Chrome
				// Fix: override with inline 'auto', do instant scroll, then restore.
				var html = document.documentElement;
				var scrollPad = parseInt(getComputedStyle(html).scrollPaddingTop) || 72;
				var dest = Math.round(target.getBoundingClientRect().top + window.scrollY - scrollPad);
				window.scrollTo({ top: dest, behavior: 'smooth' });
				history.pushState(null, '', hash);
			} catch(e) {}
		}

		// Handle initial page load with a hash in the URL
		if (window.location.hash) {
			window.addEventListener('load', function() {
				setTimeout(function() { tnScrollTo(window.location.hash); }, 150);
			});
		}

		// Intercept all anchor clicks that point to a #hash on the same page
		document.addEventListener('click', function(e) {
			var a = e.target.closest('a[href^="#"]');
			if (!a) return;
			var href = a.getAttribute('href');
			if (!href || href === '#') return;
			e.preventDefault();
			e.stopImmediatePropagation();
			tnScrollTo(href);
		}, true);
	})();
	</script>
	<?php
} );

// ─── AJAX handler for schedule mode toggle ──────────────────────────────────

add_action( 'wp_ajax_tn_set_schedule_mode', function () {
	if ( ! current_user_can( 'edit_pages' ) ) wp_send_json_error( 'Unauthorized' );
	check_ajax_referer( 'tn_schedule_mode_nonce', 'nonce' );
	$mode = sanitize_text_field( $_POST['mode'] ?? 'off' );
	update_option( 'tn_schedule_mode', $mode === 'on' ? 'on' : 'off' );
	wp_send_json_success( [ 'mode' => get_option( 'tn_schedule_mode' ) ] );
} );

add_action( 'wp_ajax_tn_save_home_event_list', function () {
	if ( ! current_user_can( 'edit_pages' ) ) wp_send_json_error( 'Unauthorized' );
	check_ajax_referer( 'tn_home_event_list_nonce', 'nonce' );
	$raw = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '[]';
	$items = json_decode( $raw, true );
	if ( ! is_array( $items ) ) {
		wp_send_json_error( 'Invalid event list.' );
	}
	$clean = tn_tde_clean_home_event_list( $items );
	update_option( 'tn_home_event_list', $clean, false );
	wp_send_json_success( [ 'items' => $clean ] );
} );

add_action( 'wp_ajax_tn_save_homepage_sections', function () {
	if ( ! current_user_can( 'edit_pages' ) ) wp_send_json_error( 'Unauthorized' );
	check_ajax_referer( 'tn_homepage_sections_nonce', 'nonce' );
	$raw = isset( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : '[]';
	$sections = json_decode( $raw, true );
	if ( ! is_array( $sections ) ) {
		wp_send_json_error( 'Invalid section list.' );
	}
	$clean = tn_tde_clean_homepage_sections( $sections );
	update_option( 'tn_homepage_sections', $clean, false );
	wp_send_json_success( [ 'sections' => $clean ] );
} );

// ─── Admin: Event Schedule Manager ──────────────────────────────────────────

add_action( 'admin_menu', function () {
	add_menu_page(
		'Trivia Nationals',
		'Trivia Nationals',
		'edit_pages',
		'trivia-desc-editor',
		'trivia_desc_editor_page',
		'dashicons-calendar-alt',
		30
	);
	add_submenu_page(
		'trivia-desc-editor',
		'Event Schedule',
		'Event Schedule',
		'edit_pages',
		'trivia-desc-editor',
		'trivia_desc_editor_page'
	);
	add_submenu_page(
		'trivia-desc-editor',
		'Homepage Event List',
		'Homepage Event List',
		'edit_pages',
		'tn-home-event-list',
		'tn_tde_home_event_list_page'
	);
	add_submenu_page(
		'trivia-desc-editor',
		'Homepage Sections',
		'Homepage Sections',
		'edit_pages',
		'tn-homepage-sections',
		'tn_tde_homepage_sections_page'
	);
	add_submenu_page(
		'trivia-desc-editor',
		'Quotes',
		'Quotes',
		'edit_pages',
		'tn-homepage-quotes',
		'tn_tde_homepage_quotes_page'
	);
	add_submenu_page(
		'trivia-desc-editor',
		'FAQ',
		'FAQ',
		'edit_pages',
		'tn-homepage-faq',
		'tn_tde_homepage_faq_page'
	);
	add_submenu_page(
		'trivia-desc-editor',
		'Venue Videos',
		'Venue Videos',
		'edit_pages',
		'tn-venue-videos',
		'tn_tde_venue_videos_page'
	);
	add_submenu_page(
		'trivia-desc-editor',
		'Signup Settings',
		'Signup Settings',
		'manage_options',
		'tn-signup-settings',
		'tn_tde_signup_settings_page'
	);
} );

add_action( 'admin_init', function() {
	register_setting( 'tn_tde_signup_settings', 'tn_tde_signup_sheets_endpoint', [
		'type' => 'string',
		'sanitize_callback' => 'esc_url_raw',
	] );
	register_setting( 'tn_tde_signup_settings', 'tn_tde_signup_sheets_secret', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	] );
} );

function tn_tde_signup_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	?>
	<div class="wrap">
		<h1>Event Signup Settings</h1>
		<p>Paste the Google Apps Script web app URL and shared secret used to append event signup rows to Google Sheets.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'tn_tde_signup_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="tn_tde_signup_sheets_endpoint">Apps Script web app URL</label></th>
					<td><input class="regular-text code" type="url" id="tn_tde_signup_sheets_endpoint" name="tn_tde_signup_sheets_endpoint" value="<?php echo esc_attr( get_option( 'tn_tde_signup_sheets_endpoint' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="tn_tde_signup_sheets_secret">Shared secret</label></th>
					<td><input class="regular-text code" type="password" id="tn_tde_signup_sheets_secret" name="tn_tde_signup_sheets_secret" value="<?php echo esc_attr( get_option( 'tn_tde_signup_sheets_secret' ) ); ?>" autocomplete="new-password"></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function tn_tde_admin_list_styles() {
	?>
	<style>
		.tn-admin-list { display: grid; gap: 0.65rem; max-width: 1100px; margin: 1rem 0; }
		.tn-admin-row {
			display: grid; grid-template-columns: auto auto minmax(0, 1fr) auto; gap: 0.5rem;
			align-items: start; padding: 0.75rem; border: 1px solid #dcdcde; border-radius: 8px; background: #fff;
		}
		.tn-admin-row.event-list-row { grid-template-columns: auto auto minmax(0, 1.2fr) minmax(240px, 0.8fr) auto; }
		.tn-admin-row.two-fields { grid-template-columns: auto auto minmax(0, 1.2fr) minmax(180px, 0.6fr) auto; }
		.tn-admin-row.faq-row { grid-template-columns: auto auto minmax(0, 1fr) auto; }
		.tn-admin-row label { display: block; margin-bottom: 0.2rem; color: #646970; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; }
		.tn-admin-row input[type="text"], .tn-admin-row textarea, .tn-admin-row select { width: 100%; }
		.tn-admin-row textarea { min-height: 76px; resize: vertical; }
		.tn-admin-index { min-width: 2rem; color: #646970; font-size: 0.75rem; font-weight: 800; text-align: right; padding-top: 1.55rem; }
		.tn-admin-order { display: inline-flex; gap: 0.25rem; padding-top: 1.35rem; }
		.tn-admin-order button { min-width: 30px; }
		.tn-admin-field-full { grid-column: 3 / 4; }
		.tn-admin-row.faq-row .tn-admin-field-full { grid-column: 3 / 5; }
		.tn-admin-row .wp-editor-wrap { max-width: 100%; }
		.tn-admin-remove { margin-top: 1.35rem !important; }
		.tn-admin-visible { display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 1.4rem; font-weight: 700; }
		.tn-admin-note { max-width: 880px; color: #646970; }
		@media (max-width: 782px) {
			.tn-admin-row, .tn-admin-row.event-list-row, .tn-admin-row.two-fields, .tn-admin-row.faq-row { grid-template-columns: 1fr; }
			.tn-admin-index, .tn-admin-order, .tn-admin-remove { padding-top: 0; margin-top: 0 !important; }
			.tn-admin-field-full { grid-column: auto; }
		}
	</style>
	<script>
	(function(){
		window.tnAdminMoveRow = function(btn, direction) {
			var row = btn && btn.closest('.tn-admin-row');
			if (!row) return;
			var sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
			if (!sibling || !sibling.classList.contains('tn-admin-row')) return;
			if (direction < 0) row.parentNode.insertBefore(row, sibling);
			else row.parentNode.insertBefore(sibling, row);
			tnAdminRenumber(row.parentNode);
		};
		window.tnAdminRemoveRow = function(btn) {
			var row = btn && btn.closest('.tn-admin-row');
			if (!row) return;
			var list = row.parentNode;
			row.remove();
			tnAdminRenumber(list);
		};
		window.tnAdminRenumber = function(list) {
			if (!list) return;
			Array.from(list.querySelectorAll('.tn-admin-row')).forEach(function(row, idx, rows) {
				var index = row.querySelector('.tn-admin-index');
				if (index) index.textContent = (idx + 1) + '.';
				var up = row.querySelector('[data-move-up]');
				var down = row.querySelector('[data-move-down]');
				if (up) up.disabled = idx === 0;
				if (down) down.disabled = idx === rows.length - 1;
				row.querySelectorAll('[name]').forEach(function(field) {
					field.name = field.name.replace(/\[\d+\]/, '[' + idx + ']');
				});
			});
		};
		window.tnAdminAddFromTemplate = function(listId) {
			var list = document.getElementById(listId);
			var template = document.getElementById(listId + '-template');
			if (!list || !template) return;
			var html = template.innerHTML.replace(/__INDEX__/g, String(list.querySelectorAll('.tn-admin-row').length));
			list.insertAdjacentHTML('beforeend', html);
			tnAdminRenumber(list);
			if (listId === 'tn-faq-admin') tnAdminInitFaqEditors(list);
			var first = list.querySelector('.tn-admin-row:last-child input, .tn-admin-row:last-child textarea');
			if (first) first.focus();
		};
		window.tnAdminInitFaqEditors = function(scope) {
			if (!window.wp || !wp.editor || !wp.editor.initialize) return;
			(scope || document).querySelectorAll('textarea.tn-faq-rich-answer:not([data-editor-ready])').forEach(function(textarea) {
				if (!textarea.id) textarea.id = 'tn_faq_answer_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
				textarea.setAttribute('data-editor-ready', '1');
				wp.editor.initialize(textarea.id, {
					mediaButtons: false,
					quicktags: true,
					tinymce: {
						wpautop: true,
						toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
						toolbar2: ''
					}
				});
			});
		};
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.tn-admin-list').forEach(tnAdminRenumber);
			tnAdminInitFaqEditors(document);
		});
	})();
	</script>
	<?php
}

function tn_tde_home_event_list_page() {
	if ( ! current_user_can( 'edit_pages' ) ) wp_die( esc_html__( 'Unauthorized', 'tn-tde' ) );
	$saved = false;
	if ( isset( $_POST['tn_home_event_list_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_home_event_list_nonce'] ) ), 'tn_home_event_list_save' ) ) {
		$items = isset( $_POST['tn_home_event_list'] ) && is_array( $_POST['tn_home_event_list'] ) ? wp_unslash( $_POST['tn_home_event_list'] ) : [];
		update_option( 'tn_home_event_list', tn_tde_clean_home_event_list( $items ), false );
		$saved = true;
	}
	$items = tn_tde_get_home_event_list();
	$event_types = tn_tde_home_event_types();
	$default_type = tn_tde_default_home_event_type_key();
	if ( empty( $items ) ) $items = [ [ 'title' => '', 'type' => $default_type ] ];
	?>
	<div class="wrap">
		<h1>Homepage Event List</h1>
		<p class="tn-admin-note">These titles replace the old Friday/Saturday/Sunday homepage schedule tabs. The full schedule data stays separate.</p>
		<?php if ( $saved ) : ?><div class="notice notice-success is-dismissible"><p>Homepage event list saved.</p></div><?php endif; ?>
		<?php tn_tde_admin_list_styles(); ?>
		<form method="post">
			<?php wp_nonce_field( 'tn_home_event_list_save', 'tn_home_event_list_nonce' ); ?>
			<ol class="tn-admin-list" id="tn-home-event-list-admin">
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$title = is_array( $item ) ? ( $item['title'] ?? '' ) : $item;
					$type = is_array( $item ) ? ( $item['type'] ?? $default_type ) : $default_type;
					if ( ! isset( $event_types[ $type ] ) ) $type = $default_type;
					?>
					<li class="tn-admin-row event-list-row">
						<span class="tn-admin-index"></span>
						<span class="tn-admin-order"><button type="button" class="button" data-move-up onclick="tnAdminMoveRow(this,-1)">↑</button><button type="button" class="button" data-move-down onclick="tnAdminMoveRow(this,1)">↓</button></span>
						<div><label>Event Title</label><input type="text" name="tn_home_event_list[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>"></div>
						<div>
							<label>Event Type</label>
							<select name="tn_home_event_list[<?php echo esc_attr( $index ); ?>][type]">
								<?php foreach ( $event_types as $key => $definition ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $definition['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<button type="button" class="button tn-admin-remove" onclick="tnAdminRemoveRow(this)">Remove</button>
					</li>
				<?php endforeach; ?>
			</ol>
			<script type="text/template" id="tn-home-event-list-admin-template">
				<li class="tn-admin-row event-list-row"><span class="tn-admin-index"></span><span class="tn-admin-order"><button type="button" class="button" data-move-up onclick="tnAdminMoveRow(this,-1)">↑</button><button type="button" data-move-down class="button" onclick="tnAdminMoveRow(this,1)">↓</button></span><div><label>Event Title</label><input type="text" name="tn_home_event_list[__INDEX__][title]" value=""></div><div><label>Event Type</label><select name="tn_home_event_list[__INDEX__][type]"><?php foreach ( $event_types as $key => $definition ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default_type, $key ); ?>><?php echo esc_html( $definition['label'] ); ?></option><?php endforeach; ?></select></div><button type="button" class="button tn-admin-remove" onclick="tnAdminRemoveRow(this)">Remove</button></li>
			</script>
			<p><button type="button" class="button" onclick="tnAdminAddFromTemplate('tn-home-event-list-admin')">Add Item</button> <button type="submit" class="button button-primary">Save Homepage Event List</button></p>
		</form>
	</div>
	<?php
}

function tn_tde_homepage_sections_page() {
	if ( ! current_user_can( 'edit_pages' ) ) wp_die( esc_html__( 'Unauthorized', 'tn-tde' ) );
	$saved = false;
	if ( isset( $_POST['tn_homepage_sections_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_homepage_sections_nonce'] ) ), 'tn_homepage_sections_save' ) ) {
		$sections = isset( $_POST['tn_homepage_sections'] ) && is_array( $_POST['tn_homepage_sections'] ) ? wp_unslash( $_POST['tn_homepage_sections'] ) : [];
		update_option( 'tn_homepage_sections', tn_tde_clean_homepage_sections( $sections ), false );
		$saved = true;
	}
	$sections = tn_tde_get_homepage_sections();
	$defs = tn_tde_homepage_section_definitions();
	$jeopardy_page_id = tn_tde_get_jeopardy_page_id();
	$jeopardy_edit_url = $jeopardy_page_id ? get_edit_post_link( $jeopardy_page_id, '' ) : '';
	$how_it_works_page_id = tn_tde_get_how_it_works_page_id();
	$how_it_works_edit_url = $how_it_works_page_id ? get_edit_post_link( $how_it_works_page_id, '' ) : '';
	?>
	<div class="wrap">
		<h1>Homepage Sections</h1>
		<p class="tn-admin-note">Reorder or hide the main homepage sections. The Jeopardy and How It Works sections display content from WordPress pages. <?php if ( $jeopardy_edit_url ) : ?><a href="<?php echo esc_url( $jeopardy_edit_url ); ?>">Edit Jeopardy</a>.<?php endif; ?> <?php if ( $how_it_works_edit_url ) : ?><a href="<?php echo esc_url( $how_it_works_edit_url ); ?>">Edit How It Works</a>.<?php endif; ?></p>
		<?php if ( $saved ) : ?><div class="notice notice-success is-dismissible"><p>Homepage sections saved.</p></div><?php endif; ?>
		<?php tn_tde_admin_list_styles(); ?>
		<form method="post">
			<?php wp_nonce_field( 'tn_homepage_sections_save', 'tn_homepage_sections_nonce' ); ?>
			<ol class="tn-admin-list" id="tn-homepage-sections-admin">
				<?php foreach ( $sections as $index => $section ) : $key = $section['key']; if ( ! isset( $defs[ $key ] ) ) continue; ?>
					<li class="tn-admin-row" data-section-key="<?php echo esc_attr( $key ); ?>">
						<span class="tn-admin-index"></span>
						<span class="tn-admin-order"><button type="button" class="button" data-move-up onclick="tnAdminMoveRow(this,-1)">↑</button><button type="button" class="button" data-move-down onclick="tnAdminMoveRow(this,1)">↓</button></span>
						<div>
							<strong><?php echo esc_html( $defs[ $key ]['label'] ?? $key ); ?></strong>
							<p class="description"><?php echo esc_html( $defs[ $key ]['selector'] ?? '' ); ?></p>
							<input type="hidden" name="tn_homepage_sections[<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $key ); ?>">
						</div>
						<label class="tn-admin-visible"><input type="checkbox" name="tn_homepage_sections[<?php echo esc_attr( $index ); ?>][visible]" value="1" <?php checked( $section['visible'] !== false ); ?>> Show</label>
					</li>
				<?php endforeach; ?>
			</ol>
			<p><button type="submit" class="button button-primary">Save Homepage Sections</button></p>
		</form>
	</div>
	<?php
}

function tn_tde_homepage_quotes_page() {
	if ( ! current_user_can( 'edit_pages' ) ) wp_die( esc_html__( 'Unauthorized', 'tn-tde' ) );
	$saved = false;
	if ( isset( $_POST['tn_homepage_quotes_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_homepage_quotes_nonce'] ) ), 'tn_homepage_quotes_save' ) ) {
		$quotes = isset( $_POST['tn_homepage_quotes'] ) && is_array( $_POST['tn_homepage_quotes'] ) ? wp_unslash( $_POST['tn_homepage_quotes'] ) : [];
		update_option( 'tn_homepage_quotes', tn_tde_clean_homepage_quotes( $quotes ), false );
		$saved = true;
	}
	$quotes = tn_tde_get_homepage_quotes();
	if ( empty( $quotes ) ) $quotes = [ [ 'quote' => '', 'credit' => '' ] ];
	?>
	<div class="wrap">
		<h1>Quotes</h1>
		<p class="tn-admin-note">Maintain quotes from past Trivia Nationals attendees for the homepage Quotes section.</p>
		<?php if ( $saved ) : ?><div class="notice notice-success is-dismissible"><p>Quotes saved.</p></div><?php endif; ?>
		<?php tn_tde_admin_list_styles(); ?>
		<form method="post">
			<?php wp_nonce_field( 'tn_homepage_quotes_save', 'tn_homepage_quotes_nonce' ); ?>
			<ol class="tn-admin-list" id="tn-quotes-admin">
				<?php foreach ( $quotes as $index => $quote ) : ?>
					<li class="tn-admin-row two-fields">
						<span class="tn-admin-index"></span>
						<span class="tn-admin-order"><button type="button" class="button" data-move-up onclick="tnAdminMoveRow(this,-1)">↑</button><button type="button" class="button" data-move-down onclick="tnAdminMoveRow(this,1)">↓</button></span>
						<div><label>Quote</label><textarea name="tn_homepage_quotes[<?php echo esc_attr( $index ); ?>][quote]"><?php echo esc_textarea( $quote['quote'] ?? '' ); ?></textarea></div>
						<div><label>Credit</label><input type="text" name="tn_homepage_quotes[<?php echo esc_attr( $index ); ?>][credit]" value="<?php echo esc_attr( $quote['credit'] ?? '' ); ?>"></div>
						<button type="button" class="button tn-admin-remove" onclick="tnAdminRemoveRow(this)">Remove</button>
					</li>
				<?php endforeach; ?>
			</ol>
			<script type="text/template" id="tn-quotes-admin-template">
				<li class="tn-admin-row two-fields"><span class="tn-admin-index"></span><span class="tn-admin-order"><button type="button" class="button" data-move-up onclick="tnAdminMoveRow(this,-1)">↑</button><button type="button" class="button" data-move-down onclick="tnAdminMoveRow(this,1)">↓</button></span><div><label>Quote</label><textarea name="tn_homepage_quotes[__INDEX__][quote]"></textarea></div><div><label>Credit</label><input type="text" name="tn_homepage_quotes[__INDEX__][credit]"></div><button type="button" class="button tn-admin-remove" onclick="tnAdminRemoveRow(this)">Remove</button></li>
			</script>
			<p><button type="button" class="button" onclick="tnAdminAddFromTemplate('tn-quotes-admin')">Add Quote</button> <button type="submit" class="button button-primary">Save Quotes</button></p>
		</form>
	</div>
	<?php
}

function tn_tde_homepage_faq_page() {
	if ( ! current_user_can( 'edit_pages' ) ) wp_die( esc_html__( 'Unauthorized', 'tn-tde' ) );
	wp_enqueue_editor();
	$saved = false;
	if ( isset( $_POST['tn_homepage_faqs_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_homepage_faqs_nonce'] ) ), 'tn_homepage_faqs_save' ) ) {
		$faqs = isset( $_POST['tn_homepage_faqs'] ) && is_array( $_POST['tn_homepage_faqs'] ) ? wp_unslash( $_POST['tn_homepage_faqs'] ) : [];
		update_option( 'tn_homepage_faqs', tn_tde_clean_homepage_faqs( $faqs ), false );
		$saved = true;
	}
	$faqs = tn_tde_get_homepage_faqs();
	if ( empty( $faqs ) ) $faqs = [ [ 'question' => '', 'answer' => '' ] ];
	?>
	<div class="wrap">
		<h1>FAQ</h1>
		<p class="tn-admin-note">Add, remove, edit, and reorder questions for the homepage FAQ section.</p>
		<?php if ( $saved ) : ?><div class="notice notice-success is-dismissible"><p>FAQ saved.</p></div><?php endif; ?>
		<?php tn_tde_admin_list_styles(); ?>
		<form method="post">
			<?php wp_nonce_field( 'tn_homepage_faqs_save', 'tn_homepage_faqs_nonce' ); ?>
			<ol class="tn-admin-list" id="tn-faq-admin">
				<?php foreach ( $faqs as $index => $faq ) : ?>
					<li class="tn-admin-row faq-row">
						<span class="tn-admin-index"></span>
						<span class="tn-admin-order"><button type="button" class="button" data-move-up onclick="tnAdminMoveRow(this,-1)">↑</button><button type="button" class="button" data-move-down onclick="tnAdminMoveRow(this,1)">↓</button></span>
						<div><label>Question</label><input type="text" name="tn_homepage_faqs[<?php echo esc_attr( $index ); ?>][question]" value="<?php echo esc_attr( $faq['question'] ?? '' ); ?>"></div>
						<button type="button" class="button tn-admin-remove" onclick="tnAdminRemoveRow(this)">Remove</button>
						<div class="tn-admin-field-full">
							<label>Answer</label>
							<?php
							wp_editor(
								$faq['answer'] ?? '',
								'tn_faq_answer_' . $index,
								[
									'textarea_name' => 'tn_homepage_faqs[' . $index . '][answer]',
									'textarea_rows' => 6,
									'media_buttons' => false,
									'quicktags'     => true,
									'tinymce'       => [
										'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
										'toolbar2' => '',
									],
								]
							);
							?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
			<script type="text/template" id="tn-faq-admin-template">
				<li class="tn-admin-row faq-row"><span class="tn-admin-index"></span><span class="tn-admin-order"><button type="button" class="button" data-move-up onclick="tnAdminMoveRow(this,-1)">↑</button><button type="button" class="button" data-move-down onclick="tnAdminMoveRow(this,1)">↓</button></span><div><label>Question</label><input type="text" name="tn_homepage_faqs[__INDEX__][question]"></div><button type="button" class="button tn-admin-remove" onclick="tnAdminRemoveRow(this)">Remove</button><div class="tn-admin-field-full"><label>Answer</label><textarea class="tn-faq-rich-answer" name="tn_homepage_faqs[__INDEX__][answer]" rows="6"></textarea></div></li>
			</script>
			<p><button type="button" class="button" onclick="tnAdminAddFromTemplate('tn-faq-admin')">Add FAQ</button> <button type="submit" class="button button-primary">Save FAQ</button></p>
		</form>
	</div>
	<?php
}

function tn_tde_venue_videos_page() {
	if ( ! current_user_can( 'edit_pages' ) ) wp_die( esc_html__( 'Unauthorized', 'tn-tde' ) );
	$saved = false;
	if ( isset( $_POST['tn_venue_videos_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tn_venue_videos_nonce'] ) ), 'tn_venue_videos_save' ) ) {
		$raw = $_POST['tn_venue_videos'] ?? [];
		$raw = is_array( $raw ) ? wp_unslash( $raw ) : [];
		update_option( 'tn_venue_videos', tn_tde_clean_venue_videos( $raw ) );
		$saved = true;
	}
	$videos = tn_tde_get_venue_videos();
	if ( empty( $videos ) ) {
		$videos = [ [ 'title' => '', 'url' => '', 'description' => '' ] ];
	}
	?>
	<div class="wrap">
		<h1>Venue Videos</h1>
		<p>Add YouTube links that appear below the venue map on the homepage.</p>
		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p>Venue videos saved.</p></div>
		<?php endif; ?>
		<style>
			.tn-video-admin-list { display: grid; gap: 1rem; max-width: 920px; margin: 1rem 0; }
			.tn-video-admin-row {
				display: grid;
				grid-template-columns: minmax(180px, 1fr) minmax(240px, 1.15fr) auto;
				gap: 0.75rem;
				align-items: end;
				padding: 1rem;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				background: #fff;
			}
			.tn-video-admin-field label {
				display: block;
				margin-bottom: 0.25rem;
				color: #646970;
				font-size: 0.72rem;
				font-weight: 700;
				letter-spacing: 0.04em;
				text-transform: uppercase;
			}
			.tn-video-admin-field input { width: 100%; }
			.tn-video-admin-desc { grid-column: 1 / 3; }
			.tn-video-admin-remove { margin-bottom: 1px !important; }
			@media (max-width: 782px) {
				.tn-video-admin-row { grid-template-columns: 1fr; }
				.tn-video-admin-desc { grid-column: auto; }
			}
		</style>
		<form method="post">
			<?php wp_nonce_field( 'tn_venue_videos_save', 'tn_venue_videos_nonce' ); ?>
			<div class="tn-video-admin-list" id="tn-video-admin-list">
				<?php foreach ( $videos as $index => $video ) : ?>
					<div class="tn-video-admin-row">
						<div class="tn-video-admin-field">
							<label>Title</label>
							<input type="text" name="tn_venue_videos[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $video['title'] ?? '' ); ?>" placeholder="South Point hotel tour">
						</div>
						<div class="tn-video-admin-field">
							<label>YouTube URL</label>
							<input type="url" name="tn_venue_videos[<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_url( $video['url'] ?? '' ); ?>" placeholder="https://www.youtube.com/watch?v=...">
						</div>
						<button type="button" class="button tn-video-admin-remove">Remove</button>
						<div class="tn-video-admin-field tn-video-admin-desc">
							<label>Description</label>
							<input type="text" name="tn_venue_videos[<?php echo esc_attr( $index ); ?>][description]" value="<?php echo esc_attr( $video['description'] ?? '' ); ?>" placeholder="Short helper text for attendees">
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" class="button" id="tn-video-admin-add">Add Video</button>
				<button type="submit" class="button button-primary">Save Venue Videos</button>
			</p>
		</form>
		<script>
		(function(){
			var list = document.getElementById('tn-video-admin-list');
			var add = document.getElementById('tn-video-admin-add');
			if (!list || !add) return;
			function renumber() {
				list.querySelectorAll('.tn-video-admin-row').forEach(function(row, idx) {
					row.querySelectorAll('[name]').forEach(function(input) {
						input.name = input.name.replace(/tn_venue_videos\[\d+\]/, 'tn_venue_videos[' + idx + ']');
					});
				});
			}
			add.addEventListener('click', function() {
				var row = list.querySelector('.tn-video-admin-row');
				var clone = row ? row.cloneNode(true) : null;
				if (!clone) return;
				clone.querySelectorAll('input').forEach(function(input) { input.value = ''; });
				list.appendChild(clone);
				renumber();
				var first = clone.querySelector('input');
				if (first) first.focus();
			});
			list.addEventListener('click', function(e) {
				if (!e.target.classList.contains('tn-video-admin-remove')) return;
				var rows = list.querySelectorAll('.tn-video-admin-row');
				var row = e.target.closest('.tn-video-admin-row');
				if (!row) return;
				if (rows.length === 1) {
					row.querySelectorAll('input').forEach(function(input) { input.value = ''; });
				} else {
					row.remove();
				}
				renumber();
			});
		})();
		</script>
	</div>
	<?php
}

function trivia_desc_editor_page() {
	wp_enqueue_media();
	$nonce          = wp_create_nonce( 'wp_rest' );
	$mode_nonce     = wp_create_nonce( 'tn_schedule_mode_nonce' );
	$home_list_nonce = wp_create_nonce( 'tn_home_event_list_nonce' );
	$homepage_sections_nonce = wp_create_nonce( 'tn_homepage_sections_nonce' );
	$schedule_mode  = get_option( 'tn_schedule_mode', 'off' );
	$home_event_list = tn_tde_get_home_event_list();
	$homepage_sections = tn_tde_get_homepage_sections();
	$homepage_section_definitions = tn_tde_homepage_section_definitions();
	?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  #tde-wrap * { box-sizing: border-box; }
  #tde-wrap {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    max-width: 900px; margin: 20px auto 40px; color: #1a1a2e;
  }
  #tde-wrap h1 { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.3rem; }
  #tde-wrap .subtitle { font-size: 0.85rem; color: #666; margin-bottom: 1.5rem; }

  /* ── Mode Toggle ── */
  .tde-mode-bar {
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 1rem; background: #f8f9fb; border: 1px solid #d0d7de;
    border-radius: 10px; padding: 0.9rem 1.2rem;
  }
  .tde-mode-bar .mode-label {
    font-size: 0.85rem; font-weight: 600; color: #333;
  }
  .tde-mode-bar .mode-desc {
    font-size: 0.78rem; color: #777; margin-left: auto;
  }
  .tde-toggle {
    position: relative; width: 44px; height: 24px; flex-shrink: 0;
  }
  .tde-toggle input { opacity: 0; width: 0; height: 0; }
  .tde-toggle .slider {
    position: absolute; inset: 0; background: #ccc; border-radius: 24px;
    cursor: pointer; transition: background 0.25s;
  }
  .tde-toggle .slider::before {
    content: ''; position: absolute; height: 18px; width: 18px;
    left: 3px; bottom: 3px; background: #fff; border-radius: 50%;
    transition: transform 0.25s;
  }
  .tde-toggle input:checked + .slider { background: #0096a0; }
  .tde-toggle input:checked + .slider::before { transform: translateX(20px); }
  .tde-mode-status {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; padding: 0.2rem 0.6rem; border-radius: 4px;
  }
  .tde-mode-status.off { background: #eaecf0; color: #666; }
  .tde-mode-status.on  { background: rgba(0,150,160,0.12); color: #006470; }

  /* ── Toolbar ── */
  .tde-toolbar {
    display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;
    background: #fff; border: 1px solid #d0d7de; border-radius: 8px; padding: 0.85rem 1rem;
  }
  .tde-btn {
    padding: 0.45rem 1.1rem; border-radius: 6px; border: none;
    font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background 0.2s;
  }
  .tde-btn-primary { background: #0096a0; color: #fff; }
  .tde-btn-primary:hover { background: #00797f; }
  .tde-btn-primary:disabled { background: #aaa; cursor: not-allowed; }
  .tde-btn-secondary { background: #f0f2f5; color: #333; border: 1px solid #d0d7de; }
  .tde-btn-secondary:hover { background: #e1e4e8; }
  .tde-status { font-size: 0.82rem; padding: 0.35rem 0.75rem; border-radius: 4px; }
  .tde-status.ok  { background: #d4edda; color: #155724; }
  .tde-status.err { background: #f8d7da; color: #721c24; }
  .tde-status.loading { background: #cce5ff; color: #004085; }
  .tde-change-count { font-size: 0.82rem; color: #666; margin-left: auto; }
  .tde-home-list-panel {
    margin-bottom: 1.25rem; background: #fff; border: 1px solid #d0d7de;
    border-radius: 8px; overflow: hidden;
  }
  .tde-home-list-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: #f8f9fb; border-bottom: 1px solid #d0d7de; padding: 0.85rem 1rem;
  }
  .tde-home-list-panel.is-collapsed .tde-home-list-body { display: none; }
  .tde-home-list-panel.is-collapsed .tde-home-list-head { border-bottom: 0; }
  .tde-home-panel-actions {
    display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; justify-content: flex-end;
  }
  .tde-home-list-head h2 {
    margin: 0; font-size: 0.95rem; color: #222;
  }
  .tde-home-list-head p {
    margin: 0.2rem 0 0; color: #666; font-size: 0.78rem;
  }
  .tde-home-list-body { padding: 0.85rem 1rem 1rem; }
  .tde-home-list {
    display: grid; gap: 0.5rem; margin: 0 0 0.8rem; padding: 0; list-style: none;
  }
  .tde-home-list-row {
    display: grid; grid-template-columns: auto auto minmax(0, 1fr) auto; gap: 0.4rem;
    align-items: center; padding: 0.55rem; border: 1px solid #e1e4e8; border-radius: 6px;
    background: #fafbfc;
  }
  .tde-home-list-row input {
    width: 100%; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.42rem 0.55rem; font-size: 0.86rem; font-family: inherit;
  }
  .tde-home-list-row input:focus {
    outline: none; border-color: #0096a0; box-shadow: 0 0 0 3px rgba(0,150,160,0.12);
  }
  .tde-home-list-order {
    display: inline-flex; gap: 0.25rem;
  }
  .tde-home-list-index {
    color: #777; font-size: 0.72rem; font-weight: 800; min-width: 2rem; text-align: right;
  }
  .tde-home-list-actions {
    display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap;
  }
  .tde-home-list-actions .tde-status { margin-left: 0; }
  .tde-home-section-row {
    grid-template-columns: auto auto minmax(0, 1fr) auto;
  }
  .tde-home-section-name {
    color: #222; font-size: 0.88rem; font-weight: 800;
  }
  .tde-home-section-meta {
    display: block; margin-top: 0.12rem; color: #777; font-size: 0.7rem; font-weight: 600;
  }
  .tde-home-section-visible {
    display: inline-flex; align-items: center; gap: 0.35rem;
    color: #444; font-size: 0.75rem; font-weight: 800;
  }
  .tde-home-section-visible input {
    accent-color: #0096a0;
  }
  .tde-managed-content {
    display: grid; gap: 0.75rem; margin-bottom: 0.85rem;
  }
  .tde-managed-row {
    display: grid; grid-template-columns: minmax(0, 1fr) minmax(160px, 0.45fr) auto;
    gap: 0.5rem; align-items: start; padding: 0.65rem;
    border: 1px solid #e1e4e8; border-radius: 6px; background: #fafbfc;
  }
  .tde-managed-row.tde-faq-row {
    grid-template-columns: minmax(0, 1fr) auto;
  }
  .tde-managed-field label {
    display: block; margin-bottom: 0.2rem; color: #646970;
    font-size: 0.68rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase;
  }
  .tde-managed-field input,
  .tde-managed-field textarea {
    width: 100%; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.42rem 0.55rem; font-size: 0.86rem; font-family: inherit;
  }
  .tde-managed-field textarea {
    min-height: 72px; resize: vertical;
  }
  .tde-managed-field-full {
    grid-column: 1 / -2;
  }
  .tde-managed-remove {
    margin-top: 1.35rem;
  }
  .tde-managed-note {
    margin: 0 0 0.85rem; color: #666; font-size: 0.8rem; line-height: 1.45;
  }
  .tde-admin-nav {
    position: sticky; top: 32px; z-index: 20;
    display: none; align-items: center; gap: 0.65rem; flex-wrap: wrap;
    margin-bottom: 1rem; background: rgba(255,255,255,0.96);
    border: 1px solid #d0d7de; border-radius: 8px; padding: 0.7rem 0.85rem;
    box-shadow: 0 8px 24px rgba(15,23,42,0.08);
  }
  .tde-day-jumps { display: flex; gap: 0.35rem; flex-wrap: wrap; }
  .tde-day-jump,
  .tde-compact-btn {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #333;
    cursor: pointer; font-size: 0.72rem; font-weight: 800; padding: 0.35rem 0.6rem;
  }
  .tde-day-jump:hover,
  .tde-compact-btn:hover { background: #f0f2f5; }
  .tde-search {
    flex: 1 1 220px; min-width: 180px; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.42rem 0.6rem; font-size: 0.82rem;
  }

  /* ── Day sections ── */
  .tde-day { margin-bottom: 1.5rem; }
  .tde-day-header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.12em; color: #555; background: #eaecf0;
    padding: 0.45rem 1rem; border-radius: 6px 6px 0 0; border-bottom: 2px solid #d0d7de;
  }
  .tde-day.is-collapsed .tde-day-items { display: none; }
  .tde-day.is-filtered-out { display: none; }
  .tde-day-title { display: inline-flex; align-items: center; gap: 0.45rem; }
  .tde-day-count {
    color: #777; font-size: 0.62rem; letter-spacing: 0.06em;
  }
  .tde-day-tools { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
  .tde-add-event {
    border: 1px solid #0096a0; border-radius: 5px; background: #fff; color: #00797f;
    cursor: pointer; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em;
    padding: 0.25rem 0.55rem; text-transform: uppercase;
  }
  .tde-add-event:hover { background: rgba(0,150,160,0.08); }
  .tde-collapse-day {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #444;
    cursor: pointer; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em;
    padding: 0.25rem 0.55rem; text-transform: uppercase;
  }
  .tde-collapse-day:hover { background: #f0f2f5; }

  /* ── Event cards ── */
  .tde-card {
    background: #fff; border: 1px solid #d0d7de; border-top: none;
    padding: 0.85rem 1.1rem;
  }
  .tde-card:last-child { border-radius: 0 0 6px 6px; }
  .tde-card + .tde-card { border-top: 1px solid #f0f0f0; }
  .tde-card.changed { background: #fffbea; }
  .tde-card.deleted {
    background: #fff1f1;
    border-color: #f1b6b6;
    opacity: 0.82;
  }
  .tde-card.deleted input,
  .tde-card.deleted select,
  .tde-card.deleted textarea {
    text-decoration: line-through;
    color: #777;
  }

  /* Header row with title + tag */
  .tde-event-header {
    display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.45rem;
    flex-wrap: wrap;
  }
  .tde-event-actions {
    display: flex; align-items: center; gap: 0.4rem; justify-content: flex-end;
    margin-bottom: 0.55rem;
  }
  .tde-new-badge {
    margin-right: auto; color: #00797f; background: rgba(0,150,160,0.1);
    border-radius: 999px; padding: 0.18rem 0.55rem; font-size: 0.62rem;
    font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em;
  }
  .tde-order-controls {
    display: flex; align-items: center; gap: 0.35rem; margin-left: auto;
  }
  .tde-order-btn {
    border: 1px solid #d0d7de; background: #f8f9fb; color: #333;
    border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 700;
    line-height: 1; padding: 0.25rem 0.45rem;
  }
  .tde-order-btn:hover { background: #e1e4e8; }
  .tde-order-btn:disabled { color: #aaa; cursor: not-allowed; opacity: 0.55; }
  .tde-remove-event,
  .tde-undo-remove {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff;
    cursor: pointer; font-size: 0.68rem; font-weight: 800;
    letter-spacing: 0.05em; padding: 0.25rem 0.55rem; text-transform: uppercase;
  }
  .tde-remove-event { color: #a90022; border-color: #e8b2bd; }
  .tde-remove-event:hover { background: #fff0f3; }
  .tde-undo-remove { display: none; color: #006470; border-color: rgba(0,150,160,0.35); }
  .tde-undo-remove:hover { background: rgba(0,150,160,0.08); }
  .tde-card.deleted .tde-remove-event { display: none; }
  .tde-card.deleted .tde-undo-remove { display: inline-flex; }
  .tde-delete-note {
    display: none; margin-bottom: 0.65rem; border: 1px solid #f1b6b6;
    border-radius: 6px; background: #fff7f7; color: #861527;
    padding: 0.45rem 0.65rem; font-size: 0.76rem; font-weight: 700;
  }
  .tde-card.deleted .tde-delete-note { display: block; }
  .tde-card.is-filtered-out { display: none; }
  .tde-day-transfer {
    display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;
  }
  .tde-day-transfer select {
    width: auto; min-width: 118px; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.28rem 0.45rem; font-size: 0.72rem; font-family: inherit; background: #fff;
  }
  .tde-transfer-btn {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #333;
    cursor: pointer; font-size: 0.68rem; font-weight: 800;
    letter-spacing: 0.05em; padding: 0.25rem 0.5rem; text-transform: uppercase;
  }
  .tde-transfer-btn:hover { background: #f0f2f5; }
  .tde-event-name {
    font-weight: 600; font-size: 0.92rem;
  }
  .tde-tag {
    font-size: 0.62rem; font-weight: 700; padding: 0.18rem 0.55rem;
    border-radius: 20px; text-transform: uppercase; letter-spacing: 0.06em;
  }
  .tag-competition { background: rgba(0,150,160,0.12); color: #006470; }
  .tag-social { background: rgba(200,160,0,0.12); color: #7a5800; }
  .tag-finals { background: rgba(220,40,120,0.10); color: #9c0040; }
  .tag-special { background: rgba(120,90,220,0.10); color: #4a2fa0; }

  /* ── Schedule-mode field rows ── */
  .tde-fields {
    display: grid; grid-template-columns: 1fr auto auto minmax(180px, 0.6fr) auto auto; gap: 0.5rem;
    align-items: end; margin-bottom: 0.5rem;
  }
  .tde-field label {
    display: block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #888; margin-bottom: 0.15rem;
  }
  .tde-field input, .tde-field select {
    width: 100%; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.4rem 0.55rem; font-size: 0.82rem; font-family: inherit;
    color: #222; transition: border-color 0.2s;
  }
  .tde-field input:focus, .tde-field select:focus {
    outline: none; border-color: #0096a0; box-shadow: 0 0 0 3px rgba(0,150,160,0.12);
  }
  .tde-field-title { grid-column: 1 / -1; }
  .tde-field-start { width: 110px; }
  .tde-field-end   { width: 110px; }
  .tde-field-location { min-width: 180px; }
  .tde-field-after-hours {
    min-width: 125px;
  }
  .tde-field-after-hours label {
    align-items: center;
    display: flex;
    gap: 0.35rem;
    min-height: 36px;
  }
  .tde-field-after-hours input {
    accent-color: #0096a0;
    border: 1px solid #d0d7de;
    height: 16px;
    margin: 0;
    padding: 0;
    width: auto;
  }
  .tde-field-tag   { width: 150px; }

  /* ── Rich description editor ── */
  .tde-rich-toolbar {
    display: flex; gap: 0.35rem; flex-wrap: wrap; margin: 0.6rem 0 0;
  }
  .tde-rich-toolbar button {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #333;
    cursor: pointer; font-size: 0.75rem; font-weight: 800; min-width: 32px;
    padding: 0.3rem 0.55rem;
  }
  .tde-rich-toolbar button:hover { background: #f0f2f5; }
  .tde-rich-desc,
  .tde-rich-bio {
    width: 100%; border: 1px solid #d0d7de; border-radius: 6px;
    padding: 0.55rem 0.7rem; font-size: 0.83rem; line-height: 1.5;
    min-height: 110px; font-family: inherit; background: #fff;
    color: #222; transition: border-color 0.2s;
  }
  .tde-rich-bio {
    min-height: 72px;
  }
  .tde-rich-desc:focus,
  .tde-rich-bio:focus {
    outline: none; border-color: #0096a0; box-shadow: 0 0 0 3px rgba(0,150,160,0.12);
  }
  .tde-rich-desc img,
  .tde-rich-bio img {
    display: block; max-width: 100%; height: auto; margin: 0.5rem 0; border-radius: 6px;
  }
  .tde-card .tde-info-url {
    margin-top: 0.5rem;
  }
  .tde-card .tde-info-url input,
  .tde-card .tde-presenter-row input {
    width: 100%; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.42rem 0.55rem; font-size: 0.82rem; font-family: inherit;
    color: #222; transition: border-color 0.2s;
  }
  .tde-card .tde-info-url input:focus,
  .tde-card .tde-presenter-row input:focus {
    outline: none; border-color: #0096a0; box-shadow: 0 0 0 3px rgba(0,150,160,0.12);
  }
  .tde-card .tde-info-url label,
  .tde-card .tde-presenters label {
    display: block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #888; margin-bottom: 0.15rem;
  }
  .tde-event-graphic {
    display: grid; grid-template-columns: auto 1fr; gap: 0.65rem; align-items: center;
    margin-top: 0.55rem; padding: 0.65rem; border: 1px solid #e1e4e8;
    border-radius: 6px; background: #fafbfc;
  }
  .tde-event-graphic label {
    display: block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #888; margin-bottom: 0.2rem;
  }
  .tde-event-graphic-preview {
    width: 92px; height: 64px; border-radius: 6px; object-fit: cover;
    border: 1px solid #d0d7de; background: #fff;
  }
  .tde-event-graphic-preview.is-empty {
    display: grid; place-items: center; color: #999; font-size: 0.68rem; text-align: center;
  }
  .tde-event-graphic-actions {
    display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center;
  }
  .tde-event-graphic-btn {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #333;
    cursor: pointer; font-size: 0.72rem; font-weight: 700; padding: 0.3rem 0.6rem;
  }
  .tde-event-graphic-btn:hover { background: #f0f2f5; }
  .tde-event-graphic-alt {
    grid-column: 1 / -1;
  }
  .tde-event-graphic-alt input {
    width: 100%; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.42rem 0.55rem; font-size: 0.82rem; font-family: inherit;
    color: #222; transition: border-color 0.2s;
  }
  .tde-event-graphic-alt input:focus {
    outline: none; border-color: #0096a0; box-shadow: 0 0 0 3px rgba(0,150,160,0.12);
  }
  .tde-presenters {
    margin-top: 0.65rem; border: 1px solid #e1e4e8; border-radius: 6px;
    background: #fafbfc; padding: 0.7rem;
  }
  .tde-presenters-head {
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
    margin-bottom: 0.55rem;
  }
  .tde-presenters-title {
    font-size: 0.68rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.08em; color: #555;
  }
  .tde-presenter-add,
  .tde-presenter-remove {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #333;
    cursor: pointer; font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.55rem;
  }
  .tde-presenter-add:hover,
  .tde-presenter-remove:hover { background: #f0f2f5; }
  .tde-presenter-row {
    display: grid; grid-template-columns: 1fr minmax(180px, 0.75fr) auto; gap: 0.5rem; align-items: end;
    padding: 0.55rem 0; border-top: 1px solid #e6e8eb;
  }
  .tde-presenter-row:first-of-type { border-top: none; padding-top: 0; }
  .tde-presenter-bio-field {
    grid-column: 1 / -1;
    min-width: 0;
  }
  .tde-presenter-bio-field .tde-rich-toolbar {
    margin-top: 0.25rem;
  }
  .tde-sessions {
    margin-top: 0.65rem; border: 1px solid #d8dee4; border-radius: 6px;
    background: #f6f8fa; padding: 0.7rem;
  }
  .tde-sessions-head {
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
    margin-bottom: 0.55rem;
  }
  .tde-sessions-title {
    font-size: 0.68rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.08em; color: #555;
  }
  .tde-session-add,
  .tde-session-remove {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #333;
    cursor: pointer; font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.55rem;
  }
  .tde-session-add:hover,
  .tde-session-remove:hover { background: #f0f2f5; }
  .tde-session-row {
    display: grid; grid-template-columns: minmax(180px, 1.4fr) 110px 110px minmax(180px, 1fr) 82px auto;
    gap: 0.5rem; align-items: end; padding: 0.55rem 0; border-top: 1px solid #e1e4e8;
  }
  .tde-session-row:first-of-type { border-top: none; padding-top: 0; }
  .tde-session-row label {
    display: block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #888; margin-bottom: 0.15rem;
  }
  .tde-session-row input,
  .tde-session-row select {
    width: 100%; border: 1px solid #d0d7de; border-radius: 5px;
    padding: 0.42rem 0.55rem; font-size: 0.82rem; font-family: inherit;
    color: #222; transition: border-color 0.2s; background: #fff;
  }
  .tde-session-row input:focus,
  .tde-session-row select:focus {
    outline: none; border-color: #0096a0; box-shadow: 0 0 0 3px rgba(0,150,160,0.12);
  }
  .tde-session-full label {
    display: flex; align-items: center; gap: 0.35rem; min-height: 36px; margin-bottom: 0;
  }
  .tde-session-full input[type="checkbox"] {
    width: auto; margin: 0; padding: 0; accent-color: #0096a0;
  }
  .tde-presenter-photo-field {
    display: grid; grid-template-columns: auto 1fr; gap: 0.5rem; align-items: center;
  }
  .tde-presenter-photo-preview {
    width: 44px; height: 44px; border-radius: 6px; object-fit: cover;
    border: 1px solid #d0d7de; background: #fff;
  }
  .tde-presenter-photo-preview.is-empty {
    display: grid; place-items: center; color: #999; font-size: 0.65rem; text-align: center;
  }
  .tde-presenter-photo-actions { display: flex; gap: 0.35rem; flex-wrap: wrap; }
  .tde-presenter-photo-btn {
    border: 1px solid #d0d7de; border-radius: 5px; background: #fff; color: #333;
    cursor: pointer; font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.55rem;
  }
  .tde-presenter-photo-btn:hover { background: #f0f2f5; }
  .tde-card.changed textarea,
  .tde-card.changed .tde-rich-desc,
  .tde-card.changed .tde-rich-bio,
  .tde-card.changed input,
  .tde-card.changed select { border-color: #e6a800; }
  .tde-char-count { font-size: 0.7rem; color: #999; text-align: right; margin-top: 0.2rem; }

  #tde-loading { text-align: center; padding: 3rem; color: #666; }
  #tde-content { display: none; }
</style>
</head>
<body>

<div id="tde-wrap">
  <h1>📅 Event Schedule Manager</h1>
  <p class="subtitle">Manage the Trivia Nationals event schedule — descriptions, titles, times, and categories.</p>
  <?php if ( $homepage_content_saved ) : ?>
    <div class="notice notice-success is-dismissible"><p>Homepage content saved.</p></div>
  <?php endif; ?>

  <!-- Schedule Mode toggle -->
  <div class="tde-mode-bar">
    <span class="mode-label">Schedule Mode</span>
    <label class="tde-toggle">
      <input type="checkbox" id="tde-mode-toggle" <?php echo $schedule_mode === 'on' ? 'checked' : ''; ?> onchange="tdeToggleMode(this)">
      <span class="slider"></span>
    </label>
    <span id="tde-mode-status" class="tde-mode-status <?php echo $schedule_mode; ?>">
      <?php echo $schedule_mode === 'on' ? 'Schedule Live' : 'Pre-Event'; ?>
    </span>
    <span class="mode-desc" id="tde-mode-desc">
      <?php echo $schedule_mode === 'on'
        ? 'Times are visible to attendees on the website. Full editing enabled.'
        : 'Times are hidden from the public. Toggle on when ready to publish the schedule.'; ?>
    </span>
  </div>

  <div class="tde-toolbar">
    <button class="tde-btn tde-btn-primary" id="tde-save-btn" onclick="tdeSaveAll()" disabled>Save All Changes</button>
    <button class="tde-btn tde-btn-secondary" onclick="tdeLoad()">↺ Reload</button>
    <span id="tde-status" class="tde-status" style="display:none"></span>
    <span id="tde-change-count" class="tde-change-count"></span>
  </div>

  <div class="tde-admin-nav" id="tde-admin-nav">
    <div class="tde-day-jumps" id="tde-day-jumps"></div>
    <input type="search" class="tde-search" id="tde-search" placeholder="Search events, presenters, locations…" oninput="tdeFilterEvents(this.value)">
    <button type="button" class="tde-compact-btn" onclick="tdeExpandAllDays()">Expand All</button>
    <button type="button" class="tde-compact-btn" onclick="tdeCollapseAllDays()">Collapse All</button>
  </div>

  <div id="tde-loading">Loading event data…</div>
  <div id="tde-content"></div>
</div>

<script>
(function() {
  var NONCE      = '<?php echo esc_js( $nonce ); ?>';
  var MODE_NONCE = '<?php echo esc_js( $mode_nonce ); ?>';
  var HOME_LIST_NONCE = '<?php echo esc_js( $home_list_nonce ); ?>';
  var HOMEPAGE_SECTIONS_NONCE = '<?php echo esc_js( $homepage_sections_nonce ); ?>';
  var API        = '<?php echo esc_js( rest_url( 'wp/v2/pages/5' ) ); ?>';
  var AJAX_URL   = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
  var _eData     = null;
  var _orig      = {};      // key → { desc, image, imageAlt, infoUrl, presenters, sessions, title, start, end, location, afterHours, tagLabel, tagClass, eventType }
  var _orderDirty = false;
  var _homeList = <?php echo wp_json_encode( $home_event_list ); ?>;
  var _homeListOrig = JSON.stringify(_homeList);
  var _homepageSectionDefinitions = <?php echo wp_json_encode( $homepage_section_definitions ); ?>;
  var _homepageSections = <?php echo wp_json_encode( $homepage_sections ); ?>;
  var _homepageSectionsOrig = JSON.stringify(_homepageSections);
  var _scheduleMode = <?php echo $schedule_mode === 'on' ? 'true' : 'false'; ?>;
  var DAY_OPTIONS = [
    { id: 'day-friday',   shortLabel: 'Fri', label: 'Friday — August 7, 2026' },
    { id: 'day-saturday', shortLabel: 'Sat', label: 'Saturday — August 8, 2026' },
    { id: 'day-sunday',   shortLabel: 'Sun', label: 'Sunday — August 9, 2026' }
  ];

  var TAG_OPTIONS = [
    { value: 'tag-competition', label: 'Competition', cls: 'tag-competition' },
    { value: 'tag-social',      label: 'Social',      cls: 'tag-social' },
    { value: 'tag-finals',      label: 'Finals',      cls: 'tag-finals' },
    { value: 'tag-special',     label: 'Special',     cls: 'tag-special' }
  ];
  var EVENT_TYPE_OPTIONS = <?php echo wp_json_encode( tn_tde_home_event_types() ); ?>;
  var DEFAULT_EVENT_TYPE = <?php echo wp_json_encode( tn_tde_default_schedule_event_type_key() ); ?>;
  var LOCATION_OPTIONS = <?php echo wp_json_encode( tn_tde_location_options() ); ?>;

  /* ── Utilities ── */
  function setStatus(msg, type) {
    var el = document.getElementById('tde-status');
    el.textContent = msg;
    el.className = 'tde-status ' + type;
    el.style.display = 'inline-block';
    if (type === 'ok') setTimeout(function(){ el.style.display = 'none'; }, 6000);
  }

  function renumberManagedRows(containerId, rootName) {
    var wrap = document.getElementById(containerId);
    if (!wrap) return;
    Array.from(wrap.querySelectorAll('[name]')).forEach(function(field) {
      var row = field.closest('.tde-managed-row');
      var rows = Array.from(wrap.querySelectorAll('.tde-managed-row'));
      var idx = rows.indexOf(row);
      field.name = field.name.replace(new RegExp(rootName + '\\[\\d+\\]'), rootName + '[' + idx + ']');
    });
  }

  window.tdeRemoveManagedRow = function(btn) {
    var row = btn && btn.closest('.tde-managed-row');
    if (!row) return;
    var wrap = row.parentNode;
    row.remove();
    if (wrap && wrap.id === 'tde-quotes-editor') renumberManagedRows('tde-quotes-editor', 'tn_homepage_quotes');
    if (wrap && wrap.id === 'tde-faq-editor') renumberManagedRows('tde-faq-editor', 'tn_homepage_faqs');
  };

  window.tdeAddQuoteRow = function() {
    var wrap = document.getElementById('tde-quotes-editor');
    if (!wrap) return;
    var idx = wrap.querySelectorAll('.tde-managed-row').length;
    wrap.insertAdjacentHTML('beforeend',
      '<div class="tde-managed-row tde-quote-row">' +
        '<div class="tde-managed-field"><label>Quote</label><textarea name="tn_homepage_quotes[' + idx + '][quote]"></textarea></div>' +
        '<div class="tde-managed-field"><label>Credit</label><input type="text" name="tn_homepage_quotes[' + idx + '][credit]"></div>' +
        '<button type="button" class="button tde-managed-remove" onclick="tdeRemoveManagedRow(this)">Remove</button>' +
      '</div>'
    );
  };

  window.tdeAddFaqRow = function() {
    var wrap = document.getElementById('tde-faq-editor');
    if (!wrap) return;
    var idx = wrap.querySelectorAll('.tde-managed-row').length;
    wrap.insertAdjacentHTML('beforeend',
      '<div class="tde-managed-row tde-faq-row">' +
        '<div class="tde-managed-field"><label>Question</label><input type="text" name="tn_homepage_faqs[' + idx + '][question]"></div>' +
        '<button type="button" class="button tde-managed-remove" onclick="tdeRemoveManagedRow(this)">Remove</button>' +
        '<div class="tde-managed-field tde-managed-field-full"><label>Answer</label><textarea name="tn_homepage_faqs[' + idx + '][answer]"></textarea></div>' +
      '</div>'
    );
  };
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'"');
  }
  function escAttrJson(value) {
    return escHtml(JSON.stringify(value || []));
  }
  function escId(s) { return s.replace(/[^a-zA-Z0-9_-]/g, '_'); }
  function stripTags(html) {
    var el = document.createElement('div');
    el.innerHTML = html || '';
    return el.textContent || '';
  }
  function sanitizeDescriptionHtml(html) {
    var allowed = { A: true, STRONG: true, B: true, EM: true, I: true, U: true, BR: true, P: true, DIV: true, UL: true, OL: true, LI: true, IMG: true };
    var template = document.createElement('template');
    template.innerHTML = html || '';
    function clean(node) {
      Array.from(node.childNodes).forEach(function(child) {
        if (child.nodeType === Node.TEXT_NODE) return;
        if (child.nodeType !== Node.ELEMENT_NODE) {
          child.remove();
          return;
        }
        if (!allowed[child.tagName]) {
          child.replaceWith(document.createTextNode(child.textContent || ''));
          return;
        }
        Array.from(child.attributes).forEach(function(attr) {
          var name = attr.name.toLowerCase();
          if (child.tagName === 'A' && name === 'href') return;
          if (child.tagName === 'IMG' && ['src','alt','width','height','class'].indexOf(name) !== -1) return;
          child.removeAttribute(attr.name);
        });
        if (child.tagName === 'A') {
          var href = child.getAttribute('href') || '';
          if (!/^(https?:|mailto:|tel:|#)/i.test(href)) {
            child.replaceWith(document.createTextNode(child.textContent || ''));
            return;
          }
          child.setAttribute('target', '_blank');
          child.setAttribute('rel', 'noopener noreferrer');
        }
        if (child.tagName === 'IMG') {
          var src = child.getAttribute('src') || '';
          if (!/^https?:/i.test(src)) {
            child.remove();
            return;
          }
        }
        clean(child);
      });
    }
    clean(template.content);
    return template.innerHTML.trim();
  }
  function normalizeDescriptionHtml(html) {
    return sanitizeDescriptionHtml(html).trim();
  }
  function normalizePresenters(value) {
    var list = [];
    if (Array.isArray(value)) {
      list = value;
    } else if (typeof value === 'string' && value.trim()) {
      try { list = JSON.parse(value); } catch(e) { list = []; }
    }
    return list.map(function(p) {
      return {
        name: String((p && p.name) || '').trim(),
        bio: String((p && p.bio) || '').trim(),
        photo: String((p && p.photo) || '').trim()
      };
    }).filter(function(p) {
      return p.name || p.bio || p.photo;
    });
  }
  function presentersEqual(a, b) {
    return JSON.stringify(normalizePresenters(a)) === JSON.stringify(normalizePresenters(b));
  }
  function normalizeSessions(value) {
    var list = [];
    if (Array.isArray(value)) {
      list = value;
    } else if (typeof value === 'string' && value.trim()) {
      try { list = JSON.parse(value); } catch(e) { list = []; }
    }
    return list.map(function(session) {
      return {
        label: String((session && session.label) || '').trim(),
        start: String((session && session.start) || '').trim(),
        end: String((session && session.end) || '').trim(),
        location: normalizeLocation((session && session.location) || ''),
        full: !!(session && session.full)
      };
    }).filter(function(session) {
      return session.label || session.start || session.end || session.location;
    });
  }
  function sessionsEqual(a, b) {
    return JSON.stringify(normalizeSessions(a)) === JSON.stringify(normalizeSessions(b));
  }
  function normalizeImageUrl(value) {
    return String(value || '').trim();
  }
  function normalizeImageAlt(value) {
    return String(value || '').trim();
  }
  function normalizeLocation(value) {
    value = String(value || '').trim();
    return Object.prototype.hasOwnProperty.call(LOCATION_OPTIONS, value) ? value : '';
  }
  function normalizeBool(value) {
    return value === true || value === 1 || value === '1' || String(value || '').toLowerCase() === 'true';
  }
  function getTagLabel(tagClass) {
    var opt = TAG_OPTIONS.find(function(o){ return o.value === tagClass; });
    return opt ? opt.label : '';
  }
  function normalizeEventType(value) {
    value = String(value || DEFAULT_EVENT_TYPE || 'none');
    return Object.prototype.hasOwnProperty.call(EVENT_TYPE_OPTIONS, value) ? value : (DEFAULT_EVENT_TYPE || 'none');
  }
  function parseStartTime(value) {
    value = String(value || '').trim().toLowerCase();
    if (!value) return null;
    var m = value.match(/^(\d{1,2})(?::(\d{2}))?\s*(a|am|p|pm)?$/);
    if (!m) return null;
    var hours = parseInt(m[1], 10);
    var mins = m[2] ? parseInt(m[2], 10) : 0;
    var meridian = m[3] || '';
    if (hours > 24 || mins > 59) return null;
    if (meridian.charAt(0) === 'p' && hours < 12) hours += 12;
    if (meridian.charAt(0) === 'a' && hours === 12) hours = 0;
    return (hours * 60) + mins;
  }

  window.tdeToggleAdminPanel = function(btn) {
    var panel = btn && btn.closest('.tde-home-list-panel');
    if (!panel) return;
    var collapsed = panel.classList.toggle('is-collapsed');
    btn.textContent = collapsed ? 'Expand' : 'Collapse';
  };

  function setHomeListStatus(msg, type) {
    var el = document.getElementById('tde-home-list-status');
    if (!el) return;
    el.textContent = msg;
    el.className = 'tde-status ' + type;
    el.style.display = 'inline-block';
    if (type === 'ok') setTimeout(function(){ el.style.display = 'none'; }, 6000);
  }

  function getHomeListValues() {
    return Array.from(document.querySelectorAll('#tde-home-list input')).map(function(input) {
      return input.value.trim();
    }).filter(Boolean);
  }

  function updateHomeListDirtyState() {
    var save = document.getElementById('tde-home-list-save');
    if (!save) return;
    save.disabled = JSON.stringify(getHomeListValues()) === _homeListOrig;
  }

  function renderHomeListEditor() {
    var list = document.getElementById('tde-home-list');
    if (!list) return;
    list.innerHTML = '';
    _homeList.forEach(function(title, idx) {
      var row = document.createElement('li');
      row.className = 'tde-home-list-row';
      row.innerHTML =
        '<span class="tde-home-list-index">' + (idx + 1) + '.</span>' +
        '<span class="tde-home-list-order">' +
          '<button type="button" class="tde-order-btn" title="Move up" onclick="tdeMoveHomeListItem(this, -1)">↑</button>' +
          '<button type="button" class="tde-order-btn" title="Move down" onclick="tdeMoveHomeListItem(this, 1)">↓</button>' +
        '</span>' +
        '<input type="text" value="' + escHtml(title) + '" placeholder="Event title" oninput="tdeHomeListInput()">' +
        '<button type="button" class="tde-remove-event" onclick="tdeRemoveHomeListItem(this)">Remove</button>';
      list.appendChild(row);
    });
    Array.from(list.querySelectorAll('.tde-home-list-row')).forEach(function(row, idx, rows) {
      var up = row.querySelector('.tde-order-btn[title="Move up"]');
      var down = row.querySelector('.tde-order-btn[title="Move down"]');
      if (up) up.disabled = idx === 0;
      if (down) down.disabled = idx === rows.length - 1;
    });
    updateHomeListDirtyState();
  }

  window.tdeHomeListInput = function() {
    updateHomeListDirtyState();
  };

  window.tdeAddHomeListItem = function() {
    _homeList = getHomeListValues();
    _homeList.push('');
    renderHomeListEditor();
    var inputs = document.querySelectorAll('#tde-home-list input');
    if (inputs.length) inputs[inputs.length - 1].focus();
    updateHomeListDirtyState();
  };

  window.tdeRemoveHomeListItem = function(btn) {
    var row = btn && btn.closest('.tde-home-list-row');
    if (!row) return;
    row.remove();
    _homeList = getHomeListValues();
    renderHomeListEditor();
    updateHomeListDirtyState();
  };

  window.tdeMoveHomeListItem = function(btn, direction) {
    var row = btn && btn.closest('.tde-home-list-row');
    if (!row) return;
    var sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
    if (!sibling) return;
    if (direction < 0) row.parentNode.insertBefore(row, sibling);
    else row.parentNode.insertBefore(sibling, row);
    _homeList = getHomeListValues();
    renderHomeListEditor();
    updateHomeListDirtyState();
  };

  window.tdeSaveHomeList = function() {
    var save = document.getElementById('tde-home-list-save');
    var items = getHomeListValues();
    if (!items.length) {
      setHomeListStatus('Add at least one title before saving.', 'err');
      return;
    }
    if (save) save.disabled = true;
    setHomeListStatus('Saving…', 'loading');
    var body = new FormData();
    body.append('action', 'tn_save_home_event_list');
    body.append('nonce', HOME_LIST_NONCE);
    body.append('items', JSON.stringify(items));
    fetch(AJAX_URL, { method: 'POST', body: body })
      .then(function(r){ return r.json(); })
      .then(function(d) {
        if (!d.success) throw new Error(d.data || 'Save failed');
        _homeList = d.data.items || [];
        _homeListOrig = JSON.stringify(_homeList);
        renderHomeListEditor();
        setHomeListStatus('Homepage list saved.', 'ok');
      })
      .catch(function(e) {
        setHomeListStatus('Error: ' + e.message, 'err');
        if (save) save.disabled = false;
      });
  };

  renderHomeListEditor();

  function setHomepageSectionsStatus(msg, type) {
    var el = document.getElementById('tde-home-sections-status');
    if (!el) return;
    el.textContent = msg;
    el.className = 'tde-status ' + type;
    el.style.display = 'inline-block';
    if (type === 'ok') setTimeout(function(){ el.style.display = 'none'; }, 6000);
  }

  function getHomepageSectionValues() {
    return Array.from(document.querySelectorAll('#tde-home-sections .tde-home-section-row')).map(function(row) {
      var cb = row.querySelector('[data-home-section-visible]');
      return {
        key: row.getAttribute('data-section-key') || '',
        visible: cb ? cb.checked : true
      };
    }).filter(function(item) {
      return item.key && _homepageSectionDefinitions[item.key];
    });
  }

  function updateHomepageSectionsDirtyState() {
    var save = document.getElementById('tde-home-sections-save');
    if (!save) return;
    save.disabled = JSON.stringify(getHomepageSectionValues()) === _homepageSectionsOrig;
  }

  function renderHomepageSectionsEditor() {
    var list = document.getElementById('tde-home-sections');
    if (!list) return;
    list.innerHTML = '';
    _homepageSections.forEach(function(section, idx) {
      var def = _homepageSectionDefinitions[section.key];
      if (!def) return;
      var row = document.createElement('li');
      row.className = 'tde-home-list-row tde-home-section-row';
      row.setAttribute('data-section-key', section.key);
      row.innerHTML =
        '<span class="tde-home-list-index">' + (idx + 1) + '.</span>' +
        '<span class="tde-home-list-order">' +
          '<button type="button" class="tde-order-btn" title="Move up" onclick="tdeMoveHomepageSection(this, -1)">↑</button>' +
          '<button type="button" class="tde-order-btn" title="Move down" onclick="tdeMoveHomepageSection(this, 1)">↓</button>' +
        '</span>' +
        '<span class="tde-home-section-name">' + escHtml(def.label || section.key) +
          '<span class="tde-home-section-meta">' + escHtml(def.selector || '') + '</span>' +
        '</span>' +
        '<label class="tde-home-section-visible"><input type="checkbox" data-home-section-visible onchange="tdeHomepageSectionChanged()"' + (section.visible === false ? '' : ' checked') + '> Show</label>';
      list.appendChild(row);
    });
    Array.from(list.querySelectorAll('.tde-home-section-row')).forEach(function(row, idx, rows) {
      var up = row.querySelector('.tde-order-btn[title="Move up"]');
      var down = row.querySelector('.tde-order-btn[title="Move down"]');
      if (up) up.disabled = idx === 0;
      if (down) down.disabled = idx === rows.length - 1;
    });
    updateHomepageSectionsDirtyState();
  }

  window.tdeHomepageSectionChanged = function() {
    updateHomepageSectionsDirtyState();
  };

  window.tdeMoveHomepageSection = function(btn, direction) {
    var row = btn && btn.closest('.tde-home-section-row');
    if (!row) return;
    var sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
    if (!sibling) return;
    if (direction < 0) row.parentNode.insertBefore(row, sibling);
    else row.parentNode.insertBefore(sibling, row);
    _homepageSections = getHomepageSectionValues();
    renderHomepageSectionsEditor();
    updateHomepageSectionsDirtyState();
  };

  window.tdeSaveHomepageSections = function() {
    var save = document.getElementById('tde-home-sections-save');
    var sections = getHomepageSectionValues();
    if (!sections.length) {
      setHomepageSectionsStatus('No sections found to save.', 'err');
      return;
    }
    if (save) save.disabled = true;
    setHomepageSectionsStatus('Saving…', 'loading');
    var body = new FormData();
    body.append('action', 'tn_save_homepage_sections');
    body.append('nonce', HOMEPAGE_SECTIONS_NONCE);
    body.append('sections', JSON.stringify(sections));
    fetch(AJAX_URL, { method: 'POST', body: body })
      .then(function(r){ return r.json(); })
      .then(function(d) {
        if (!d.success) throw new Error(d.data || 'Save failed');
        _homepageSections = d.data.sections || [];
        _homepageSectionsOrig = JSON.stringify(_homepageSections);
        renderHomepageSectionsEditor();
        setHomepageSectionsStatus('Homepage sections saved.', 'ok');
      })
      .catch(function(e) {
        setHomepageSectionsStatus('Error: ' + e.message, 'err');
        if (save) save.disabled = false;
      });
  };

  renderHomepageSectionsEditor();

  /* ── Mode Toggle ── */
  window.tdeToggleMode = function(cb) {
    var mode = cb.checked ? 'on' : 'off';
    var body = new FormData();
    body.append('action', 'tn_set_schedule_mode');
    body.append('nonce', MODE_NONCE);
    body.append('mode', mode);
    fetch(AJAX_URL, { method: 'POST', body: body })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success) {
          _scheduleMode = (d.data.mode === 'on');
          var statusEl = document.getElementById('tde-mode-status');
          statusEl.textContent = _scheduleMode ? 'Schedule Live' : 'Pre-Event';
          statusEl.className = 'tde-mode-status ' + (_scheduleMode ? 'on' : 'off');
          document.getElementById('tde-mode-desc').textContent = _scheduleMode
            ? 'Times are visible to attendees on the website. Full editing enabled.'
            : 'Times are hidden from the public. Toggle on when ready to publish the schedule.';
          tdeLoad(); // re-render with new mode
        }
      });
  };

  /* ── Load ── */
  function tdeLoad() {
    document.getElementById('tde-loading').style.display = 'block';
    document.getElementById('tde-content').style.display = 'none';
    setStatus('Loading…', 'loading');

    fetch(API + '?context=edit&_fields=meta', { headers: { 'X-WP-Nonce': NONCE } })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (!d.meta || !d.meta._elementor_data) throw new Error('No Elementor data found');
      var eData = JSON.parse(d.meta._elementor_data);
      _eData = Array.isArray(eData) ? eData : [eData];

      var html = findHtmlInTree(_eData);
      if (!html) throw new Error('Could not locate HTML widget in Elementor data');

      _orig = {};
      _orderDirty = false;
      renderEditor(html);
      document.getElementById('tde-loading').style.display = 'none';
      document.getElementById('tde-content').style.display = 'block';
      setStatus('Loaded ✓', 'ok');
    })
    .catch(function(e){
      document.getElementById('tde-loading').style.display = 'none';
      setStatus('Error: ' + e.message, 'err');
    });
  }
  window.tdeLoad = tdeLoad;

  function findHtmlInTree(nodes) {
    var fallback = null;
    for (var i = 0; i < nodes.length; i++) {
      var n = nodes[i];
      if (n.widgetType === 'html' && n.settings && n.settings.html) {
        if (n.settings.html.indexOf('schedule-item') !== -1) return n.settings.html;
        if (!fallback) fallback = n.settings.html;
      }
      if (n.elements && n.elements.length) { var r = findHtmlInTree(n.elements); if (r && r.indexOf('schedule-item') !== -1) return r; }
    }
    return fallback;
  }

  /* ── Render ── */
  function buildPresenterRow(key, idx, presenter) {
    var sid = escId(key);
    presenter = presenter || { name: '', bio: '', photo: '' };
    var baseId = 'presenter-' + sid + '-' + idx;
    var out = '';
    out += '<div class="tde-presenter-row" data-presenter-index="' + idx + '">';
    out += '<div>';
    out += '<label for="' + baseId + '-name">Name</label>';
    out += '<input type="text" id="' + baseId + '-name" data-key="' + escHtml(key) + '" data-presenter-field="name" value="' + escHtml(presenter.name || '') + '" oninput="tdePresenterChange(this)">';
    out += '</div>';
    out += '<div>';
    out += '<label>Photo</label>';
    out += '<div class="tde-presenter-photo-field">';
    if (presenter.photo) {
      out += '<img class="tde-presenter-photo-preview" src="' + escHtml(presenter.photo) + '" alt="">';
    } else {
      out += '<div class="tde-presenter-photo-preview is-empty">No photo</div>';
    }
    out += '<div class="tde-presenter-photo-actions">';
    out += '<input type="hidden" id="' + baseId + '-photo" data-key="' + escHtml(key) + '" data-presenter-field="photo" value="' + escHtml(presenter.photo || '') + '">';
    out += '<button type="button" class="tde-presenter-photo-btn" onclick="tdeChoosePresenterPhoto(this)">Choose</button>';
    out += '<button type="button" class="tde-presenter-photo-btn" onclick="tdeRemovePresenterPhoto(this)">Remove</button>';
    out += '</div>';
    out += '</div>';
    out += '</div>';
    out += '<button type="button" class="tde-presenter-remove" onclick="tdeRemovePresenter(this)">Remove</button>';
    out += '<div class="tde-presenter-bio-field">';
    out += '<label for="' + baseId + '-bio">Bio</label>';
    out += '<div class="tde-rich-toolbar" data-key="' + escHtml(key) + '">';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichCommand(this, \'bold\')" title="Bold"><strong>B</strong></button>';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichCommand(this, \'italic\')" title="Italic"><em>I</em></button>';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichCommand(this, \'insertUnorderedList\')" title="Bulleted list">• List</button>';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichLink(this)" title="Add link">Link</button>';
    out += '</div>';
    out += '<div id="' + baseId + '-bio" class="tde-rich-bio" data-key="' + escHtml(key) + '" data-presenter-field="bio" contenteditable="true" oninput="tdeRichChange(this)">' + normalizeDescriptionHtml(presenter.bio || '') + '</div>';
    out += '</div>';
    out += '</div>';
    return out;
  }

  function buildPresentersEditor(key, presenters) {
    var sid = escId(key);
    presenters = normalizePresenters(presenters);
    var out = '';
    out += '<div class="tde-presenters" id="presenters-' + sid + '" data-key="' + escHtml(key) + '">';
    out += '<div class="tde-presenters-head">';
    out += '<span class="tde-presenters-title">Presenter(s)</span>';
    out += '<button type="button" class="tde-presenter-add" data-key="' + escHtml(key) + '" onclick="tdeAddPresenterFromButton(this)">+ Add Presenter</button>';
    out += '</div>';
    out += '<div class="tde-presenter-list">';
    presenters.forEach(function(p, idx) { out += buildPresenterRow(key, idx, p); });
    out += '</div>';
    out += '</div>';
    return out;
  }

  function buildSessionRow(key, idx, session) {
    var sid = escId(key);
    session = session || { label: '', start: '', end: '', location: '', full: false };
    session.location = normalizeLocation(session.location);
    session.full = !!session.full;
    var baseId = 'session-' + sid + '-' + idx;
    var out = '';
    out += '<div class="tde-session-row" data-session-index="' + idx + '">';
    out += '<div>';
    out += '<label for="' + baseId + '-label">Session</label>';
    out += '<input type="text" id="' + baseId + '-label" data-key="' + escHtml(key) + '" data-session-field="label" value="' + escHtml(session.label || '') + '" placeholder="Prelim Flight A" oninput="tdeSessionChange(this)">';
    out += '</div>';
    out += '<div>';
    out += '<label for="' + baseId + '-start">Start</label>';
    out += '<input type="text" id="' + baseId + '-start" data-key="' + escHtml(key) + '" data-session-field="start" value="' + escHtml(session.start || '') + '" placeholder="9:00 AM" oninput="tdeSessionChange(this)">';
    out += '</div>';
    out += '<div>';
    out += '<label for="' + baseId + '-end">End</label>';
    out += '<input type="text" id="' + baseId + '-end" data-key="' + escHtml(key) + '" data-session-field="end" value="' + escHtml(session.end || '') + '" placeholder="10:30 AM" oninput="tdeSessionChange(this)">';
    out += '</div>';
    out += '<div>';
    out += '<label for="' + baseId + '-location">Location</label>';
    out += '<select id="' + baseId + '-location" data-key="' + escHtml(key) + '" data-session-field="location" onchange="tdeSessionChange(this)">';
    out += '<option value="">Location TBA</option>';
    Object.keys(LOCATION_OPTIONS).forEach(function(value) {
      out += '<option value="' + escHtml(value) + '"' + (session.location === value ? ' selected' : '') + '>' + escHtml(LOCATION_OPTIONS[value]) + '</option>';
    });
    out += '</select>';
    out += '</div>';
    out += '<div class="tde-session-full">';
    out += '<label for="' + baseId + '-full"><input type="checkbox" id="' + baseId + '-full" data-key="' + escHtml(key) + '" data-session-field="full" ' + (session.full ? ' checked' : '') + ' onchange="tdeSessionChange(this)"> Full</label>';
    out += '</div>';
    out += '<button type="button" class="tde-session-remove" onclick="tdeRemoveSession(this)">Remove</button>';
    out += '</div>';
    return out;
  }

  function buildSessionsEditor(key, sessions) {
    var sid = escId(key);
    sessions = normalizeSessions(sessions);
    var out = '';
    out += '<div class="tde-sessions" id="sessions-' + sid + '" data-key="' + escHtml(key) + '">';
    out += '<div class="tde-sessions-head">';
    out += '<span class="tde-sessions-title">Sessions</span>';
    out += '<button type="button" class="tde-session-add" data-key="' + escHtml(key) + '" onclick="tdeAddSessionFromButton(this)">+ Add Session</button>';
    out += '</div>';
    out += '<div class="tde-session-list">';
    sessions.forEach(function(session, idx) { out += buildSessionRow(key, idx, session); });
    out += '</div>';
    out += '</div>';
    return out;
  }

  function buildDayTransferControls(dayId, key) {
    var out = '';
    out += '<span class="tde-day-transfer">';
    out += '<select data-transfer-day="' + escHtml(key) + '" aria-label="Target day">';
    DAY_OPTIONS.forEach(function(day) {
      out += '<option value="' + escHtml(day.id) + '"' + (day.id === dayId ? ' selected' : '') + '>' + escHtml(day.shortLabel) + '</option>';
    });
    out += '</select>';
    out += '<button type="button" class="tde-transfer-btn" onclick="tdeMoveEventToDay(this)">Move</button>';
    out += '<button type="button" class="tde-transfer-btn" onclick="tdeCloneEventToDay(this)">Clone</button>';
    out += '</span>';
    return out;
  }

  function buildEventCard(dayId, key, title, tagLabel, tagClass, eventType, desc, image, imageAlt, infoUrl, presenters, sessions, start, end, location, afterHours, isNew) {
    var sid = escId(key);
    var out = '';
    desc = normalizeDescriptionHtml(desc);
    image = normalizeImageUrl(image);
    imageAlt = normalizeImageAlt(imageAlt);
    eventType = normalizeEventType(eventType);
    location = normalizeLocation(location);
    afterHours = normalizeBool(afterHours);

    out += '<div class="tde-card' + (isNew ? ' changed' : '') + '" id="card-' + sid + '" data-key="' + escHtml(key) + '" data-day="' + escHtml(dayId) + '"' + (isNew ? ' data-new="1"' : '') + '>';

    out += '<div class="tde-event-actions">';
    if (isNew) out += '<span class="tde-new-badge">New event</span>';
    out += '<span class="tde-order-controls">';
    out += '<button type="button" class="tde-order-btn" onclick="tdeMoveEvent(this, -1)" title="Move up">↑</button>';
    out += '<button type="button" class="tde-order-btn" onclick="tdeMoveEvent(this, 1)" title="Move down">↓</button>';
    out += '</span>';
    out += buildDayTransferControls(dayId, key);
    out += '<button type="button" class="tde-remove-event" onclick="tdeRemoveEvent(this)">Remove Event</button>';
    out += '<button type="button" class="tde-undo-remove" onclick="tdeUndoRemoveEvent(this)">Undo Remove</button>';
    out += '</div>';
    out += '<div class="tde-delete-note">Marked for removal. Click “Save All Changes” to remove this event from the public schedule.</div>';

    if (!_scheduleMode && isNew) {
      out += '<div class="tde-event-header">';
      out += '<span class="tde-event-name">New event</span>';
      out += '</div>';
    }

    if (!_scheduleMode && !isNew) {
      out += '<div class="tde-event-header">';
      if (tagLabel) out += '<span class="tde-tag ' + escHtml(tagClass) + '">' + escHtml(tagLabel) + '</span>';
      out += '</div>';
    }

    out += '<div class="tde-fields">';

    out += '<div class="tde-field tde-field-title">';
    out += '<label>Event Title</label>';
    out += '<input type="text" id="f-title-' + sid + '" data-key="' + escHtml(key) + '" data-field="title" value="' + escHtml(title) + '" oninput="tdeFieldChange(this)">';
    out += '</div>';

    out += '<div class="tde-field tde-field-start">';
    out += '<label>Start</label>';
    out += '<input type="text" id="f-start-' + sid + '" data-key="' + escHtml(key) + '" data-field="start" value="' + escHtml(start) + '" placeholder="e.g. 9:00 AM" oninput="tdeFieldChange(this)">';
    out += '</div>';

    out += '<div class="tde-field tde-field-end">';
    out += '<label>End</label>';
    out += '<input type="text" id="f-end-' + sid + '" data-key="' + escHtml(key) + '" data-field="end" value="' + escHtml(end) + '" placeholder="e.g. 10:30 AM" oninput="tdeFieldChange(this)">';
    out += '</div>';

    out += '<div class="tde-field tde-field-location">';
    out += '<label>Location</label>';
    out += '<select id="f-location-' + sid + '" data-key="' + escHtml(key) + '" data-field="location" onchange="tdeFieldChange(this)">';
    out += '<option value="">Location TBA</option>';
    Object.keys(LOCATION_OPTIONS).forEach(function(value) {
      out += '<option value="' + escHtml(value) + '"' + (location === value ? ' selected' : '') + '>' + escHtml(LOCATION_OPTIONS[value]) + '</option>';
    });
    out += '</select>';
    out += '</div>';

    out += '<div class="tde-field tde-field-after-hours">';
    out += '<label><input type="checkbox" id="f-after-hours-' + sid + '" data-key="' + escHtml(key) + '" data-field="afterHours" onchange="tdeFieldChange(this)"' + (afterHours ? ' checked' : '') + '> After Hours</label>';
    out += '</div>';

    out += '<div class="tde-field tde-field-tag">';
    out += '<label>Category</label>';
    out += '<select id="f-tag-' + sid + '" data-key="' + escHtml(key) + '" data-field="tag" onchange="tdeFieldChange(this)">';
    TAG_OPTIONS.forEach(function(opt) {
      out += '<option value="' + opt.value + '"' + (tagClass === opt.value ? ' selected' : '') + '>' + escHtml(opt.label) + '</option>';
    });
    out += '</select>';
    out += '</div>';

    out += '<div class="tde-field tde-field-event-type">';
    out += '<label>Event Type</label>';
    out += '<select id="f-event-type-' + sid + '" data-key="' + escHtml(key) + '" data-field="eventType" onchange="tdeFieldChange(this)">';
    Object.keys(EVENT_TYPE_OPTIONS).forEach(function(value) {
      var opt = EVENT_TYPE_OPTIONS[value] || {};
      out += '<option value="' + escHtml(value) + '"' + (eventType === value ? ' selected' : '') + '>' + escHtml(opt.label || value) + '</option>';
    });
    out += '</select>';
    out += '</div>';
    out += '</div>';

    out += '<div class="tde-rich-toolbar" data-key="' + escHtml(key) + '">';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichCommand(this, \'bold\')" title="Bold"><strong>B</strong></button>';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichCommand(this, \'italic\')" title="Italic"><em>I</em></button>';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichCommand(this, \'insertUnorderedList\')" title="Bulleted list">• List</button>';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichLink(this)" title="Add link">Link</button>';
    out += '<button type="button" onmousedown="event.preventDefault()" onclick="tdeRichImage(this)" title="Insert image">Image</button>';
    out += '</div>';
    out += '<div id="ta-' + sid + '" class="tde-rich-desc" data-key="' + escHtml(key) + '" data-field="desc" contenteditable="true" oninput="tdeRichChange(this)">' + desc + '</div>';
    out += '<div class="tde-char-count" id="cc-' + sid + '">' + stripTags(desc).length + ' chars</div>';
    out += '<div class="tde-event-graphic" id="graphic-' + sid + '">';
    if (image) {
      out += '<img class="tde-event-graphic-preview" src="' + escHtml(image) + '" alt="">';
    } else {
      out += '<div class="tde-event-graphic-preview is-empty">No graphic</div>';
    }
    out += '<div>';
    out += '<label>Event Graphic</label>';
    out += '<div class="tde-event-graphic-actions">';
    out += '<input type="hidden" id="f-image-' + sid + '" data-key="' + escHtml(key) + '" data-field="image" value="' + escHtml(image) + '">';
    out += '<button type="button" class="tde-event-graphic-btn" onclick="tdeChooseEventGraphic(this)">Choose</button>';
    out += '<button type="button" class="tde-event-graphic-btn" onclick="tdeRemoveEventGraphic(this)">Remove</button>';
    out += '</div>';
    out += '</div>';
    out += '<div class="tde-event-graphic-alt">';
    out += '<label for="f-image-alt-' + sid + '">Alt Text</label>';
    out += '<input type="text" id="f-image-alt-' + sid + '" data-key="' + escHtml(key) + '" data-field="imageAlt" value="' + escHtml(imageAlt) + '" placeholder="Brief description of the graphic" oninput="tdeFieldChange(this)">';
    out += '</div>';
    out += '</div>';
    out += '<div class="tde-info-url">';
    out += '<label for="f-info-' + sid + '">More Info URL</label>';
    out += '<input type="text" id="f-info-' + sid + '" data-key="' + escHtml(key) + '" data-field="infoUrl" value="' + escHtml(infoUrl || '') + '" placeholder="https://trivianationals.org/event-page/ or /event-page/" oninput="tdeFieldChange(this)">';
    out += '</div>';
    out += buildSessionsEditor(key, sessions);
    out += buildPresentersEditor(key, presenters);
    out += '</div>';

    return out;
  }

  function renderEditor(html) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');
    var out = '';

    DAY_OPTIONS.forEach(function(day) {
      var dayEl = doc.getElementById(day.id);
      if (!dayEl) return;
      var items = dayEl.querySelectorAll('.schedule-item[data-title]');
      out += '<div class="tde-day" id="tde-day-' + escHtml(day.id) + '" data-day="' + escHtml(day.id) + '">';
      out += '<div class="tde-day-header"><span class="tde-day-title">' + escHtml(day.label) + ' <span class="tde-day-count" data-day-count="' + escHtml(day.id) + '"></span></span><span class="tde-day-tools"><button type="button" class="tde-collapse-day" onclick="tdeToggleDay(\'' + escHtml(day.id) + '\', this)">Collapse</button><button type="button" class="tde-add-event" onclick="tdeAddEvent(\'' + escHtml(day.id) + '\')">+ Add Event</button></span></div>';
      out += '<div class="tde-day-items">';

      items.forEach(function(item, idx) {
        var title    = item.getAttribute('data-title') || '';
        var tagLabel = item.getAttribute('data-tag-label') || '';
        var tagClass = item.getAttribute('data-tag-class') || 'tag-special';
        var eventType = normalizeEventType(item.getAttribute('data-event-type') || '');
        var desc     = item.getAttribute('data-desc') || '';
        var image    = item.getAttribute('data-image') || '';
        var imageAlt = item.getAttribute('data-image-alt') || '';
        var infoUrl  = item.getAttribute('data-info-url') || '';
        var presenters = normalizePresenters(item.getAttribute('data-presenters') || '');
        var sessions = normalizeSessions(item.getAttribute('data-sessions') || '');
        var start    = item.getAttribute('data-start') || '';
        var end      = item.getAttribute('data-end') || '';
        var location = item.getAttribute('data-location') || '';
        var afterHours = normalizeBool(item.getAttribute('data-after-hours') || '');

        var key = day.id + '|' + idx;
        _orig[key] = { title: title, desc: normalizeDescriptionHtml(desc), image: image, imageAlt: imageAlt, infoUrl: infoUrl, presenters: presenters, sessions: sessions, start: start, end: end, location: normalizeLocation(location), afterHours: afterHours, tagLabel: tagLabel, tagClass: tagClass, eventType: eventType };
        out += buildEventCard(day.id, key, title, tagLabel, tagClass, eventType, desc, image, imageAlt, infoUrl, presenters, sessions, start, end, location, afterHours, false);
      });

      out += '</div></div>'; // end .tde-day-items, .tde-day
    });

    document.getElementById('tde-content').innerHTML = out;
    renderDayNav();
    updateOrderButtons();
    updateDayCounts();
    updateCount();
  }

  function renderDayNav() {
    var nav = document.getElementById('tde-admin-nav');
    var jumps = document.getElementById('tde-day-jumps');
    if (!nav || !jumps) return;
    jumps.innerHTML = DAY_OPTIONS.map(function(day) {
      return '<button type="button" class="tde-day-jump" data-day-jump="' + escHtml(day.id) + '" onclick="tdeJumpToDay(\'' + escHtml(day.id) + '\')">' + escHtml(day.shortLabel) + ' <span data-nav-count="' + escHtml(day.id) + '"></span></button>';
    }).join('');
    nav.style.display = 'flex';
  }

  function updateDayCounts() {
    DAY_OPTIONS.forEach(function(day) {
      var section = document.getElementById('tde-day-' + day.id);
      var count = section ? section.querySelectorAll('.tde-card:not([data-delete="1"])').length : 0;
      document.querySelectorAll('[data-day-count="' + day.id + '"]').forEach(function(el) {
        el.textContent = count + ' event' + (count === 1 ? '' : 's');
      });
      document.querySelectorAll('[data-nav-count="' + day.id + '"]').forEach(function(el) {
        el.textContent = '(' + count + ')';
      });
    });
  }

  window.tdeJumpToDay = function(dayId) {
    var section = document.getElementById('tde-day-' + dayId);
    if (!section) return;
    section.classList.remove('is-collapsed');
    var btn = section.querySelector('.tde-collapse-day');
    if (btn) btn.textContent = 'Collapse';
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  window.tdeToggleDay = function(dayId, btn) {
    var section = document.getElementById('tde-day-' + dayId);
    if (!section) return;
    var collapsed = section.classList.toggle('is-collapsed');
    if (btn) btn.textContent = collapsed ? 'Expand' : 'Collapse';
  };

  window.tdeExpandAllDays = function() {
    document.querySelectorAll('.tde-day').forEach(function(section) {
      section.classList.remove('is-collapsed');
      var btn = section.querySelector('.tde-collapse-day');
      if (btn) btn.textContent = 'Collapse';
    });
  };

  window.tdeCollapseAllDays = function() {
    document.querySelectorAll('.tde-day').forEach(function(section) {
      section.classList.add('is-collapsed');
      var btn = section.querySelector('.tde-collapse-day');
      if (btn) btn.textContent = 'Expand';
    });
  };

  window.tdeFilterEvents = function(query) {
    var needle = String(query || '').trim().toLowerCase();
    document.querySelectorAll('.tde-day').forEach(function(section) {
      var visible = 0;
      section.classList.remove('is-collapsed');
      var collapseBtn = section.querySelector('.tde-collapse-day');
      if (collapseBtn) collapseBtn.textContent = 'Collapse';
      section.querySelectorAll('.tde-card').forEach(function(card) {
        var text = card.textContent.toLowerCase();
        var matched = !needle || text.indexOf(needle) !== -1;
        card.classList.toggle('is-filtered-out', !matched);
        if (matched) visible++;
      });
      section.classList.toggle('is-filtered-out', needle && visible === 0);
    });
  };

  function updateOrderButtons() {
    document.querySelectorAll('#tde-content .tde-day-items').forEach(function(list) {
      var cards = Array.from(list.querySelectorAll(':scope > .tde-card'));
      cards.forEach(function(card, idx) {
        var up = card.querySelector('.tde-order-btn[title="Move up"]');
        var down = card.querySelector('.tde-order-btn[title="Move down"]');
        if (up) up.disabled = idx === 0;
        if (down) down.disabled = idx === cards.length - 1;
      });
    });
  }

  window.tdeAddEvent = function(dayId) {
    var dayItems = document.querySelector('#tde-day-' + dayId + ' .tde-day-items');
    if (!dayItems) return;

    var key = dayId + '|new|' + Date.now();
    var tagClass = 'tag-competition';
    _orig[key] = {
      title: '', desc: '', image: '', imageAlt: '', infoUrl: '', presenters: [], sessions: [], start: '', end: '', location: '', afterHours: false,
      tagLabel: getTagLabel(tagClass), tagClass: tagClass, eventType: DEFAULT_EVENT_TYPE, isNew: true
    };
    dayItems.insertAdjacentHTML('beforeend', buildEventCard(dayId, key, '', getTagLabel(tagClass), tagClass, DEFAULT_EVENT_TYPE, '', '', '', '', [], [], '', '', '', false, true));
    var titleEl = document.getElementById('f-title-' + escId(key));
    if (titleEl) titleEl.focus();
    updateOrderButtons();
    updateDayCounts();
    updateCount();
  };

  window.tdeMoveEvent = function(btn, direction) {
    var card = btn.closest('.tde-card');
    if (!card) return;
    var sibling = direction < 0 ? card.previousElementSibling : card.nextElementSibling;
    if (!sibling || !sibling.classList.contains('tde-card')) return;

    if (direction < 0) {
      card.parentNode.insertBefore(card, sibling);
    } else {
      card.parentNode.insertBefore(sibling, card);
    }

    _orderDirty = true;
    card.classList.add('changed');
    updateOrderButtons();
    updateDayCounts();
    updateCount();
  };

  function getTransferTargetDay(btn) {
    var card = btn && btn.closest('.tde-card');
    if (!card) return '';
    var select = card.querySelector('[data-transfer-day]');
    return select ? select.value : '';
  }

  function setCardTransferDay(card, dayId) {
    var select = card && card.querySelector('[data-transfer-day]');
    if (select) select.value = dayId;
  }

  window.tdeMoveEventToDay = function(btn) {
    var card = btn && btn.closest('.tde-card');
    if (!card) return;
    var targetDay = getTransferTargetDay(btn);
    var targetList = document.querySelector('#tde-day-' + targetDay + ' .tde-day-items');
    if (!targetList) return;
    var currentDay = card.getAttribute('data-day') || '';
    if (targetDay === currentDay) {
      setStatus('That event is already on that day.', 'ok');
      return;
    }
    targetList.appendChild(card);
    card.setAttribute('data-day', targetDay);
    setCardTransferDay(card, targetDay);
    card.classList.add('changed');
    _orderDirty = true;
    updateOrderButtons();
    updateDayCounts();
    updateCount();
    tdeJumpToDay(targetDay);
  };

  window.tdeCloneEventToDay = function(btn) {
    var card = btn && btn.closest('.tde-card');
    if (!card) return;
    var targetDay = getTransferTargetDay(btn);
    var targetList = document.querySelector('#tde-day-' + targetDay + ' .tde-day-items');
    if (!targetList) return;
    var key = card.getAttribute('data-key');
    var sid = escId(key);
    var cur = getCurrentValues(key, sid);
    var newKey = targetDay + '|clone|' + Date.now();
    _orig[newKey] = {
      title: '', desc: '', image: '', imageAlt: '', infoUrl: '', presenters: [], sessions: [], start: '', end: '', location: '', afterHours: false,
      tagLabel: getTagLabel(cur.tagClass || 'tag-competition'), tagClass: cur.tagClass || 'tag-competition', eventType: normalizeEventType(cur.eventType), isNew: true
    };
    targetList.insertAdjacentHTML('beforeend', buildEventCard(
      targetDay,
      newKey,
      cur.title,
      cur.tagLabel || getTagLabel(cur.tagClass || 'tag-competition'),
      cur.tagClass || 'tag-competition',
      normalizeEventType(cur.eventType),
      cur.desc,
      cur.image,
      cur.imageAlt,
      cur.infoUrl,
      cur.presenters,
      cur.sessions,
      cur.start,
      cur.end,
      cur.location,
      cur.afterHours,
      true
    ));
    _orderDirty = true;
    updateOrderButtons();
    updateDayCounts();
    updateCount();
    tdeJumpToDay(targetDay);
  };

  window.tdeRemoveEvent = function(btn) {
    var card = btn && btn.closest('.tde-card');
    if (!card) return;
    var key = card.getAttribute('data-key');
    var sid = escId(key);
    var orig = _orig[key] || {};
    var cur = getCurrentValues(key, sid);
    var title = (cur.title || orig.title || 'this event').trim();

    if (card.getAttribute('data-new') === '1') {
      if (!confirm('Discard this unsaved event?')) return;
      card.remove();
      delete _orig[key];
      updateOrderButtons();
      updateDayCounts();
      updateCount();
      return;
    }

    if (!confirm('Remove "' + title + '" from the schedule? It will not be removed from the website until you click Save All Changes.')) return;
    card.setAttribute('data-delete', '1');
    card.classList.add('deleted', 'changed');
    updateOrderButtons();
    updateDayCounts();
    updateCount();
  };

  window.tdeUndoRemoveEvent = function(btn) {
    var card = btn && btn.closest('.tde-card');
    if (!card) return;
    var key = card.getAttribute('data-key');
    card.removeAttribute('data-delete');
    card.classList.remove('deleted');
    refreshCardChangeState(key);
    updateDayCounts();
  };

  window.tdeAddPresenterFromButton = function(btn) {
    if (!btn) return;
    tdeAddPresenter(btn.getAttribute('data-key'));
  };

  window.tdeAddPresenter = function(key) {
    var wrap = document.getElementById('presenters-' + escId(key));
    if (!wrap) return;
    var list = wrap.querySelector('.tde-presenter-list');
    if (!list) return;
    var idx = list.querySelectorAll('.tde-presenter-row').length;
    list.insertAdjacentHTML('beforeend', buildPresenterRow(key, idx, { name: '', bio: '', photo: '' }));
    var firstInput = list.querySelector('.tde-presenter-row:last-child input');
    if (firstInput) firstInput.focus();
    markPresenterCardChanged(key);
  };

  window.tdeRemovePresenter = function(btn) {
    var row = btn && btn.closest('.tde-presenter-row');
    var wrap = btn && btn.closest('.tde-presenters');
    if (!row || !wrap) return;
    var key = wrap.getAttribute('data-key');
    row.remove();
    markPresenterCardChanged(key);
  };

  window.tdePresenterChange = function(el) {
    var key = el.getAttribute('data-key');
    markPresenterCardChanged(key);
  };

  window.tdeAddSessionFromButton = function(btn) {
    if (!btn) return;
    tdeAddSession(btn.getAttribute('data-key'));
  };

  window.tdeAddSession = function(key) {
    var wrap = document.getElementById('sessions-' + escId(key));
    if (!wrap) return;
    var list = wrap.querySelector('.tde-session-list');
    if (!list) return;
    var idx = list.querySelectorAll('.tde-session-row').length;
    list.insertAdjacentHTML('beforeend', buildSessionRow(key, idx, { label: '', start: '', end: '', location: '' }));
    var firstInput = list.querySelector('.tde-session-row:last-child input');
    if (firstInput) firstInput.focus();
    markSessionCardChanged(key);
  };

  window.tdeRemoveSession = function(btn) {
    var row = btn && btn.closest('.tde-session-row');
    var wrap = btn && btn.closest('.tde-sessions');
    if (!row || !wrap) return;
    var key = wrap.getAttribute('data-key');
    row.remove();
    markSessionCardChanged(key);
  };

  window.tdeSessionChange = function(el) {
    var key = el.getAttribute('data-key');
    markSessionCardChanged(key);
  };

  window.tdeRichChange = function(el) {
    if (!el) return;
    var key = el.getAttribute('data-key');
    var sid = escId(key);
    var counter = document.getElementById('cc-' + sid);
    if (counter) counter.textContent = stripTags(el.innerHTML).length + ' chars';
    refreshCardChangeState(key);
  };

  function richEditorForButton(btn) {
    var bioField = btn && btn.closest('.tde-presenter-bio-field');
    if (bioField) return bioField.querySelector('.tde-rich-bio');
    var card = btn && btn.closest('.tde-card');
    return card ? card.querySelector('.tde-rich-desc') : null;
  }

  window.tdeRichCommand = function(btn, command) {
    var editor = richEditorForButton(btn);
    if (!editor) return;
    editor.focus();
    document.execCommand(command, false, null);
    tdeRichChange(editor);
  };

  window.tdeRichLink = function(btn) {
    var editor = richEditorForButton(btn);
    if (!editor) return;
    var url = prompt('Link URL');
    if (!url) return;
    editor.focus();
    document.execCommand('createLink', false, url);
    editor.querySelectorAll('a').forEach(function(anchor) {
      anchor.setAttribute('target', '_blank');
      anchor.setAttribute('rel', 'noopener noreferrer');
    });
    tdeRichChange(editor);
  };

  window.tdeRichImage = function(btn) {
    if (!window.wp || !wp.media) {
      setStatus('Media library is not available on this screen.', 'err');
      return;
    }
    var editor = richEditorForButton(btn);
    if (!editor) return;
    var frame = wp.media({
      title: 'Insert description image',
      button: { text: 'Insert image' },
      library: { type: 'image' },
      multiple: false
    });
    frame.on('select', function() {
      var image = frame.state().get('selection').first();
      if (!image) return;
      var data = image.toJSON();
      var url = data.sizes && data.sizes.large ? data.sizes.large.url : data.url;
      if (!url) return;
      editor.focus();
      document.execCommand('insertHTML', false, '<img src="' + escHtml(url) + '" alt="' + escHtml(data.alt || data.title || '') + '">');
      tdeRichChange(editor);
    });
    frame.open();
  };

  window.tdeChoosePresenterPhoto = function(btn) {
    if (!window.wp || !wp.media) {
      setStatus('Media library is not available on this screen.', 'err');
      return;
    }
    var row = btn && btn.closest('.tde-presenter-row');
    if (!row) return;
    var input = row.querySelector('[data-presenter-field="photo"]');
    if (!input) return;
    var frame = wp.media({
      title: 'Choose presenter photo',
      button: { text: 'Use this photo' },
      library: { type: 'image' },
      multiple: false
    });
    frame.on('select', function() {
      var image = frame.state().get('selection').first();
      if (!image) return;
      var data = image.toJSON();
      var url = data.sizes && data.sizes.medium ? data.sizes.medium.url : data.url;
      input.value = url || '';
      updatePresenterPhotoPreview(row, input.value);
      markPresenterCardChanged(input.getAttribute('data-key'));
    });
    frame.open();
  };

  window.tdeRemovePresenterPhoto = function(btn) {
    var row = btn && btn.closest('.tde-presenter-row');
    if (!row) return;
    var input = row.querySelector('[data-presenter-field="photo"]');
    if (!input) return;
    input.value = '';
    updatePresenterPhotoPreview(row, '');
    markPresenterCardChanged(input.getAttribute('data-key'));
  };

  window.tdeChooseEventGraphic = function(btn) {
    if (!window.wp || !wp.media) {
      setStatus('Media library is not available on this screen.', 'err');
      return;
    }
    var card = btn && btn.closest('.tde-card');
    if (!card) return;
    var key = card.getAttribute('data-key');
    var input = card.querySelector('[data-field="image"]');
    var altInput = card.querySelector('[data-field="imageAlt"]');
    if (!input) return;
    var frame = wp.media({
      title: 'Choose event graphic',
      button: { text: 'Use this graphic' },
      library: { type: 'image' },
      multiple: false
    });
    frame.on('select', function() {
      var image = frame.state().get('selection').first();
      if (!image) return;
      var data = image.toJSON();
      var url = data.sizes && data.sizes.large ? data.sizes.large.url : data.url;
      input.value = url || '';
      if (altInput && !altInput.value) {
        altInput.value = data.alt || data.title || '';
      }
      updateEventGraphicPreview(card, input.value);
      refreshCardChangeState(key);
    });
    frame.open();
  };

  window.tdeRemoveEventGraphic = function(btn) {
    var card = btn && btn.closest('.tde-card');
    if (!card) return;
    var key = card.getAttribute('data-key');
    var input = card.querySelector('[data-field="image"]');
    var altInput = card.querySelector('[data-field="imageAlt"]');
    if (!input) return;
    input.value = '';
    if (altInput) altInput.value = '';
    updateEventGraphicPreview(card, '');
    refreshCardChangeState(key);
  };

  function updateEventGraphicPreview(card, url) {
    var current = card.querySelector('.tde-event-graphic-preview');
    if (!current) return;
    if (url) {
      if (current.tagName.toLowerCase() !== 'img') {
        var img = document.createElement('img');
        img.className = 'tde-event-graphic-preview';
        img.alt = '';
        current.replaceWith(img);
        current = img;
      }
      current.src = url;
      current.classList.remove('is-empty');
      current.textContent = '';
    } else {
      if (current.tagName.toLowerCase() === 'img') {
        var empty = document.createElement('div');
        empty.className = 'tde-event-graphic-preview is-empty';
        empty.textContent = 'No graphic';
        current.replaceWith(empty);
      } else {
        current.className = 'tde-event-graphic-preview is-empty';
        current.textContent = 'No graphic';
      }
    }
  }

  function updatePresenterPhotoPreview(row, url) {
    var current = row.querySelector('.tde-presenter-photo-preview');
    if (!current) return;
    if (url) {
      if (current.tagName.toLowerCase() !== 'img') {
        var img = document.createElement('img');
        img.className = 'tde-presenter-photo-preview';
        img.alt = '';
        current.replaceWith(img);
        current = img;
      }
      current.src = url;
      current.classList.remove('is-empty');
      current.textContent = '';
    } else {
      if (current.tagName.toLowerCase() === 'img') {
        var empty = document.createElement('div');
        empty.className = 'tde-presenter-photo-preview is-empty';
        empty.textContent = 'No photo';
        current.replaceWith(empty);
      } else {
        current.className = 'tde-presenter-photo-preview is-empty';
        current.textContent = 'No photo';
      }
    }
  }

  function getCurrentPresenters(key) {
    var wrap = document.getElementById('presenters-' + escId(key));
    if (!wrap) return [];
    return normalizePresenters(Array.from(wrap.querySelectorAll('.tde-presenter-row')).map(function(row) {
      var nameEl = row.querySelector('[data-presenter-field="name"]');
      var bioEl = row.querySelector('[data-presenter-field="bio"]');
      var photoEl = row.querySelector('[data-presenter-field="photo"]');
      return {
        name: nameEl ? nameEl.value : '',
        bio: bioEl ? normalizeDescriptionHtml(bioEl.innerHTML) : '',
        photo: photoEl ? photoEl.value : ''
      };
    }));
  }

  function getCurrentSessions(key) {
    var wrap = document.getElementById('sessions-' + escId(key));
    if (!wrap) return [];
    return normalizeSessions(Array.from(wrap.querySelectorAll('.tde-session-row')).map(function(row) {
      var labelEl = row.querySelector('[data-session-field="label"]');
      var startEl = row.querySelector('[data-session-field="start"]');
      var endEl = row.querySelector('[data-session-field="end"]');
      var locationEl = row.querySelector('[data-session-field="location"]');
      var fullEl = row.querySelector('[data-session-field="full"]');
      return {
        label: labelEl ? labelEl.value : '',
        start: startEl ? startEl.value : '',
        end: endEl ? endEl.value : '',
        location: locationEl ? locationEl.value : '',
        full: !!(fullEl && fullEl.checked)
      };
    }));
  }

  function markSessionCardChanged(key) {
    refreshCardChangeState(key);
  }

  function markPresenterCardChanged(key) {
    refreshCardChangeState(key);
  }

  function refreshCardChangeState(key) {
    var sid = escId(key);
    var card = document.getElementById('card-' + sid);
    var orig = _orig[key] || {};
    if (!card) return;
    if (card.getAttribute('data-delete') === '1') {
      card.classList.add('changed');
      updateCount();
      return;
    }
    var cur = getCurrentValues(key, sid);
    var changed = cur.desc !== orig.desc ||
      cur.image !== orig.image ||
      cur.imageAlt !== orig.imageAlt ||
      cur.infoUrl !== orig.infoUrl ||
      !presentersEqual(cur.presenters, orig.presenters) ||
      !sessionsEqual(cur.sessions, orig.sessions) ||
      cur.title !== orig.title;
    if (cur.start !== orig.start) changed = true;
    if (cur.end !== orig.end) changed = true;
    if (cur.location !== orig.location) changed = true;
    if (cur.afterHours !== normalizeBool(orig.afterHours)) changed = true;
    if (cur.tagClass !== orig.tagClass) changed = true;
    if (normalizeEventType(cur.eventType) !== normalizeEventType(orig.eventType)) changed = true;
    if (changed) card.classList.add('changed');
    else card.classList.remove('changed');
    updateCount();
  }

  /* ── Field change handler ── */
  window.tdeFieldChange = function(el) {
    var key   = el.getAttribute('data-key');
    var field = el.getAttribute('data-field');
    var sid   = escId(key);
    var orig  = _orig[key];

    if (field === 'desc') {
      document.getElementById('cc-' + sid).textContent = el.value.length + ' chars';
    }

    // Check if card has any change
    var changed = false;
    var card = document.getElementById('card-' + sid);
    if (card && card.getAttribute('data-delete') === '1') {
      card.classList.add('changed');
      updateCount();
      return;
    }

    // Get current values from the DOM
    var cur = getCurrentValues(key, sid);
    if (cur.desc !== orig.desc) changed = true;
    if (cur.image !== orig.image) changed = true;
    if (cur.imageAlt !== orig.imageAlt) changed = true;
    if (cur.infoUrl !== orig.infoUrl) changed = true;
    if (!presentersEqual(cur.presenters, orig.presenters)) changed = true;
    if (!sessionsEqual(cur.sessions, orig.sessions)) changed = true;
    if (cur.title !== orig.title) changed = true;
    if (cur.start !== orig.start) changed = true;
    if (cur.end !== orig.end) changed = true;
    if (cur.location !== orig.location) changed = true;
    if (cur.afterHours !== normalizeBool(orig.afterHours)) changed = true;
    if (cur.tagClass !== orig.tagClass) changed = true;
    if (normalizeEventType(cur.eventType) !== normalizeEventType(orig.eventType)) changed = true;

    if (changed) card.classList.add('changed');
    else card.classList.remove('changed');

    updateCount();
  };

  function getCurrentValues(key, sid) {
    var vals = {};
    var descEl = document.getElementById('ta-' + sid);
    var imageEl = document.getElementById('f-image-' + sid);
    var imageAltEl = document.getElementById('f-image-alt-' + sid);
    var infoEl = document.getElementById('f-info-' + sid);
    vals.desc = descEl ? normalizeDescriptionHtml(descEl.innerHTML) : (_orig[key] ? _orig[key].desc : '');
    vals.image = imageEl ? normalizeImageUrl(imageEl.value) : (_orig[key] ? (_orig[key].image || '') : '');
    vals.imageAlt = imageAltEl ? normalizeImageAlt(imageAltEl.value) : (_orig[key] ? (_orig[key].imageAlt || '') : '');
    vals.infoUrl = infoEl ? infoEl.value.trim() : (_orig[key] ? (_orig[key].infoUrl || '') : '');
    vals.presenters = getCurrentPresenters(key);
    vals.sessions = getCurrentSessions(key);
    var titleEl = document.getElementById('f-title-' + sid);
    vals.title = titleEl ? titleEl.value : (_orig[key] ? _orig[key].title : '');

    var startEl = document.getElementById('f-start-' + sid);
    var endEl   = document.getElementById('f-end-' + sid);
    var locationEl = document.getElementById('f-location-' + sid);
    var afterHoursEl = document.getElementById('f-after-hours-' + sid);
    var tagEl   = document.getElementById('f-tag-' + sid);
    var eventTypeEl = document.getElementById('f-event-type-' + sid);
    vals.start    = startEl ? startEl.value : '';
    vals.end      = endEl   ? endEl.value   : '';
    vals.location = locationEl ? normalizeLocation(locationEl.value) : (_orig[key] ? (_orig[key].location || '') : '');
    vals.afterHours = afterHoursEl ? afterHoursEl.checked : (_orig[key] ? normalizeBool(_orig[key].afterHours) : false);
    vals.tagClass = tagEl   ? tagEl.value   : (_orig[key] ? _orig[key].tagClass : '');
    vals.tagLabel = getTagLabel(vals.tagClass);
    vals.eventType = eventTypeEl ? normalizeEventType(eventTypeEl.value) : normalizeEventType(_orig[key] ? _orig[key].eventType : '');
    return vals;
  }

  function updateCount() {
    var cards = document.querySelectorAll('#tde-content .tde-card');
    var n = 0;
    cards.forEach(function(card) {
      if (card.classList.contains('changed')) n++;
    });
    document.getElementById('tde-save-btn').disabled = (n === 0);
    document.getElementById('tde-change-count').textContent = n > 0
      ? n + ' unsaved change' + (n !== 1 ? 's' : '') : '';
    if (_orderDirty && n === 0) {
      document.getElementById('tde-save-btn').disabled = false;
      document.getElementById('tde-change-count').textContent = 'Order changed';
    }
  }

  /* ── Save ── */
  window.tdeSaveAll = function() {
    setStatus('Saving…', 'loading');
    document.getElementById('tde-save-btn').disabled = true;

    var html = findHtmlInTree(_eData);
    if (!html) { setStatus('Error: HTML widget not found', 'err'); return; }

    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');

    var days = DAY_OPTIONS.map(function(day) { return day.id; });
    var changed = 0;
    var invalidNew = false;
    var dayLists = {};
    var currentOrderByDay = {};
    var itemByKey = {};

    days.forEach(function(dayId) {
      var dayEl = doc.getElementById(dayId);
      if (!dayEl) return;
      var listEl = dayEl.querySelector('.schedule-list') || dayEl;
      dayLists[dayId] = listEl;
      currentOrderByDay[dayId] = Array.from(listEl.querySelectorAll('.schedule-item[data-title]'));
      currentOrderByDay[dayId].forEach(function(item, idx) {
        itemByKey[dayId + '|' + idx] = item;
      });
    });

    days.forEach(function(dayId) {
      var cards = Array.from(document.querySelectorAll('#tde-day-' + dayId + ' .tde-card'));

      cards.filter(function(card) { return card.getAttribute('data-new') !== '1'; }).forEach(function(card) {
        var key = card.getAttribute('data-key');
        var sid = escId(key);
        var orig = _orig[key];
        if (!orig) return;

        var item = itemByKey[key];
        if (!item) return;

        if (card.getAttribute('data-delete') === '1') {
          item.remove();
          delete itemByKey[key];
          changed++;
          return;
        }

        var cur = getCurrentValues(key, sid);
        var thisChanged = false;

        if (cur.desc !== orig.desc) {
          item.setAttribute('data-desc', cur.desc);
          thisChanged = true;
        }

        if (cur.image !== orig.image) {
          if (cur.image) item.setAttribute('data-image', cur.image);
          else item.removeAttribute('data-image');
          thisChanged = true;
        }

        if (cur.imageAlt !== orig.imageAlt) {
          if (cur.imageAlt) item.setAttribute('data-image-alt', cur.imageAlt);
          else item.removeAttribute('data-image-alt');
          thisChanged = true;
        }

        if (cur.infoUrl !== orig.infoUrl) {
          if (cur.infoUrl) item.setAttribute('data-info-url', cur.infoUrl);
          else item.removeAttribute('data-info-url');
          thisChanged = true;
        }

        if (!presentersEqual(cur.presenters, orig.presenters)) {
          if (cur.presenters.length) item.setAttribute('data-presenters', JSON.stringify(cur.presenters));
          else item.removeAttribute('data-presenters');
          thisChanged = true;
        }

        if (!sessionsEqual(cur.sessions, orig.sessions)) {
          if (cur.sessions.length) item.setAttribute('data-sessions', JSON.stringify(cur.sessions));
          else item.removeAttribute('data-sessions');
          thisChanged = true;
        }

        if (cur.title !== orig.title) {
          item.setAttribute('data-title', cur.title);
          var nameSpan = item.querySelector('.event-name');
          if (nameSpan) nameSpan.textContent = cur.title;
          thisChanged = true;
        }

        if (cur.start !== orig.start) {
          if (cur.start) item.setAttribute('data-start', cur.start);
          else item.removeAttribute('data-start');
          thisChanged = true;
        }
        if (cur.end !== orig.end) {
          if (cur.end) item.setAttribute('data-end', cur.end);
          else item.removeAttribute('data-end');
          thisChanged = true;
        }

        if (cur.location !== orig.location) {
          if (cur.location) item.setAttribute('data-location', cur.location);
          else item.removeAttribute('data-location');
          thisChanged = true;
        }

        if (cur.afterHours !== normalizeBool(orig.afterHours)) {
          if (cur.afterHours) item.setAttribute('data-after-hours', 'true');
          else item.removeAttribute('data-after-hours');
          thisChanged = true;
        }

        if (cur.tagClass !== orig.tagClass) {
          item.setAttribute('data-tag-class', cur.tagClass);
          item.setAttribute('data-tag-label', cur.tagLabel);
          var tagSpan = item.querySelector('.event-tag');
          if (tagSpan) {
            tagSpan.className = 'event-tag ' + cur.tagClass;
            tagSpan.textContent = cur.tagLabel;
          }
          thisChanged = true;
        }

        if (normalizeEventType(cur.eventType) !== normalizeEventType(orig.eventType)) {
          if (normalizeEventType(cur.eventType) !== DEFAULT_EVENT_TYPE) item.setAttribute('data-event-type', normalizeEventType(cur.eventType));
          else item.removeAttribute('data-event-type');
          thisChanged = true;
        }

        if (thisChanged) {
          changed++;
          _orig[key] = {
            title: cur.title, desc: cur.desc, image: cur.image, imageAlt: cur.imageAlt, infoUrl: cur.infoUrl, presenters: cur.presenters, sessions: cur.sessions, start: cur.start,
            end: cur.end, location: cur.location, afterHours: cur.afterHours, tagLabel: cur.tagLabel, tagClass: cur.tagClass, eventType: normalizeEventType(cur.eventType)
          };
        }
      });

      cards.filter(function(card) { return card.getAttribute('data-new') === '1' && card.getAttribute('data-delete') !== '1'; }).forEach(function(card) {
        var key = card.getAttribute('data-key');
        var sid = escId(key);
        var cur = getCurrentValues(key, sid);

        cur.title = cur.title.trim();
        cur.desc = cur.desc.trim();
        cur.image = normalizeImageUrl(cur.image);
        cur.imageAlt = normalizeImageAlt(cur.imageAlt);
        cur.infoUrl = cur.infoUrl.trim();
        cur.presenters = normalizePresenters(cur.presenters);
        cur.sessions = normalizeSessions(cur.sessions);
        cur.start = cur.start.trim();
        cur.end = cur.end.trim();
        cur.location = normalizeLocation(cur.location);
        cur.afterHours = normalizeBool(cur.afterHours);
        cur.eventType = normalizeEventType(cur.eventType);

        if (!cur.title) {
          invalidNew = true;
          card.classList.add('changed');
          return;
        }

        var item = doc.createElement('div');
        item.className = 'schedule-item';
        item.setAttribute('data-title', cur.title);
        item.setAttribute('data-desc', cur.desc || 'Description coming soon.');
        if (cur.image) item.setAttribute('data-image', cur.image);
        if (cur.imageAlt) item.setAttribute('data-image-alt', cur.imageAlt);
        if (cur.infoUrl) item.setAttribute('data-info-url', cur.infoUrl);
        if (cur.presenters.length) item.setAttribute('data-presenters', JSON.stringify(cur.presenters));
        if (cur.sessions.length) item.setAttribute('data-sessions', JSON.stringify(cur.sessions));
        item.setAttribute('data-tag-label', cur.tagLabel || 'Competition');
        item.setAttribute('data-tag-class', cur.tagClass || 'tag-competition');
        if (normalizeEventType(cur.eventType) !== DEFAULT_EVENT_TYPE) item.setAttribute('data-event-type', normalizeEventType(cur.eventType));
        if (cur.start) item.setAttribute('data-start', cur.start);
        if (cur.end) item.setAttribute('data-end', cur.end);
        if (cur.location) item.setAttribute('data-location', cur.location);
        if (cur.afterHours) item.setAttribute('data-after-hours', 'true');

        var nameSpan = doc.createElement('span');
        nameSpan.className = 'event-name';
        nameSpan.textContent = cur.title;

        var tagSpan = doc.createElement('span');
        tagSpan.className = 'event-tag ' + (cur.tagClass || 'tag-competition');
        tagSpan.textContent = cur.tagLabel || 'Competition';

        item.appendChild(nameSpan);
        item.appendChild(doc.createTextNode(' '));
        item.appendChild(tagSpan);
        itemByKey[key] = item;
        changed++;
      });

      if (invalidNew) return;
    });

    if (!invalidNew) {
      days.forEach(function(dayId) {
        var listEl = dayLists[dayId];
        if (!listEl) return;
        var cards = Array.from(document.querySelectorAll('#tde-day-' + dayId + ' .tde-card'));

        var orderedItems = cards.filter(function(card) {
          return card.getAttribute('data-delete') !== '1';
        }).map(function(card, idx) {
          var key = card.getAttribute('data-key');
          var item = itemByKey[key];
          if (item) item.setAttribute('data-tde-order', String(idx));
          return item;
        }).filter(Boolean);

        if (_scheduleMode) {
          orderedItems.sort(function(a, b) {
            var at = parseStartTime(a.getAttribute('data-start'));
            var bt = parseStartTime(b.getAttribute('data-start'));
            if (at === null && bt === null) return parseInt(a.getAttribute('data-tde-order'), 10) - parseInt(b.getAttribute('data-tde-order'), 10);
            if (at === null) return 1;
            if (bt === null) return -1;
            if (at !== bt) return at - bt;
            return parseInt(a.getAttribute('data-tde-order'), 10) - parseInt(b.getAttribute('data-tde-order'), 10);
          });
        }

        var currentOrder = currentOrderByDay[dayId] || [];
        var changedOrder = orderedItems.length !== currentOrder.length || orderedItems.some(function(item, idx) {
          return currentOrder[idx] !== item;
        });
        orderedItems.forEach(function(item) {
          item.removeAttribute('data-tde-order');
          listEl.appendChild(item);
        });
        if (changedOrder) changed++;
      });
    }

    if (invalidNew) {
      setStatus('Add a title before saving a new event.', 'err');
      document.getElementById('tde-save-btn').disabled = false;
      return;
    }

    // Serialize and write back
    var updatedHtml = doc.body.innerHTML;

    function setHtmlInTree(nodes) {
      var fallback = null;
      for (var i = 0; i < nodes.length; i++) {
        var n = nodes[i];
        if (n.widgetType === 'html' && n.settings && n.settings.html !== undefined) {
          if (n.settings.html.indexOf('schedule-item') !== -1) {
            n.settings.html = updatedHtml; return true;
          }
          if (!fallback) fallback = n;
        }
        if (n.elements && n.elements.length && setHtmlInTree(n.elements)) return true;
      }
      if (fallback) {
        fallback.settings.html = updatedHtml;
        return true;
      }
      return false;
    }
    setHtmlInTree(_eData);

    fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
      body: JSON.stringify({ meta: { _elementor_data: JSON.stringify(_eData) } })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.id) {
        setStatus('✓ Saved ' + changed + ' change' + (changed !== 1 ? 's' : '') + '. Clear Elementor cache if needed (Tools → Clear Files & Data).', 'ok');
        tdeLoad();
      } else {
        setStatus('Save failed: ' + JSON.stringify(d).substring(0, 120), 'err');
        document.getElementById('tde-save-btn').disabled = false;
      }
    })
    .catch(function(e){
      setStatus('Network error: ' + e.message, 'err');
      document.getElementById('tde-save-btn').disabled = false;
    });
  };

  // Auto-load
  tdeLoad();
})();
</script>

</body>
</html>
	<?php
}
