<?php
/**
 * Plugin Name: Trivia Nationals – Event Schedule Manager
 * Description: Admin editor for homepage event schedule — descriptions, titles, times, and tags. Includes a Schedule Mode toggle that shows times on the public site.
 * Version: 2.0
 * Author: Trivia Nationals
 */
if ( ! defined( 'ABSPATH' ) ) exit;

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
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', insertVenueVideos);
		else insertVenueVideos();
	})();
	</script>
	<?php
}, 21 );

add_action( 'wp_head', function () {
	if ( is_admin() || ! ( is_front_page() || is_page( 5 ) ) ) return;
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
		max-width: 760px;
		margin-bottom: 1.2rem !important;
	}
	body.page-id-5 .schedule-tabs {
		display: flex !important;
		gap: 0.6rem !important;
		flex-wrap: wrap !important;
		margin: 0 0 1.3rem !important;
	}
	body.page-id-5 .schedule-tab {
		border: 1px solid rgba(255,255,255,0.16) !important;
		border-radius: 999px !important;
		background: rgba(255,255,255,0.055) !important;
		color: #cdd4ea !important;
		font-family: var(--font-display, Outfit, sans-serif) !important;
		font-size: 0.78rem !important;
		font-weight: 900 !important;
		letter-spacing: 0.04em !important;
		padding: 0.78rem 1.15rem !important;
		text-transform: uppercase !important;
	}
	body.page-id-5 .schedule-tab.active {
		background: #00e5ff !important;
		border-color: #00e5ff !important;
		color: #071019 !important;
	}
	body.page-id-5 .schedule-list {
		display: grid !important;
		grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
		gap: 0.85rem !important;
	}
	body.page-id-5 .schedule-item {
		display: grid !important;
		grid-template-columns: minmax(0, 1fr) auto !important;
		align-items: center !important;
		gap: 0.8rem !important;
		min-height: 78px !important;
		padding: 1rem !important;
		border: 1px solid rgba(255,255,255,0.12) !important;
		border-radius: 8px !important;
		background: linear-gradient(180deg, rgba(255,255,255,0.045), rgba(17,21,37,0.88)) !important;
		box-shadow: 0 12px 34px rgba(0,0,0,0.18) !important;
		transition: border-color 0.18s ease, transform 0.18s ease !important;
	}
	body.page-id-5 .schedule-item:hover {
		border-color: rgba(0,229,255,0.42) !important;
		transform: translateY(-1px);
	}
	body.page-id-5 .schedule-item .event-name {
		color: #f7f8ff !important;
		font-family: var(--font-display, Outfit, sans-serif) !important;
		font-size: 1.03rem !important;
		font-weight: 900 !important;
		line-height: 1.18 !important;
	}
	body.page-id-5 .schedule-item .event-tag {
		justify-self: end;
		white-space: nowrap;
	}
	@media (max-width: 767px) {
		nav .nav-links {
			align-items: center;
		}
		body.page-id-5 .schedule-list {
			grid-template-columns: 1fr !important;
		}
		body.page-id-5 .schedule-item {
			grid-template-columns: 1fr !important;
			align-items: start !important;
		}
		body.page-id-5 .schedule-item .event-tag {
			justify-self: start;
		}
	}
	</style>
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
	#modal-desc .tn-event-modal-graphic {
		display: block;
		width: 100%;
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
		#modal-desc .tn-event-modal-graphic {
			display: block;
			width: 100%;
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
			var btn = moreButton();
			if (!btn) return;
			var href = safeInfoUrl(url);
			if (!href) {
				btn.removeAttribute('href');
				btn.style.display = 'none';
				return;
			}
			btn.href = href;
			btn.style.display = 'inline-flex';
		}

		function safeImageUrl(url) {
			url = String(url || '').trim();
			if (!url) return '';
			try {
				var parsed = new URL(url, window.location.origin);
				if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') return '';
				if (parsed.origin !== window.location.origin) return '';
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
			var allowedInline = { A: true, STRONG: true, B: true, EM: true, I: true, U: true, BR: true, P: true, UL: true, OL: true, LI: true };
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
						if (child.tagName !== 'A' || attr.name.toLowerCase() !== 'href') {
							child.removeAttribute(attr.name);
						}
					});
					if (child.tagName === 'A') {
						var href = child.getAttribute('href') || '';
						if (!/^(https?:|mailto:|tel:|#)/i.test(href)) {
							child.replaceWith(document.createTextNode(child.textContent || ''));
							return;
						}
						prepareLink(child, href);
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
			if (typeof originalOpen === 'function') {
				originalOpen(title, tagLabel, tagClass, desc);
			} else {
				openModalFallback(title, tagLabel, tagClass, desc);
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
				href,
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
		return [
			'title'      => sanitize_text_field( $item->getAttribute( 'data-title' ) ),
			'day'        => tn_tde_day_label_from_item( $item ) ?: 'Day TBA',
			'time'       => $time,
			'has_time'   => (bool) $start,
			'category'   => sanitize_text_field( $item->getAttribute( 'data-tag-label' ) ) ?: 'Event',
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
		'category'  => $event['category'] ?: 'Event',
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
				html.style.scrollBehavior = 'auto';
				html.scrollTop = dest;
				setTimeout(function() { html.style.scrollBehavior = ''; }, 50);
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

// ─── Admin: Event Schedule Manager ──────────────────────────────────────────

add_action( 'admin_menu', function () {
	add_menu_page(
		'Event Schedule',
		'Event Schedule',
		'edit_pages',
		'trivia-desc-editor',
		'trivia_desc_editor_page',
		'dashicons-calendar-alt',
		30
	);
	add_submenu_page(
		'trivia-desc-editor',
		'Venue Videos',
		'Venue Videos',
		'edit_pages',
		'tn-venue-videos',
		'tn_tde_venue_videos_page'
	);
} );

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
	$schedule_mode  = get_option( 'tn_schedule_mode', 'off' );
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

  /* ── Day sections ── */
  .tde-day { margin-bottom: 1.5rem; }
  .tde-day-header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.12em; color: #555; background: #eaecf0;
    padding: 0.45rem 1rem; border-radius: 6px 6px 0 0; border-bottom: 2px solid #d0d7de;
  }
  .tde-add-event {
    border: 1px solid #0096a0; border-radius: 5px; background: #fff; color: #00797f;
    cursor: pointer; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em;
    padding: 0.25rem 0.55rem; text-transform: uppercase;
  }
  .tde-add-event:hover { background: rgba(0,150,160,0.08); }

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
    display: grid; grid-template-columns: 1fr auto auto auto; gap: 0.5rem;
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
  .tde-field-tag   { width: 150px; }

  /* ── Textarea ── */
  .tde-card textarea {
    width: 100%; border: 1px solid #d0d7de; border-radius: 6px;
    padding: 0.55rem 0.7rem; font-size: 0.83rem; line-height: 1.5;
    resize: vertical; min-height: 75px; font-family: inherit;
    color: #222; transition: border-color 0.2s;
  }
  .tde-card textarea:focus {
    outline: none; border-color: #0096a0; box-shadow: 0 0 0 3px rgba(0,150,160,0.12);
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
  .tde-presenter-bio-field { grid-column: 1 / 3; }
  .tde-presenter-row textarea {
    min-height: 58px;
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

  <div id="tde-loading">Loading event data…</div>
  <div id="tde-content"></div>
</div>

<script>
(function() {
  var NONCE      = '<?php echo esc_js( $nonce ); ?>';
  var MODE_NONCE = '<?php echo esc_js( $mode_nonce ); ?>';
  var API        = '<?php echo esc_js( rest_url( 'wp/v2/pages/5' ) ); ?>';
  var AJAX_URL   = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
  var _eData     = null;
  var _orig      = {};      // key → { desc, image, imageAlt, infoUrl, presenters, title, start, end, tagLabel, tagClass }
  var _orderDirty = false;
  var _scheduleMode = <?php echo $schedule_mode === 'on' ? 'true' : 'false'; ?>;

  var TAG_OPTIONS = [
    { value: 'tag-competition', label: 'Competition', cls: 'tag-competition' },
    { value: 'tag-social',      label: 'Social',      cls: 'tag-social' },
    { value: 'tag-finals',      label: 'Finals',      cls: 'tag-finals' },
    { value: 'tag-special',     label: 'Special',     cls: 'tag-special' }
  ];

  /* ── Utilities ── */
  function setStatus(msg, type) {
    var el = document.getElementById('tde-status');
    el.textContent = msg;
    el.className = 'tde-status ' + type;
    el.style.display = 'inline-block';
    if (type === 'ok') setTimeout(function(){ el.style.display = 'none'; }, 6000);
  }
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function escAttrJson(value) {
    return escHtml(JSON.stringify(value || []));
  }
  function escId(s) { return s.replace(/[^a-zA-Z0-9_-]/g, '_'); }
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
  function normalizeImageUrl(value) {
    return String(value || '').trim();
  }
  function normalizeImageAlt(value) {
    return String(value || '').trim();
  }
  function getTagLabel(tagClass) {
    var opt = TAG_OPTIONS.find(function(o){ return o.value === tagClass; });
    return opt ? opt.label : '';
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
    out += '<textarea id="' + baseId + '-bio" data-key="' + escHtml(key) + '" data-presenter-field="bio" rows="2" oninput="tdePresenterChange(this)">' + escHtml(presenter.bio || '') + '</textarea>';
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

  function buildEventCard(dayId, key, title, tagLabel, tagClass, desc, image, imageAlt, infoUrl, presenters, start, end, isNew) {
    var sid = escId(key);
    var out = '';
    var showScheduleFields = _scheduleMode || isNew;
    image = normalizeImageUrl(image);
    imageAlt = normalizeImageAlt(imageAlt);

    out += '<div class="tde-card' + (isNew ? ' changed' : '') + '" id="card-' + sid + '" data-key="' + escHtml(key) + '" data-day="' + escHtml(dayId) + '"' + (isNew ? ' data-new="1"' : '') + '>';

    out += '<div class="tde-event-actions">';
    if (isNew) out += '<span class="tde-new-badge">New event</span>';
    out += '<span class="tde-order-controls">';
    out += '<button type="button" class="tde-order-btn" onclick="tdeMoveEvent(this, -1)" title="Move up">↑</button>';
    out += '<button type="button" class="tde-order-btn" onclick="tdeMoveEvent(this, 1)" title="Move down">↓</button>';
    out += '</span>';
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

    if (showScheduleFields) {
      out += '<div class="tde-field tde-field-start">';
      out += '<label>Start</label>';
      out += '<input type="text" id="f-start-' + sid + '" data-key="' + escHtml(key) + '" data-field="start" value="' + escHtml(start) + '" placeholder="e.g. 9:00 AM" oninput="tdeFieldChange(this)">';
      out += '</div>';

      out += '<div class="tde-field tde-field-end">';
      out += '<label>End</label>';
      out += '<input type="text" id="f-end-' + sid + '" data-key="' + escHtml(key) + '" data-field="end" value="' + escHtml(end) + '" placeholder="e.g. 10:30 AM" oninput="tdeFieldChange(this)">';
      out += '</div>';

      out += '<div class="tde-field tde-field-tag">';
      out += '<label>Category</label>';
      out += '<select id="f-tag-' + sid + '" data-key="' + escHtml(key) + '" data-field="tag" onchange="tdeFieldChange(this)">';
      TAG_OPTIONS.forEach(function(opt) {
        out += '<option value="' + opt.value + '"' + (tagClass === opt.value ? ' selected' : '') + '>' + escHtml(opt.label) + '</option>';
      });
      out += '</select>';
      out += '</div>';
    }
    out += '</div>';

    out += '<textarea id="ta-' + sid + '" data-key="' + escHtml(key) + '" data-field="desc" oninput="tdeFieldChange(this)" rows="3">' + escHtml(desc) + '</textarea>';
    out += '<div class="tde-char-count" id="cc-' + sid + '">' + desc.length + ' chars</div>';
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
    out += buildPresentersEditor(key, presenters);
    out += '</div>';

    return out;
  }

  function renderEditor(html) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');
    var days = [
      { id: 'day-friday',   label: 'Friday — August 7, 2026' },
      { id: 'day-saturday', label: 'Saturday — August 8, 2026' },
      { id: 'day-sunday',   label: 'Sunday — August 9, 2026' }
    ];
    var out = '';

    days.forEach(function(day) {
      var dayEl = doc.getElementById(day.id);
      if (!dayEl) return;
      var items = dayEl.querySelectorAll('.schedule-item[data-title]');
      out += '<div class="tde-day" id="tde-day-' + escHtml(day.id) + '" data-day="' + escHtml(day.id) + '">';
      out += '<div class="tde-day-header"><span>' + escHtml(day.label) + '</span><button type="button" class="tde-add-event" onclick="tdeAddEvent(\'' + escHtml(day.id) + '\')">+ Add Event</button></div>';
      out += '<div class="tde-day-items">';

      items.forEach(function(item, idx) {
        var title    = item.getAttribute('data-title') || '';
        var tagLabel = item.getAttribute('data-tag-label') || '';
        var tagClass = item.getAttribute('data-tag-class') || 'tag-special';
        var desc     = item.getAttribute('data-desc') || '';
        var image    = item.getAttribute('data-image') || '';
        var imageAlt = item.getAttribute('data-image-alt') || '';
        var infoUrl  = item.getAttribute('data-info-url') || '';
        var presenters = normalizePresenters(item.getAttribute('data-presenters') || '');
        var start    = item.getAttribute('data-start') || '';
        var end      = item.getAttribute('data-end') || '';

        var key = day.id + '|' + idx;
        _orig[key] = { title: title, desc: desc, image: image, imageAlt: imageAlt, infoUrl: infoUrl, presenters: presenters, start: start, end: end, tagLabel: tagLabel, tagClass: tagClass };
        out += buildEventCard(day.id, key, title, tagLabel, tagClass, desc, image, imageAlt, infoUrl, presenters, start, end, false);
      });

      out += '</div></div>'; // end .tde-day-items, .tde-day
    });

    document.getElementById('tde-content').innerHTML = out;
    updateOrderButtons();
    updateCount();
  }

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
      title: '', desc: '', image: '', imageAlt: '', infoUrl: '', presenters: [], start: '', end: '',
      tagLabel: getTagLabel(tagClass), tagClass: tagClass, isNew: true
    };
    dayItems.insertAdjacentHTML('beforeend', buildEventCard(dayId, key, '', getTagLabel(tagClass), tagClass, '', '', '', '', [], '', '', true));
    var titleEl = document.getElementById('f-title-' + escId(key));
    if (titleEl) titleEl.focus();
    updateOrderButtons();
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
    updateCount();
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
      updateCount();
      return;
    }

    if (!confirm('Remove "' + title + '" from the schedule? It will not be removed from the website until you click Save All Changes.')) return;
    card.setAttribute('data-delete', '1');
    card.classList.add('deleted', 'changed');
    updateOrderButtons();
    updateCount();
  };

  window.tdeUndoRemoveEvent = function(btn) {
    var card = btn && btn.closest('.tde-card');
    if (!card) return;
    var key = card.getAttribute('data-key');
    card.removeAttribute('data-delete');
    card.classList.remove('deleted');
    refreshCardChangeState(key);
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
        bio: bioEl ? bioEl.value : '',
        photo: photoEl ? photoEl.value : ''
      };
    }));
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
      cur.title !== orig.title;
    if (_scheduleMode || orig.isNew) {
      if (cur.start !== orig.start) changed = true;
      if (cur.end !== orig.end) changed = true;
      if (cur.tagClass !== orig.tagClass) changed = true;
    }
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
    if (cur.title !== orig.title) changed = true;
    if (_scheduleMode || orig.isNew) {
      if (cur.start !== orig.start) changed = true;
      if (cur.end !== orig.end)     changed = true;
      if (cur.tagClass !== orig.tagClass) changed = true;
    }

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
    vals.desc = descEl ? descEl.value : (_orig[key] ? _orig[key].desc : '');
    vals.image = imageEl ? normalizeImageUrl(imageEl.value) : (_orig[key] ? (_orig[key].image || '') : '');
    vals.imageAlt = imageAltEl ? normalizeImageAlt(imageAltEl.value) : (_orig[key] ? (_orig[key].imageAlt || '') : '');
    vals.infoUrl = infoEl ? infoEl.value.trim() : (_orig[key] ? (_orig[key].infoUrl || '') : '');
    vals.presenters = getCurrentPresenters(key);
    var titleEl = document.getElementById('f-title-' + sid);
    vals.title = titleEl ? titleEl.value : (_orig[key] ? _orig[key].title : '');

    if (_scheduleMode || (_orig[key] && _orig[key].isNew)) {
      var startEl = document.getElementById('f-start-' + sid);
      var endEl   = document.getElementById('f-end-' + sid);
      var tagEl   = document.getElementById('f-tag-' + sid);
      vals.start    = startEl ? startEl.value : '';
      vals.end      = endEl   ? endEl.value   : '';
      vals.tagClass = tagEl   ? tagEl.value   : (_orig[key] ? _orig[key].tagClass : '');
      vals.tagLabel = getTagLabel(vals.tagClass);
    } else {
      vals.start    = _orig[key] ? _orig[key].start    : '';
      vals.end      = _orig[key] ? _orig[key].end      : '';
      vals.tagClass = _orig[key] ? _orig[key].tagClass  : '';
      vals.tagLabel = _orig[key] ? _orig[key].tagLabel  : '';
    }
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

    var days = ['day-friday', 'day-saturday', 'day-sunday'];
    var changed = 0;
    var invalidNew = false;

    days.forEach(function(dayId) {
      var dayEl = doc.getElementById(dayId);
      if (!dayEl) return;
      var listEl = dayEl.querySelector('.schedule-list') || dayEl;
      var items = dayEl.querySelectorAll('.schedule-item[data-title]');
      var itemByKey = {};
      var cards = Array.from(document.querySelectorAll('#tde-day-' + dayId + ' .tde-card'));

      items.forEach(function(item, idx) {
        var key = dayId + '|' + idx;
        var sid = escId(key);
        var orig = _orig[key];
        if (!orig) return;

        var sourceCard = document.getElementById('card-' + sid);
        if (sourceCard && sourceCard.getAttribute('data-delete') === '1') {
          item.remove();
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

        if (thisChanged) {
          changed++;
          _orig[key] = {
            title: cur.title, desc: cur.desc, image: cur.image, imageAlt: cur.imageAlt, infoUrl: cur.infoUrl, presenters: cur.presenters, start: cur.start,
            end: cur.end, tagLabel: cur.tagLabel, tagClass: cur.tagClass
          };
        }
        itemByKey[key] = item;
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
        cur.start = cur.start.trim();
        cur.end = cur.end.trim();

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
        item.setAttribute('data-tag-label', cur.tagLabel || 'Competition');
        item.setAttribute('data-tag-class', cur.tagClass || 'tag-competition');
        if (cur.start) item.setAttribute('data-start', cur.start);
        if (cur.end) item.setAttribute('data-end', cur.end);

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

      var currentOrder = Array.from(listEl.querySelectorAll('.schedule-item[data-title]'));
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

      var changedOrder = orderedItems.length !== currentOrder.length || orderedItems.some(function(item, idx) {
        return currentOrder[idx] !== item;
      });
      orderedItems.forEach(function(item) {
        item.removeAttribute('data-tde-order');
        listEl.appendChild(item);
      });
      if (changedOrder) changed++;
    });

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
