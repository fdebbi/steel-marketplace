<?php
/**
 * Demo content provider — VISUAL TESTING ONLY.
 *
 * This is not reference data. Unit weights and prices below are sample values for
 * layout review, marked as demo in the interface. The validated profile tables of the
 * European standard will be owned by sm-weight-engine (Phase 3), and real listings by
 * sm-products / sm-reuse / sm-buildings. Disable demo mode in Customize -> SM Brand.
 *
 * Each record holds every language in one record (one listing, several languages),
 * mirroring the sm_translations model of the architecture document.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

class SM_Theme_Demo_Data {

	/** Route a resource request to its demo source. */
	public static function get( $resource, $args = array() ) {
		switch ( $resource ) {
			case 'rfqs':
				$items = self::rfqs();
				return array( 'items' => $items, 'total' => count( $items ), 'pages' => 1 );
			case 'listings':
				return self::query_listings( $args );
			case 'listing':
				foreach ( self::listings() as $item ) {
					if ( (string) $item['id'] === (string) ( $args['id'] ?? '' ) ) {
						return $item;
					}
				}
				return null;
			case 'companies':
				$companies = self::companies();
				// Only companies and professionals are public (0.13.0).
				$companies = array_filter( $companies, static fn( $c ) => in_array( $c['class'], array( 'company', 'professional' ), true ) );
				if ( ! empty( $args['class'] ) ) {
					$companies = array_filter( $companies, static fn( $c ) => $c['class'] === $args['class'] );
				}
				if ( ! empty( $args['activity'] ) ) {
					$companies = array_filter( $companies, static fn( $c ) => in_array( $args['activity'], $c['activities'], true ) );
				}
				return array_values( $companies );
			case 'company':
				$companies = self::companies();
				$found     = $companies[ $args['slug'] ?? '' ] ?? null;
				return $found && in_array( $found['class'], array( 'company', 'professional' ), true ) ? $found : null;
			case 'services':
				$services = self::services();
				if ( ! empty( $args['kind'] ) ) {
					$services = array_filter( $services, static fn( $s ) => $s['kind'] === $args['kind'] );
				}
				if ( ! empty( $args['company'] ) ) {
					$services = array_filter( $services, static fn( $s ) => $s['company'] === $args['company'] );
				}
				return array_values( $services );
			case 'categories':
				return self::categories( $args['market'] ?? 'products' );
			case 'wilayas':
				return self::wilayas();
			case 'account':
				return self::account( $args );
			case 'stats':
				return array(
					'listings'  => 1284,
					'companies' => 312,
					'reuse_kg'  => 486000,
					'wilayas'   => 41,
				);
		}
		return null;
	}

	/** Wilayas used by the demo (codes as on the official list). */
	public static function wilayas() {
		return array(
			'16' => array( 'fr' => 'Alger', 'ar' => 'الجزائر' ),
			'31' => array( 'fr' => 'Oran', 'ar' => 'وهران' ),
			'25' => array( 'fr' => 'Constantine', 'ar' => 'قسنطينة' ),
			'19' => array( 'fr' => 'Sétif', 'ar' => 'سطيف' ),
			'28' => array( 'fr' => "M'Sila", 'ar' => 'المسيلة' ),
			'09' => array( 'fr' => 'Blida', 'ar' => 'البليدة' ),
			'23' => array( 'fr' => 'Annaba', 'ar' => 'عنابة' ),
			'34' => array( 'fr' => 'Bordj Bou Arréridj', 'ar' => 'برج بوعريريج' ),
		);
	}

	public static function categories( $market ) {
		$all = array(
			'products'  => array(
				'beams'    => array( 'fr' => 'Poutrelles IPE / HEA / HEB', 'ar' => 'روافد IPE / HEA / HEB' ),
				'channels' => array( 'fr' => 'Profilés UPN', 'ar' => 'مقاطع UPN' ),
				'angles'   => array( 'fr' => 'Cornières', 'ar' => 'زوايا فولاذية' ),
				'tubes'    => array( 'fr' => 'Tubes carrés, rectangulaires et ronds', 'ar' => 'أنابيب مربعة ومستطيلة ودائرية' ),
				'plates'   => array( 'fr' => 'Tôles et larges plats', 'ar' => 'صفائح وأشرطة عريضة' ),
				'sheets'   => array( 'fr' => 'Tôles galvanisées et larmées', 'ar' => 'صفائح مجلفنة ومضلعة' ),
				'bars'     => array( 'fr' => 'Fers plats et ronds', 'ar' => 'قضبان مسطحة ودائرية' ),
				'bolts'    => array( 'fr' => 'Boulonnerie et tiges d’ancrage', 'ar' => 'براغي وقضبان تثبيت' ),
			),
			'reuse'     => array(
				'offcuts'  => array( 'fr' => 'Chutes de profilés', 'ar' => 'بقايا المقاطع' ),
				'surplus'  => array( 'fr' => 'Surplus de chantier', 'ar' => 'فائض الورشات' ),
				'used'     => array( 'fr' => 'Profilés et poutres d’occasion', 'ar' => 'مقاطع وروافد مستعملة' ),
				'purlins'  => array( 'fr' => 'Pannes et liernes', 'ar' => 'مدادات السقف' ),
				'scrap'    => array( 'fr' => 'Ferraille', 'ar' => 'خردة' ),
			),
			'buildings' => array(
				'industrial'  => array( 'fr' => 'Hangars industriels', 'ar' => 'هناجر صناعية' ),
				'agricultural' => array( 'fr' => 'Hangars agricoles', 'ar' => 'هناجر فلاحية' ),
				'poultry'     => array( 'fr' => 'Bâtiments d’élevage', 'ar' => 'مباني تربية الدواجن والمواشي' ),
				'storage'     => array( 'fr' => 'Stockage et entrepôts', 'ar' => 'مستودعات وتخزين' ),
				'workshop'    => array( 'fr' => 'Ateliers', 'ar' => 'ورشات' ),
			),
		);
		return $all[ $market ] ?? array();
	}

	public static function companies() {
		return array(
			'acier-hodna'          => array(
				'slug'         => 'acier-hodna',
				'name'         => array( 'fr' => 'Acier du Hodna', 'ar' => 'فولاذ الحضنة' ),
				'class'        => 'company',
				'activities'   => array( 'manufacturer', 'supplier', 'recycler' ),
				'wilaya'       => '28',
				'verification' => 2,
				'since'        => '2011',
				'phone'        => '035 00 00 00',
				'initials'     => 'AH',
				'about'        => array(
					'fr' => 'Fabrication de charpentes métalliques et négoce de profilés. Atelier de 4 000 m² avec ligne de découpe, perçage et soudage. Nous revendons aussi nos chutes et surplus de chantier.',
					'ar' => 'تصنيع الهياكل المعدنية وتجارة المقاطع الفولاذية. ورشة مساحتها 4000 م² مجهزة بخط قص وثقب ولحام. نبيع أيضا بقايا وفائض الورشات.',
				),
			),
			'setif-metal'          => array(
				'slug'         => 'setif-metal',
				'name'         => array( 'fr' => 'Sétif Métal Distribution', 'ar' => 'سطيف لتوزيع المعادن' ),
				'class'        => 'company',
				'activities'   => array( 'supplier' ),
				'wilaya'       => '19',
				'verification' => 2,
				'since'        => '2008',
				'phone'        => '036 00 00 00',
				'initials'     => 'SM',
				'about'        => array(
					'fr' => 'Distributeur de produits sidérurgiques : poutrelles, tubes, tôles et accessoires. Stock permanent et livraison dans l’Est.',
					'ar' => 'موزع منتجات الحديد والصلب: روافد وأنابيب وصفائح وملحقات. مخزون دائم وتوصيل في الشرق.',
				),
			),
			'oran-montage'         => array(
				'slug'         => 'oran-montage',
				'name'         => array( 'fr' => 'Oran Montage Charpente', 'ar' => 'وهران لتركيب الهياكل' ),
				'class'        => 'company',
				'activities'   => array( 'construction', 'dismantler' ),
				'wilaya'       => '31',
				'verification' => 1,
				'since'        => '2015',
				'phone'        => '041 00 00 00',
				'initials'     => 'OM',
				'about'        => array(
					'fr' => 'Montage et démontage de hangars, bâtiments industriels et agricoles. Équipes avec nacelles et grue mobile.',
					'ar' => 'تركيب وتفكيك الهناجر والمباني الصناعية والفلاحية. فرق مجهزة برافعات وسلال رفع.',
				),
			),
			'recup-acier-blida'    => array(
				'slug'         => 'recup-acier-blida',
				'name'         => array( 'fr' => 'Récup’Acier Blida', 'ar' => 'استرجاع الفولاذ البليدة' ),
				'class'        => 'company',
				'activities'   => array( 'recycler', 'dismantler' ),
				'wilaya'       => '09',
				'verification' => 2,
				'since'        => '2017',
				'phone'        => '025 00 00 00',
				'initials'     => 'RA',
				'about'        => array(
					'fr' => 'Achat et revente de structures démontées, profilés d’occasion et ferraille triée.',
					'ar' => 'شراء وإعادة بيع الهياكل المفككة والمقاطع المستعملة والخردة المفرزة.',
				),
			),
			'benali-soudure'       => array(
				'slug'         => 'benali-soudure',
				'name'         => array( 'fr' => 'Benali Soudure', 'ar' => 'بن علي للحام' ),
				'class'        => 'professional',
				'activities'   => array( 'trade_welder', 'trade_erector' ),
				'wilaya'       => '16',
				'verification' => 1,
				'since'        => '2020',
				'phone'        => '0550 00 00 00',
				'initials'     => 'BS',
				'experience'   => 14,
				'title'        => array( 'fr' => 'Soudeur qualifié — montage de charpentes', 'ar' => 'لحام مؤهل — تركيب الهياكل' ),
				'about'        => array(
					'fr' => 'Soudure MIG et à l’arc, montage de charpentes et réparations sur chantier dans le centre du pays.',
					'ar' => 'لحام MIG وبالقوس، تركيب الهياكل والإصلاحات في الورشات بوسط البلاد.',
				),
			),
			'ferme-el-amel'        => array(
				'slug'         => 'ferme-el-amel',
				'name'         => array( 'fr' => 'Ferme avicole El Amel', 'ar' => 'مزرعة الأمل للدواجن' ),
				'class'        => 'client',
				'activities'   => array( 'client' ),
				'wilaya'       => '34',
				'verification' => 1,
				'since'        => '2019',
				'phone'        => '035 00 00 01',
				'initials'     => 'FA',
				'about'        => array(
					'fr' => 'Exploitation avicole. Nous vendons un bâtiment d’élevage démonté suite à notre extension.',
					'ar' => 'مستثمرة لتربية الدواجن. نبيع مبنى تربية مفككا بعد توسعة المستثمرة.',
				),
			),
			'bet-structures-acier' => array(
				'slug'         => 'bet-structures-acier',
				'name'         => array( 'fr' => 'BET Structures Acier', 'ar' => 'مكتب دراسات الهياكل الفولاذية' ),
				'class'        => 'engineer',
				'activities'   => array( 'engineer' ),
				'wilaya'       => '25',
				'verification' => 3,
				'since'        => '2013',
				'phone'        => '031 00 00 00',
				'initials'     => 'BS',
				'title'        => array( 'fr' => 'Bureau d’études — charpente métallique', 'ar' => 'مكتب دراسات — الهياكل المعدنية' ),
				'about'        => array(
					'fr' => 'Conception et calcul de structures métalliques, assemblages, plans d’exécution et expertise de bâtiments existants avant réemploi.',
					'ar' => 'تصميم وحساب الهياكل المعدنية والوصلات ومخططات التنفيذ وخبرة المباني القائمة قبل إعادة استعمالها.',
				),
				'qualifications' => array(
					array( 'fr' => 'Ingénieur d’État en génie civil', 'ar' => 'مهندس دولة في الهندسة المدنية' ),
					array( 'fr' => 'Agrément bureau d’études', 'ar' => 'اعتماد مكتب دراسات' ),
				),
				'specialities' => array(
					array( 'fr' => 'Calcul selon l’Eurocode 3', 'ar' => 'الحساب وفق Eurocode 3' ),
					array( 'fr' => 'Hangars à portiques', 'ar' => 'هناجر بالإطارات' ),
					array( 'fr' => 'Diagnostic de structures d’occasion', 'ar' => 'تشخيص الهياكل المستعملة' ),
				),
			),
		);
	}

	/**
	 * Demo listings. Weights are stored as listings will store them: the computed
	 * total weight and its source. "actual" = measured by the seller.
	 */
	public static function listings() {
		$d = static fn( $days ) => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		return array(
			array(
				'id' => 101, 'market' => 'products', 'category' => 'beams', 'designation' => 'IPE 200', 'grade' => 'S275JR',
				'title' => array( 'fr' => 'Poutrelles IPE 200 — barres de 12 m', 'ar' => 'روافد IPE 200 — قضبان بطول 12 م' ),
				'description' => array(
					'fr' => 'Poutrelles neuves laminées à chaud, livrées avec certificat matière 3.1. Découpe à la demande dans notre atelier.',
					'ar' => 'روافد جديدة مدرفلة على الساخن، تسلم مع شهادة المادة 3.1. القص حسب الطلب في ورشتنا.',
				),
				'length_m' => 12, 'quantity' => 24, 'weight_kg' => 6451, 'weight_source' => 'theoretical',
				'price_per_kg' => 245, 'wilaya' => '28', 'company' => 'acier-hodna', 'featured' => true, 'date' => $d( 1 ),
				'image' => 'demo:ibeam', 'stock' => 'in_stock',
				'specs' => array( 'standard' => 'EN 10365', 'finish' => array( 'fr' => 'Brut de laminage', 'ar' => 'خام الدرفلة' ) ),
			),
			array(
				'id' => 102, 'market' => 'products', 'category' => 'beams', 'designation' => 'HEA 200', 'grade' => 'S275JR',
				'title' => array( 'fr' => 'Poutrelles HEA 200 en stock', 'ar' => 'روافد HEA 200 متوفرة' ),
				'description' => array( 'fr' => 'Stock disponible, enlèvement ou livraison sur chantier.', 'ar' => 'مخزون متوفر، استلام من المستودع أو توصيل إلى الورشة.' ),
				'length_m' => 12, 'quantity' => 10, 'weight_kg' => 5076, 'weight_source' => 'theoretical',
				'price_per_kg' => 250, 'wilaya' => '19', 'company' => 'setif-metal', 'featured' => false, 'date' => $d( 2 ),
				'image' => 'demo:ibeam', 'stock' => 'in_stock',
				'specs' => array( 'standard' => 'EN 10365' ),
			),
			array(
				'id' => 103, 'market' => 'products', 'category' => 'tubes', 'designation' => 'RHS 100×50×4', 'grade' => 'S235JRH',
				'title' => array( 'fr' => 'Tubes rectangulaires 100×50×4', 'ar' => 'أنابيب مستطيلة 100×50×4' ),
				'description' => array( 'fr' => 'Tubes soudés, barres de 6 m, idéals pour ossatures secondaires.', 'ar' => 'أنابيب ملحومة بطول 6 م، مناسبة للهياكل الثانوية.' ),
				'length_m' => 6, 'quantity' => 80, 'weight_kg' => 4320, 'weight_source' => 'theoretical',
				'price_per_kg' => 265, 'wilaya' => '19', 'company' => 'setif-metal', 'featured' => true, 'date' => $d( 3 ),
				'image' => 'demo:tubes', 'stock' => 'in_stock',
			),
			array(
				'id' => 104, 'market' => 'products', 'category' => 'plates', 'designation' => 'PL 10 mm', 'grade' => 'S355J2',
				'title' => array( 'fr' => 'Tôles épaisseur 10 mm, 2 000 × 6 000', 'ar' => 'صفائح سمك 10 مم، 2000 × 6000' ),
				'description' => array( 'fr' => 'Tôles fortes pour platines et goussets, découpe plasma possible.', 'ar' => 'صفائح سميكة للصفائح القاعدية والوصلات، مع إمكانية القص بالبلازما.' ),
				'length_m' => 6, 'quantity' => 8, 'weight_kg' => 7536, 'weight_source' => 'theoretical',
				'price_per_kg' => 240, 'wilaya' => '16', 'company' => 'acier-hodna', 'featured' => false, 'date' => $d( 5 ),
				'image' => 'demo:plates', 'stock' => 'on_order',
			),
			array(
				'id' => 105, 'market' => 'products', 'category' => 'channels', 'designation' => 'UPN 120', 'grade' => 'S235JR',
				'title' => array( 'fr' => 'UPN 120 — lot de 40 barres', 'ar' => 'UPN 120 — مجموعة من 40 قضيبا' ),
				'description' => array( 'fr' => 'Profilés en U pour pannes et chevêtres.', 'ar' => 'مقاطع على شكل U للمدادات والعوارض.' ),
				'length_m' => 12, 'quantity' => 40, 'weight_kg' => 6432, 'weight_source' => 'theoretical',
				'price_per_kg' => 238, 'wilaya' => '31', 'company' => 'oran-montage', 'featured' => false, 'date' => $d( 6 ),
				'image' => 'demo:channel', 'stock' => 'in_stock',
			),
			array(
				'id' => 201, 'market' => 'reuse', 'category' => 'offcuts', 'designation' => 'IPE 300', 'grade' => 'S275JR', 'condition' => 'new_surplus',
				'title' => array( 'fr' => 'Chutes IPE 300 neuves, longueurs 1,8 à 3,2 m', 'ar' => 'بقايا IPE 300 جديدة، أطوال من 1,8 إلى 3,2 م' ),
				'description' => array( 'fr' => 'Chutes issues d’un chantier de charpente, jamais posées. Pesées sur pont-bascule.', 'ar' => 'بقايا من ورشة هياكل لم تركب أبدا. وزنت على الميزان الأرضي.' ),
				'length_m' => 2.5, 'quantity' => 36, 'weight_kg' => 3820, 'weight_source' => 'actual',
				'price_per_kg' => 165, 'wilaya' => '28', 'company' => 'acier-hodna', 'featured' => true, 'date' => $d( 1 ),
				'image' => 'demo:offcuts',
			),
			array(
				'id' => 202, 'market' => 'reuse', 'category' => 'used', 'designation' => 'HEA 240', 'grade' => 'S235', 'condition' => 'good',
				'title' => array( 'fr' => 'Poteaux HEA 240 d’occasion, 7 m', 'ar' => 'أعمدة HEA 240 مستعملة، 7 م' ),
				'description' => array( 'fr' => 'Issus du démontage d’un hangar. Platines découpées, légère corrosion de surface.', 'ar' => 'ناتجة عن تفكيك هنغار. الصفائح القاعدية مقطوعة، صدأ سطحي خفيف.' ),
				'length_m' => 7, 'quantity' => 12, 'weight_kg' => 5130, 'weight_source' => 'actual',
				'price_per_kg' => 140, 'wilaya' => '09', 'company' => 'recup-acier-blida', 'featured' => true, 'date' => $d( 2 ),
				'image' => 'demo:ibeam-used',
			),
			array(
				'id' => 203, 'market' => 'reuse', 'category' => 'purlins', 'designation' => 'Z 200', 'grade' => 'S350GD', 'condition' => 'excellent',
				'title' => array( 'fr' => 'Pannes Z 200 galvanisées démontées', 'ar' => 'مدادات Z 200 مجلفنة مفككة' ),
				'description' => array( 'fr' => 'Pannes en excellent état, galvanisation intacte, numérotées au démontage.', 'ar' => 'مدادات في حالة ممتازة، التجليفن سليم، مرقمة عند التفكيك.' ),
				'length_m' => 6, 'quantity' => 64, 'weight_kg' => 2110, 'weight_source' => 'actual',
				'price_per_kg' => 155, 'wilaya' => '31', 'company' => 'oran-montage', 'featured' => false, 'date' => $d( 4 ),
				'image' => 'demo:channel',
			),
			array(
				'id' => 204, 'market' => 'reuse', 'category' => 'used', 'designation' => 'IPE 360', 'grade' => 'S235', 'condition' => 'inspection',
				'title' => array( 'fr' => 'Traverses IPE 360 — à contrôler', 'ar' => 'عوارض IPE 360 — تحتاج فحصا' ),
				'description' => array( 'fr' => 'Traverses de portique, trous de boulonnage existants. Contrôle conseillé avant réemploi structurel.', 'ar' => 'عوارض إطارات بثقوب براغ موجودة. ينصح بالفحص قبل إعادة الاستعمال الإنشائي.' ),
				'length_m' => 10, 'quantity' => 6, 'weight_kg' => 3420, 'weight_source' => 'actual',
				'price_per_kg' => 120, 'wilaya' => '23', 'company' => 'recup-acier-blida', 'featured' => false, 'date' => $d( 7 ),
				'image' => 'demo:ibeam-used',
			),
			array(
				'id' => 205, 'market' => 'reuse', 'category' => 'scrap', 'designation' => 'E40', 'grade' => '—', 'condition' => 'scrap',
				'title' => array( 'fr' => 'Ferraille de charpente triée — 18 t', 'ar' => 'خردة هياكل مفرزة — 18 طن' ),
				'description' => array( 'fr' => 'Ferraille lourde triée, chargement par grue sur site.', 'ar' => 'خردة ثقيلة مفرزة، التحميل بالرافعة في الموقع.' ),
				'length_m' => 0, 'quantity' => 1, 'weight_kg' => 18000, 'weight_source' => 'actual',
				'price_per_kg' => 42, 'wilaya' => '09', 'company' => 'recup-acier-blida', 'featured' => false, 'date' => $d( 3 ),
				'image' => 'demo:scrap',
			),
			array(
				'id' => 301, 'market' => 'buildings', 'category' => 'industrial', 'mode' => 'assembled', 'condition' => 'good',
				'title' => array( 'fr' => 'Hangar industriel 40 × 20 m monté', 'ar' => 'هنغار صناعي 40 × 20 م مركب' ),
				'description' => array( 'fr' => 'Hangar à portiques en bon état, vendu monté. Démontage possible par nos équipes.', 'ar' => 'هنغار بإطارات في حالة جيدة، يباع مركبا. التفكيك ممكن من طرف فرقنا.' ),
				'length' => 40, 'width' => 20, 'height' => 8, 'portals' => 9,
				'system' => array( 'fr' => 'Portiques à âme pleine', 'ar' => 'إطارات بجذع مصمت' ),
				'roof' => array( 'fr' => 'Deux versants, pente 10 %', 'ar' => 'سطح بمنحدرين، ميل 10 %' ),
				'roof_cover' => array( 'fr' => 'Panneaux sandwich 40 mm', 'ar' => 'ألواح ساندويتش 40 مم' ),
				'cladding' => array( 'fr' => 'Bardage simple peau', 'ar' => 'كسوة جانبية بطبقة واحدة' ),
				'asking_price' => 9800000, 'weight_kg' => 32000, 'weight_source' => 'theoretical',
				'includes' => array( 'dismantling' => true, 'transport' => false, 'reinstall' => false, 'foundations' => false ),
				'wilaya' => '31', 'company' => 'oran-montage', 'featured' => true, 'date' => $d( 2 ),
				'image' => 'demo:warehouse',
			),
			array(
				'id' => 302, 'market' => 'buildings', 'category' => 'poultry', 'mode' => 'dismantled', 'condition' => 'excellent',
				'title' => array( 'fr' => 'Bâtiment d’élevage démonté 60 × 12 m', 'ar' => 'مبنى تربية مفكك 60 × 12 م' ),
				'description' => array( 'fr' => 'Structure démontée et stockée à l’abri, inventaire complet ci-dessous.', 'ar' => 'هيكل مفكك ومخزن في مكان مغطى، الجرد الكامل أدناه.' ),
				'length' => 60, 'width' => 12, 'height' => 4.5, 'portals' => 11,
				'system' => array( 'fr' => 'Portiques IPE, poteaux HEA', 'ar' => 'إطارات IPE وأعمدة HEA' ),
				'roof' => array( 'fr' => 'Deux versants', 'ar' => 'سطح بمنحدرين' ),
				'roof_cover' => array( 'fr' => 'Panneaux sandwich 50 mm', 'ar' => 'ألواح ساندويتش 50 مم' ),
				'cladding' => array( 'fr' => 'Non inclus', 'ar' => 'غير مشمولة' ),
				'price_per_kg' => 170, 'weight_source' => 'theoretical',
				'elements' => array(
					array( 'type' => array( 'fr' => 'Poteaux', 'ar' => 'أعمدة' ), 'designation' => 'HEA 160', 'qty' => 22, 'length' => 4.5, 'unit_weight' => 30.4 ),
					array( 'type' => array( 'fr' => 'Traverses', 'ar' => 'عوارض' ), 'designation' => 'IPE 220', 'qty' => 22, 'length' => 6.2, 'unit_weight' => 26.2 ),
					array( 'type' => array( 'fr' => 'Pannes', 'ar' => 'مدادات' ), 'designation' => 'IPE 140', 'qty' => 50, 'length' => 6, 'unit_weight' => 12.9 ),
					array( 'type' => array( 'fr' => 'Lisses', 'ar' => 'عوارض جانبية' ), 'designation' => 'UPN 120', 'qty' => 40, 'length' => 6, 'unit_weight' => 13.4 ),
				),
				'includes' => array( 'dismantling' => true, 'transport' => false, 'reinstall' => false, 'foundations' => false ),
				'wilaya' => '34', 'company' => 'ferme-el-amel', 'featured' => true, 'date' => $d( 4 ),
				'image' => 'demo:warehouse-frame',
			),
			array(
				'id' => 303, 'market' => 'buildings', 'category' => 'storage', 'mode' => 'assembled', 'condition' => 'new',
				'title' => array( 'fr' => 'Kit d’entrepôt neuf 30 × 15 m', 'ar' => 'هيكل مستودع جديد 30 × 15 م' ),
				'description' => array( 'fr' => 'Kit fabriqué sur mesure, livré avec plans et boulonnerie. Montage en option.', 'ar' => 'هيكل مصنع حسب المقاس، يسلم مع المخططات والبراغي. التركيب اختياري.' ),
				'length' => 30, 'width' => 15, 'height' => 7, 'portals' => 7,
				'system' => array( 'fr' => 'Portiques à âme pleine', 'ar' => 'إطارات بجذع مصمت' ),
				'roof' => array( 'fr' => 'Deux versants', 'ar' => 'سطح بمنحدرين' ),
				'roof_cover' => array( 'fr' => 'Bac acier', 'ar' => 'صفائح فولاذية مضلعة' ),
				'cladding' => array( 'fr' => 'Bac acier', 'ar' => 'صفائح فولاذية مضلعة' ),
				'asking_price' => 8600000, 'weight_kg' => 21500, 'weight_source' => 'theoretical',
				'includes' => array( 'dismantling' => false, 'transport' => true, 'reinstall' => false, 'foundations' => false ),
				'wilaya' => '28', 'company' => 'acier-hodna', 'featured' => false, 'date' => $d( 8 ),
				'image' => 'demo:warehouse',
			),
		);
	}

	public static function services() {
		return array(
			array(
				'id' => 401, 'kind' => 'fabrication', 'company' => 'acier-hodna', 'image' => 'demo:welding',
				'title' => array( 'fr' => 'Fabrication de charpente : découpe, perçage, soudage', 'ar' => 'تصنيع الهياكل: قص وثقب ولحام' ),
				'tags' => array( array( 'fr' => 'Découpe', 'ar' => 'قص' ), array( 'fr' => 'Soudage', 'ar' => 'لحام' ), array( 'fr' => 'Peinture', 'ar' => 'طلاء' ) ),
			),
			array(
				'id' => 402, 'kind' => 'fabrication', 'company' => 'oran-montage', 'image' => 'demo:warehouse-frame',
				'title' => array( 'fr' => 'Montage et démontage de hangars', 'ar' => 'تركيب وتفكيك الهناجر' ),
				'tags' => array( array( 'fr' => 'Montage', 'ar' => 'تركيب' ), array( 'fr' => 'Démontage', 'ar' => 'تفكيك' ), array( 'fr' => 'Transport', 'ar' => 'نقل' ) ),
			),
			array(
				'id' => 403, 'kind' => 'fabrication', 'company' => 'benali-soudure', 'image' => 'demo:welding',
				'title' => array( 'fr' => 'Soudure et montage de charpente sur chantier', 'ar' => 'لحام وتركيب الهياكل في الورشة' ),
				'tags' => array( array( 'fr' => 'Soudage', 'ar' => 'لحام' ), array( 'fr' => 'Montage', 'ar' => 'تركيب' ) ),
			),
		);
	}

	/** Demo dashboard account: ?account=engineer shows the engineering dashboard. */
	public static function account( $args ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type = isset( $_GET['account'] ) ? sanitize_key( wp_unslash( $_GET['account'] ) ) : ( $args['type'] ?? 'company' );
		if ( 'engineer' === $type ) {
			return array(
				'class'    => 'engineer',
				'company'  => self::companies()['bet-structures-acier'],
				'user'     => 'Karim B.',
				'plan'     => array( 'fr' => 'Pro', 'ar' => 'احترافي' ),
				'usage'    => array( 'services' => array( 2, 10 ), 'ai_credits' => array( 40, 100 ) ),
				'kpis'     => array( 'requests' => 7, 'views' => 1240, 'quotes' => 3 ),
			);
		}
		return array(
			'class'   => 'company',
			'company' => self::companies()['acier-hodna'],
			'user'    => 'Samir H.',
			'plan'    => array( 'fr' => 'Business', 'ar' => 'أعمال' ),
			'usage'   => array( 'listings' => array( 18, 50 ), 'featured' => array( 2, 5 ), 'ai_credits' => array( 120, 300 ) ),
			'kpis'    => array( 'active' => 18, 'views' => 5320, 'enquiries' => 26, 'offers' => 4 ),
		);
	}

	/** Demo requests for quotation (same shape as sm-rfq's "rfq" resource). */
	public static function rfqs() {
		$d    = static fn( $days ) => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$in   = static fn( $days ) => gmdate( 'Y-m-d', time() + $days * DAY_IN_SECONDS );
		$base = array( 'description' => array(), 'notes' => array(), 'category' => '', 'category_label' => array(), 'dimensions' => '', 'lines' => array(), 'place' => '', 'needed_by' => null, 'status' => 'open', 'is_open' => true, 'company' => '', 'company_name' => array(), 'verification' => 1, 'weight_source' => '', 'demo' => true );
		return array(
			array_merge( $base, array( 'id' => 9001, 'title' => array( 'fr' => 'IPE 270 — 48 barres de 12 m, livraison chantier', 'ar' => 'IPE 270 — 48 قضيبا بطول 12 م، توصيل إلى الورشة' ), 'market' => 'products', 'market_label' => array( 'fr' => 'Produits neufs', 'ar' => 'منتجات جديدة' ), 'designation' => 'IPE 270', 'quantity' => 48, 'unit' => 'pieces', 'weight_kg' => 20909, 'weight_source' => 'theoretical', 'wilaya' => '16', 'date' => $d( 1 ), 'deadline' => $in( 12 ), 'days_left' => 12, 'offers_count' => 5 ) ),
			array_merge( $base, array( 'id' => 9002, 'title' => array( 'fr' => 'Hangar de stockage 25 × 15 m, neuf ou occasion', 'ar' => 'هنغار تخزين 25 × 15 م، جديد أو مستعمل' ), 'market' => 'buildings', 'market_label' => array( 'fr' => 'Bâtiments métalliques', 'ar' => 'المباني الفولاذية' ), 'designation' => 'Hangar 25 × 15 m', 'quantity' => 1, 'unit' => 'lot', 'weight_kg' => 0, 'wilaya' => '19', 'date' => $d( 2 ), 'deadline' => $in( 20 ), 'days_left' => 20, 'offers_count' => 3 ) ),
			array_merge( $base, array( 'id' => 9003, 'title' => array( 'fr' => 'Tubes carrés 80×80×4 — 120 barres', 'ar' => 'أنابيب مربعة 80×80×4 — 120 قضيبا' ), 'market' => 'products', 'market_label' => array( 'fr' => 'Produits neufs', 'ar' => 'منتجات جديدة' ), 'designation' => 'SHS 80x80x4', 'quantity' => 120, 'unit' => 'pieces', 'weight_kg' => 0, 'wilaya' => '28', 'date' => $d( 4 ), 'deadline' => $in( 6 ), 'days_left' => 6, 'offers_count' => 7 ) ),
		);
	}

	/** Very small in-memory filter, enough to review filters and empty states. */
	private static function query_listings( $args ) {
		$items = self::listings();
		$f     = static function ( $key ) use ( $args ) {
			return isset( $args[ $key ] ) && '' !== $args[ $key ] ? $args[ $key ] : null;
		};

		if ( $f( 'market' ) ) {
			$markets = (array) $args['market'];
			$items   = array_filter( $items, static fn( $i ) => in_array( $i['market'], $markets, true ) );
		}
		foreach ( array( 'category', 'condition', 'wilaya', 'company', 'mode' ) as $key ) {
			if ( $f( $key ) ) {
				$items = array_filter( $items, static fn( $i ) => ( $i[ $key ] ?? '' ) === $args[ $key ] );
			}
		}
		if ( $f( 'featured' ) ) {
			$items = array_filter( $items, static fn( $i ) => ! empty( $i['featured'] ) );
		}
		if ( $f( 'q' ) ) {
			$q     = mb_strtolower( $args['q'] );
			$items = array_filter(
				$items,
				static function ( $i ) use ( $q ) {
					$hay = mb_strtolower( implode( ' ', array_merge( (array) $i['title'], array( $i['designation'] ?? '' ) ) ) );
					return false !== mb_strpos( $hay, $q );
				}
			);
		}

		$items = array_map( array( __CLASS__, 'with_totals' ), array_values( $items ) );

		$sort = $args['sort'] ?? 'recent';
		usort(
			$items,
			static function ( $a, $b ) use ( $sort ) {
				if ( 'price_asc' === $sort ) {
					return ( $a['price_per_kg'] ?? 0 ) <=> ( $b['price_per_kg'] ?? 0 );
				}
				if ( 'price_desc' === $sort ) {
					return ( $b['price_per_kg'] ?? 0 ) <=> ( $a['price_per_kg'] ?? 0 );
				}
				return strcmp( $b['date'], $a['date'] );
			}
		);

		$total    = count( $items );
		$per_page = max( 1, (int) $args['per_page'] );
		$page     = max( 1, (int) $args['page'] );
		return array(
			'items' => array_slice( $items, ( $page - 1 ) * $per_page, $per_page ),
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Demo totals so cards show complete price blocks. In production these values are
	 * computed by sm-weight-engine and stored on the listing; the theme only displays them.
	 */
	public static function with_totals( $item ) {
		if ( ! empty( $item['elements'] ) ) {
			$total = 0;
			foreach ( $item['elements'] as $k => $el ) {
				$item['elements'][ $k ]['total'] = $el['qty'] * $el['length'] * $el['unit_weight'];
				$total                          += $item['elements'][ $k ]['total'];
			}
			$item['weight_kg']   = $total;
			$item['total_price'] = $total * $item['price_per_kg'];
		} elseif ( isset( $item['asking_price'] ) ) {
			$item['total_price']  = $item['asking_price'];
			$item['price_per_kg'] = $item['weight_kg'] ? $item['asking_price'] / $item['weight_kg'] : 0;
		} else {
			$item['total_price'] = $item['weight_kg'] * $item['price_per_kg'];
		}
		if ( isset( $item['length'], $item['width'] ) ) {
			$item['area']         = $item['length'] * $item['width'];
			$item['price_per_m2'] = $item['total_price'] / $item['area'];
		}
		$item['demo'] = true;
		return $item;
	}
}

// The single listing resource also gets demo totals.
add_filter(
	'sm_theme_data_listing',
	static function ( $result, $args ) {
		if ( null === $result && sm_theme_demo_enabled() ) {
			$item = SM_Theme_Demo_Data::get( 'listing', $args );
			return $item ? SM_Theme_Demo_Data::with_totals( $item ) : null;
		}
		return $result;
	},
	999,
	2
);
