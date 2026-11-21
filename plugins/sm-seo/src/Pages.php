<?php
/**
 * Title, description, structured data and breadcrumb of each kind of page.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Pages {

	/** Title and description of the pages of the marketplace (without argument). */
	public static function texts( $key ) {
		$t = array(
			'search'          => array( __( 'Explore the steel marketplace', 'sm-seo' ), __( 'New steel, steel reuse and steel buildings for sale in Algeria, by type of product and by wilaya, with the weight computed and the price asked by the sellers.', 'sm-seo' ) ),
			'products'        => array( __( 'New steel: beams, tubes, plates and bars', 'sm-seo' ), __( 'Listings of new steel in Algeria: profiles, tubes, plates and bars, with the weight computed from the standard catalogue and the price per kilogram asked by the sellers.', 'sm-seo' ) ),
			'reuse'           => array( __( 'Steel reuse: offcuts, surplus and used steel', 'sm-seo' ), __( 'Offcuts, surplus, used steel and scrap for sale in Algeria, with the condition stated by the seller and the weight computed.', 'sm-seo' ) ),
			'buildings'       => array( __( 'Steel buildings for sale: hangars assembled or dismantled', 'sm-seo' ), __( 'Hangars and steel buildings for sale in Algeria, assembled or dismantled: dimensions, structure and price asked by the seller.', 'sm-seo' ) ),
			'services'        => array( __( 'Services around steel', 'sm-seo' ), __( 'Companies that offer services around steel in Algeria. Send your request through the platform.', 'sm-seo' ) ),
			'engineers'       => array( __( 'Engineering services for steel structures', 'sm-seo' ), __( 'Engineering studies by verified partner engineers, delivered by the platform: one quote, one contact.', 'sm-seo' ) ),
			'companies'       => array( __( 'Steel companies in Algeria', 'sm-seo' ), __( 'Directory of the companies that sell steel on ReuSteel, by activity and by wilaya.', 'sm-seo' ) ),
			'professionals'   => array( __( 'Steel professionals in Algeria', 'sm-seo' ), __( 'Directory of the professionals of steel on ReuSteel, by activity and by wilaya.', 'sm-seo' ) ),
			'rfq'             => array( __( 'Requests for quotes for steel', 'sm-seo' ), __( 'Requests for quotes published by buyers of steel in Algeria: answer with your offer.', 'sm-seo' ) ),
			'price-index'     => array( __( 'Prices of steel asked on ReuSteel', 'sm-seo' ), __( 'Prices asked by the sellers on ReuSteel, per type of steel, in DA/kg.', 'sm-seo' ) ),
			'pricing'         => array( __( 'Plans and prices', 'sm-seo' ), __( 'The plans of ReuSteel for the sellers of steel and what each plan includes.', 'sm-seo' ) ),
			'contact'         => array( __( 'Contact', 'sm-seo' ), __( 'Contact the ReuSteel team.', 'sm-seo' ) ),
			'weight'          => array( __( 'Steel weight calculator', 'sm-seo' ), __( 'Compute the weight of beams, tubes, plates and bars from the standard catalogue. Free, in French, Arabic and English.', 'sm-seo' ) ),
			'ai'              => array( __( 'AI assistant for steel', 'sm-seo' ), __( 'The AI tools of ReuSteel for buying and selling steel.', 'sm-seo' ) ),
			'cutting'         => array( __( 'Cutting optimiser for steel bars', 'sm-seo' ), __( 'Plan the cutting of your steel bars to reduce the offcuts.', 'sm-seo' ) ),
			'engineer-apply'  => array( __( 'Become a partner engineer', 'sm-seo' ), __( 'Engineers and design offices: join the network of ReuSteel and carry out studies of steel structures.', 'sm-seo' ) ),
		);
		return $t[ $key ] ?? array( '', '' );
	}

	public static function home( array $m ) {
		$tagline          = __( 'We give steel a new life', 'sm-seo' );
		$m['title']       = get_bloginfo( 'name' ) . ' – ' . $tagline;
		$m['title_full']  = true;
		$m['description'] = __( 'We give steel a new life… transforming used resources into value and new opportunities.', 'sm-seo' ) . ' ' . __( 'New steel, steel reuse and steel buildings for sale in Algeria.', 'sm-seo' );
		$m['canonical']   = home_url( '/' );
		$org              = Schema::organization();
		$m['schema'][]    = $org;
		$m['schema'][]    = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'name'            => get_bloginfo( 'name' ),
			'url'             => home_url( '/' ),
			'inLanguage'      => Text::lang(),
			'publisher'       => array( '@id' => $org['@id'] ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => add_query_arg( 'q', '{search_term_string}', Text::url( 'search' ) ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
		return $m;
	}

	/** A page of the marketplace, with its type of product when there is one. */
	public static function page( array $m, $key, array $args ) {
		list( $title, $desc ) = self::texts( $key );
		if ( '' === $title ) {
			$title = get_the_title( get_queried_object_id() );
		}
		$url   = Text::url( $key );
		$m['title']       = $title;
		$m['description'] = $desc;
		$m['crumbs']      = array( array( get_the_title( Text::page_id( $key ) ) ?: $title, $url ) );

		// Type of product (?category=, ?family=): its own title, indexed.
		$keep = array();
		if ( ! empty( $args['category'] ) && in_array( $key, array( 'products', 'reuse', 'buildings', 'services' ), true ) ) {
			$cats  = 'services' === $key ? array() : (array) Text::data( 'categories', array( 'market' => $key ) );
			$label = isset( $cats[ $args['category'] ] ) ? Text::pick( $cats[ $args['category'] ] ) : '';
			if ( '' === $label ) {
				$m['index'] = false; // Unknown type: not a page of its own.
			} else {
				$keep['category']   = $args['category'];
				$m['title']         = $label . ' – ' . Text::market_label( $key );
				/* translators: 1: type of product, 2: market. */
				$m['description']   = sprintf( __( '%1$s for sale in Algeria (%2$s): listings with the weight computed and the price asked by the sellers.', 'sm-seo' ), $label, Text::market_label( $key ) );
				$m['crumbs'][]      = array( $label, add_query_arg( $keep, $url ) );
			}
		}
		if ( ! empty( $args['family'] ) && in_array( $key, array( 'products', 'reuse' ), true ) ) {
			$family = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $args['family'] ) );
			if ( '' !== $family ) {
				$keep['family'] = $family;
				$m['title']     = $family . ' – ' . ( isset( $keep['category'] ) ? $m['title'] : Text::market_label( $key ) );
				/* translators: 1: family of profiles (IPE, HEA…), 2: market. */
				$m['description'] = sprintf( __( '%1$s profiles for sale in Algeria (%2$s): sizes, steel grades, weight computed and price asked by the sellers.', 'sm-seo' ), $family, Text::market_label( $key ) );
				$m['crumbs'][]    = array( $family, add_query_arg( $keep, $url ) );
			}
		}
		if ( ! empty( $args['activity'] ) && in_array( $key, array( 'companies', 'professionals' ), true ) ) {
			$keep['activity'] = sanitize_key( $args['activity'] );
		}
		if ( ! empty( $args['pg'] ) && (int) $args['pg'] > 1 ) {
			$keep['pg'] = (int) $args['pg'];
		}
		$m['canonical'] = $keep ? add_query_arg( $keep, $url ) : $url;

		if ( 'engineers' === $key ) {
			$m = self::engineering( $m );
		}
		return $m;
	}

	/** Engineering page: the services of the platform (never a partner engineer). */
	private static function engineering( array $m ) {
		$data     = Text::data( 'engineers', array( 'per_page' => 1 ) );
		$services = is_array( $data ) ? (array) ( $data['services'] ?? array() ) : array();
		if ( ! $services ) {
			return $m;
		}
		$names = array_values( array_filter( array_map( static fn( $s ) => trim( (string) ( $s['title'] ?? '' ) ), $services ) ) );
		/* translators: %s: list of the engineering services. */
		$m['description'] = Text::cut( sprintf( __( 'Engineering services by verified partner engineers, delivered by the platform: %s. One quote, one contact.', 'sm-seo' ), implode( ', ', $names ) ), 300 );
		$list = array();
		foreach ( array_values( $services ) as $i => $s ) {
			$svc = array(
				'@type'      => 'Service',
				'name'       => (string) $s['title'],
				'provider'   => array( '@id' => Schema::organization()['@id'] ),
				'areaServed' => array( '@type' => 'Country', 'name' => __( 'Algeria', 'sm-seo' ) ),
			);
			$items = array_values( array_filter( array_map( 'strval', (array) ( $s['items'] ?? array() ) ) ) );
			if ( $items ) {
				$svc['description'] = Text::cut( implode( ', ', $items ), 300 );
			}
			if ( ! empty( $s['image'] ) ) {
				$svc['image'] = (string) $s['image'];
			}
			$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => $svc );
		}
		$m['schema'][] = Schema::organization();
		$m['schema'][] = array(
			'@type'           => 'ItemList',
			'name'            => $m['title'],
			'itemListElement' => $list,
		);
		return $m;
	}

	/** Detail of a listing: product, reuse or building. */
	public static function listing( array $m, $id ) {
		$item = $id ? Text::data( 'listing', array( 'id' => $id, 'no_count' => true ) ) : null;
		$m['canonical'] = Text::url( 'listing', array( 'id' => $id ) );
		if ( ! is_array( $item ) || empty( $item['id'] ) ) {
			$m['index'] = false;
			return $m;
		}
		$market  = (string) $item['market'];
		$wilaya  = Text::wilaya( $item['wilaya'] ?? '' );
		$cat     = Text::pick( $item['category_label'] ?? '' );
		$is_bld  = 'buildings' === $market;
		$expired = ! empty( $item['expires_at'] ) && strtotime( $item['expires_at'] . ' UTC' ) < time();
		if ( 'published' !== ( $item['status'] ?? '' ) || $expired ) {
			$m['index'] = false; // Sold, paused or expired: out of the index.
		}

		// Name: « IPE 200 S275JR » for a profile of the catalogue, the title of the seller otherwise.
		$engine = ! $is_bld && 'engine' === ( $item['kind'] ?? '' ) && '' !== (string) ( $item['designation'] ?? '' );
		$name   = $engine ? trim( $item['designation'] . ' ' . ( $item['grade'] ?? '' ) ) : Text::pick( $item['title'] ?? '' );
		$bars   = '';
		if ( ! $is_bld && 'bars' === ( $item['qty_mode'] ?? '' ) && (int) ( $item['quantity'] ?? 0 ) > 0 && (float) ( $item['length_m'] ?? 0 ) > 0 ) {
			$n    = (int) $item['quantity'];
			/* translators: 1: number of bars, 2: length in metres. */
			$bars = sprintf( _n( '%1$s bar of %2$s m', '%1$s bars of %2$s m', $n, 'sm-seo' ), Text::number( $n, 0 ), Text::number( $item['length_m'] ) );
		}
		$m['title'] = implode( ' – ', array_filter( array( $name, $engine ? $bars : '', $wilaya ) ) );

		// Description: facts only.
		$facts = array();
		if ( $is_bld ) {
			if ( (float) ( $item['area'] ?? 0 ) > 0 ) {
				$facts[] = Text::number( $item['area'], 0 ) . ' m²';
			}
			if ( in_array( $item['mode'] ?? '', array( 'assembled', 'dismantled' ), true ) ) {
				$facts[] = 'assembled' === $item['mode'] ? __( 'assembled', 'sm-seo' ) : __( 'dismantled', 'sm-seo' );
			}
		} else {
			if ( $engine && $bars ) {
				$facts[] = $bars;
			}
		}
		if ( (float) ( $item['weight_kg'] ?? 0 ) > 0 ) {
			$facts[] = Text::weight( $item['weight_kg'] );
		}
		if ( 'reuse' === $market && '' !== Text::condition_label( $item['condition'] ?? '' ) ) {
			$facts[] = Text::condition_label( $item['condition'] );
		}
		$head = implode( ' · ', array_filter( array( Text::market_label( $market ), $cat ) ) );
		$desc = $head . ' : ' . implode( ', ', array_filter( array_merge( array( $name ), $facts ) ) ) . '.';
		if ( 'ar' === Text::lang() ) {
			$desc = $head . ': ' . implode( '، ', array_filter( array_merge( array( $name ), $facts ) ) ) . '.';
		} elseif ( 'en' === Text::lang() ) {
			$desc = $head . ': ' . implode( ', ', array_filter( array_merge( array( $name ), $facts ) ) ) . '.';
		}
		$price = self::price( $item );
		$desc .= ' ' . $price['text'];
		if ( '' !== $wilaya ) {
			$desc .= ' ' . self::wilaya_phrase( $wilaya );
		}
		$m['description'] = Text::cut( $desc, 300 );
		$m['og_type']     = 'product';
		foreach ( (array) ( $item['photos'] ?? array() ) as $p ) {
			$img = Text::image( $p );
			if ( $img ) {
				$m['images'][] = $img;
			}
		}
		$m['image'] = $m['images'][0] ?? '';

		$market_url = Text::url( $market );
		$m['crumbs'] = array( array( Text::market_label( $market ), $market_url ) );
		if ( '' !== $cat && ! empty( $item['category'] ) ) {
			$m['crumbs'][] = array( $cat, add_query_arg( 'category', $item['category'], $market_url ) );
		}
		$m['crumbs'][] = array( $name, $m['canonical'] );
		$m['schema'][] = Schema::product( $item, $name, $m, $price );
		return $m;
	}

	/** « Wilaya de M'Sila. » / « Wilaya d'Alger. » (0.1.1: elision before a vowel in French). */
	public static function wilaya_phrase( $wilaya ) {
		$first = remove_accents( mb_substr( (string) $wilaya, 0, 1 ) );
		if ( preg_match( '/^[aeiouy]$/i', $first ) ) {
			/* translators: %s: wilaya whose name starts with a vowel (French: « Wilaya d’Alger. »). */
			return sprintf( _x( 'Wilaya of %s.', 'before a vowel', 'sm-seo' ), $wilaya );
		}
		/* translators: %s: wilaya. */
		return sprintf( __( 'Wilaya of %s.', 'sm-seo' ), $wilaya );
	}

	/**
	 * Asked price of a listing: sentence and offer data.
	 *
	 * @return array { text, price (0 = on request), per_kg (bool) }
	 */
	public static function price( array $item ) {
		$unit = (string) ( $item['price_unit'] ?? '' );
		$kg   = (float) ( $item['price_per_kg'] ?? 0 );
		$lot  = (float) ( $item['lot_price'] ?? 0 ) ?: (float) ( $item['total_price'] ?? 0 );
		$mode = (string) ( $item['sale_mode'] ?? '' );
		if ( 'kg' === $unit && $kg > 0 ) {
			if ( 'both' === $mode && $lot > 0 ) {
				/* translators: 1: price per kilogram, 2: price of the whole lot. */
				$text = sprintf( __( 'Price asked by the seller: %1$s DA/kg, or %2$s DA for the whole lot.', 'sm-seo' ), Text::number( $kg ), Text::number( $lot, 0 ) );
			} else {
				/* translators: %s: price per kilogram. */
				$text = sprintf( __( 'Price asked by the seller: %s DA/kg.', 'sm-seo' ), Text::number( $kg ) );
			}
			return array( 'text' => $text, 'price' => $kg, 'per_kg' => true );
		}
		if ( $lot > 0 ) {
			/* translators: %s: price of the lot. */
			return array( 'text' => sprintf( __( 'Price of the lot asked by the seller: %s DA.', 'sm-seo' ), Text::number( $lot, 0 ) ), 'price' => $lot, 'per_kg' => false );
		}
		return array( 'text' => __( 'Price on request.', 'sm-seo' ), 'price' => 0, 'per_kg' => false );
	}

	/** Public page of a company or a professional. */
	public static function company( array $m, $slug ) {
		$c = '' !== $slug ? Text::data( 'company', array( 'slug' => $slug ) ) : null;
		$m['canonical'] = Text::url( 'company', array( 'c' => $slug ) );
		if ( ! is_array( $c ) || empty( $c['id'] ) || empty( $c['real'] ) ) {
			$m['index'] = false;
			return $m;
		}
		$name   = Text::pick( $c['name'] ?? '' );
		$wilaya = Text::wilaya( $c['wilaya'] ?? '' );
		$pro    = 'professional' === ( $c['class'] ?? '' );
		if ( '' !== $wilaya ) {
			/* translators: %s: wilaya. */
			$what = $pro ? sprintf( __( 'steel professional in %s', 'sm-seo' ), $wilaya ) : sprintf( __( 'steel company in %s', 'sm-seo' ), $wilaya );
		} else {
			$what = $pro ? __( 'steel professional', 'sm-seo' ) : __( 'steel company', 'sm-seo' );
		}
		$about        = Text::pick( $c['about'] ?? '' );
		$m['title']   = $name . ' – ' . $what;
		/* translators: 1: name of the company, 2: « steel company in M'Sila ». */
		$m['description'] = '' !== $about ? Text::cut( $about, 300 ) : sprintf( __( '%1$s, %2$s on ReuSteel. Contact through the platform.', 'sm-seo' ), $name, $what );
		$m['image']       = Text::image( $c['logo_id'] ?? 0, 'full' );
		$m['og_type']     = 'profile';
		// A company page without listing, service or presentation is too thin to be indexed.
		if ( '' === $about && ! self::has_content( (int) $c['id'] ) ) {
			$m['index'] = false;
		}
		$dir          = $pro ? 'professionals' : 'companies';
		$m['crumbs']  = array( array( get_the_title( Text::page_id( $dir ) ) ?: ( $pro ? __( 'Professionals', 'sm-seo' ) : __( 'Companies', 'sm-seo' ) ), Text::url( $dir ) ), array( $name, $m['canonical'] ) );
		$m['schema'][] = Schema::company( $c, $name, $about, $wilaya, $m );
		return $m;
	}

	/** Whether a company has a published listing or service. */
	public static function has_content( $company_id ) {
		global $wpdb;
		$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_listing_index WHERE company_id = %d AND status = 'published'", $company_id ) ); // phpcs:ignore WordPress.DB
		if ( ! $n && Sitemaps::table_exists( $wpdb->prefix . 'sm_services' ) ) {
			$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_services WHERE company_id = %d AND status = 'published'", $company_id ) ); // phpcs:ignore WordPress.DB
		}
		return $n > 0;
	}

	/** Page of a service of a company (sm-services). */
	public static function service( array $m, $id ) {
		$s = $id ? Text::data( 'service', array( 'id' => $id, 'no_count' => true ) ) : null;
		$m['canonical'] = Text::url( 'service', array( 'id' => $id ) );
		if ( ! is_array( $s ) || empty( $s['id'] ) ) {
			$m['index'] = false;
			return $m;
		}
		if ( 'published' !== ( $s['status'] ?? '' ) ) {
			$m['index'] = false;
		}
		$title   = Text::pick( $s['title'] ?? '' );
		$company = ! empty( $s['company'] ) ? Text::data( 'company', array( 'slug' => $s['company'] ) ) : null;
		$cname   = is_array( $company ) ? Text::pick( $company['name'] ?? '' ) : '';
		$areas   = array_values( array_filter( array_map( array( Text::class, 'wilaya' ), (array) ( $s['wilayas'] ?? array() ) ) ) );
		$m['title'] = implode( ' – ', array_filter( array( $title, $cname ) ) );
		$desc       = Text::pick( $s['description'] ?? '' );
		if ( '' === $desc ) {
			$tags = array_values( array_filter( array_map( array( Text::class, 'pick' ), (array) ( $s['tags'] ?? array() ) ) ) );
			$desc = implode( ', ', array_filter( array( $title, implode( ', ', $tags ) ) ) ) . '.';
		}
		$where = $areas ? implode( ', ', array_slice( $areas, 0, 6 ) ) : __( 'all Algeria', 'sm-seo' );
		/* translators: %s: wilayas served. */
		$m['description'] = Text::cut( $desc . ' ' . sprintf( __( 'Served: %s.', 'sm-seo' ), $where ), 300 );
		$photo            = Text::image( $s['photos'][0] ?? 0 );
		$m['image']       = $photo;
		$m['crumbs']      = array( array( get_the_title( Text::page_id( 'services' ) ) ?: __( 'Services', 'sm-seo' ), Text::url( 'services' ) ), array( $title, $m['canonical'] ) );
		$svc              = array(
			'@type'      => 'Service',
			'name'       => $title,
			'url'        => $m['canonical'],
			'description' => $m['description'],
			'areaServed' => $areas ? array_map( static fn( $w ) => array( '@type' => 'AdministrativeArea', 'name' => $w ), $areas ) : array( '@type' => 'Country', 'name' => __( 'Algeria', 'sm-seo' ) ),
		);
		if ( $photo ) {
			$svc['image'] = $photo;
		}
		if ( '' !== $cname ) {
			$svc['provider'] = array( '@type' => 'Organization', 'name' => $cname, 'url' => Text::url( 'company', array( 'c' => $s['company'] ) ) );
		}
		$m['schema'][] = $svc;
		return $m;
	}
}
