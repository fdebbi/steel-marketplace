<?php
/**
 * SM Guides tests (test site only): wp eval-file wp-content/plugins/sm-guides/tests/run.php
 *
 * @package SM\Guides
 */

use SM\Guides\Faq;
use SM\Guides\Guides;
use SM\Guides\Install\Installer;
use SM\Guides\Markdown;
use SM\Guides\Pages;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smg_t'] = array( 'pass' => 0, 'fail' => 0 );
function smg_assert( $cond, $label ) {
	++$GLOBALS['smg_t'][ $cond ? 'pass' : 'fail' ];
	echo ( $cond ? '  ✓ ' : '  ✗ ' ) . $label . "\n";
}

echo "Markdown\n";
$r = Markdown::render( "## Un titre\n\nTexte **gras** et *italique*.\n\n- [ ] point\n- [ ] autre\n\n1. un\n2. deux\n\n| A | B |\n| --- | --- |\n| 1 | 2 |\n\n> note" );
smg_assert( false !== strpos( $r['html'], '<h2 id="s1">Un titre</h2>' ) && 1 === count( $r['toc'] ) && 'Un titre' === $r['toc'][0]['title'], 'section heading with its id, in the table of contents' );
smg_assert( false !== strpos( $r['html'], '<strong>gras</strong>' ) && false !== strpos( $r['html'], '<em>italique</em>' ), 'bold and italic' );
smg_assert( false !== strpos( $r['html'], '<ul class="sm-guide__check"><li>point</li>' ), 'check list' );
smg_assert( false !== strpos( $r['html'], '<ol><li>un</li><li>deux</li></ol>' ), 'numbered list' );
smg_assert( false !== strpos( $r['html'], '<th scope="col">A</th>' ) && false !== strpos( $r['html'], '<td>2</td>' ), 'table' );
smg_assert( false !== strpos( $r['html'], '<blockquote><p>note</p></blockquote>' ), 'quote' );
$x = Markdown::render( "<script>alert(1)</script> [lien](javascript:alert(1)) [ok](https://reusteel.com/)" );
smg_assert( false === strpos( $x['html'], '<script' ) && false === strpos( $x['html'], 'javascript:' ) && false !== strpos( $x['html'], '<a href="https://reusteel.com/">ok</a>' ), 'HTML escaped, only http(s) links' );
$ar = Markdown::render( "## قسم\n\nمحسوب م م\n\n- [ ] عاينت الدفعة" );
smg_assert( false !== strpos( $ar['html'], '<h2 id="s1">قسم</h2>' ) && false !== strpos( $ar['html'], '<p>محسوب م م</p>' ) && false !== strpos( $ar['html'], '<li>عاينت الدفعة</li>' ), 'Arabic text kept whole (« م » holds the byte 0x85)' );
$s = Markdown::split( "# Titre\n\nPremier paragraphe\nsur deux lignes.\n\n## A\n\nTexte" );
smg_assert( 'Titre' === $s['title'] && 'Premier paragraphe sur deux lignes.' === $s['summary'] && 0 === strpos( $s['body'], '## A' ), 'split: title, first paragraph, body' );

echo "\nGuides written at installation\n";
$list = Guides::published();
$slugs = wp_list_pluck( $list, 'post_name' );
smg_assert( array( 'acheter-acier-reemploi', 'batiment-metallique-occasion', 'reduire-chutes-barres-6-12-m', 'vendre-chutes-surplus-acier' ) === $slugs, 'the 4 validated guides, in the order 3, 5, 6, 4' );
$all3 = true;
foreach ( $list as $p ) {
	$all3 = $all3 && array( 'fr', 'ar', 'en' ) === Guides::languages( $p );
}
smg_assert( $all3, 'each guide written in French, Arabic and English' );
$f = Guides::fields( $list[0] );
smg_assert( false === strpos( implode( ' ', $f['body'] ), 'Brouillon' ) && false === strpos( implode( ' ', $f['body'] ), '@' ), 'no review note and no author mention in the texts' );
smg_assert( array( 'reuse', 'weight', 'engineers', 'catalogue' ) === $f['tools'], 'tools of the guide 3: reuse first (main button)' );
$id = $list[0]->ID;
wp_update_post( array( 'ID' => $id, 'post_title' => 'Titre modifié' ) );
delete_option( 'sm_guides_seed' );
smg_assert( 0 === Installer::seed() && 'Titre modifié' === get_the_title( $id ), 'installation again: an existing guide is never written over' );
wp_update_post( array( 'ID' => $id, 'post_title' => $f['title']['fr'] ) );
$n = 0;
foreach ( $list as $p ) {
	$g = Guides::fields( $p );
	foreach ( array( 'fr', 'ar', 'en' ) as $l ) {
		$n += Markdown::sections( $g['body'][ $l ] ) === Markdown::sections( $g['body']['fr'] ) ? 0 : 1;
	}
}
smg_assert( 0 === $n, 'same number of sections in the 3 languages of each guide' );

echo "\nPages\n";
smg_assert( (bool) preg_match( '#/guides/acheter-acier-reemploi/$#', Pages::url( 'acheter-acier-reemploi' ) ), 'address /guides/{name}/' );
$d = Pages::data( null, array( 'slug' => 'acheter-acier-reemploi' ) );
smg_assert( 'guide' === $d['kind'] && 7 === count( $d['toc'] ) && 3 === count( $d['others'] ) && false === $d['fallback'], 'a guide: table of contents, 3 other guides' );
smg_assert( 'reuse' === $d['tools'][0]['key'] && '' !== $d['tools'][0]['url'], 'tools with their address' );
smg_assert( null === Pages::data( null, array( 'slug' => 'nexiste-pas' ) ), 'unknown name: null (page not found)' );
$i = Pages::data( null, array() );
smg_assert( 'index' === $i['kind'] && 4 === count( $i['items'] ) && $i['items'][0]['sections'] > 0, 'list of the guides with their number of sections' );
$rel = Pages::data( null, array( 'for' => 'cutting' ) );
smg_assert( 1 === count( $rel ) && false !== strpos( $rel[0]['url'], 'reduire-chutes' ), 'guide proposed on the cutting optimiser' );
smg_assert( 2 === count( Pages::data( null, array( 'for' => 'reuse' ) ) ), 'guides proposed on the reuse market: 2' );

echo "\nLanguage not written\n";
$en = Guides::fields( $id );
$saved_body = $en['body']['en'];
$en['body']['en'] = '';
Guides::save_fields( $id, $en );
smg_assert( array( 'fr', 'ar' ) === Guides::languages( $id ), 'without its English text, the guide is in 2 languages' );
$fallback = \SM\Core\I18n\Language::with_language( 'en', static fn() => Pages::data( null, array( 'slug' => 'acheter-acier-reemploi' ) ) );
smg_assert( true === $fallback['fallback'] && false !== strpos( $fallback['html'], 'état déclaré' ), 'shown in French at the English address, marked fallback' );
$en['body']['en'] = $saved_body;
Guides::save_fields( $id, $en );

echo "\nFrequently asked questions (0.2.0)\n";
$qs = Faq::published();
smg_assert( 23 === count( $qs ), 'the 23 validated questions (' . count( $qs ) . ')' );
$all3 = true;
foreach ( $qs as $p ) {
	$all3 = $all3 && array( 'fr', 'ar', 'en' ) === Faq::languages( $p );
}
smg_assert( $all3, 'each question written in French, Arabic and English' );
$txt = '';
foreach ( $qs as $p ) {
	$txt .= implode( ' ', Faq::fields( $p )['answer'] ) . ' ' . implode( ' ', Faq::fields( $p )['question'] );
}
smg_assert( false === strpos( $txt, 'réglage' ) && false === strpos( $txt, '[page' ) && false === strpos( $txt, 'relecture' ), 'no review note and no [réglage] / [page] marker' );
smg_assert( ! preg_match( '/\d[\d\s]*DA\b/u', $txt ), 'no price in the answers' );
$d = Faq::data( null, array() );
smg_assert( 'faq' === $d['kind'] && 6 === count( $d['groups'] ) && 'account' === $d['groups'][0]['key'] && 3 === count( $d['groups'][0]['items'] ), '6 themes in their order, 3 questions about the account' );
smg_assert( array( 3, 4, 5, 4, 3, 4 ) === array_map( static fn( $g ) => count( $g['items'] ), $d['groups'] ), 'questions per theme: 3, 4, 5, 4, 3, 4' );
$want = array(
	'reuse'     => array( 'qui-peut-publier', 'declarer-etat-reemploi', 'verifier-lot' ),
	'buildings' => array( 'qui-peut-publier', 'verifier-lot', 'demander-etude-inspection' ),
	'engineers' => array( 'demander-etude-inspection', 'ingenieur-sans-coordonnees', 'ingenieur-verifie' ),
	'rfq'       => array( 'demander-devis', 'qui-voit-offres', 'prix-da-kg' ),
);
foreach ( $want as $k => $slugs ) {
	smg_assert( $slugs === wp_list_pluck( Faq::data( null, array( 'for' => $k ) )['items'], 'slug' ), "questions at the bottom of the page $k" );
}
smg_assert( array() === Faq::data( null, array( 'for' => 'weight' ) )['items'], 'no question on a page that has none' );
$all = array_merge( ...array_map( static fn( $g ) => $g['items'], $d['groups'] ) );
$by  = array_column( $all, null, 'slug' );
smg_assert( false !== strpos( $by['declarer-etat-reemploi']['answer'], 'href="' . Pages::url( 'acheter-acier-reemploi' ) . '"' ), 'link to a guide (guide:name)' );
smg_assert( (bool) preg_match( '#<a href="[^"]+/contact/">Contact</a>#', $by['contacter-equipe']['answer'] ), 'link to a page of the site (page:contact)' );
smg_assert( 4 === substr_count( $by['annonce-pas-encore-visible']['answer'], '<li>' ), 'list of the 4 statuses' );
smg_assert( 'Hors lien et page' === Faq::links( 'Hors [lien](guide:nexiste-pas) et [page](page:nexiste-pas)' ), 'unknown guide or page: the text alone' );
smg_assert( false === strpos( Markdown::render( Faq::links( '[x](guide:nexiste-pas)' ) )['html'], '<a' ), 'unknown guide: no link' );
$ar = \SM\Core\I18n\Language::with_language( 'ar', static fn() => Faq::data( null, array( 'for' => 'rfq' ) ) );
smg_assert( 'لماذا الأسعار بـ <bdi>DA/kg</bdi>؟' === $ar['items'][2]['q_html'], 'Arabic: DA/kg isolated (<bdi>) in the question' );
smg_assert( false !== strpos( Faq::isolate( '<p>التسميات (IPE 200، S275JR) و <a href="https://x/">رابط</a> &amp;</p>', 'ar' ), '(<bdi>IPE 200</bdi>، <bdi>S275JR</bdi>) و <a href="https://x/">رابط</a> &amp;' ), 'Arabic: Latin words isolated, links and entities kept' );
smg_assert( 'IPE 200' === Faq::isolate( 'IPE 200', 'fr' ), 'French and English: text unchanged' );
smg_assert( false === strpos( $by['prix-da-kg']['text'], '<' ) && false === strpos( $by['qui-peut-sinscrire']['text'], '&#039;' ), 'plain text of the answer (search engines)' );

$q0 = $qs[0];
$t0 = $q0->post_title;
wp_update_post( array( 'ID' => $q0->ID, 'post_title' => 'Question modifiée' ) );
delete_option( 'sm_guides_faq_seed' );
smg_assert( 0 === Installer::seed_faq() && 'Question modifiée' === get_the_title( $q0->ID ), 'installation again: an existing question is never written over' );
wp_update_post( array( 'ID' => $q0->ID, 'post_title' => $t0 ) );
smg_assert( $t0 === get_post_field( 'post_title', $q0->ID ), 'title restored' );

$f = Faq::fields( $q0 );
$saved_en = $f;
$f['answer']['en'] = '';
Faq::save_fields( $q0->ID, $f );
$fb = \SM\Core\I18n\Language::with_language( 'en', static fn() => Faq::data( null, array() ) );
smg_assert( true === $fb['groups'][0]['items'][0]['fallback'] && 'fr' === $fb['groups'][0]['items'][0]['lang'], 'without its English answer: shown in French at the English address, marked fallback' );
Faq::save_fields( $q0->ID, $saved_en );
$parsed = Installer::parse_faq( "## Thème\n\n### Une question ?\n\nRéponse **une**.\n\n- a\n- b\n\n## Autre\n\n### Deux ?\n\nRéponse deux." );
smg_assert( 2 === count( $parsed ) && 'Une question ?' === $parsed[0]['q'] && "Réponse **une**.\n\n- a\n- b" === $parsed[0]['a'] && 'Réponse deux.' === $parsed[1]['a'], 'reading of data/faq: questions and answers, themes skipped' );

printf( "\n%d/%d passed\n", $GLOBALS['smg_t']['pass'], $GLOBALS['smg_t']['pass'] + $GLOBALS['smg_t']['fail'] );
