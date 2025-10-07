import { createRoot } from '@wordpress/element';
import Auth from './components/Auth';
import Import from './components/Import';

// Settings page root
const settingsRoot = document.getElementById( 'notion2wp-admin-root' );
if ( settingsRoot ) {
	createRoot( settingsRoot ).render( <Auth /> );
}

// Import page root
const importRoot = document.getElementById( 'notion2wp-import-root' );
if ( importRoot ) {
	createRoot( importRoot ).render( <Import /> );
}
