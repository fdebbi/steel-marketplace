/**
 * Build: concatenates assets/css/*.css into dist/main.css and generates
 * dist/main-rtl.css with RTLCSS. Run: npm install && npm run build
 */
import { readFileSync, writeFileSync, readdirSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import rtlcss from 'rtlcss';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const srcDir = join( root, 'assets/css' );
const outDir = join( root, 'dist' );
mkdirSync( outDir, { recursive: true } );

const css = readdirSync( srcDir )
	.filter( ( f ) => f.endsWith( '.css' ) )
	.sort()
	.map( ( f ) => `/* ${ f } */\n` + readFileSync( join( srcDir, f ), 'utf8' ) )
	.join( '\n' );

// Light minification: strip comments and collapse whitespace.
const minify = ( s ) => s
	.replace( /\/\*[\s\S]*?\*\//g, '' )
	.replace( /\s+/g, ' ' )
	.replace( /\s*([{};,>])\s*/g, '$1' )
	.replace( /;}/g, '}' )
	.trim();

const ltr = minify( css );
const rtl = minify( rtlcss.process( css ) );
writeFileSync( join( outDir, 'main.css' ), ltr );
writeFileSync( join( outDir, 'main-rtl.css' ), rtl );
console.log( `main.css ${ ltr.length } bytes, main-rtl.css ${ rtl.length } bytes` );
